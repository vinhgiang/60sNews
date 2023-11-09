<?php

namespace App\Services\StreamingProviders\Domain;

use App\Services\StreamingProviders\Exceptions\EmptyIpListException;
use Exception;

class Thvl1 extends StreamingProvider
{
    /** @var string[] */
    private static $ips;

    public function __construct()
    {
        parent::__construct();

        self::$ips = explode("\n", file_get_contents('data/thvl1-ip.txt'));;
    }

    public function getLastIdPath()
    {
        return 'data/thvl1-last-id.txt';
    }

    public function fetchServerIp()
    {
        $url = $_ENV['THVL1_SERVER_FETCHER_URL'];
        $ips = [];
        for ($i = 0; $i < 20; $i++) {
            $providerUrl = file_get_contents($url);
            if ($providerUrl === false) {
                // if could not fetch data, wait for 1 second and then try again.
                sleep(1);
                continue;
            }
            preg_match('/http[s]?:\/\/.+\/.+\/\d+\//', $providerUrl, $matches);
            if (! empty($matches[1])) {
                $ips[$matches[1]] = '';
                break;
            }
        }

        if (empty($ips)) {
            throw new EmptyIpListException("Could not get server Ip list at $url");
        }

        $this->saveIp($ips);

        return count($ips);
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

    private function saveIp($ips)
    {
        file_put_contents('data/thvl1-ip.txt', join("\n", array_keys($ips)));
    }
}