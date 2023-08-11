<?php
namespace App\Utils;

class Logger
{
    /** @var string */
    private $filePath = '';

    public function __construct($filePath = 'log/info.log')
    {
        $this->filePath = $filePath;
    }

    public function log($msg)
    {
        $msg = '[' . Date('Y-m-d H:i:s') . "] $msg \n";

        $existingLog = file_get_contents($this->filePath);
        $existingLog = $existingLog !== false ? $existingLog : '';

        file_put_contents($this->filePath, $existingLog . $msg);
    }
}