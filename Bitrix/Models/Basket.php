<?php

namespace App\Models;

class Cart extends Model
{
    public static string $table = \Bitrix\Sale\Internals\BasketTable::class;

    /**
     * Поиск товара в корзине с учётом соглашения
     *
     * @param int $productId ID товара
     *
     * @return self|null
     */
    public static function findItem(int $productId) : ?self
    {
        return self::findBy('PRODUCT_ID', $productId);
    }

    /**
     * Очистка корзины
     *
     * @return void
     */
    public static function clear() : void
    {
        $items = self::getList();
        foreach($items as $item) {
            $item->delete();
        }
    }

    protected static function getCustomFilter() : array
    {
        return [
            'FUSER_ID' => \Bitrix\Sale\Fuser::getId()
        ];
    }

    protected static function getJoins() : array
    {
        return [
            new \Bitrix\Main\ORM\Fields\Relations\Reference(
                'PROPERTIES',
                \Bitrix\Sale\Internals\BasketPropertyTable::class,
                \Bitrix\Main\ORM\Query\Join::on('this.ID', 'ref.BASKET_ID'),
                ['join_type' => 'left']
            )
        ];
    }
}
