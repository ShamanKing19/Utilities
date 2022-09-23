<?php // * Компонент для оформления подписки

// * Отключение подтверждения

// 1). Находим эту строчку
$ID = $obSubscription->Add($arFields);

// 2). Перед ней добавляем эти строки
$arFields["CONFIRMED"] = "Y";
$arFields["SEND_CONFIRM"] = "N";

?>

<!-- В форму добавляем -->
<?=bitrix_sessid_post()?>
<input type="hidden" name="RUB_ID[]" id="RUB_ID_<?=$arResult["RUBRIC_ID"]?>" value="<?=$arResult["RUBRICK_ID"]?>" checked/>
<input type="hidden" name="FORMAT" id="MAIL_TYPE_HTML" value="html" checked/>
<input type="hidden" name="PostAction" value="Add"/>
<input type="hidden" name="ID" value="<?=$arResult["SUBSCRIPTION"]["ID"]?>"/>

<!-- Поле ввода почты -->
<input type="email" name="EMAIL" value="<?=$arResult["EMAIL"]?>">

<!-- Кнопка отправки формы -->
<input type="submit" name="Save"/>
