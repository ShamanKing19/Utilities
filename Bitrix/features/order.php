<?php
// ! Работа с заказами

function getOrders($isActiveOnly = false, $filter = [], $sort = []) : array
{
    global $USER;
    $filter['USER_ID'] = $USER->GetID();

     if($isActiveOnly) {
        $filter['!STATUS_ID'] = 'F';
    }

    $request = CSaleOrder::GetList($sort, $filter);
    $orders = [];
    while($res = $request->GetNext()) {
        $orders[] = $res;
    }

    return $orders;
}


function getShipmentTypes() : array
{
    return \Bitrix\Sale\Delivery\Services\Manager::getActiveList();
}


function getStatuses() : array
{
    $request = \Bitrix\Sale\Internals\StatusLangTable::getList([
        'filter' => [
            'LID' => getCurrentLanguage()
        ]
    ]);

    $statuses = [];
    while($status = $request->Fetch()) {
        $statuses[$status['STATUS_ID']] = $status;
    }

    return $statuses;
}



function getPaymentTypes() : array
{
    $request = \Bitrix\Sale\PaySystem\Manager::getList([
        'filter'  => [
            'ACTIVE' => 'Y',
        ]
    ]);

    while($res = $request->Fetch()) {
        $payments[$res['ID']] = $res;
    }

    return $payments;

}


function getPayments($orderIds) : array
{

    $payments = [];
    $request = \Bitrix\Sale\Payment::getList([
        'filter' => [
            'ORDER_ID' => $orderIds,
        ],
    ]);

    while($res = $request->Fetch()) {
        $payments[$res['ID']] = $res;
    }

    return $payments;
}


/**
 * Получение информации о свойстве корзины по символьному коду
 */
function getOrderPropertyInfo(string $code) : array
{
    $request = \Bitrix\Sale\Internals\OrderPropsTable::getList([
        'filter' => ['CODE' => $code]
    ]);

    return $request->fetch() ?: []; 
}



/**
 * Создание заказа
 * 
 * @param int    $personTypeId  Id типа покупателя (1 - физ. лицо, 2 - юр. лицо)
 * @param string $name          Имя покупателя
 * @param string $phone         Номер телфона
 * @param string $email         Почта покупателя
 * @param string $comment       Комментарий к заказу
 * @param array $products       Массив с id товара и его количеством [$id => $quantity] 
 * @return array                Результат добавления
 */
function createOrder(
    int $personTypeId,
    string $name,
    string $phone,
    string $email,
    string $comment,
    array $products,
    int $storeId,

) {
    $basket = \Bitrix\Sale\Basket::create(SITE_ID);

    $productList = $this->getProductsInfo($products);

    foreach($products as $productId => $quantity)
    {
        $productFields = [
            'PRODUCT_ID' => $productId,
            'QUANTITY' => $quantity,
            'NAME' => $productList[$productId]['NAME'],
            'PRICE' => $productList[$productId]['PRICE']['DISCOUNT_PRICE'],
            'PRODUCT_PROVIDER_CLASS' => '\Bitrix\Catalog\Product\CatalogProvider',
        ];

        $item = $basket->createItem("catalog", $productId);
        $item->setFields($productFields);
    }

    global $USER;
    $order = Bitrix\Sale\Order::create(SITE_ID, $USER->GetID());
    $order->setBasket($basket);
    $order->setPersonTypeId($personTypeId);
    $order->setField('USER_DESCRIPTION', $comment);

    $propertyCollection = $order->getPropertyCollection();
    $arProperties = $propertyCollection->getArray();


    // ! Названия кастомный свойств заказаы 
    $propertyValues = [
        'F_STORE' => $storeId,
        'F_EMAIL' => $email,
        'F_PHONE' => $phone,
        'F_FIO' => $name
    ];

    foreach($arProperties['properties'] as $property) {
        $prop = $propertyCollection->getItemByOrderPropertyId($property['ID']);
        $propCode = $prop->getField('CODE');
        $propValue = $propertyValues[$propCode];
        $prop->setValue($propValue);
    }

    return $$order->save();
}


/**
 * @param $products [$id => $quantity]
 * @return array
 */
function getProductsInfo($products)
{
    $productIds = array_keys($products);
    $request = \CIBlockElement::GetList([], ['ID' => $productIds], false, false, [
        'ID', 'IBLOCK_ID', 'NAME'
    ]);
    $products = [];
    while($res = $request->Fetch()) {
        $res['PRICE'] = \CCatalogProduct::GetOptimalPrice($res['ID']);
        $products[$res['ID']] = $res;
    }
    return $products;
}