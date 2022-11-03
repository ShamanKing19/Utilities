<?php
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();
$this->setFrameMode(false);
?>

<!-- Простейшая форма для отправки -->
<form name="search-form" action="<?=POST_FORM_ACTION_URI?>" method="post">
    <input type="text" id="search-input" name="q" value="<?=$arResult["REQUEST"]["QUERY"]?>" size="40" />
    <input type="hidden" name="how" value="<?=$arResult["REQUEST"]["HOW"]=="d"? "d": "r"?>" />
    <input type="submit" class="hidden" value="Найти" id="submit-form"/>
</form>


<script>
    submitForm();
    focusOnSearch();
</script>
