<!-- https://training.bitrix24.com/api_help/iblock/classes/user_properties/GetUserTypeDescription.php -->

<?php
namespace App\CustomProperties;

use Bitrix\Main\Localization\Loc,
    Bitrix\Main\Loader;

Loc::loadMessages(__FILE__);

class SomeCustomProperty {
    public static function getTypeDescription()
    {
        /**
         * E - привязка к элементам
         * S - строка
         * ...
         */

        return [
            'PROPERTY_TYPE' => 'S', // Используем строку, потому что выводим html
            'USER_TYPE' => 'SOME_PROPERTY', // Символьный код свойства
            'DESCRIPTION' => 'Меня ты увидишь в админке', // При выборе типа свойства будет отображаться это значение
            'GetPropertyFieldHtml' => [__CLASS__, 'GetPropertyFieldHtml'],
            'GetSettingsHTML' => [__CLASS__, 'GetSettingsHTML'],
        ];
    }

    static function GetPropertyFieldHtml($arProperty, $value, $strHTMLControlName) {
        $cache = [];
        $html = '';

        if (Loader::includeModule('sale'))
        {
            $cache['ITEMS'] = self::getItemList(); // Выборка необходимых элементов


            $varName = str_replace("VALUE", "DESCRIPTION", $strHTMLControlName["VALUE"]);
            $val = $value["VALUE"] ?: $arProperty["DEFAULT_VALUE"];
            $html = '<select name="' . $strHTMLControlName["VALUE"] . '" onchange="document.getElementById(\'DESCR_' . $varName . '\').value=this.options[this.selectedIndex].text">
			<option value="" >-</option>';
            foreach ($cache['ITEMS'] as $item)
            {
                $html .= '<option value="' . $item["ID"] . '"';
                if ($val == $item["~ID"])
                {
                    $html .= ' selected';
                }

                $html .= '>' . $item["NAME"] . '</option>'; // тут вместо ['NAME'] можно вывести что-нибудь другое
            }

            $html .= '</select>';
        }

        return $html;
    }

    static function GetSettingsHTML($arProperty, $strHTMLControlName, &$arPropertyFields){
        $arPropertyFields = [
            'HIDE' => [
                'SMART_FILTER',
                'SEARCHABLE',
                'COL_COUNT',
                'ROW_COUNT',
                'FILTER_HINT',
            ],
            'SET' => [
                'SMART_FILTER' => 'N',
                'SEARCHABLE' => 'N',
                'ROW_COUNT' => '10',
            ],
        ];

        return $html;
    }


    private static function getItemList()
    {
        $items = [];
        $promotionsResponse = \CSaleDiscount::GetList(["NAME" => "ASC"]);

        // Тут надо использовать GetNext() либо мб можно GetNextElement() (нужно значение ~ID)
        while ($item = $promotionsResponse->GetNext()) 
        {
            if ($item["NAME"])
            {
                $items[$item["ID"]] = $item;
            }
        }
    }
}
