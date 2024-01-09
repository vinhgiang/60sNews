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
    $boundaryInfo = json_decode(file_get_contents("log/scan-boundary.txt"));

    $preDataFile = 'scan-ads.txt';
    $data        = json_decode(file_get_contents("log/$preDataFile"));

    $startAt = $boundaryInfo->result[0][0] ? $boundaryInfo->result[0][0] + 3000 : 1; // skip 5 minutes after starting the program

    $isStart = $data->isStart ?? true;
    $result  = $data->result ?? [];
    $isDone  = $data->isDone ?? false;

    if (! $isDone) {
        $adsStartIndicatorsDir = 'resources/ads/start';
        $adsEndIndicatorsDir   = 'resources/ads/end';
        $adsDetectorService    = new DetectorService($adsStartIndicatorsDir, $adsEndIndicatorsDir);
        $adsTimesDetected      = $adsDetectorService->scanBundle($totalFrames, $frameDir, $startAt, $boundaryInfo->result[0][1] ?? -1, $isStart, $result, 95, 21 * $fps, 2500, $preDataFile);

        Logger::log("Ads: ");
        Logger::log($adsTimesDetected);
    }

} catch (Exception $e) {
    Logger::log($e->getMessage());
}

die('took ' . time() - $startTime . ' seconds.');