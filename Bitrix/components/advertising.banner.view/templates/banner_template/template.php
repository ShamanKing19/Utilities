<?php if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();
$this->setFrameMode(true);

/* Значения полей, указанных в .parameters.php */
$props = $arParams['PROPS'];
/* Файлы и картинки */
$files = $arParams['FILES'];

/**
 * Подключение компонента где-нибдуь в шаблоне (отсюда удалить)
 * В названиях и типах баннеров нельзя использовать точки
 */
$APPLICATION->IncludeComponent('bitrix:advertising.banner', 'banner_type', [
    'TYPE' => 'banner_type',
    'NOINDEX' => 'Y', // выключает индексацию поисковиков
    'CACHE_TYPE' => 'A',
    'CACHE_TIME' => env('CACHE') ? 3600 : 0,
]);
?>


<!-- Вёрстка -->