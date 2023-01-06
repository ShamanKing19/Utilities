<?php

/**
 * Возвращает цену товара с учётом типа цены в выбранном соглашении и скидками
 *
 * @param $productId
 * @return int
 */
function getPriceByCurrentPriceType($productId) {
    $currentPriceTypeId = 1; // Это ID типа цены, его нужно доставать самостоятельно
    $priceRequest = \Bitrix\Catalog\PriceTable::getList([
        'select' => ['ID', 'CATALOG_GROUP_ID', 'PRICE', 'CURRENCY', 'PRICE_SCALE'],
        'filter' => [
            '=PRODUCT_ID' => $productId,
            '@CATALOG_GROUP_ID' => $currentPriceTypeId
        ]
    ]);

    $price = $priceRequest->fetch();

    if (empty($price)) return 0;

    $res = \CCatalogProduct::GetOptimalPrice($productId, 1, $USER->GetUserGroupArray(), 'N', [[
        'ID' => $price['ID'],
        'PRICE' => $price['PRICE'],
        'CURRENCY' => $price['CURRENCY'],
        'CATALOG_GROUP_ID' => $price['CATALOG_GROUP_ID']
    ]]);

    return $res['DISCOUNT_PRICE'];
}