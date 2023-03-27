<!-- 
    https://training.bitrix24.com/api_help/iblock/classes/user_properties/GetUserTypeDescription.php 

    Значения лежат в таблицах b_iblock_element_prop_s1 и b_iblock_element_prop_m1 (одиночные и множественные свойства соответственно, а 1 это id сайта)
    Свойство будет называться PROP_{{id свойства}}
-->

<?php
namespace App\CustomProperties;


class SomeCustomProperty 
{
    public static function getTypeDescription()
    {
        return [
            'PROPERTY_TYPE' => 'S', // Используем строку, потому что сохраняем сериализованный массив
            'USER_TYPE' => 'SOME_PROPERTY', // Символьный код свойства
            'DESCRIPTION' => 'Меня ты увидишь в админке', // При выборе типа свойства будет отображаться это значение
            'GetPropertyFieldHtml' => [__CLASS__, 'GetPropertyFieldHtml'],
            'GetPropertyFieldHtmlMulty' => [__CLASS__, 'GetPropertyFieldHtmlMulty'],
            'GetSettingsHTML' => [__CLASS__, 'GetSettingsHTML'], // Позволяет добавить кастомные настройки для свойства
            'PrepareSettings' => [__CLASS__, 'PrepareSettings'], // Нужна чтобы сохранить настройки, добавленные в GetSettingsHtml
            'ConvertToDB' => [__CLASS__, 'ConvertToDB'], // ! Нужно только когда сохраняем массив
            'ConvertFromDB' => [__CLASS__, 'ConvertFromDB'], // ! Нужно только когда сохраняем массив 
        ];
    }


    /**
     * @param array $arProperty Массив с информацией о свойстве
     * @param string $value Массив ['VALUE' => 'string', 'DESCRIPTION' => 'string']. В $value['VALUE'] значения из формы, обработанные в ConvertFromDB
     * @param $htmlInputName Массив с какой-то шляпой, но с нужным параметром ['VALUE'] для инпутов
     *
     * @return false|string
     */
    static function GetPropertyFieldHtml($arProperty, $value, $htmlInputName) {
        $value = $value['VALUE'];
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
            1). Если в форме один input или select (тоже не множественные), то name="<?=$htmlInputName?>" прокатит,
            2). Если в форме один select с атрибутом multiple, то нужно name="<?=$htmlInputName?>[]" 
            3). Если есть несколько инпутов, то нужно делать name="<?=$htmlInputName?>[KEY1]", name="<?=$htmlInputName?>[KEY2]" и т. д.
        -->
        <select name="<?=$htmlInputName?>" id="">
            <option value="">-</option>
            <?php foreach ($items as $item): ?>
                <option value="<?=$item['ID']?>"<?php if($item['ID'] === $chosenPropertyValue1):?> selected<?php endif?>><?=$item['NAME']?></option>
            <?php endforeach ?>
        </select>
        
        <?php
        return ob_get_clean();
    }


    /**
     * @param array $arProperty Массив с информацией о свойстве
     * @param array $values Массив c элементами ['VALUE' => string, 'DESCRIPTION' => string]. В $value['VALUE'] значения из формы, обработанные в ConvertFromDB/> 
     * @param $htmlInputName Массив с какой-то шляпой, но с нужным параметром ['VALUE'] для инпутов
     *
     * @return false|string
     */
    static function GetPropertyFieldHtmlMulty($arProperty, $values, $htmlInputName) {
        $value = $value['VALUE'];
        $chosenPropertyValue1 = $value['FIRST_PROP_NAME'];
        $chosenPropertyValue2 = $value['SECOND_PROP_NAME'];
        $chosenPropertyValue3 = $value['THIRD_PROP_NAME'];

        $items = self::getItemList(); // Выборка необходимых элементов
        $number = 0;
        ob_start()
        ?>

        <!--
            1). Если один input, то name="<?=$htmlInputName?>[][]" // ? Это точно хз, лучше перепроверить
            2). Если input'ов несколько, то точно сработает name="<?=$htmlInputName?>[<?=$number?>][SOME_KEY]"
        -->

        <!-- Если один инпут, то надо попробовать ещё вот так -->
        <select name="<?=$htmlInputName?>[<?=$number?>]"></select> 
            <option value="">-</option>
            <?php foreach ($items as $item): ?>
                <?php
                $value = $value['VALUE'];
                $chosenOptionId = $value['FIRST_PROP_NAME'];
                ?>
                
                <option value="<?=$item['ID']?>"<?php if($item['ID'] === $chosenOptionId):?> selected<?php endif?>><?=$item['NAME']?></option>
                <?php $number++ ?>
            <?php endforeach ?>

        <?php
        return ob_get_clean();
    }


    /**
     * Для сохранения нескольких значений нужно сохранять массив, а в базе хранится строка, поэтому нужно сериализовать
     * 
     * ! Вызывается для каждого элемента индивидуально, то есть прилетает всегда ['VALUE' => 'string']
     */
    public static function ConvertToDB($arProperty, $value)
    {
        // * Тут нужно проверять на пустое значения какого-нибудь поля, чтобы каждый раз не добавлять пустое значение
        $value['VALUE'] = serialize($value['VALUE']);
        return $value;
    }


    /**
     * Достаём сериализованное значение из таблицы
     * 
     * ! Вызывается для каждого элемента индивидуально, то есть прилетает всегда ['VALUE' => 'string']
     */
    public static function ConvertFromDB($arProperty, $value, $format = '')
    {
        $value['VALUE'] = unserialize($value['VALUE']);
        return $value;
    }


    /**
     * Добавляем кастомные настройки
     */
    static function GetSettingsHTML($arProperty, $strHTMLControlName, &$arPropertyFields){
        // Это просто настройки того что показывать, что прятать и какие стантартные значения ставить
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

        // Кастомные свойства
        $customProperties = $arProperty['USER_TYPE_SETTINGS'];
        $savedValue = $customProperties['TEST'];

        ob_start();
        ?>

        <tr>
            <td>Кастомная настройка:</td>
            <td><input type="text" name="<?=$strHTMLControlName['NAME']?>[TEST]" value="<?=$savedValue?>"></td>
        </tr>

        <?php
        return ob_get_clean();
    }


    /**
     * Нужно для сохранения кастомных настроек
     */
    static function PrepareSettings($arProperty)
    {
        // Массив со значениями дополнительных свойств, из GetSettingsHtml
        $properties = $arProperty['USER_TYPE_SETTINGS'];
        $testProperty = $properties['TEST'];

        /**
         * Эти поля появятся в методах GetPropertyFieldHtml и GetPropertyFieldHtmlMulty
         * в первом параметре ($arProperty)
         * с ключём USER_TYPE_SETTINGS
         */
        return [
            'TEST' => $testProperty
        ];
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
