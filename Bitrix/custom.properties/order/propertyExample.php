<?php
namespace App\CustomProperties;

use CModule;

class PropertyExampleClassName extends \Bitrix\Sale\Internals\Input\Base
{
    public static function getViewHtmlSingle(array $input, $value)
    {
        $itemList = self::getItemList();
        
        if($itemId = (int)$value) {
            $currentItem = $itemList[$itemId];
            
            return '['.$currentItem['ID'].'] ' . $currentItem['NAME']; // ? Может быть что-то кроме 'NAME'
        }
        
        return '';
    }
    
    public static function getEditHtmlSingle($name, array $input, $value)
    {
        $itemList = self::getItemList();
        $options = '<option value="">-</option>';

        foreach($itemList as $item) {
            $options .= '<option value="'.$item['ID'].'">'.htmlspecialcharsbx($item['NAME']).'</option>'; // ? Может быть что-то кроме 'NAME'
        }
        
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
    
    // ! Вот этот метод везде свой будет
    public static function getItemList(): array
    {
        CModule::IncludeModule('iblock');
        
        $dbResult = \CIBlockElement::GetList(
            ['SORT' => 'ASC',],
            ['IBLOCK_CODE' => UP_CONTRACTS_IBLOCK_CODE],
            false,
            false,
            ['*', 'UF_*']
        );
        
        $result = [];
        
        while($item = $dbResult->fetch()) {
            $result[$item['ID']] = $item;
        }
        
        return $result;
    }
}