<?php if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();

/* Подключение стилей для админки */
\App\Tools\Assets::addCss(UP_FRONT_PATH . '/template_styles.min.css');

// ! Тут ничего не трогать
$frame = $this->createFrame()->begin('');
echo $arResult["BANNER"];
$frame->end();