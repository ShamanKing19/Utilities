<?php
namespace App\Tools;

class Log
{
    const LOG_ROOT = '/zxc_logs';

    private string $logName;
    private bool $split;

    public function __construct(string $logName, bool $split)
    {
        $this->logName = $logName;
        $this->split = $split;
    }

    /**
     * Создание объекта
     * @param string $logName название файла
     * @param bool $split разделять ли файл по папкам с датой (для длительных логов)
     */
    public static function open(string $logName, bool $split = false): self
    {
        return new self($logName, $split);
    }

    public function write($data)
    {
        $this->complexWrite($data, $this->logName, $this->split);
    }

    /**
     * Запись данных в файл
     * @param mixed $data данные, которые будут записаны
     * @param string $logName название файла
     * @param bool $split разделять ли файл по папкам с датой (для длительных логов)
     */
    protected function complexWrite($data, string $name, bool $split)
    {
        $logGlobalRoot = $_SERVER['DOCUMENT_ROOT'] . self::LOG_ROOT;
        if (!file_exists($logGlobalRoot)) {
            mkdir($logGlobalRoot, 0777, true);
        }

        if($split) {
            $openPath = $logGlobalRoot . '/' .$name;
            if (!file_exists($openPath)) {
                mkdir($openPath, 0777, true);
            }

            $filePath = $openPath . '/' . date('d_m_Y') . '.log';
        } else {
            $filePath = $logGlobalRoot . '/' . $name . '.log';
        }

        $filePath = preg_replace(['/\/{2,}/', '/\/\./'], ['/', '.'], $filePath);
        $file = fopen($filePath, 'ab+');
        $this->putStringToFile($file, $this->formatData($data));
        fclose($file);
    }

    private function formatData($data) : string
    {
        return '['.date('d.m.Y H:i:s').']: '.print_r($data, true) . "\n";
    }

    private function putStringToFile($file, string $string) : void
    {
        fwrite($file, $string);
    }
}