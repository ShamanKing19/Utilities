<?php
namespace App\Tools;

class Log
{
    const LOG_ROOT = '/sl_logs';

    private $logName;

    public function __construct($logName)
    {
        $this->logName = $logName;
    }

    public static function open($logName)
    {
        return new self($logName);
    }

    public function write($data)
    {
        $this->complexWrite($data, $this->logName);
    }

    protected function complexWrite($data, $name)
    {
        $logGlobalRoot = $_SERVER['DOCUMENT_ROOT'] . self::LOG_ROOT;

        if (!file_exists($logGlobalRoot)) {
            mkdir($logGlobalRoot, 0777, true);
        }

        $filePath = $logGlobalRoot . '/' . $name . '.log';
        $filePath = preg_replace(['/\/{2,}/', '/\/\./'], ['/', '.'], $filePath);

        $file = fopen($filePath, 'ab+');

        $this->putStringToFile($file, $this->formatData($data));

        fclose($file);
    }

    private function formatData($data): string
    {
        return '['.date('d.m.Y H:i:s').']: '.print_r($data, true) . "\n";
    }

    private function putStringToFile($file, string $string): void
    {
        fwrite($file, $string);
    }
}