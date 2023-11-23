<?php

namespace App\Services\StreamingProviders\Domain;

use App\Utils\Logger;

abstract class StreamingProvider
{
    /**
     * @var resource
     */
    protected $timeoutCtx;

    public function __construct()
    {
        $this->timeoutCtx = stream_context_create([
            'http' => [
                'timeout' => 5,
                // 5 Seconds
            ]
        ]);
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
     * @param string[] $streamingPlaylist
     * @param string $path
     * @param string $fileName
     * @param int $startId
     * @param bool $isOverride
     * @param bool $isFragmented
     * @return string
     */
    public function downloadStreamingList($streamingPlaylist, $path = 'video', $fileName = '', $startId = 0, $isOverride = true, $isFragmented = false)
    {
        $fileName      = $fileName == '' ? date('ymd-his') : $fileName;
        $finalPath     = $isFragmented ? "$path/$fileName" : "$path/$fileName.ts";
        $streamingData = [];
        $index         = 0;

        if ($isFragmented && ! file_exists($finalPath)) {
            mkdir($finalPath, 0777, true);
        }

        foreach ($streamingPlaylist as $id => $stream) {
            $index++;
            if ($id <= $startId) {
                continue;
            }
            $data = file_get_contents($stream);

            if ($data === false) {
                Logger::log("Could not get data from $stream");
                continue;
            }

            if ($isFragmented && ! file_exists("$finalPath/$id.ts")) {
                file_put_contents("$finalPath/$id.ts", $data);
            }
            else {
                $streamingData[] = $data;
            }

            if ($index > 12) {
                sleep(2);
            }
        }

        if (! $isFragmented) {
            file_put_contents($finalPath, join('', $streamingData), $isOverride ? 0 : FILE_APPEND);
        }

        return $finalPath;
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
}