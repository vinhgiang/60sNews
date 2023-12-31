<?php

namespace App\Services\StreamingProviders;

use App\Services\StreamingProviders\Domain\StreamingProvider;
use App\Services\StreamingProviders\Exceptions\WrongMomentException;
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
     * @param string $path
     * @return string
     * @throws Exception
     */
    public function record($from, $to, $path = 'video')
    {
        $lastIdPath = $this->streamingProvider->getLastIdPath();
        $now        = date('H:i:s');

        if ($now < $from || $now > $to) {
            throw new WrongMomentException("The job will be started from $from to $to. Now is $now.");
        }

        $streamingList = $this->streamingProvider->getStreamingPlaylist();

        $lastId    = file_get_contents($lastIdPath);
        $videoPath = $this->streamingProvider->downloadStreamingFiles($streamingList, $path, $lastId);

        $lastId = array_key_last($streamingList);

        file_put_contents($lastIdPath, $lastId);

        return $videoPath;
    }

    /**
     * @param array[] $moments
     * @param string $path
     * @return string
     * @throws Exception
     */
    public function recordMoments($moments, $path = 'video')
    {
        if (!is_array($moments)) {
            throw new Exception("Moments need to be an array. For example: [['06:00:00', '07:00:00'], ['13:30:00', '13:45:00']]");
        }

        $videoPath = '';
        foreach ($moments as $programName => [$start, $end]) {
            try {
                $videoPath = $this->record($start, $end, $path . '/' . $programName . '-' . str_replace(':', '-', $start) . date('-Y-m-d'));
            }
            catch (WrongMomentException $ignored) {
                // ignore the exception since we will evaluate multiple moments
                print_r($ignored->getMessage() . "</br>");
            }

            // stop the process as soon as we captured the correct moment
            if (!empty($videoPath)) {
                break;
            }
        }

        return $videoPath;
    }
}