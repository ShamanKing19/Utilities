<?php // ! Полезные кастомные функции для Bitrix
/**
 * Получение ссылкок для редактирования и удаления элемента инфоблока
 * 
 * @param $elementId
 * @param $elementIBlockId
 * @return array - Возвращает ссылки EDIT_LINK и DELETE_LINK
 */
function getActionLinks($elementId, $elementIBlockId) : array
{
    $actions = \CIBlock::GetPanelButtons($elementIBlockId, $elementId);

    $links = [
        "EDIT_LINK" => $actions["edit"]["edit_element"]["ACTION_URL"],
        "DELETE_LINK" => $actions["edit"]["delete_element"]["ACTION_URL"],
    ];

    return $links;
}


/**
 * Получение ID инфоблока по его символьному коду
 * 
 * @param $code - Символьный код инфоблока
 */
function getIblockIdByCode(string $code)
{
    $iBlock = \Bitrix\Iblock\IblockTable::getRow([
        'filter' => [
            'CODE' => $code
        ],
        'select' => [
            'ID'
        ]
    ]);

    if ($iBlock) {
        return $iBlock['ID'];
    }

    return false;
}


/**
 * Получаем свойство по символьному коду
 * 
 * @param int $iBlockId ID инфоблока
 * @param string $propertyCode Символьный код свойства
 * @param bool $enum Свойство типа список
*/
function getPropertyIdByCode(int $iBlockId, string $propertyCode, bool $enum = false) 
{
    $listValueList = [];
    \Bitrix\Main\Loader::includeModule('iblock');

    $property = \Bitrix\Iblock\PropertyTable::getRow([
        'filter' => [
            'IBLOCK_ID' => $iBlockId,
            'CODE' => $propertyCode
        ],
        'select' => [
            'ID'
        ]
    ]);

    if (!$property) {
        return false;
    }

    if($enum) {
        $listPropertyValueQuery = \Bitrix\Iblock\PropertyEnumerationTable::getList([
            'filter' => [
                'PROPERTY_ID' => $property['ID']
            ],
            'select' => [
                'ID',
                'VALUE',
                'XML_ID'
            ]
        ]);

        while ($listPropertyValue = $listPropertyValueQuery->fetch()) {
            $listValueList[$listPropertyValue['XML_ID']] = $listPropertyValue;
        }
        
        return $listValueList;
    }

    return $property['ID'];
}



/**
 * Создаёт элемент в инфоблоке (Не забыть об обязательном символьном коде!)
 * 
 * @param array $fields         Массив со значениями полей
 * @param array $properties     Массив со значениями свойств (['PROPERTY_CODE' => 'VALUE']) 
 * @return int                  Id созданного элемента
 */
function createIBlockElement(array $fields, array $properties) : int
{
    $elem = new CIBlockElement();


    $fields = [
        "ACTIVE" => $fields['ACTIVE'],
        "IBLOCK_ID" => $fields['IBLOCK_ID'],
        "NAME" => $fields['NAME'],
        "PROPERTY_VALUES" => $properties
    ];

    return $elem->Add($fields);
}


/**
 * Запускает событие по отправке почтового шаблона с переданными полями
 * (Настройки продукта -> Почтовые и СМС события)
 *
 * @param string $eventName название почтового события (Типы событий)
 * @param array $fields массив со значениями, которые можно использовать в почтовом шаблоне, привязанному к почтовому событию
 * @param string $siteId название сайта (используется 's1' вместо SITE_ID, т. к. при использовании в админке SITE_ID = 'ru )
 * return $result
 */
function sendEmail(string $eventName, array $fields, string $siteId = 's1')
{
    return Bitrix\Main\Mail\Event::send([
        'EVENT_NAME' => $eventName,
        'LID' => $siteId,
        'C_FIELDS' => $fields
    ]);
}


/**
 * Символьный код выбранного языка на сайте (в системе Битрикс)
 *
 * @param bool $toUpper
 * @return string
 */
function getCurrentLanguage(bool $toUpper = false): string
{
    $lang = \Bitrix\Main\Application::getInstance()->getContext()->getLanguage();

    if($toUpper) {
        $lang = strtoupper($lang);
    }

    return $lang;
}

/**
 * Получаем ссылку на logout
 *
 * @return mixed|string
 */
function getLogoutUrl() {
    return \CHTTP::urlAddParams('/', ["logout" => 'yes', 'sessid' => bitrix_sessid()]);
}

