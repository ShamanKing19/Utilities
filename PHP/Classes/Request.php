<?php
use JetBrains\PhpStorm\NoReturn;
use JetBrains\PhpStorm\ArrayShape;

class Request
{
    private static self $instance;
    private array $getParams;
    private array $postParams;


    private function __construct()
    {
        $this->getParams = array_map('htmlspecialchars', $_GET);
        $this->postParams = array_map('htmlspecialchars', $_POST);
    }


    public static function getInstance() : self
    {
        if(empty(self::$instance)) {
            self::$instance = new self();
        }

        return self::$instance;
    }


    public function toArray() : array
    {
        return [
            'GET' => $this->getParams,
            'POST' => $this->postParams
        ];
    }


    public function get(string $key = '') : mixed
    {
        if($key) {
            return $this->getParams[$key];
        }
        return $this->getParams;
    }


    public function getPost(string $key = '') : mixed
    {
        if($key) {
            return $this->postParams[$key];
        }

        return $this->postParams;
    }
}