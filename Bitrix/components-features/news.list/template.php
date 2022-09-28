<?php
    // * Можно сделать редактируемым каждый элемент инфоблока прямо на странице
    // Перед контейнером элемента вставляем
    $this->AddEditAction($item["ID"], $item["EDIT_LINK"], CIBlock::GetArrayByID($item["IBLOCK_ID"], "ELEMENT_EDIT"));
    $this->AddDeleteAction($item["ID"], $item["DELETE_LINK"], CIBlock::GetArrayByID($item["IBLOCK_ID"], "ELEMENT_DELETE"), ["CONFIRM" => GetMessage('CT_BNL_ELEMENT_DELETE_CONFIRM')]);


    // * EDIT_LINK и DELETE_LINK выглядят следующим образом
    $editLink = "/bitrix/admin/iblock_element_edit.php?IBLOCK_ID=12&type=service&ID=43&lang=ru&force_catalog=&filter_section=0&bxpublic=Y&from_module=iblock&return_url=%2Fcontacts%2F%3Fbitrix_include_areas%3DY%26clear_cache%3DY%26_r%3D4714";
    $deleteLink = "/bitrix/admin/iblock_element_admin.php?IBLOCK_ID=12&type=service&lang=ru&action=delete&ID=43&return_url=%2Fcontacts%2F%3Fbitrix_include_areas%3DY%26clear_cache%3DY%26_r%3D4714";
    
    
?>

<!-- В контейнер элемента добавляем id -->
<div id="<?=$this->GetEditAreaId($item["ID"])?>">


<?php
    private function getEditLink($elementId, $elementIBlockId) : string
    {
       return  "/bitrix/admin/iblock_element_edit.php?IBLOCK_ID=$elementIBlockId&type=service&ID=$elementId&lang=ru&force_catalog=&filter_section=0&bxpublic=Y&from_module=iblock&return_url=%3Fbitrix_include_areas%3DY%26clear_cache%3DY%26_r%3D4714";
    }


    private function getDeleteLink($elementId, $elementIBlockId) : string
    {
        return "/bitrix/admin/iblock_element_admin.php?IBLOCK_ID=$elementIBlockId&type=service&lang=ru&action=delete&ID=$elementId&return_url=%3Fbitrix_include_areas%3DN%26clear_cache%3DY%26_r%3D4714";
    }
?>