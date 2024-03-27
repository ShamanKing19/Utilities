<?php

/* Добавление кнопок в панель админки */
global $USER;
// Проверка нужна, потому что у неавторизованного пользователя тоже появится окно админки
if ($USER->IsAdmin()) {
    $iblockId = $arResult['IBLOCK_ID'];
    $sectionId = $arResult['IBLOCK_SECTION_ID'];

    $APPLICATION->AddPanelButton([
        'HREF' => '/bitrix/admin/' . \CIBlock::GetAdminElementEditLink($iblockId, $arResult['ID']),
        'SRC' => "/bitrix/images/fileman/panel/web_form.gif",
        'TEXT' => 'Перейти к товару в админке',
        'MAIN_SORT' => 400,
        'TYPE' => 'SMALL',
        'SORT' => 100
    ]);

    if ($sectionId) {
        $APPLICATION->AddPanelButton([
            'HREF' => '/bitrix/admin/' . \CIBlock::GetAdminSectionEditLink($iblockId, $sectionId),
            'SRC' => "/bitrix/images/fileman/panel/web_form.gif",
            'TEXT' => "Перейти к разделу товара в админке",
            'MAIN_SORT' => 400,
            'TYPE' => 'SMALL',
            'SORT' => 100
        ]);
    }

    $iblock = \Bitrix\Iblock\IblockTable::getRow(['filter' => ['=CODE' => $iblockCode], 'cache' => ['ttl' => 86400]]);
    $typeId = $iblock[IBLOCK_TYPE_ID];

    $APPLICATION->AddPanelButton([
        'HREF' => "/bitrix/admin/iblock_element_admin.php?IBLOCK_ID=$iblockId&type=$typeId&lang=ru&apply_filter=Y" . $sectionId ? "&find_section_section=$sectionId&SECTION_ID=$sectionId" : '',
        'SRC' => '/bitrix/images/fileman/panel/web_form.gif',
        'TEXT' => 'Перейти к инфоблоку',
        'MAIN_SORT' => 400,
        'TYPE' => 'SMALL',
        'SORT' => 100
    ]);
}


/* Добавление кнопок для редактирования куда угодно */
$tooltipHtmlId = "Любой уникальный id";
$APPLICATION->setEditArea($tooltipHtmlId, [
    // Просто кнопка
    [
        'URL' => 'https://yandex.ru',
        'ICON' => 'bx-context-toolbar-edit-icon', // CSS класс иконки
        'TITLE' => 'Кнопка с иконкой',
    ],
    // Кнопка, открывающая что-нибудь в маленьком окне (попапе)
    [
        'URL' => 'javascript:'.$APPLICATION->getPopupLink([
                'URL' => '/index.php',
                'PARAMS' => [
                    'width' => '640',
                    'height' => '480',
                ],
            ]),
        'SRC' => 'URL картинки',
        'TITLE' => 'Кнопка с картинкой и всплывающий окном',
    ],
]);