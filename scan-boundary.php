<?php

use App\Services\Detectors\DetectorService;
use App\Services\Ffmpeg\FfmpegService;
use App\Utils\Logger;

require __DIR__ . '/App/Configs/configs.php';

$video = 'video/' . date('ymd-a') . '.ts';
if (!file_exists($video)) {
    die("No file is needed to be scanned at $video");
}

$startTime = time();
$fps       = 10;

$ffmpegService = new FfmpegService($video);
$totalFrames   = ceil($ffmpegService->getVideoDuration() * $fps);

$frameDir = $ffmpegService->getFramesDir();

try {
    $preDataFile = "scan-boundary.txt";
    $data        = json_decode(file_get_contents("log/$preDataFile"));

    $startAt = $data->processed ?? 1;
    $isStart = $data->isStart ?? true;
    $result  = $data->result ?? [];
    $isDone  = $data->isDone ?? false;

    if (! $isDone) {
        $borderStartIndicatorsDir = 'resources/border/start';
        $borderEndIndicatorsDir   = 'resources/border/end';
        $borderDetectorService    = new DetectorService($borderStartIndicatorsDir, $borderEndIndicatorsDir);
        $borderTimesDetected      = $borderDetectorService->scanBundle($totalFrames, $frameDir, $startAt, $isStart, $result, 95, 10, $preDataFile);

        if (count($borderTimesDetected) == 0) {
            throw new Exception('could not detect border. Empty array.');
        }

        Logger::log("Boundary: ");
        Logger::log($borderTimesDetected);
    }

} catch (Exception $e) {
    Logger::log($e->getMessage());
}