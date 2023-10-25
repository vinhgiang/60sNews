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

        self::$ips = explode("\n", file_get_contents('log/ip.txt'));
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
        // They will respond the same dataset even in different servers
        return 'http://' . self::$ips[$ipIndex] . '/sctv/s10/cdn-cgi/edge/v2/e2.endpoint.cdn.sctvonline.vn/nginx.s10.edge.cdn.sctvonline.vn/hls/htv7/';
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
        $formatStreamingPlaylist = [];
        // the real streaming start at element #5
        for ($i = 5; $i < count($streamingPlaylist); $i++) {
            if ($i % 2 != 0) {
                preg_match('/(\d+)[.]ts/', $streamingPlaylist[$i], $matches);
                $id                        = $matches[1];
                $formatStreamingPlaylist[$id] = $this->getServerPath($ipIndex) . "$id.ts";
            }
        }

        return $formatStreamingPlaylist;
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
        file_put_contents('log/ip.txt', join("\n", array_keys($ips)));
    }
}