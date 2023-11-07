<?php

namespace App\Services\StreamingProviders\Domain;

use Exception;

class Thvl1 extends StreamingProvider
{
    /** @var string[] */
    private static $ips;

    public function __construct()
    {
        parent::__construct();

        self::$ips = [$_ENV['THVL1_LIVE_DOMAIN']];
    }

    public function getLastIdPath()
    {
        return 'data/thvl1-last-id.txt';
    }

    public function fetchServerIp()
    {
        // does not need to fetch IP for this channel
    }

    /**
     * @param int $ipIndex
     * @return string
     */
    public function getServerPath($ipIndex = 0)
    {

        return 'http://' . self::$ips[$ipIndex] . '/thvli/thvl1-abr/tracks-v3a1/';
    }

    /**
     * @return string
     * @throws Exception
     */
    public function getStreamingPlaylistUrl($ipIndex = 0)
    {
        return $this->getServerPath($ipIndex) . "mono.m3u8";
    }

    /**
     * @return string[]
     * @throws Exception
     */
    public function getStreamingPlaylist()
    {
        $url = $this->getStreamingPlaylistUrl();
        $result = file_get_contents($url, false, $this->timeoutCtx);
        if ($result === false) {
            throw new Exception("Could not get data from $url");
        }

        $streamingPlaylist = explode("\n", $result);
        if (!$this->isStreamingPlaylistValid($streamingPlaylist)) {
            throw new Exception("Streaming list is not in the correct format");
        }

        return $this->formatStreamingPlaylist($streamingPlaylist);
    }

    /**
     * @param string[] $streamingPlaylist
     * @param int $ipIndex
     * @return array
     */
    private function formatStreamingPlaylist($streamingPlaylist, $ipIndex = 0)
    {
        $formatStreamingPlaylist = [];
        // the real streaming start at element #6
        for ($i = 6; $i < count($streamingPlaylist); $i++) {
            if ($i % 2 == 0) {
                $id                           = str_replace('.ts', '', str_replace('-', '', str_replace('/', '', $streamingPlaylist[$i])));
                $formatStreamingPlaylist[$id] = $this->getServerPath($ipIndex) . $streamingPlaylist[$i];
            }
        }

        return $formatStreamingPlaylist;
    }
}