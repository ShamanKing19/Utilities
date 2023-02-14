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
            'ConvertToDB' => [__CLASS__, 'ConvertToDB'], // ! Нужно только когда сохраняем массив с ключами и значениями
            'ConvertFromDB' => [__CLASS__, 'ConvertFromDB'], // ! Нужно только когда сохраняем массив с ключами и значениями 
        ];
    }


    /**
     * @param array $arProperty Массив с информацией о свойстве
     * @param string $value Значения из формы (если его сериализовать, то может прилететь строка). Пример: <input name="<?=htmlInputName?>[PROP_NAME]" value="$id"/> 
     * @param $htmlInputName Массив с какой-то шляпой, но с нужным параметром ['VALUE'] для инпутов
     *
     * @return false|string
     */
    static function GetPropertyFieldHtml($arProperty, $value, $htmlInputName) {
        $value = $value['VALUE'];

        if (!Loader::includeModule('sale')) {
            return '';
        }

        $items = self::getItemList(); // Выборка необходимых элементов

        // ! Если поле не множественное, то так прокатит
        $value = $value['VALUE'] ?: $arProperty['DEFAULT_VALUE'];
        
        // * Если несколько полей, то значения достаём так
        $chosenPropertyValue1 = $value['FIRST_PROP_NAME'];
        $chosenPropertyValue2 = $value['SECOND_PROP_NAME'];
        $chosenPropertyValue3 = $value['THIRD_PROP_NAME'];

        ob_start()
        ?>

        <!--
            Тут при сохранении странная схема:
            1). Если свойство не множественное и в форме один input или select (тоже не множественные), то name="<?=$htmlInputName?>" прокатит,
            1). Если свойство не множественное и в форме один select с атрибутом multiple, то нужно name="<?=$htmlInputName?>[]" 
            2). Если свойство множественное, но с одним инпутом, то name="<?=$htmlInputName?>[][]" // ? Это точно хз, лучше перепроверить
            3). Если свойство множественное с несколькими инпутами, то точно сработает name="<?=$htmlInputName?>[<?=$number?>][SOME_KEY]"
        -->
        <select name="<?=$htmlInputName?>" id=""></select>
            <option value="">-</option>
            <?php foreach ($items as $item): ?>
                <option value="<?=$item['ID']?>"<?php if($item['ID'] === $chosenPropertyValue1):?> selected<?php endif?>><?=$item['NAME']?></option>
            <?php endforeach ?>

        <?php
        $html = ob_get_contents();
        ob_end_clean();

        return $html;
    }


    /**
     * Для сохранения нескольких значений нужно сохранять массив, а в базе хранится строка, поэтому нужно сериализовать
     * 
     * ! Если свойство множественное, то в $value прилетает массив, если нет, то строка
     */
    public static function ConvertToDB($arProperty, $value)
    {
        // * Тут нужны проверки на пустые значения, чтобы каждый раз не добавлять пустое значение
        $value['VALUE'] = serialize($value['VALUE']);
        return $value;
    }


    /**
     * Достаём сериализованное значение из таблицы
     * 
     * ! Если свойство множественное, то в $value прилетает массив, если нет, то строка 
     */
    public static function ConvertFromDB($arProperty, $value, $format = '')
    {
        $value['VALUE'] = unserialize($value['VALUE']);
        return $value;
    }

    static function GetSettingsHTML($arProperty, $htmlInputName, &$arPropertyFields){
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


    private static function getItemList() : array
    {
        $items = [];
        $promotionsResponse = \CSaleDiscount::getList(['NAME' => 'ASC']);

        while ($item = $promotionsResponse->fetch()) {
            if ($item['NAME']) {
                $items[$item['ID']] = $item;
            }
        }

        return $items;
    }
}
