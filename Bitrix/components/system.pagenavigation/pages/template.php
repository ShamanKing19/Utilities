<?php

if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();

/** @var array $arParams */
/** @var array $arResult */
/** @global CMain $APPLICATION */
/** @global CUser $USER */
/** @global CDatabase $DB */
/** @var CBitrixComponentTemplate $this */
/** @var string $templateName */
/** @var string $templateFile */
/** @var string $templateFolder */
/** @var string $componentPath */
/** @var CBitrixComponent $component */

$this->setFrameMode(true);
ob_start();

if(!$arResult["NavShowAlways"]) {
    if ($arResult["NavRecordCount"] == 0 || ($arResult["NavPageCount"] == 1 && $arResult["NavShowAll"] == false)) {
        return;
    }
}

$strNavQueryString = ($arResult["NavQueryString"] !== "" ? $arResult["NavQueryString"]."&" : "");
$strNavQueryStringFull = ($arResult["NavQueryString"] !== "" ? "?".$arResult["NavQueryString"] : "");

$stringUrl = $arResult["sUrlPath"].'?'.$strNavQueryString;
$stringUrl = CHTTP::urlDeleteParams($stringUrl, ['bxajaxid', 'ajaxModeFilter'], ["delete_system_params" => true]);

$fullStringUrl = $arResult["sUrlPath"].'?'.$strNavQueryStringFull;
$fullStringUrl = CHTTP::urlDeleteParams($fullStringUrl, ['bxajaxid', 'ajaxModeFilter'], ["delete_system_params" => true]);

$arrowFullStringUrl = $arResult["sUrlPath"].$strNavQueryStringFull;
?>

<div class="pagination">
<?php if($arResult["bDescPageNumbering"] === false):?>
    <?php if ($arResult["NavPageNomer"] > 1):?>
        <?php if($arResult["bSavePage"]):?>
            <a href="<?=$stringUrl?>PAGEN_<?=$arResult["NavNum"]?>=<?=($arResult["NavPageNomer"]-1)?>" target="_self" class="pagination__btn-arrow left-arrow">
                <svg><use xlink:href="<?=MMB_FRONT_PATH;?>/img/sprite.svg#arrow-for-line"></use></svg>
            </a>
        <?php else:?>
            <?php if ($arResult["NavPageNomer"] > 2):?>
                <a href="<?=$stringUrl?>PAGEN_<?=$arResult["NavNum"]?>=<?=($arResult["NavPageNomer"]-1)?>" target="_self" class="pagination__btn-arrow left-arrow">
                    <svg><use xlink:href="<?=MMB_FRONT_PATH;?>/img/sprite.svg#arrow-for-line"></use></svg>
                </a>
            <?php else:?>
                <?php
                $firstUrl = $arrowFullStringUrl;
                $url = stripParamFromUrl($firstUrl, 'page');
                ?>
                <a href="<?=$url?>" target="_self" class="pagination__btn-arrow left-arrow">
                    <svg><use xlink:href="<?=MMB_FRONT_PATH;?>/img/sprite.svg#arrow-for-line"></use></svg>
                </a>
            <?php endif?>
        <?php endif?>
    <?php else:?>
        <span class="pagination__btn-arrow left-arrow inactive-btn">
            <svg><use xlink:href="<?=MMB_FRONT_PATH;?>/img/sprite.svg#arrow-for-line"></use></svg>
        </span>
    <?php endif?>

    <ul class="pagination__list">
    <?php while($arResult["nStartPage"] <= $arResult["nEndPage"]):?>
        <?php if ($arResult["nStartPage"] == $arResult["NavPageNomer"]):?>
            <li>
                <span class="_active"><?=$arResult["nStartPage"]?></span>
            </li>
        <?php elseif($arResult["nStartPage"] == 1 && $arResult["bSavePage"] == false):?>
            <?php
            $firstUrl = $arrowFullStringUrl;
            $url = stripParamFromUrl($firstUrl, 'page');
            ?>
            <li>
                <a class="_page" target="_self" href="<?=$url?>"><?=$arResult["nStartPage"]?></a>
            </li>
        <?php else:?>
            <li>
                <a class="_page" target="_self" href="<?=$stringUrl?>PAGEN_<?=$arResult["NavNum"]?>=<?=$arResult["nStartPage"]?>"><?=$arResult["nStartPage"]?></a>
            </li>
        <?php endif?>
        <?php $arResult["nStartPage"]++?>
    <?php endwhile?>
    </ul>

    <?php if($arResult["NavPageNomer"] < $arResult["NavPageCount"]):?>
        <a href="<?=$stringUrl?>PAGEN_<?=$arResult["NavNum"]?>=<?=($arResult["NavPageNomer"]+1)?>" class="pagination__btn-arrow" target="_self">
            <svg><use xlink:href="<?=MMB_FRONT_PATH;?>/img/sprite.svg#arrow-for-line"></use></svg>
        </a>
    <?php else:?>
        <span class="pagination__btn-arrow inactive-btn">
            <svg><use xlink:href="<?=MMB_FRONT_PATH;?>/img/sprite.svg#arrow-for-line"></use></svg>
        </span>
    <?php endif?>
