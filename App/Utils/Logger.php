<?php
namespace App\Utils;

class Logger
{
    /** @var string */
    private static $filePath = 'log/info.log';

    public function __construct($filePath)
    {
        self::$filePath = $filePath;
    }

    public static function log($msg)
    {
        $msg = '[' . Date('Y-m-d H:i:s') . "] $msg \n";

        $existingLog = file_get_contents(self::$filePath);
        $existingLog = $existingLog !== false ? $existingLog : '';

        file_put_contents(self::$filePath, $existingLog . $msg);
    }
}