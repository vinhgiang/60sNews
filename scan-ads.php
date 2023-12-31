<?php

use App\Services\Detectors\DetectorService;
use App\Services\Ffmpeg\FfmpegService;
use App\Utils\Logger;

require __DIR__ . '/App/Configs/configs.php';

$name  = $_GET['file'] ?? $_GET['program'] . date('-Y-m-d') . '.mp4';
$video = "video/$name";

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
        $adsTimesDetected      = $adsDetectorService->scanBundle($totalFrames, $frameDir, $startAt, $isStart, $result, 95, 21 * $fps, $preDataFile);

        Logger::log("Ads: ");
        Logger::log($adsTimesDetected);
    }

} catch (Exception $e) {
    Logger::log($e->getMessage());
}

die('took ' . time() - $startTime . ' seconds.');