<?php else:?>
    <?php if ($arResult["NavPageNomer"] < $arResult["NavPageCount"]):?>
        <?php if($arResult["bSavePage"]):?>
            <a href="<?=$stringUrl?>PAGEN_<?=$arResult["NavNum"]?>=<?=($arResult["NavPageNomer"]+1)?>" target="_self" class="pagination__btn-arrow left-arrow">
                <svg><use xlink:href="<?=MMB_FRONT_PATH;?>/img/sprite.svg#arrow-for-line"></use></svg>
            </a>
        <?php else:?>
            <?php if ($arResult["NavPageCount"] == ($arResult["NavPageNomer"]+1) ):?>
                <a href="<?=$fullStringUrl?>" target="_self" class="pagination__btn-arrow left-arrow">
                    <svg><use xlink:href="<?=MMB_FRONT_PATH;?>/img/sprite.svg#arrow-for-line"></use></svg>
                </a>
            <?php else:?>
                <a href="<?=$stringUrl?>PAGEN_<?=$arResult["NavNum"]?>=<?=($arResult["NavPageNomer"]+1)?>" target="_self" class="pagination__btn-arrow left-arrow">
                    <svg><use xlink:href="<?=MMB_FRONT_PATH;?>/img/sprite.svg#arrow-for-line"></use></svg>
                </a>
            <?php endif?>
        <?php endif?>
    <?php else:?>
        <span class="pagination__btn-arrow left-arrow inactive-btn">
            <svg><use xlink:href="<?=MMB_FRONT_PATH;?>/img/sprite.svg#arrow-for-line"></use></svg>
        </span>
    <?php endif?>

    <ul class="pagination__list">
    <?php while($arResult["nStartPage"] >= $arResult["nEndPage"]):?>
        <?php $NavRecordGroupPrint = $arResult["NavPageCount"] - $arResult["nStartPage"] + 1;?>
        <?php if ($arResult["nStartPage"] == $arResult["NavPageNomer"]):?>
            <li>
                <span class="_active"><?=$NavRecordGroupPrint?></span>
            </li>
        <?php elseif($arResult["nStartPage"] == $arResult["NavPageCount"] && $arResult["bSavePage"] == false):?>
            <li>
                <a class="_page" target="_self" href="<?=$fullStringUrl?>"><?=$NavRecordGroupPrint?></a>
            </li>
        <?php else:?>
            <li>
                <a class="_page" target="_self" href="<?=$stringUrl?>PAGEN_<?=$arResult["NavNum"]?>=<?=$arResult["nStartPage"]?>"><?=$NavRecordGroupPrint?></a>
            </li>
        <?php endif?>

        <?php $arResult["nStartPage"]--?>
    <?php endwhile?>
    </ul>

    <?php if ($arResult["NavPageNomer"] > 1):?>
        <a target="_self" href="<?=$stringUrl?>PAGEN_<?=$arResult["NavNum"]?>=<?=($arResult["NavPageNomer"]-1)?>" class="pagination__btn-arrow">
            <svg><use xlink:href="<?=MMB_FRONT_PATH;?>/img/sprite.svg#arrow-for-line"></use></svg>
        </a>
    <?php else:?>
        <span class="pagination__btn-arrow inactive-btn">
            <svg><use xlink:href="<?=MMB_FRONT_PATH;?>/img/sprite.svg#arrow-for-line"></use></svg>
        </span>
    <?php endif?>
<?php endif?>
</div>

<?php
$paging = ob_get_contents();
$paging = preg_replace_callback('/href="([^"]+)"/is', function($matches) {
    $url = $matches[1];
    if(str_contains($url, 'sprite.svg')) {
        return 'href="'.$url.'"';
    }
    
    $newUrl = '';
    if ($arUrl = parse_url($url)) {
        $newUrl .= $arUrl['path'];
        if (substr($newUrl, -1) != '/') {
            $newUrl .= '/';
        }

        $newUrl = preg_replace('#(page=[\d]+/)#is', '', $newUrl);
        parse_str(htmlspecialcharsback($arUrl['query']), $arQuery);
        foreach ($arQuery as $k => $v) {
            if (in_array($k, ['SECTION_CODE'])) {
                unset($arQuery[$k]);
            } elseif (substr($k, 0, 5) == 'PAGEN') {
                $arQuery['page'] = intval($v);
                unset($arQuery[$k]);
            }
        }
        
        $buildQuery = http_build_query(array_filter($arQuery));
        if ($buildQuery) {
            $newUrl .= '?' . $buildQuery;
        }
    }
    
    return 'href="'.$newUrl.'"';
}, $paging);

ob_end_clean();
echo $paging;