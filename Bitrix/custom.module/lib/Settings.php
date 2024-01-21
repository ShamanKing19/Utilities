<?php

namespace Prospektestate;

class Settings
{
    private static self $instance;

    private array $values = [];

    /**
     * Singleton
     *
     * @return self
     */
    public static function getInstance(): self
    {
        if (isset(self::$instance)) {
            return self::$instance;
        }

        return self::$instance = new self();
    }

    /**
     * Номер телефона WhatsApp
     *
     * @return string
     */
    public function getWhatsAppPhoneNumber(): string
    {
        return $this->getOption('whatsapp_phone') ?? '';
    }

    /**
     * Получение значения поля из настроек
     *
     * @param string $key
     *
     * @return string
     */
    private function getOption(string $key)
    {
        if (isset($this->values[$key])) {
            return $this->values[$key];
        }

        return $this->values[$key] = \Bitrix\Main\Config\Option::get($this->getModuleId(), $key);
    }

    /**
     * Получение id модуля для подключения и обращения к нему
     *
     * @return string
     */
    private function getModuleId(): string
    {
        return basename(dirname(__DIR__));
    }
}