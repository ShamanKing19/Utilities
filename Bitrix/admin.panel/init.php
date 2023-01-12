<?php

/* Добавление кнопок в панель админки */
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