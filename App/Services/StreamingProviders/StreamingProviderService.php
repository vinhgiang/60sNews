<?php

namespace App\Services\StreamingProviders;

use App\Services\StreamingProviders\Domain\StreamingProvider;
use Exception;

class StreamingProviderService
{
    /** @var StreamingProvider */
    private $streamingProvider;

    /**
     * @param StreamingProvider $streamingProvider
     */
    public function __construct($streamingProvider)
    {
        $this->streamingProvider = $streamingProvider;
    }

    /**
     * @param string $from
     * @param string $to
     * @param string $fileName
     * @param string $path
     * @return string
     * @throws Exception
     */
    public function record($from, $to, $fileName = '', $path = 'video')
    {
        $lastIdPath = 'log/last-id.txt';
        $now        = date('H:i:s');

        if ($now < $from || $now > $to) {
            throw new Exception("The job will be started from $from to $to. Now is $now.");
        }

        $streamingList = $this->streamingProvider->getStreamingPlaylist();

        $lastId    = file_get_contents($lastIdPath);
        $videoPath = $this->streamingProvider->downloadStreamingList($streamingList, $path, $fileName, $lastId, false);

        $lastId = array_key_last($streamingList);

        file_put_contents($lastIdPath, $lastId);

        return $videoPath;
    }

    /**
     * @param array[] $moments
     * @param string $fileName
     * @param string $path
     * @return string
     * @throws Exception
     */
    public function recordMoments($moments, $fileName = '', $path = 'video')
    {
        if (!is_array($moments)) {
            throw new Exception("Moments need to be an array. For example: [['06:00:00', '07:00:00'], ['13:30:00', '13:45:00']]");
        }

        $videoPath = '';
        foreach ($moments as [$start, $end]) {
            try {
                $videoPath = $this->record($start, $end, $fileName, $path);
            } catch (Exception $ignored) {
                // ignore the exception since we will evaluate multiple moments
            }

            // stop the process as soon as we captured the correct moment
            if (!empty($videoPath)) {
                break;
            }
        }

        return $videoPath;
    }
}