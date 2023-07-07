<?php
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();
?>

<ul class="<?=$arResult['PAGINATION']['SHOW_MORE']['WRAPPER_CLASS']?>">
    <?php foreach($arResult['ITEMS'] as $item): ?>
        <li class="<?=$arResult['PAGINATION']['SHOW_MORE']['ITEM_CLASS']?>"><a href="<?=$item['DETAIL_PAGE_URL']?>"><?=$item['NAME']?></a></li>
    <?php endforeach; ?>
</ul>
<button class="<?=$arResult['PAGINATION']['SHOW_MORE']['BUTTON_CLASS']?>" <?=$arResult['PAGINATION']['SHOW_MORE']['DATA_ATTRIBUTE']?>>Показать ещё</button>

<br>
<br>
<br>

<?php foreach($arResult['PAGINATION']['ITEMS'] as $page): ?>
    <a href="<?=$page['URL']?>"><?=$page['NUMBER']?></a>
<?php endforeach; ?>
