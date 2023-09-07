<?php

use App\Services\Ffmpeg\FfmpegService;

require __DIR__ . '/App/Configs/configs.php';

$video    = 'video/' . date('ymd-a') . '.ts';
if (! file_exists($video)) {
    die("No file is needed to be extracted at $video");
}

$startTime = time();
$fps       = 10;

$ffmpegService = new FfmpegService($video);
$ffmpegService->extractAllFrames(10);

die('took' . time() - $startTime . ' seconds.');