<?php
/* Взаимодействие с каталогом */

// Получение цены товара с учётом скидок
$prices = CCatalogProduct::GetOptimalPrice($productId);

// Форматированная цена с учётом валюты
$formattedPrice = CCurrencyLang::CurrencyFormat($prices['DISCOUNT_PRICE'], $prices['PRICE']['CURRENCY']);


/* Взаимодействие со складами */

// Получение списка складов
CCatalogStore::GetList();

// Получение информации о наличии товаров на складах
$storeAmountRequest = CCatalogStoreProduct::GetList();