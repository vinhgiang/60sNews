<?php

use App\Utils\Logger;

require __DIR__ . '/vendor/autoload.php';

error_reporting(E_ALL);
ini_set('display_errors', 'On');

$startTime = '06:30:00';
$endTime   = '07:00:00';
$now       = date('H:i:s');
if ($now < $startTime || $now > $endTime) {
//    http_response_code(400);
    die("The job will be started from $startTime to $endTime. Now is $now.");
}


$resourcePath = 'http://171.232.93.6:8080/sctv/s10/cdn-cgi/edge/v2/e2.endpoint.cdn.sctvonline.vn/nginx.s10.edge.cdn.sctvonline.vn/hls/htv7/';

// the token is optional during this examination
$token       = 'token=epGJ0I_QNuDvH0vqMePrRw&e=1691633682';
$playListUrl = "{$resourcePath}index.m3u8?{$token}";

$playList = explode("\n", file_get_contents($playListUrl));

$playList = [];

/**
 * StreamingInstruction example
 * Array
 * (
 * [0] => #EXTM3U
 * [1] => #EXT-X-VERSION:3
 * [2] => #EXT-X-MEDIA-SEQUENCE:1102063
 * [3] => #EXT-X-TARGETDURATION:3
 * [4] => #EXTINF:3.000,
 * [5] => 1691696149512.ts?token=epGJ0I_QNuDvH0vqMePrRw&e=1691633682
 * [6] => #EXTINF:3.000,
 * [7] => 1691696152619.ts?token=epGJ0I_QNuDvH0vqMePrRw&e=1691633682
 * [8] => #EXTINF:3.000,
 * [9] => 1691696155721.ts?token=epGJ0I_QNuDvH0vqMePrRw&e=1691633682
 * [10] => #EXTINF:3.000,
 * [11] => 1691696158825.ts?token=epGJ0I_QNuDvH0vqMePrRw&e=1691633682
 * [12] => #EXTINF:3.000,
 * [13] => 1691696161416.ts?token=epGJ0I_QNuDvH0vqMePrRw&e=1691633682
 * [14] => #EXTINF:3.000,
 * [15] => 1691696164526.ts?token=epGJ0I_QNuDvH0vqMePrRw&e=1691633682
 * [16] => #EXTINF:3.000,
 * [17] => 1691696167634.ts?token=epGJ0I_QNuDvH0vqMePrRw&e=1691633682
 * [18] => #EXTINF:3.000,
 * [19] => 1691696170735.ts?token=epGJ0I_QNuDvH0vqMePrRw&e=1691633682
 * [20] => #EXTINF:3.000,
 * [21] => 1691696173837.ts?token=epGJ0I_QNuDvH0vqMePrRw&e=1691633682
 * [22] => #EXTINF:3.000,
 * [23] => 1691696176428.ts?token=epGJ0I_QNuDvH0vqMePrRw&e=1691633682
 * [24] => #EXTINF:3.000,
 * [25] => 1691696179540.ts?token=epGJ0I_QNuDvH0vqMePrRw&e=1691633682
 * [26] => #EXTINF:3.000,
 * [27] => 1691696182643.ts?token=epGJ0I_QNuDvH0vqMePrRw&e=1691633682
 * [28] => #EXTINF:3.000,
 * [29] => 1691696185748.ts?token=epGJ0I_QNuDvH0vqMePrRw&e=1691633682
 * [30] => #EXTINF:3.000,
 * [31] => 1691696188851.ts?token=epGJ0I_QNuDvH0vqMePrRw&e=1691633682
 * [32] => #EXTINF:3.000,
 * [33] => 1691696191446.ts?token=epGJ0I_QNuDvH0vqMePrRw&e=1691633682
 * [34] => #EXTINF:3.000,
 * [35] => 1691696194550.ts?token=epGJ0I_QNuDvH0vqMePrRw&e=1691633682
 * [36] => #EXTINF:3.000,
 * [37] => 1691696197658.ts?token=epGJ0I_QNuDvH0vqMePrRw&e=1691633682
 * [38] => #EXTINF:3.000,
 * [39] => 1691696200759.ts?token=epGJ0I_QNuDvH0vqMePrRw&e=1691633682
 * [40] => #EXTINF:3.000,
 * [41] => 1691696203351.ts?token=epGJ0I_QNuDvH0vqMePrRw&e=1691633682
 * [42] => #EXTINF:3.000,
 * [43] => 1691696206456.ts?token=epGJ0I_QNuDvH0vqMePrRw&e=1691633682
 * [44] =>
 * )
 */

// the real streaming start at element #5
$streamingData = [];
$lastId        = file_get_contents('last-id.txt');
for ($i = 5; $i < count($playList); $i++) {
    if ($i % 2 != 0) {
        preg_match('/(\d+)[.]ts/', $playList[$i], $matches);
        $id = $matches[1];
        if ($id <= $lastId) {
            continue;
        }
        $streamingData[] = file_get_contents("{$resourcePath}$id.ts?{$token}");
        $newLastId       = $id;
    }
}

if (!isset($newLastId)) {
    $logger = new Logger();

    $logger->log('No new ID produced.');
    $logger->log('Playlist:');
    $logger->log(print_r($playList, true));

    http_response_code(400);
    exit;
}

$lastId = $newLastId;

file_put_contents('last-id.txt', $lastId);
file_put_contents('whole.ts', join('', $streamingData), FILE_APPEND);

die('DONE');