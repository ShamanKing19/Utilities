<?php
namespace App;

use Exception;

class BackgroundTask {
    private string $scriptPath;
    private array $args;
    private string $phpPath;

    /**
     * BackgroundTask constructor.
     * 
     * @param string $scriptPath Путь до файла
     * @param array $args Аргументы запуска
     * @param string $phpPath Путь до PHP (пример /opt/remi/php81/root/bin/php)
     */
    public function __construct(string $scriptPath, array $args = [], string $phpPath = '') {
        $this->scriptPath = $scriptPath;
        $this->args = $args;
        $this->phpPath = $phpPath;
    }

    /**
     * @throws Exception
     */
    public function run()
    {
        if(!file_exists($this->scriptPath)) {
            throw new Exception('File not found');
        }
        
        $argsString = false;
        $phpPath = 'php';
        if(!empty($this->phpPath)) {
            $phpPath = $this->phpPath;
        }
        
        $shellCommand = 'nohup '.$phpPath.' '.$this->scriptPath;
        if(count($this->args)) {
            $argsString = escapeshellarg(http_build_query($this->args));
        }

        $shellCommand .= ' '.$argsString.' > /dev/null 2>&1 &';
        
        return exec($shellCommand);
    }
}