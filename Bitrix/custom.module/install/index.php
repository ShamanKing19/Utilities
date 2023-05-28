<?php if(class_exists('b24_report')) return;

class b24_report extends \CModule
{
    public static $moduleNameId;

    public $MODULE_ID = "b24.report";
    public $MODULE_DESCRIPTION;
    public $MODULE_GROUP_RIGHTS = "Y";

    public $MODULE_NAME;
    public $MODULE_VERSION;
    public $MODULE_VERSION_DATE;
    public $PARTNER_NAME;
    public $PARTNER_URI;

    public function __construct()
    {
        if(file_exists(__DIR__ . '/version.php')) {
            $arModuleVersion = [];
            include __DIR__ . '/version.php';

            $classNameFormatted = str_replace('_', '.', get_class($this));
            self::$moduleNameId = $classNameFormatted;
            $this->MODULE_ID = $classNameFormatted;
            $this->MODULE_VERSION = $arModuleVersion['VERSION'];
            $this->MODULE_VERSION_DATE = $arModuleVersion['VERSION_DATE'];
            $this->MODULE_NAME = 'Название модуля';
            $this->MODULE_DESCRIPTION  = 'Описание модуля';
            $this->PARTNER_NAME = 'ShamanKing19';
            $this->PARTNER_URI = 'https://github.com/ShamanKing19';
        } else {
            ShowError('Не найден обязательный файл version.php');
        }
    }


    public function DoInstall()
    {
        global $APPLICATION;
        $moduleRight = $APPLICATION->GetGroupRight($this->MODULE_ID);
        if($moduleRight == "W") {
            if(CheckVersion(\Bitrix\Main\ModuleManager::getVersion('main'), '14.00.00')) {
                if(!$this->InstallFiles()) {
                    $APPLICATION->ThrowException(
                        'Что-то пошло не так при установке файлов'
                    );
                }

                \Bitrix\Main\ModuleManager::registerModule($this->MODULE_ID);
            } else {
                $APPLICATION->ThrowException(
                    'Версия главного модуля ниже 14. Не поддерживается технология D7, необходимая модулю. Пожалуйста обновите систему.'
                );
            }

            return true;
        }

        return false;
    }


    public function InstallFiles()
    {
        /* Копирование компонентов модуля */
        $componentsPath = \Bitrix\Main\Application::getDocumentRoot().'/local/components/'.$this->MODULE_ID.'/';
        CopyDirFiles(__DIR__.'/components', $componentsPath, true, true);
        return true;
    }


    public function DoUninstall()
    {
        if(!$this->UnInstallFiles();) {
            $APPLICATION->ThrowException('Что-то пошло не так при удалении файлов');
            return false;
        }
        
        \Bitrix\Main\ModuleManager::unRegisterModule($this->MODULE_ID);
        return true;
    }

    public function UnInstallFiles()
    {
        /* Удаление компонентов модуля */
        $dirPath = \Bitrix\Main\Application::getDocumentRoot().'/local/components/'.$this->MODULE_ID;
        \Bitrix\Main\IO\Directory::deleteDirectory($dirParh);
        return true;
    }
}
