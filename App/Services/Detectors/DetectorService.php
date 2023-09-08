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
     * @param int $foundOffset  number of frame will be skipped after matching
     * @return array
     */
    public function scan($totalFrames, $frameDir, $sensitive = 95, $foundOffset = 10)
    {
        return $this->scanBundle($totalFrames, $frameDir, 1, true, [], $sensitive, $foundOffset);
    }

    /**
     * @param $totalFrames
     * @param $frameDir
     * @param $startAt
     * @param $isStart
     * @param $previousResult
     * @param $sensitive
     * @param $foundOffset
     * @param $logResulFilename
     * @return array|mixed
     * @throws ImageResourceException
     */
    public function scanBundle($totalFrames, $frameDir, $startAt = 1, $isStart = true, $previousResult = [], $sensitive = 95, $foundOffset = 10, $logResulFilename = '')
    {
        $times = $previousResult;
        $index = $isStart ? count($times) : count($times) - 1;
        for ($i = $startAt; $i <= $totalFrames; $i++) {
            $frame        = "$frameDir/$i.jpg";
            $similarities = [];

            if ($isStart) {
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
                $i       += $foundOffset;
            }

            if (! empty($logResulFilename)) {
                if ($i % 100 == 0 || $i == $totalFrames) {
                    $data = [
                        'processed' => $i,
                        'isStart'   => $isStart,
                        'result'    => $times,
                        'isDone'    => $i == $totalFrames
                    ];
                    file_put_contents("log/$logResulFilename", json_encode($data));
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