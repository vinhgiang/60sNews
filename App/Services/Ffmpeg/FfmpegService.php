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

        if (! is_dir($dir)) {
            throw new UnexpectedValueException("$dir is not a directory");
        }

        $files = scandir($dir);

        $fileListingPath = self::createFileList($files, $dir);

        exec("ffmpeg -f concat -safe 0 -i $fileListingPath -c copy $finalFile");

        if ($cleanupAfterConcat && filesize($finalFile) >= 400000000) {
            FileSystem::rmdir_recursive($dir);
        }

        return $finalFile;
    }

    /**
     * @param string[] $videos
     * @param string $fileName
     * @return string
     */
    public static function concatVideos($videos, $fileName)
    {
        $fileName        = "$fileName.mp4";
        $fileListingPath = self::createFileList($videos);

        $command = "ffmpeg -f concat -safe 0 -i $fileListingPath -vf 'setpts=PTS-STARTPTS' $fileName";

        exec($command);

        unlink($fileListingPath);

        return $fileName;
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
     * @return string
     */
    public function trimVideo($times, $newFilename = '', $fps = 10)
    {
        $newFilename   = empty($newFilename) ? "$this->fileName-trimmed.mp4" : "$newFilename.mp4";
        Logger::log($times);

        $cutterCommand = $this->trimVideoCommandBuilder($times, $newFilename, $fps);

        Logger::log($cutterCommand);

        exec($cutterCommand . ' 2>&1', $this->debug);

        return $newFilename;
    }

    /**
     * @param int $duration
     * @param bool $isAtStart
     */
    public function addBlackFrame($duration, $isAtStart = false)
    {
        $duration  = intval($duration);
        $direction = $isAtStart ? "[black][0:v]" : "[0:v][black]";

        $command = "ffmpeg -y -i '{$this->video}' -filter_complex 'color=black:s=1024x576:r=25:d={$duration}[black];{$direction}concat=n=2:v=1:a=0[outv_black]' -map '[outv_black]' -map 0:a '{$this->workDir}/$this->fileName-black.mp4'";

        exec($command);
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

        return "ffmpeg -i '{$this->video}' -i 'resources/logo/60sec-logo-small.png' -filter_complex '$filterCommand $concatCommand $overlayCommand $blackScreenCommand' -map '[outv_black]' -map '[outa]' '{$this->workDir}/$newFilename'";
    }

    /**
     * @param string[] $files
     * @param string $dir
     * @return string
     */
    private static function createFileList($files, $dir = '')
    {
        if (empty($files)) {
            throw new LengthException("No file need to concat.");
        }

        $fileListing = '';
        foreach ($files as $file) {
            if ($file === '.' || $file === '..') {
                continue;
            }
            $fileListing .= "file '$file'\n";
        }

        $path = $dir == '' ? 'playlist.txt' : '$dir' . '/playlist.txt';

        file_put_contents($path, $fileListing);

        return $path;
    }
}