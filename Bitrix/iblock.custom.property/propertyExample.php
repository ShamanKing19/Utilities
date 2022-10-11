<!-- https://training.bitrix24.com/api_help/iblock/classes/user_properties/GetUserTypeDescription.php -->

<?php
namespace App\CustomProperties;

use Bitrix\Main\Localization\Loc,
    Bitrix\Main\Loader;

Loc::loadMessages(__FILE__);

class PromotionRule {
    public static function getTypeDescription()
    {
        /**
         * E - привязка к элементам
         * S -строка
         * ...
         */

        return [
            'PROPERTY_TYPE' => 'S', // Используем строку, потому что выводим html
            'USER_TYPE' => 'TYPE_NAME',
            'DESCRIPTION' => 'TYPE_DESCRIPTION', // При выборе типа свойства будет отображаться это значение
            'GetPropertyFieldHtml' => [__CLASS__, 'GetPropertyFieldHtml'],
            'GetSettingsHTML' => [__CLASS__, 'GetSettingsHTML'],
        ];
    }

    static function GetPropertyFieldHtml($arProperty, $value, $strHTMLControlName) {
        $cache = [];
        $html = '';

        if (Loader::includeModule('sale'))
        {
            $cache["PROMOTIONS"] = [];
            $promotionsResponse = \CSaleDiscount::GetList(["NAME" => "ASC"]); // Выборка необходимых элементов

            while ($promotionElement = $promotionsResponse->GetNext())
            {
                if ($promotionElement["NAME"])
                {
                    $cache["PROMOTIONS"][$promotionElement["ID"]] = $promotionElement;
                }
            }

            $varName = str_replace("VALUE", "DESCRIPTION", $strHTMLControlName["VALUE"]);
            $val = $value["VALUE"] ?: $arProperty["DEFAULT_VALUE"];
            $html = '<select name="' . $strHTMLControlName["VALUE"] . '" onchange="document.getElementById(\'DESCR_' . $varName . '\').value=this.options[this.selectedIndex].text">
			<option value="" >-</option>';
            foreach ($cache["PROMOTIONS"] as $promotion)
            {
                $html .= '<option value="' . $promotion["ID"] . '"';
                if ($val == $promotion["~ID"])
                {
                    $html .= ' selected';
                }

                $html .= '>' . $promotion["NAME"] . '</option>';
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
}
