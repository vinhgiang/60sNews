<?php

namespace App\Services\AdsDetector;

use Exception;
use SapientPro\ImageComparator\ImageComparator;
use SapientPro\ImageComparator\ImageResourceException;

class AdsDetectorService
{
    /** @var ImageComparator */
    private $imageComparator;

    /** @var string[] */
    private $adsStartIndicators;

    /** @var string[] */
    private $adsEndIndicators;

    public function __construct()
    {
        // TODO: dependency injection
        $this->imageComparator = new ImageComparator();

        $this->initAdsIndicator();
    }

    /**
     * @param int $totalFrames
     * @param string $frameDir
     * @param int $sensitive
     * @param int $foundOffset  number of frame will be skipped after matching
     * @return array
     * @throws ImageResourceException
     */
    public function scan($totalFrames, $frameDir, $sensitive = 95, $foundOffset = 10)
    {
        $isStart = true;
        $times   = [];
        $index   = 0;
        for ($i = 1; $i < $totalFrames; $i++) {
            $frame        = "$frameDir/$i.jpg";
            $similarities = [];

            if ($isStart) {
                foreach ($this->adsStartIndicators as $indicator) {
                    $similarities[] = $this->imageComparator->compare($indicator, $frame);
                }
            }
            else {
                foreach ($this->adsEndIndicators as $indicator) {
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
        }

        return $times;
    }

    /**
     * @return void
     * @throws Exception
     */
    protected function initAdsIndicator()
    {
        $adsStartIndicatorsDir = 'resources/ads/start';
        $adsEndIndicatorsDir   = 'resources/ads/end';

        if (! is_dir($adsStartIndicatorsDir) || ! is_dir($adsEndIndicatorsDir)){
            throw new Exception('Ads start indicator or end indicator are not exist in resources/ads/start or resources/ads/end.');
        }

        $this->adsStartIndicators = array_map(function ($i) use ($adsStartIndicatorsDir) {
            return "$adsStartIndicatorsDir/$i";
        }, array_values(array_diff(scandir($adsStartIndicatorsDir), ['.', '..'])));

        $this->adsEndIndicators   = array_map(function ($i) use ($adsEndIndicatorsDir) {
            return "$adsEndIndicatorsDir/$i";
        }, array_values(array_diff(scandir($adsEndIndicatorsDir), ['.', '..'])));
    }
}