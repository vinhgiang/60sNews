<?php

use App\Services\Detectors\DetectorService;
use App\Services\Ffmpeg\FfmpegService;
use App\Utils\Logger;

require __DIR__ . '/App/Configs/configs.php';

$filename = '60s-' . (date('a') == 'am' ? 'sang-06-30-00' : 'chieu-18-30-00') . '.ts';
$video = 'video/' . $filename;
if (!file_exists($video)) {
    die("No file is needed to be scanned at $video");
}

$startTime = time();
$fps       = 10;

$ffmpegService = new FfmpegService($video);
$totalFrames   = ceil($ffmpegService->getVideoDuration() * $fps);

$frameDir = $ffmpegService->getFramesDir();

try {
    $preDataFile = 'scan-ads.txt';
    $data        = json_decode(file_get_contents("log/$preDataFile"));

    $startAt = $data->processed ?? 1;
    $isStart = $data->isStart ?? true;
    $result  = $data->result ?? [];
    $isDone  = $data->isDone ?? false;

    if (! $isDone) {
        $adsStartIndicatorsDir = 'resources/ads/start';
        $adsEndIndicatorsDir   = 'resources/ads/end';
        $adsDetectorService    = new DetectorService($adsStartIndicatorsDir, $adsEndIndicatorsDir);
        $adsTimesDetected      = $adsDetectorService->scanBundle($totalFrames, $frameDir, $startAt, $isStart, $result, 95, 60 * $fps, $preDataFile);

        Logger::log("Ads: ");
        Logger::log($adsTimesDetected);
    }

} catch (Exception $e) {
    Logger::log($e->getMessage());
}

die('took ' . time() - $startTime . ' seconds.');