<?php

namespace App\Services\StreamingProviders\Domain;

use App\Services\StreamingProviders\Exceptions\AccessException;
use App\Services\StreamingProviders\Exceptions\EmptyIpListException;
use Exception;
use http\Exception\UnexpectedValueException;

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

        return 'http://' . self::$ips[$ipIndex] . '/thvli/thvl1-abr/tracks-v1a1/';
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

        // since this provider does not stream in a full minute, we need to add extra 12 seconds
        $latestDate = (array_key_last($formatStreamingPlaylist) - 4000) / 100000;
        preg_match('/(\d{4})(\d{2})(\d{2})(\d{2})(\d{2})(\d{2})/', $latestDate, $matches);
        $year   = $matches[1];
        $month  = $matches[2];
        $day    = $matches[3];
        $hour   = $matches[4];
        $minute = $matches[5];
        $second = $matches[6];

        $latestTime = mktime($hour, $minute, $second, $month, $day, $year);
        for ($i = 0; $i < 3; $i++) {
            $latestTime                   += 4;
            $id                           = date('YmdHis04000', $latestTime);
            $fileName                     = date('Y/m/d/H/i/s-04000', $latestTime) . '.ts';
            $formatStreamingPlaylist[$id] = $this->getServerPath($ipIndex) . $fileName;
        }

        return $formatStreamingPlaylist;
    }

    private function saveIp($ips)
    {
        file_put_contents('data/thvl1-ip.txt', join("\n", array_keys($ips)));
    }
}