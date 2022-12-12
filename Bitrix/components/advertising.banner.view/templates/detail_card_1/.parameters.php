<?php

use App\Tools\IBlock;

if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();

if (!CModule::IncludeModule("advertising")) {
    return;
}

$seriesList = [];

CModule::IncludeModule("iblock");

$res = \CIBlockElement::GetList(['SORT' => 'ASC'], [
    'IBLOCK_ID' => IBlock::getIdByCode(UP_SERIES_IBLOCK_CODE),
], false, [], [
    'ID',
    'NAME',
]);

while ($ob = $res->GetNextElement()) {
    $arFields = $ob->GetFields();
    $seriesList[$arFields['ID']] = '['.$arFields['ID'].']: '.$arFields['NAME'];
}

$arTemplateParameters["PARAMETERS"]["BLOCK_TITLE"] = [
    "NAME" => 'Заголовок блока',
    "TYPE" => "STRING",
    "DEFAULT" => '',
    "SORT" => 1
];

$arTemplateParameters["PARAMETERS"]["TEXT_BLOCK_COUNT"] = [
    "NAME" => 'Количество блоков с текстом и изображением',
    "TYPE" => "LIST",
    "VALUES" => [
        1 => 1,
        2 => 2,
        3 => 3,
        4 => 4,
    ],
    "REFRESH" => 'Y',
    "SORT" => 2
];

$arTemplateParameters["PARAMETERS"]["SERIES_LIST"] = [
    "NAME" => 'В какой серии товаров показывать',
    "TYPE" => "LIST",
    "SIZE" => "5",
    "VALUES" => $seriesList,
    'MULTIPLE' => 'Y',
    "SORT" => 2
];

$sort = 1;
if((int)$arCurrentValues['TEXT_BLOCK_COUNT']) {
    for ($i = 1; $i < (int)$arCurrentValues['TEXT_BLOCK_COUNT'] + 1; ++$i) {
        $arTemplateParameters["PARAMETERS"]["IMG_" . $i] = [
            "NAME" => 'Изображение ' . $i,
            "TYPE" => "IMAGE",
            "DEFAULT" => "Y",
            "SORT" => 3+$sort
        ];

        $arTemplateParameters["PARAMETERS"]["TITLE_" . $i] = [
            "NAME" => 'Заголовок ' . $i,
            "TYPE" => "STRING",
            "DEFAULT" => "",
            "SORT" => 3+$sort
        ];

        $arTemplateParameters["PARAMETERS"]["TEXT_" . $i] = [
            "NAME" => 'Текст ' . $i,
            "TYPE" => "STRING",
            "ROWS" => "5",
            "COLS" => "50",
            "DEFAULT" => "",
            "SORT" => 3+$sort
        ];

        $sort++;
    }
} else {
    $arTemplateParameters["PARAMETERS"]["IMG_1"] = [
        "NAME" => 'Изображение 1',
        "TYPE" => "IMAGE",
        "DEFAULT" => "Y",
        "SORT" => 3
    ];

    $arTemplateParameters["PARAMETERS"]["TITLE_1"] = [
        "NAME" => 'Заголовок 1',
        "TYPE" => "STRING",
        "DEFAULT" => "",
        "SORT" => 3
    ];

    $arTemplateParameters["PARAMETERS"]["TEXT_1"] = [
        "NAME" => 'Текст 1',
        "ROWS" => "5",
        "COLS" => "50",
        "TYPE" => "STRING",
        "DEFAULT" => "",
        "SORT" => 3
    ];
}

$arTemplateParameters["PARAMETERS"]["MAIN_IMG"] = [
    "NAME" => 'Основное изображение для точек',
    "TYPE" => "IMAGE",
    "DEFAULT" => "Y",
    "SORT" => 100
];

$arTemplateParameters["PARAMETERS"]["DOTS_COUNT"] = [
    "NAME" => 'Количество точек на основном изображении',
    "TYPE" => "LIST",
    "VALUES" => [
        1 => 1,
        2 => 2,
        3 => 3,
        5 => 5,
        6 => 6,
        7 => 7,
        8 => 8,
        9 => 9,
        10 => 10,
    ],
    "REFRESH" => 'Y',
    "SORT" => 101
];

$sort = 1;
if((int)$arCurrentValues['DOTS_COUNT']) {
    for ($i = 1; $i < (int)$arCurrentValues['DOTS_COUNT'] + 1; ++$i) {
        $arTemplateParameters["PARAMETERS"]["DOTS_LEFT_" . $i] = [
            "NAME" => 'Отступ слева (%) ' . $i,
            "TYPE" => "STRING",
            "DEFAULT" => "",
            "SORT" => 102+$sort
        ];

        $arTemplateParameters["PARAMETERS"]["DOTS_TOP_" . $i] = [
            "NAME" => 'Отступ сверху (%) ' . $i,
            "TYPE" => "STRING",
            "DEFAULT" => "",
            "SORT" => 102+$sort
        ];

        $arTemplateParameters["PARAMETERS"]["DOTS_TEXT_" . $i] = [
            "NAME" => 'Текст в точке ' . $i,
            "TYPE" => "STRING",
            "ROWS" => "3",
            "COLS" => "50",
            "DEFAULT" => "",
            "SORT" => 102+$sort
        ];
        
        $sort++;
    }
} else {
    $arTemplateParameters["PARAMETERS"]["DOTS_LEFT_1"] = [
        "NAME" => 'Отступ слева (%) 1',
        "TYPE" => "STRING",
        "DEFAULT" => "",
        "SORT" => 102
    ];

    $arTemplateParameters["PARAMETERS"]["DOTS_TOP_1"] = [
        "NAME" => 'Отступ справа (%) 1',
        "TYPE" => "STRING",
        "DEFAULT" => "",
        "SORT" => 102
    ];

    $arTemplateParameters["PARAMETERS"]["DOTS_TEXT_1"] = [
        "NAME" => 'Текст в точке 1',
        "TYPE" => "STRING",
        "ROWS" => "3",
        "COLS" => "50",
        "DEFAULT" => "",
        "SORT" => 102
    ];
}

$arTemplateParameters["SETTINGS"]["MULTIPLE"] = 'N';