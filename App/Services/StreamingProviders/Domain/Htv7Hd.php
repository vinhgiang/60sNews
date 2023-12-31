<?php

namespace App\Services\StreamingProviders\Domain;

use App\Services\StreamingProviders\Exceptions\EmptyIpListException;
use App\Utils\Logger;
use Exception;

class Htv7 extends StreamingProvider
{
    /** @var string[] */
    private static $ips;

    public function __construct()
    {
        parent::__construct();

        self::$ips   = explode("\n", file_get_contents('data/htv7-ip.txt'));
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
        $url = $_ENV['HTV7_SERVER_FETCHER_URL'];
        $ips = [];
        for ($i = 0; $i < 20; $i++) {
            $providerUrl = file_get_contents($url);
            if ($providerUrl === false) {
                // if could not fetch data, wait for 1 second and then try again.
                sleep(1);
                continue;
            }
            preg_match('/http[s]?:\/\/([\d*[.]*]*:\d*)/', $providerUrl, $matches);
            if (! empty($matches[1])) {
                $ips[$matches[1]] = '';
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
        return 'http://' . self::$ips[$ipIndex] . '/sctv/oipf0znpmnliv.vcdn.cloud/hls/htv7/';
    }

    /**
     * @return string
     * @throws Exception
     */
    public function getStreamingPlaylistUrl($ipIndex = 0)
    {
        return $this->getServerPath($ipIndex) . "index.m3u8";
    }

    /**
     * @return string[]
     * @throws Exception
     */
    public function getStreamingPlaylist()
    {
        $ipIndex = $this->getIpIndex();
        $url     = $this->getStreamingPlaylistUrl($ipIndex);

        $result       = '';
        $isProcessing = true;
        while ($isProcessing) {
            $result = file_get_contents($url, false, $this->timeoutCtx);
            if ($result === false) {
                Logger::log("Could not get data from $url");
                $this->removeIp($ipIndex);
                $ipIndex = $this->getIpIndex();
                $url     = $this->getStreamingPlaylistUrl($ipIndex);
            } else {
                $isProcessing = false;
            }
        }

        $streamingPlaylist = explode("\n", $result);
        if (!$this->isStreamingPlaylistValid($streamingPlaylist)) {
            throw new Exception("Streaming list is not in the correct format");
        }

        return $this->formatStreamingPlaylist($streamingPlaylist, $ipIndex);
    }

    /**
     * @param string[] $streamingPlaylist
     * @param int $ipIndex
     * @return array
     */
    private function formatStreamingPlaylist($streamingPlaylist, $ipIndex)
    {
        $duration                = $this->getStreamingItemDuration($streamingPlaylist);
        $formatStreamingPlaylist = [];

        for ($i = 0; $i < count($streamingPlaylist); $i++) {
            if (! str_contains($streamingPlaylist[$i], '#') && ! empty($streamingPlaylist[$i])) {
                preg_match('/[a-zA-Z]+(\d+)[.]ts/', $streamingPlaylist[$i], $matches);

                $fileName = $matches[0];
                $id       = $matches[1];

                $formatStreamingPlaylist[$id] = $this->getServerPath($ipIndex) . $fileName;
            }
        }

        return $this->trimStreamingList($formatStreamingPlaylist, $duration);
    }

    /**
     * @return int
     * @throws Exception
     */
    private function getIpIndex()
    {
        $ipLength = count(self::$ips);
        if ($ipLength == 0) {
            Logger::log('HTV7 IP list is empty. Getting the new list');
            $ipLength = $this->fetchServerIp();
        }

        return rand(0, $ipLength - 1);
    }

    /**
     * @param int $ipIndex
     * @return void
     */
    private function removeIp($ipIndex)
    {
        array_splice(self::$ips, $ipIndex, 1);
        $this->saveIp(self::$ips);
    }

    /**
     * @param string[] $ips
     * @return void
     */
    private function saveIp($ips)
    {
        $ips       = array_keys($ips);
        self::$ips = $ips;

        file_put_contents('data/htv7-ip.txt', join("\n", $ips));
    }
}