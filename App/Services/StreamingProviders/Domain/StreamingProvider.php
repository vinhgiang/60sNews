<?php

namespace App\Services\StreamingProviders\Domain;

use App\Utils\Logger;

abstract class StreamingProvider
{
    /**
     * @var resource
     */
    protected $timeoutCtx;

    /**
     * @var int
     */
    protected $minFileSize;

    /**
     * @param int $minFileSize
     */
    public function __construct($minFileSize = 900000)
    {
        $this->timeoutCtx = stream_context_create([
            'http' => [
                'timeout' => 7,
                // 7 Seconds
            ]
        ]);

        $this->minFileSize = $minFileSize;
    }

    public abstract function fetchServerIp();

    /**
     * @param int $ipIndex
     * @return string
     */
    public abstract function getServerPath($ipIndex = 0);

    /**
     * @return string
     */
    public abstract function getStreamingPlaylistUrl($ipIndex = 0);

    /**
     * @return string[]
     */
    public abstract function getStreamingPlaylist();

    public abstract function getLastIdPath();

    /**
     * @param string[] $streamingPlaylist
     * @return bool
     */
    public function isStreamingPlaylistValid($streamingPlaylist)
    {
        return is_array($streamingPlaylist) && $streamingPlaylist[0] == '#EXTM3U';
    }

    /**
     * @deprecated
     *
     * @param string[] $streamingPlaylist
     * @param string $path
     * @param string $fileName
     * @param int $startId
     * @param bool $isOverride
     * @return string
     */
    public function downloadStreamingList($streamingPlaylist, $path = 'video', $fileName = '', $startId = 0, $isOverride = true)
    {
        $fileName = $fileName == '' ? date('ymd-his') : $fileName;
        $streamingData = [];
        $index = 0;
        foreach ($streamingPlaylist as $id => $stream) {
            $index++;
            if ($id <= $startId) {
                continue;
            }
            $data = file_get_contents($stream);
            if ($data !== false) {
                $streamingData[] = $data;
            }
            if ($index > 12) {
                sleep(2);
            }
        }

        $finalPath = "$path/$fileName.ts";
        file_put_contents($finalPath, join('', $streamingData), $isOverride ? 0 : FILE_APPEND);

        return $finalPath;
    }

    /**
     * @param string[] $streamingPlaylist
     * @param string $path
     * @param int $startId
     * @param int $trial
     * @return string
     */
    public function downloadStreamingFiles($streamingPlaylist, $path = 'video', $startId = 0, $trial = 0)
    {
        if ($trial == 3) {
            return '';
        }

        $path        = $path == 'video' ? 'video/' . date('ymd-his') : $path;
        $index       = 0;
        $isLastTrial = $trial == 2;

        if (! file_exists($path)) {
            mkdir($path, 0777, true);
        }

        foreach ($streamingPlaylist as $id => $stream) {
            $index++;
            if ($id <= $startId) {
                continue;
            }

            $data    = file_get_contents($stream, false, $this->timeoutCtx);

            if (! $this->isStreamingDataValid($data, $isLastTrial, $stream)) {
                if (! $isLastTrial) {
                    $retryList[$id] = $stream;
                    continue;
                }
            }

            file_put_contents("$path/$id.ts", $data);

            if ($index > 12) {
                sleep(3);
            }
        }

        if (! empty($retryList)) {
            sleep(1);
            $this->downloadStreamingFiles($retryList, $path, $startId, $trial + 1);
        }

        return $path;
    }

    /**
     * @param array $streamingPlaylist
     * @return int
     */
    public function getStreamingItemDuration($streamingPlaylist)
    {
        $duration = 0;
        for ($i = 0; $i < count($streamingPlaylist); $i++) {
            if (str_contains($streamingPlaylist[$i], '#EXT-X-TARGETDURATION')) {
                $duration = str_replace('#EXT-X-TARGETDURATION:', '', $streamingPlaylist[$i]);
            }
        }

        return $duration;
    }

    /**
     * @param array $streamingPlaylist
     * @param int $duration
     * @return array
     */
    public function trimStreamingList($streamingPlaylist, $duration)
    {
        if ($duration * count($streamingPlaylist) > 60) {
            $offset = floor(60 / $duration) + 1;
            $streamingPlaylist = array_slice($streamingPlaylist, 0, $offset, true);
        }

        return $streamingPlaylist;
    }

    /**
     * @param string $data
     * @param bool $logEnabled
     * @param string $streamName
     * @return bool
     */
    private function isStreamingDataValid($data, $logEnabled = false, $streamName = 'link')
    {
        $dataLen = strlen($data);

        if ($data === false) {
            if ($logEnabled) {
                Logger::log("Could not get data from $streamName");
            }
            return false;
        }
        else if ($dataLen < $this->minFileSize) {
            if ($logEnabled) {
                Logger::log("got $streamName for only: " . $dataLen / (1024 * 1024) . " Mb. End trial");
            }
            return false;
        }

        return true;
    }
}