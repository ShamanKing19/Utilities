<?php
use JetBrains\PhpStorm\NoReturn;
use JetBrains\PhpStorm\ArrayShape;

class Response
{
    private bool $success;
    private array $data;
    private string $error;
    private string $defaultErrorMessage = 'Something went wrong...';
    private string $sessionErrorMessage = 'Сессия недействительна. Пожалуйста, перезагрузите страницу.';

    private function __construct(bool $success, int $statusCode)
    {
        http_response_code($statusCode);
        $this->success = $success;
        $this->data = [];
    }


    public static function getInstance(bool $success = true, int $statusCode = 200) : self
    {
        return new self($success, $statusCode);
    }


    /**
     * Добавление данных к уже существующим
     *
     * @param string $key ключ
     * @param mixed $value значение
     */
    public function addData(string $key, mixed $value) : self
    {
        if($this->data) {
            $this->data[$key] = $value;
        } else {
            $this->data = [$key => $value];
        }

        return $this;
    }


    /**
     * Добавление сразу нескольких полей к уже существующим
     * @param array $data данные
     */
    public function mergeData(array $data) : void
    {
        $this->data = array_merge($this->data, $data);
    }


    /**
     * Установка данных к запросу
     * @param array $data данные
     */
    public function setData(array $data) : self
    {
        if($data) {
            $this->data = $data;
        }

        return $this;
    }


    /**
     * Установка статуса ошибки сессии
     */
    public function setSessionError() : self
    {
        $this->setError($this->sessionErrorMessage);
        return $this;
    }


    /**
     * Добавление сообщения об ошибке и установка неудачного статуса ответа
     * @param string $message сообщение об ошибке
     */
    public function setError(string $message = '', int $statusCode = 400) : self
    {
        $this->setStatusCode($statusCode);
        if(empty($message)) {
            $message = $this->defaultErrorMessage;
        }

        $this->error = $message;
        $this->success = false;

        return $this;
    }

    /**
     * Формирование массива из данных
     */
    #[ArrayShape([
        'success' => 'bool',
        'data' => 'array',
        'error' => 'string'
    ])]
    public function toArray() : array
    {
        $response = [
            'success' => $this->success,
            'timestamp' => time()
        ];
        if($this->data) {
            $response['data'] = $this->data;
        }

        if(!$this->success) {
            $response['error'] = $this->error ?: $this->defaultErrorMessage;
        }

        return $response;
    }


    /**
     * Отправка ответа на запрос
     */
    #[NoReturn] #[ArrayShape([
        'success' => 'bool',
        'data' => 'array',
        'error' => 'string'
    ])]
    public function send() : void
    {
        $this->setJsonHeaders();
        die(json_encode($this->toArray(), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    }


    /**
     * Установка и отправка сообщения об ошибке
     * @param string $message
     */
    #[NoReturn] #[ArrayShape([
        'success' => 'bool',
        'error' => 'string'
    ])]
    public function sendError(string $message = '') : void
    {
        $this->setError($message);
        $this->send();
    }


    private function setStatusCode(int $statusCode) : void
    {
        header('Status: ' . $statusCode, true, $statusCode);
        header('X-PHP-Response-Code: ' . $statusCode, true, $statusCode);
    }


    private function setJsonHeaders() : void
    {
        header('Content-Type: application/json');
    }
}