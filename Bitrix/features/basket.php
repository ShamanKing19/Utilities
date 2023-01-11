<?php

/**
 * Получение товаров из корзины с их свойствами
 */
public function getCartItems() : array
    {
        \Bitrix\Main\Loader::includeModule("sale");
        $registry = Sale\Registry::getInstance(Sale\Registry::REGISTRY_TYPE_ORDER);

        /** @var Sale\Basket $basketClass */
        $basketClass = $registry->getBasketClassName();

        $basketItemsResult = $basketClass::getList([
            'filter' => [
                'FUSER_ID' => Sale\Fuser::getId(),
                '=LID' => SITE_ID,
                'ORDER_ID' => null,
            ],
            'order' => [
                'SORT' => 'ASC',
                'ID' => 'ASC',
            ],
        ]);

        $items = [];
        while ($item = $basketItemsResult->fetch()) {
            $items[$item['ID']] = $item;
        }

        $propertyResult = Sale\BasketPropertiesCollection::getList(
            [
                'filter' => [
                    '=BASKET_ID' => array_keys($items)
                ]
            ]
        );
        while ($property = $propertyResult->fetch())
        {
            $items[$property['BASKET_ID']]['PROPERTIES'][] = $property;
        }


        return $items;
    }