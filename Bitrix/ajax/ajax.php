<?php

// * Включение AJAX режима в кастомных компонентаэ
if($arParams["AJAX_MODE"] == "Y")
{
	$ajaxSession = CAjax::GetSession();
}


// * Для корректного режима работы AJAX

// 1). После проверки пролога пишем это в шаблоне компонента
if ($_REQUEST["AJAX_CALL"] == "Y") {
    $APPLICATION->RestartBuffer();
}

// 2). В самом конце шаблона компонента пишем это
if ($_REQUEST["AJAX_CALL"] == "Y") {
    die();
}