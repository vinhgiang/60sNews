<?php

namespace App\Services\Detectors;

use Exception;
use SapientPro\ImageComparator\ImageComparator;
use SapientPro\ImageComparator\ImageResourceException;

class DetectorService
{
    /** @var ImageComparator */
    private $imageComparator;

    /** @var string[] */
    private $startIndicators;

    /** @var string[] */
    private $endIndicators;

    /**
     * @param string $startIndicatorsDir
     * @param string $endIndicatorsDir
     * @throws Exception
     */
    public function __construct($startIndicatorsDir, $endIndicatorsDir)
    {
        // TODO: dependency injection
        $this->imageComparator = new ImageComparator();

        $this->initIndicator($startIndicatorsDir, $endIndicatorsDir);
    }

    /**
     * @param int $totalFrames
     * @param string $frameDir
     * @param int $sensitive
     * @param int $foundOffset number of frame will be skipped after matching
     * @return array
     * @throws ImageResourceException
     */
    public function scan($totalFrames, $frameDir, $sensitive = 95, $foundOffset = 10)
    {
        return $this->scanBundle($totalFrames, $frameDir, 1, -1, true, [], $sensitive, $foundOffset);
    }

    /**
     * @param int $totalFrames
     * @param string $frameDir
     * @param int $startAt
     * @param bool $isStart
     * @param array $previousResult
     * @param int $sensitive
     * @param int $foundOffset
     * @param int $endPairOffset
     * @param string $logResulFilename
     * @return array
     * @throws ImageResourceException
     */
    public function scanBundle($totalFrames, $frameDir, $startAt = 1, $endAt = -1, $isStart = true, $previousResult = [], $sensitive = 95, $foundOffset = 10, $endPairOffset = 0, $logResulFilename = '')
    {
        $times = $previousResult;
        $index = $isStart ? count($times) : count($times) - 1;
        $endAt = $endAt < 0 ? PHP_INT_MAX : $endAt;

        for ($i = $startAt; $i <= $totalFrames; $i++) {
            $frame        = "$frameDir/$i.jpg";
            $similarities = [];

            if ($isStart) {
                // if already found 1st pair, skip for $endPairOffset seconds
                if ($index > 0 && $i < $times[$index - 1][1] + $endPairOffset) {
                    $i = $times[$index - 1][1] + $endPairOffset;
                }
                foreach ($this->startIndicators as $indicator) {
                    $similarities[] = $this->imageComparator->compare($indicator, $frame);
                }
            }
            else {
                foreach ($this->endIndicators as $indicator) {
                    $similarities[] = $this->imageComparator->compare($indicator, $frame);
                }
            }
            $similarity = max($similarities);
            if ($similarity >= $sensitive) {
                $times[$index][$isStart ? 0 : 1] = $i;
                if (!$isStart) {
                    $index++;
                }
                $isStart = !$isStart;
                $i       = min($i + $foundOffset, $totalFrames);
            }

            if (! empty($logResulFilename)) {
                $isDone = $i == $totalFrames || $i >= $endAt;
                if ($i % 100 == 0 || $isDone) {
                    $data = [
                        'processed' => $i,
                        'isStart'   => $isStart,
                        'result'    => $times,
                        'isDone'    => $isDone
                    ];
                    file_put_contents("log/$logResulFilename", json_encode($data));
                }
                if ($isDone) {
                    break;
                }
            }
        }

        return $times;
    }


    /**
     * @param string $startIndicatorsDir
     * @param string $endIndicatorsDir
     * @return void
     * @throws Exception
     */
    private function initIndicator($startIndicatorsDir, $endIndicatorsDir)
    {
        if (! is_dir($startIndicatorsDir) || ! is_dir($endIndicatorsDir)){
            throw new Exception("start indicator or end indicator are not exist in $startIndicatorsDir or $endIndicatorsDir.");
        }

        $this->startIndicators = array_map(function ($i) use ($startIndicatorsDir) {
            return "$startIndicatorsDir/$i";
        }, array_values(array_diff(scandir($startIndicatorsDir), ['.', '..'])));

        $this->endIndicators = array_map(function ($i) use ($endIndicatorsDir) {
            return "$endIndicatorsDir/$i";
        }, array_values(array_diff(scandir($endIndicatorsDir), ['.', '..'])));
    }
}