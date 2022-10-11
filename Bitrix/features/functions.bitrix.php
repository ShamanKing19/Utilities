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
    $actions = CIBlock::GetPanelButtons($elementIBlockId, $elementId);

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
    $iBlock = IblockTable::getRow([
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
function getPropertyIdByCode(int $iBlockId, string $propertyCode, bool $enum = false) {
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
