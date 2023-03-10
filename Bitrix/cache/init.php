<?php

AddEventHandler('iblock', 'OnAfterIBlockElementUpdate', 'OnAfterIBlockElementUpdateHandler');


function OnAfterIBlockElementUpdateHandler(&$arFields) 
{
    $iblockId = (int)$arFields['IBLOCK_ID'];

    // Обработка только нужного инфоблока
    if($iblockId === (int)\App\Tools\IBlock::getIdByCode(MMB_CATALOG_IBLOCK_CODE)) {
        $cache = new \CPHPCache();
        // Нужны те же параметры, передаваемые в StartResultCache()
        $cache->Clean('some_cache_id', 'some_cache_path');
    }
}