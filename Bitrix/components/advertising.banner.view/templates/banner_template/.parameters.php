<?php if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();

/* Это обязательно */
if (!CModule::IncludeModule('advertising')) {
    return;
}

/* Это необязательно */
if (!CModule::IncludeModule('iblock')) {
    return;
}

/* Сохранённые переменные лежат тут */
$arCurrentValues;


/* Поля ввода с настройками добавляем так */
$arTemplateParameters['PARAMETERS']['TITLE'] = [
    'NAME' => 'Текстовое поле',
    'TYPE' => 'STRING',
    'DEFAULT' => '',
    'ROWS' => '5',
    'COLS' => '50',
    'SORT' => 100
];

$arTemplateParameters['PARAMETERS']['SOME_LIST'] = [
    'NAME' => 'Поле типа список',
    'TYPE' => 'LIST',
    /* В базу сохраняется значение ключа */
    'VALUES' => [
        'KEY1' => 'VALUE1',
        'KEY2' => 'VALUE2',
        'KEY3' => 'VALUE3',
        'KEY4' => 'VALUE4',
    ],
    'REFRESH' => 'Y', // При выборе элемента обновит страницу аяксом (вроде бы)
    'SIZE' => '5', // ! Хз что делает
    'MULTIPLE' => 'Y', // ! Хз вроде не работает
    'SORT' => 100
];


$arTemplateParameters['PARAMETERS']['IMAGE'] = [
    'NAME' => 'Какая-нибудь картинка',
    'TYPE' => 'IMAGE',
    'DEFAULT' => 'Y', // ! Хз что делает
    'SORT' => 100
];

$arTemplateParameters['PARAMETERS']['FILE'] = [
    'NAME' => 'Какой-нибудь файл',
    'TYPE' => 'FILE',
    'DEFAULT' => 'Y', // ! Хз что делает
    'SORT' => 100
];

/**
 * Если здесь установить Y, то в админке можно будет добавлять слайды.
 * При подключении компонента слайды будут дублироваться друг за другом
 */

$arTemplateParameters['SETTINGS']['MULTIPLE'] = 'N';