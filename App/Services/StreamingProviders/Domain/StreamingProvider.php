<?php

namespace App\Services\StreamingProviders\Domain;

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
     * @return string
     */
    public function downloadStreamingList($streamingPlaylist, $path = 'video', $fileName = '', $startId = 0, $isOverride = true)
    {
        $fileName = $fileName == '' ? date('ymd-his') : $fileName;
        $streamingData = [];
        foreach ($streamingPlaylist as $id => $stream) {
            if ($id <= $startId) {
                continue;
            }
            $streamingData[] = file_get_contents($stream);
        }

        $finalPath = "$path/$fileName.ts";
        file_put_contents($finalPath, join('', $streamingData), $isOverride ? 0 : FILE_APPEND);

        return $finalPath;
    }
}