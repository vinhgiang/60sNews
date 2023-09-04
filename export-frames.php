<?php

use App\Services\AdsDetector\AdsDetectorService;
use App\Services\Ffmpeg\FfmpegService;

require __DIR__ . '/App/Configs/configs.php';

set_time_limit(300);

$video = 'video/' . date('ymd-a') . '.ts';
if (! file_exists($video)) {
    die('No file is needed to be uploaded.');
}

$fps   = 10;

$ffmpegService = new FfmpegService($video);

$frameDir = $ffmpegService->getFramesDir();

$ffmpegService->extractAllFrames(10);

$totalFrames = ceil($ffmpegService->getVideoDuration() * $fps);

$adsDetectorService = new AdsDetectorService();

$times = $adsDetectorService->scan($totalFrames, $frameDir);
$ffmpegService->trimVideo($times);

$ffmpegService->cleanup();