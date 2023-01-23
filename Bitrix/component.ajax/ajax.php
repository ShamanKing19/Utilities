<?php

// * Включение AJAX режима в любых помпонентах
[
    'AJAX_MODE' => 'Y',
    'AJAX_OPTION_JUMP' => 'N', // Вывключить прокрутку после перезагрузки компонента
    'AJAX_OPTION_HISTORY' => 'N', // Выключает появление параметров при клике на ссылку
]

// Проверка на ajax
$request = \Bitrix\Main\Context::getCurrent()->getRequest();
$isAjax = $request->isAjaxRequest();



// * Для корректного режима работы AJAX

// 1). Чистит всё что прилетело до этой строчки (может прилететь HTML)
if ($_REQUEST["AJAX_CALL"] == "Y") {
    $APPLICATION->RestartBuffer();
}

// 2). Убивает всё что может прилететь дальше
if ($_REQUEST["AJAX_CALL"] == "Y") {
    die();
}

// 3). Иногда нужно вставить это в начало шаблона. Хз зачем
$this->setFrameMode(false);