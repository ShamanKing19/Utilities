<?php
    // * Можно сделать редактируемым каждый элемент инфоблока прямо на странице
    // Перед контейнером элемента вставляем
    $this->AddEditAction($item["ID"], $item["EDIT_LINK"], CIBlock::GetArrayByID($item["IBLOCK_ID"], "ELEMENT_EDIT"));
    $this->AddDeleteAction($item["ID"], $item["DELETE_LINK"], CIBlock::GetArrayByID($item["IBLOCK_ID"], "ELEMENT_DELETE"), ["CONFIRM" => GetMessage('CT_BNL_ELEMENT_DELETE_CONFIRM')]);
?>

<!-- В контейнер элемента добавляем id -->
<div id="<?=$this->GetEditAreaId($item["ID"])?>">
