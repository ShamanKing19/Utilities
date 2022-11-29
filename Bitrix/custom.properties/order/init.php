<?php
// Тут что-то происходит ...

// Регистрация пользовательского поля
if(CModule::IncludeModule('sale')) {
    \Bitrix\Sale\Internals\Input\Manager::register('UNIQUE_CODE', [ // Тут нужен уникальный код
        'CLASS' => '\App\CustomProperties\PropertyExampleClassName', // Название класса
        'NAME' => Loc::getMessage('STORE_ATTACH_NAME'), // Название поля в админке
    ]);
}