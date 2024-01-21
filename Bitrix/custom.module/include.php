<?php
defined('B_PROLOG_INCLUDED') and (B_PROLOG_INCLUDED === true) or die();

use Bitrix\Main\Loader;
use Bitrix\Main\EventManager;

Loader::registerAutoLoadClasses(basename(__DIR__), [
    'Prospektestate\Settings' => 'lib/Settings.php'
]);

//EventManager::getInstance()->addEventHandler('main', 'OnAfterUserAdd', function(){
    // do something when new user added
//});
