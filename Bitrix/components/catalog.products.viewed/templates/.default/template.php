<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();

/**
 * @global CMain $APPLICATION
 * @var array $arParams
 * @var array $arResult
 * @var CatalogSectionComponent $component
 * @var CBitrixComponentTemplate $this
 */

$elementEdit = CIBlock::GetArrayByID($arParams['IBLOCK_ID'], 'ELEMENT_EDIT');
$elementDelete = CIBlock::GetArrayByID($arParams['IBLOCK_ID'], 'ELEMENT_DELETE');


/**
 * ! Подключение компонента
 */
$APPLICATION->IncludeComponent(
    'mmb:catalog.products.viewed',
    '.default',
    [
        'IBLOCK_ID' => 1,
        'IBLOCK_TYPE' => 'content',
        'CACHE_TYPE' => 'A',
        'CACHE_TIME' => 3600,
        'CACHE_GROUPS' => 'Y',
        'PAGE_ELEMENT_COUNT' => '8',
        'PRICE_CODE' => ['BASE'],
    ]
);
?>

<?php if($arResult['ITEMS']):?>
    <?php foreach($arResult['ITEMS'] as $item): ?>
        <?php
        $uniqueId = $item['ID'] . '_' . md5($this->randString() . $component->getAction());
        $areaIds[$item['ID']] = $this->GetEditAreaId($uniqueId);
        $this->AddEditAction($uniqueId, $item['EDIT_LINK'], $elementEdit);
        $this->AddDeleteAction($uniqueId, $item['DELETE_LINK'], $elementDelete);

        $APPLICATION->IncludeComponent(
            'mmb:catalog.item',
            '.default',
            [
                'RESULT' => [
                    'ITEM' => $item,
                    'AREA_ID' => $areaIds[$item['ID']],
                ],
            ],
            $component
        );
        ?>
    <?php endforeach;?>
<?php endif;?>