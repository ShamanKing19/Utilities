<?php
namespace App;

use JetBrains\PhpStorm\ArrayShape;

class Cart
{
    /**
     * Проверка: есть ли товар в корзине
     *
     * @param int $productId
     * @return bool
     */
    public static function isInCart(int $productId) : bool
    {
        $cartItems = self::getCartItems();

        foreach($cartItems as $item) {
            if((int)$item['PRODUCT_ID'] === $productId) {
                return true;
            }
        }

        return false;
    }


    /**
     * Получение количества товаров в корзине
     *
     * @return int
     * @throws \Bitrix\Main\ArgumentException
     * @throws \Bitrix\Main\SystemException
     */
    public static function getCartItemsCount() : int
    {
        $basket = self::getBasket();
        $basketItemsResult = $basket::getList([
            'filter' => [
                'FUSER_ID' => \Bitrix\Sale\Fuser::getId(),
                'LID' => SITE_ID,
                'ORDER_ID' => null,
            ],
            'select' => ['ID']
        ])->fetchAll();
        return count($basketItemsResult ?? []);
    }


    /**
     * Получение элемента корзины по BASKET_ID
     *
     * @param int $basketId id элемента корзины
     * @return array
     *
     * @throws \Bitrix\Main\ArgumentException
     * @throws \Bitrix\Main\SystemException
     */
    public static function getCartItemByBasketId(int $basketId) : array
    {
        $cartItems = self::getCartItems();
        $filteredItems = array_filter($cartItems, fn($item) => (int)$item['ID'] === $basketId);
        $item = current($filteredItems) ?: [];
        if(empty($item)) {
            return [];
        }

        if((int)\Bitrix\Sale\Fuser::getId() === (int)$item['FUSER_ID']) {
            return $item;
        }

        return [];
    }


    /**
     * Получение элемента корзины по id товара
     *
     * @param int $productId id товара
     * @return array
     *
     * @throws \Bitrix\Main\ArgumentException
     * @throws \Bitrix\Main\SystemException
     */
    public static function getCartItem(int $productId) : array
    {
        $cartItems = self::getCartItems();
        $filteredItems = array_filter($cartItems, fn($item) => (int)$item['PRODUCT_ID'] === $productId);
        return current($filteredItems) ?: [];
    }


    /**
     * Получение товаров из корзины текущего пользователя
     *
     * @return array
     * @throws \Bitrix\Main\ArgumentException
     * @throws \Bitrix\Main\SystemException
     */
    public static function getCartItems() : array
    {
        $basket = self::getBasket();
        $basketItemsResult = $basket::getList([
            'filter' => [
                'FUSER_ID' => \Bitrix\Sale\Fuser::getId(),
                '=LID' => SITE_ID,
                'ORDER_ID' => null,
            ],
            'order' => [
                'SORT' => 'ASC',
                'ID' => 'ASC',
            ],
        ]);

        $items = [];
        while($item = $basketItemsResult->fetch()) {
            $items[$item['ID']] = $item;
        }

        $propertyResult = \Bitrix\Sale\BasketPropertiesCollection::getList([
            'filter' => ['=BASKET_ID' => array_keys($items)]
        ]);

        while($property = $propertyResult->fetch()) {
            $items[$property['BASKET_ID']]['PROPERTIES'][] = $property;
        }

        return $items;
    }


    /**
     * Получение класса корзины
     *
     * @return mixed
     * @throws \Bitrix\Main\ArgumentException
     * @throws \Bitrix\Main\SystemException
     */
    private static function getBasket()
    {
        $registry = \Bitrix\Sale\Registry::getInstance(\Bitrix\Sale\Registry::REGISTRY_TYPE_ORDER);
        return $registry->getBasketClassName();
    }
}