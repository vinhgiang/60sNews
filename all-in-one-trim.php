<?php

use App\Services\Detectors\DetectorService;
use App\Services\Ffmpeg\FfmpegService;
use App\Utils\Logger;

require __DIR__ . '/App/Configs/configs.php';

$filename = date('ymd-a');
$video    = 'video/' . $filename . '.ts';
if (! file_exists($video) || file_exists('video/' . $filename . '-trimmed.mp4')) {
    die('No file is needed to be trimmed.');
}

$fps = 10;

$ffmpegService = new FfmpegService($video);
$ffmpegService->extractAllFrames(10);

$totalFrames = ceil($ffmpegService->getVideoDuration() * $fps);

$frameDir = $ffmpegService->getFramesDir();

try {
    $borderStartIndicatorsDir = 'resources/border/start';
    $borderEndIndicatorsDir   = 'resources/border/end';
    $borderDetectorService    = new DetectorService($borderStartIndicatorsDir, $borderEndIndicatorsDir);
    $borderTimesDetected      = $borderDetectorService->scan($totalFrames, $frameDir);

    if (count($borderTimesDetected) == 0) {
        throw new Exception('could not detect border. Empty array.');
    }

    // if there is no end border indicator
    $borderTimesDetected[0][1] = $borderTimesDetected[0][1] ?? $totalFrames;

    $times[] = [0, $borderTimesDetected[0][0]];

    $adsStartIndicatorsDir = 'resources/ads/start';
    $adsEndIndicatorsDir   = 'resources/ads/end';
    $adsDetectorService    = new DetectorService($adsStartIndicatorsDir, $adsEndIndicatorsDir);
    $adsTimesDetected      = $adsDetectorService->scan($totalFrames, $frameDir);

    // filter out parts that beyonds borders
    $adsTimesDetected = array_filter($adsTimesDetected, function ($moment) use ($borderTimesDetected) {
        return $moment[0] > $borderTimesDetected[0][0] && $moment[0] < $borderTimesDetected[0][1];
    });

    $times = array_merge($times, $adsTimesDetected);

    $lastOccasion = end($times);
    // has a pair
    if (count($lastOccasion) == 2) {
        $times[] = [$borderTimesDetected[0][1], $borderTimesDetected[0][1]];
    }
    // missing one indicator
    else {
        $times[count($times) - 1][0] = $borderTimesDetected[0][1];
    }

} catch (Exception $e) {
    Logger::log($e->getMessage());
}

$ffmpegService->trimVideo($times);

$ffmpegService->cleanup();