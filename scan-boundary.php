<?php

use App\Services\Detectors\DetectorService;
use App\Services\Ffmpeg\FfmpegService;
use App\Utils\Logger;

require __DIR__ . '/App/Configs/configs.php';

$video = 'video/' . date('ymd-a') . '.ts';
if (!file_exists($video)) {
    die('No file is needed to be trimmed.');
}

$startTime = time();
$fps       = 10;

$ffmpegService = new FfmpegService($video);
$totalFrames   = ceil($ffmpegService->getVideoDuration() * $fps);

$frameDir = $ffmpegService->getFramesDir();

try {
    $data = json_decode(file_get_contents('log/scan-boundary.txt'));

    $startAt = $data->processed ?? 1;
    $isStart = $data->isStart ?? true;
    $result  = $data->result ?? [];
    $isDone  = $data->isDone ?? false;

    if (! $isDone) {
        $borderStartIndicatorsDir = 'resources/border/start';
        $borderEndIndicatorsDir   = 'resources/border/end';
        $borderDetectorService    = new DetectorService($borderStartIndicatorsDir, $borderEndIndicatorsDir);
        $borderTimesDetected      = $borderDetectorService->scanBundle($totalFrames, $frameDir, $startAt, $isStart, $result);

        if (count($borderTimesDetected) == 0) {
            throw new Exception('could not detect border. Empty array.');
        }

        Logger::log("Boundary: ");
        Logger::log($borderTimesDetected);
    }

} catch (Exception $e) {
    Logger::log($e->getMessage());
}