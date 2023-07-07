<?php if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();

// Пример вызова
$arParams = [
    'IBLOCK' => MMB_CATALOG_IBLOCK_CODE, // [ОБЯЗАТЕЛЬНЫЙ] ID или символьный код инфоблока,
    'SEARCH_PARAMS' => [
        'ORDER' => [], // Сортировка для \CIBlockElement::getList($arOrder)',
        'FILTER' => [], // Фильтр для \CIBlockElement::getList([], $arFilter)'
        /**
         * Лучше всего выбирать только ID, а для элементов использовать модель с кэшированием в result_modifier.php,
         * т. к. автоматический сброс кэша выборки тут сделать не выйдет
         */
        'SELECT' => ['ID'], // Выбираемые поля для \CIBlockElement::getList([], [], false, false, $arSelect)'
    ],
    'PAGINATION' => [
        'USE_NUMBER_PAGINATION' => false, // Использовать ли пагинацию с числами (по умолчанию false)
        'USE_SHOW_MORE_PAGINATION' => false, // Использовать ли пагинацию "Показать ещё" (по умолчанию false). Для работы нужно навесить 3 класса и data-атрибут на кнопку "Показать ещё"
        'ITEMS_PER_PAGE' => 0, // Количество элементов, выводимых на странице (по умолчанию 0, то есть выводиться будут все)
        'PAGE_VARIABLE' => 'page', // Название переменной, по которой в GET запросе определяется текущая страница (по умолчанию 'page')
        'PAGE' => 1 // Номер страницы, которая будет отображена (по умолчанию 1)
    ],
    'CACHE' => [
        'ENABLED' => true, // Кэшировать ли список элементов
    ]
];
