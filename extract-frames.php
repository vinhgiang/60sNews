<?php

use App\Services\Ffmpeg\FfmpegService;

require __DIR__ . '/App/Configs/configs.php';

$name  = '60s-' . (date('a') == 'am' ? 'sang-06-30-00' : 'chieu-18-30-00') . date('-Y-m-d');
$dir   = "video/$name";
$video = "video/$name.ts";

$files = scandir($dir);
foreach ($files as $file) {
    if ($file == '.' || $file == '..') {
        continue;
    }
    $data = file_get_contents("$dir/$file");
    file_put_contents($video, $data, FILE_APPEND);
    unlink("$dir/$file");
}
rmdir($dir);

if (! file_exists($video)) {
    die("No file is needed to be extracted at $video");
}

$startTime = time();
$fps       = 10;

$ffmpegService = new FfmpegService($video);
$ffmpegService->extractAllFrames(10);

die('took ' . time() - $startTime . ' seconds.');