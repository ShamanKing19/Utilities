<?php

use Bitrix\Main\ModuleManager;

class prospektestate_settings extends \CModule
{
    public static $moduleNameId;

    public $MODULE_GROUP_RIGHTS = 'Y';

    public $MODULE_NAME = 'Настройки сайта';
    public $MODULE_DESCRIPTION  = 'Модуль для управления настройками сайта';
    public $PARTNER_NAME = 'ShamanKing19';
    public $PARTNER_URI = 'https://github.com/ShamanKing19';
    public $MODULE_VERSION;
    public $MODULE_VERSION_DATE;

    public function __construct()
    {
        if(!file_exists(__DIR__ . '/version.php')) {
            throw new \Exception('Не найден обязательный файл version.php в модуле "' . get_class($this) . '"');
        }

        $arModuleVersion = [];
        include __DIR__ . '/version.php';

        $this->MODULE_ID = str_replace('_', '.', get_class($this));
        $this->MODULE_VERSION = $arModuleVersion['VERSION'];
        $this->MODULE_VERSION_DATE = $arModuleVersion['VERSION_DATE'];
        self::$moduleNameId = $this->MODULE_ID;
    }


    public function DoInstall()
    {
        ModuleManager::registerModule($this->MODULE_ID);
        return true;
    }


    public function InstallFiles()
    {
        return true;
    }


    public function DoUninstall()
    {
        ModuleManager::unRegisterModule($this->MODULE_ID);
        return true;
    }

    public function UnInstallFiles()
    {
        return true;
    }
}
