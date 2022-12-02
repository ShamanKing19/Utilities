<?php
namespace App\CustomProperties;

use CModule;

class AddressOrderAttach extends \Bitrix\Sale\Internals\Input\Base
{
    public static function getViewHtmlSingle(array $input, $value)
    {
        $itemList = self::getItemList();

        if($itemId = (int)$value) {
            $currentItem = $itemList[$itemId];

            return '['.$currentItem['ID'].'] ' . $currentItem['NAME'];
        }

        return '';
    }

    public static function getEditHtmlSingle($name, array $input, $value)
    {
        $itemList = self::getItemList();
        $options = '<option value="">-</option>';

        foreach($itemList as $item) {
            $selected = (int)current($value) === (int)$item['ID'] ? ' selected' : '';
            $options .= '<option value="'.$item['ID'].'"'.$selected.'>'.htmlspecialcharsbx($item['NAME']).'</option>';        }

        $multiple = $input['MULTIPLE'] == 'Y' ? ' multiple' : '';

        return '<select name="'.$name.'"'.$multiple.'>'.$options.'</select>';
    }

    public static function getFilterEditHtml($name, array $input, $value)
    {
        return static::getEditHtmlSingle($name, $input, $value);
    }

    public static function getEditHtml($name, array $input, $value = null)
    {
        return static::getEditHtmlSingle($name, $input, $value);
    }

    public static function getErrorSingle(array $input, $value)
    {
        return [];
    }

    static function getSettings(array $input, $reload)
    {
        return [];
    }

    public static function getItemList(): array
    {
        CModule::IncludeModule('iblock');

        $dbResult = \CIBlockElement::GetList(
            ['SORT' => 'ASC',],
            ['IBLOCK_CODE' => UP_DELIVERY_ADDRESSES_IBLOCK_CODE],
        );

        $result = [];

        while($item = $dbResult->fetch()) {
            $result[$item['ID']] = $item;
        }

        return $result;
    }
}