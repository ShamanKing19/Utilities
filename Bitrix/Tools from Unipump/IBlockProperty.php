<?php
namespace App\Tools;

class IBlockProperty
{
    /**
     * Получаем свойство по символьному коду
     * 
     * @param int $iBlockId ID инфоблока
     * @param string $propertyCode Символьный код свойства
     * @return int|array
     */
    public static function getPropertyIdByCode(int $iBlockId, string $propertyCode) {
        $listValueList = [];
        \Bitrix\Main\Loader::includeModule('iblock');

        $property = \Bitrix\Iblock\PropertyTable::getRow([
            'filter' => ['IBLOCK_ID' => $iBlockId, 'CODE' => $propertyCode],
            'select' => ['ID', 'PROPERTY_TYPE']
        ]);

        if (!$property) {
            return 0;
        }

        $enum = $property['PROPERTY_TYPE'] === \Bitrix\Iblock\PropertyTable::TYPE_LIST;

        if (!$enum) {
            return $property['ID'];
        }

        $listPropertyValueQuery = \Bitrix\Iblock\PropertyEnumerationTable::getList([
            'filter' => ['PROPERTY_ID' => $property['ID']],
            'select' => ['ID', 'VALUE', 'XML_ID']
        ]);

        while ($listPropertyValue = $listPropertyValueQuery->fetch()) {
            $listValueList[$listPropertyValue['XML_ID']] = $listPropertyValue;
        }
        
        return $listValueList;
    }

    /**
     * Получение информации о свойстве
     *
     * @param int $iblockId id инфоблока
     * @param string $code символьный код свойства
     *
     * @return array
     * @throws \Bitrix\Main\ArgumentException
     * @throws \Bitrix\Main\ObjectPropertyException
     * @throws \Bitrix\Main\SystemException
     */
    public static function getPropertyInfo(int $iblockId, string $code): array {
        return \Bitrix\Iblock\PropertyTable::getRow([
            'filter' => [
                'IBLOCK_ID' => $iblockId,
                'CODE' => $code
            ]
        ]);
    }


    /**
     * Очищает значение свойства (нужно для множественного свойства типа "Файл")
     *
     * @param int $elementId id элемента
     * @param string $propertyCode символьный код свойства, которое нужно очистить
     */
    public static function clearPropertyValue(int $elementId, string $propertyCode): void {
        $item = \CIBlockElement::getList(
            arFilter: ['ID' => $elementId],
            arSelectFields: ['ID', 'IBLOCK_ID']
        )->getNextElement();

        $itemProperties = $item->getProperties();
        $property = $itemProperties[$propertyCode];

        $deleteFileList = [];
        foreach($property['VALUE'] as $key => $imageId) {
            $deleteFileList[$property['PROPERTY_VALUE_ID'][$key]] = ['VALUE' => ['del' => 'Y']];
        }

        \CIBlockElement::SetPropertyValueCode($elementId, $propertyCode, $deleteFileList);
    }
}
