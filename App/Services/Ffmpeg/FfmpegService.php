<?php

namespace App\Services\Ffmpeg;

use App\Utils\FileSystem;
use App\Utils\Logger;
use LengthException;
use UnexpectedValueException;

class FfmpegService
{
    /** @var string */
    private $video;

    /** @var string */
    private $fileName;

    /** @var string */
    private $workDir;

    /** @var string[] */
    private $debug;

    /**
     * @param string $video
     */
    public function __construct($video)
    {
        $this->video    = $video;
        $this->fileName = pathinfo($video, PATHINFO_FILENAME);
        $this->workDir  = dirname($video);
    }

    /**
     * @param string $dir
     * @param bool $cleanupAfterConcat
     * @return string
     */
    public static function concatVideoInDir($dir, $cleanupAfterConcat = true)
    {
        $finalFile   = $dir . '.mp4';
        $fileListing = '';
        $files       = scandir($dir);

        if (! is_dir($dir)) {
            throw new UnexpectedValueException("$dir is not a directory");
        }

        foreach ($files as $file) {
            if ($file === '.' || $file === '..') {
                continue;
            }
            $fileListing .= "file '$file'\n";
        }

        if ($fileListing === '') {
            throw new LengthException("$dir is empty");
        }

        file_put_contents($dir . '/playlist.txt', $fileListing);

        exec("ffmpeg -f concat -safe 0 -i $dir/playlist.txt -c copy $finalFile");

        if ($cleanupAfterConcat && filesize($finalFile) >= 400000000) {
            FileSystem::rmdir_recursive($dir);
        }

        return $finalFile;
    }

    /**
     * @return string[]
     */
    public function getDebugInfo()
    {
        return $this->debug;
    }

    /**
     * @return false|string
     */
    public function getVideoDuration()
    {
        return exec("ffprobe -i '{$this->video}' -show_entries format=duration -v quiet -of csv='p=0'");
    }

    public function getFramesDir()
    {
        return "$this->workDir/frames/$this->fileName";
    }

    /**
     * extract all frame at 10 photos / second. 1/3 for every 3 seconds. 1/10 for every 10 seconds.
     *
     * @param float $fps
     * @param string $filenameFormat
     * @return false|string
     */
    public function extractAllFrames($fps = 10, $filenameFormat = '%1d.jpg')
    {
        $framesPath = $this->getFramesDir();
        if (! is_dir($framesPath)) {
            mkdir($framesPath, 0777, true);
        }

        return exec("ffmpeg -i '{$this->video}' -r $fps '$framesPath/$filenameFormat' 2>&1", $this->debug);
    }

    /**
     * @return void
     */
    public function cleanup()
    {
        FileSystem::rmdir_recursive($this->getFramesDir());
        file_put_contents('log/scan-boundary.txt', '');
        file_put_contents('log/scan-ads.txt', '');
    }

    /**
     * @param string[][] $times
     * @param string $newFilename
     * @param int $fps
     * @return false|string
     */
    public function trimVideo($times, $newFilename = '', $fps = 10)
    {
        $newFilename   = empty($newFilename) ? "$this->fileName-trimmed" : $newFilename;
        Logger::log($times);

        $cutterCommand = $this->trimVideoCommandBuilder($times, $newFilename, $fps);

        Logger::log($cutterCommand);

        return exec($cutterCommand . ' 2>&1', $this->debug);
    }

    /**
     * @param string[][] $times
     * @param string $newFilename
     * @param int $fps
     * @return string
     */
    private function trimVideoCommandBuilder($times, $newFilename, $fps)
    {
        $numAds = count($times);
        $filterCommand = '';
        $concatCommand = '';
        $index = 0;
        $start = 0;
        foreach ($times as [$startFrame, $endFrame]) {
            $startFrame /= $fps;
            $isLast     = $index == $numAds;
            $endFilter  = $isLast ? '' : ":end=$startFrame";

            $filterCommand .= "[0:v]eq=brightness=0.05,trim=start={$start}{$endFilter},setpts=PTS-STARTPTS,format=yuv420p[{$index}v];";
            $filterCommand .= "[0:a]volume=5dB,atrim=start={$start}{$endFilter},asetpts=PTS-STARTPTS[{$index}a];";

            $concatCommand .= "[{$index}v][{$index}a]";

            $start = $endFrame / $fps;
            $index++;
        }

        $concatCommand .= "concat=n=" . $numAds . ":v=1:a=1[outv][outa];";
        $overlayCommand = "[outv][1:v]overlay=814:49[outv_overlay];";
        $blackScreenCommand = "color=black:s=1024x576:r=25:d=60[black];[outv_overlay][black]concat=n=2:v=1:a=0[outv_black];";

        $outroVideos = [];
        while (count($outroVideos) < 10) {
            $videoId = rand(0, 481);
            $outroVideos[$videoId] = "'resources/outro/$videoId.mp4'";
        }

        Logger::log('[' . implode(', ', array_keys($outroVideos)). ']');

        $outroVideoInputs = '-i ' . implode(' -i ', $outroVideos);

        $outroCommand = "[outv_black][outa][2:v][2:a][3:v][3:a][4:v][4:a][5:v][5:a][6:v][6:a][7:v][7:a][8:v][8:a][9:v][9:a][10:v][10:a][11:v][11:a]concat=n=11:v=1:a=1[concat_v][concat_a];";

        return "ffmpeg -i '{$this->video}' -i 'resources/logo/60sec-logo-small.png' $outroVideoInputs -filter_complex '$filterCommand $concatCommand $blackScreenCommand $overlayCommand $outroCommand' -map '[concat_v]' -map '[concat_a]' '{$this->workDir}/$newFilename.mp4'";
    }
}