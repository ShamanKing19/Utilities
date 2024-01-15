<?php defined('B_PROLOG_INCLUDED') and (B_PROLOG_INCLUDED === true) or die();

/**
 * Документация по настройкам пункта меню
 * @see https://dev.1c-bitrix.ru/api_help/main/general/admin.section/menu.php
 */

\Bitrix\Main\Localization\Loc::loadMessages(__FILE__);

$moduleId = basename(dirname(__DIR__));

$menu = [
    'settings' => [
        'parent_menu' => 'global_menu_settings',
        'sort' => 1,
        'text' => 'Настройки сайта',
        'title' => 'Какое-то описание...',
        'url' => "/bitrix/admin/settings.php?lang=ru&mid=$moduleId&mid_menu=1",
        'icon' => 'fileman_menu_icon'
//        'items_id' => 'menu_references', //описание подпункта, то же, что и ранее, либо другое, можно вставить сколько угодно пунктов меню
//        'items' => [
//            [
//                'text' => 'Что-то 1',
//                'url' => '/bitrix/admin/settings.php?lang=ru&mid=settings&mid_menu=1',
//                'more_url' => ['mymodule_index.php?lang=' . LANGUAGE_ID],
//                'title' => 'Что-то 2',
//            ],
//        ],
    ],
];

return $menu;
