<?php

use App\Utils\FileSystem;

require __DIR__ . '/App/Configs/configs.php';

// clear all frames
FileSystem::rmdir_recursive(__DIR__ . '/video/frames/');

// reset all scan info
file_put_contents('log/scan-boundary.txt', '');
file_put_contents('log/scan-ads.txt', '');