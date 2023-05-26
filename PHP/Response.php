<?php
namespace App;

use JetBrains\PhpStorm\NoReturn;
use JetBrains\PhpStorm\ArrayShape;

class Response
{
    private bool $success;
    private array $data;
    private string $error;
    private string $defaultErrorMessage = 'Упс, что-то пошло не так...';
    private string $sessionErrorMessage = 'Сессия недействительна. Пожалуйста, перезагрузите страницу.';

    public function __construct(bool $success = true)
    {
        $this->success = $success;
        $this->data = [];
    }


    /**
     * Добавление данных к уже существующим
     *
     * @param string $key ключ
     * @param mixed $value значение
     */
    public function addData(string $key, mixed $value) : void
    {
        if($this->data) {
            $this->data[$key] = $value;
        } else {
            $this->data = [$key => $value];
        }
    }


    /**
     * Установка данных к запросу
     * @param array $data данные
     */
    public function setData(array $data) : void
    {
        if($data) {
            $this->data = $data;
        }
    }


    /**
     * Установка статуса ошибки сессии
     */
    public function setSessionError() : void
    {
        $this->setError($this->sessionErrorMessage);
    }


    /**
     * Добавление сообщения об ошибке и установка неудачного статуса ответа
     * @param string $message сообщение об ошибке
     */
    public function setError(string $message = '') : void
    {
        if(empty($message)) {
            $message = $this->defaultErrorMessage;
        }

        $this->error = $message;
        $this->success = false;
    }

    #[ArrayShape([
        'SUCCESS' => 'bool',
        'DATA' => 'array',
        'ERROR' => 'string'
    ])]
    public function toArray() : array
    {
        $response = ['SUCCESS' => $this->success];
        if($this->data) {
            $response['DATA'] = $this->data;
        }

        if(!$this->success) {
            $response['ERROR'] = $this->error ?: $this->defaultErrorMessage;
        }

        return $response;
    }


    /**
     * Отправка ответа на запрос
     */
    #[NoReturn] #[ArrayShape([
        'SUCCESS' => 'bool',
        'DATA' => 'array',
        'ERROR' => 'string'
    ])]
    public function send() : void
    {
        die(json_encode($this->toArray(), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    }
}