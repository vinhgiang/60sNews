<?php

use App\Services\Ffmpeg\FfmpegService;

require __DIR__ . '/App/Configs/configs.php';

$name  = '60s-' . (date('a') == 'am' ? 'sang-06-30-00' : 'chieu-18-30-00') . date('-Y-m-d');
$dir   = "video/$name";

$video = FfmpegService::concatVideoInDir($dir);

if (! file_exists($video)) {
    die("No file is needed to be extracted at $video");
}

$startTime = time();
$fps       = 10;

$ffmpegService = new FfmpegService($video);
$ffmpegService->extractAllFrames(10);

die('took ' . time() - $startTime . ' seconds.');