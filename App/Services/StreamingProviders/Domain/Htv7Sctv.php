<?php

namespace App\Services\StreamingProviders\Domain;

use App\Utils\FileSystem;
use App\Utils\Logger;
use Exception;

class Htv7Sctv extends StreamingProvider
{
    /** @var string[] */
    private static $ips;

    public function __construct()
    {
        $header = [
            'referer: https://sctvonline.vn/'
        ];

        parent::__construct($header);

        self::$ips = [$_ENV['HTV7_SCTV_STREAMING_URL']];
    }

    public function getLastIdPath()
    {
        return 'data/htv7-last-id.txt';
    }

    /**
     * @return int
     * @throws Exception
     */
    public function fetchServerIp()
    {
        return $_ENV['HTV7_SCTV_STREAMING_URL'];
    }

    /**
     * @param int $ipIndex
     * @return string
     */
    public function getServerPath($ipIndex = 0)
    {
        return 'https://' . self::$ips[$ipIndex] . '/cdn-cgi/edge/v2/e2.endpoint.cdn.sctvonline.vn/nginx.s4.edge.cdn.sctvonline.vn/hls/htv7/';
    }

    /**
     * @return string
     * @throws Exception
     */
    public function getStreamingPlaylistUrl($ipIndex = 0)
    {
        return $this->getServerPath($ipIndex) . "02.m3u8";
    }

    /**
     * @return string[]
     * @throws Exception
     */
    public function getStreamingPlaylist()
    {
        $url = $this->getStreamingPlaylistUrl();

        $result       = '';
        $isProcessing = true;
        while ($isProcessing) {
            $result = FileSystem::downloadFileViaCurl($url, $this->headers);
            if ($result === false) {
                Logger::log("Could not get data from $url");
            } else {
                $isProcessing = false;
            }
        }

        $streamingPlaylist = explode("\n", $result);
        if (!$this->isStreamingPlaylistValid($streamingPlaylist)) {
            throw new Exception("Streaming list is not in the correct format");
        }

        return $this->formatStreamingPlaylist($streamingPlaylist);
    }

    /**
     * @param string[] $streamingPlaylist
     * @return array
     */
    private function formatStreamingPlaylist($streamingPlaylist)
    {
        $duration                = $this->getStreamingItemDuration($streamingPlaylist);
        $formatStreamingPlaylist = [];

        for ($i = 0; $i < count($streamingPlaylist); $i++) {
            if (! str_contains($streamingPlaylist[$i], '#') && ! empty($streamingPlaylist[$i])) {
                preg_match('/(\d+)[.]ts/', $streamingPlaylist[$i], $matches);

                $fileName = trim($streamingPlaylist[$i]);
                $id       = $matches[1];

                $formatStreamingPlaylist[$id] = $this->getServerPath() . $fileName;
            }
        }

        return $this->trimStreamingList($formatStreamingPlaylist, $duration);
    }
}