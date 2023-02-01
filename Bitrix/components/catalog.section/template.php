<?php

// Фильтр для выводимых элементов
global $productsFilter;

$productsFilter = [
    'ID' => $arResult['COMPARED_PRODUCT_IDS']
];

$APPLICATION->IncludeComponent('unipump:catalog.section', 'comparison', [
    'IBLOCK_ID' => 1,
    'IBLOCK_TYPE' => 'content',
    'CACHE_TYPE' => 'N',
    'CACHE_TIME' => 0,
    'FILTER_NAME' => 'productsFilter',

    // Без этого говна не вылезут свойства элементов
    "PRICE_CODE" => ["BASE"],

]);