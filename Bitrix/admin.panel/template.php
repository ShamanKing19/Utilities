<?php

/* Добавление кнопок в панель админки */
global $USER;
// Проверка нужна, потому что у неавторизованного пользователя тоже появится окно админки
if ($USER->IsAuthorized() && $USER->IsAdmin()) {
    $APPLICATION->AddPanelButton(
        [
            'HREF' => '/bitrix/admin/' . CIBlock::GetAdminElementEditLink($iBlockId, $arResult['ID']),
            'SRC' => "/bitrix/images/fileman/panel/web_form.gif",
            'TEXT' => "Перейти к товару в админке",
            'MAIN_SORT' => 400,
            'TYPE' => 'SMALL',
            'SORT' => 100
        ]
    );

    $APPLICATION->AddPanelButton(
        [
            'HREF' => '/bitrix/admin/' . CIBlock::GetAdminSectionEditLink($iBlockId, $sectionId),
            'SRC' => "/bitrix/images/fileman/panel/web_form.gif",
            'TEXT' => "Перейти к разделу товара в админке",
            'MAIN_SORT' => 400,
            'TYPE' => 'SMALL',
            'SORT' => 100
        ]
    );
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