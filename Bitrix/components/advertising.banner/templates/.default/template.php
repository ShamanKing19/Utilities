<?php
// ! Нужно создать такой шаблон для каждого баннера

if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();

// Частный случай подключения стилей для баннера, можно и без этой строки
App\Tools\Assets::addCss(UP_FRONT_PATH . '/template_styles.min.css');

// Это всегда должно быть
$frame = $this->createFrame()->begin('');
echo $arResult["BANNER"];
$frame->end();