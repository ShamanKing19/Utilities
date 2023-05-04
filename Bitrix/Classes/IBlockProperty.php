<?php
namespace App;

class IBlockProperty
{
    /**
     * Получаем свойство по символьному коду
     *
     * @param int $iBlockId ID инфоблока
     * @param string $propertyCode Символьный код свойства
     * @param bool $idKey Ключи = ID вместо XML_ID
     * @return array|false|mixed
     * @throws \Bitrix\Main\ArgumentException
     * @throws \Bitrix\Main\LoaderException
     * @throws \Bitrix\Main\ObjectPropertyException
     * @throws \Bitrix\Main\SystemException
     */
    public static function getPropertyIdByCode(int $iBlockId, string $propertyCode, bool $idKey = false) {
        $listValueList = [];
        \Bitrix\Main\Loader::includeModule('iblock');

        $property = \Bitrix\Iblock\PropertyTable::getRow([
            'filter' => ['IBLOCK_ID' => $iBlockId, 'CODE' => $propertyCode],
            'select' => ['ID', 'PROPERTY_TYPE']
        ]);

        if (!$property) {
            return false;
        }

        $enum = $property['PROPERTY_TYPE'] === \Bitrix\Iblock\PropertyTable::TYPE_LIST;

        if($enum) {
            $listPropertyValueQuery = \Bitrix\Iblock\PropertyEnumerationTable::getList([
                'filter' => ['PROPERTY_ID' => $property['ID']],
                'select' => ['ID', 'VALUE', 'XML_ID', 'SORT'],
                'order' => ['SORT' => 'ASC']
            ]);

            while($listPropertyValue = $listPropertyValueQuery->fetch()) {
                $key = $idKey ? $listPropertyValue['ID'] : $listPropertyValue['XML_ID'];
                $listValueList[$key] = $listPropertyValue;
            }

            return $listValueList;
        }

        return $property['ID'];
    }


    /**
     * Получение названия свойства
     *
     * @param int $iblockId id инфоблока
     * @param string $code символьный код свойства
     * @return string
     */
    public static function getName(int $iblockId, string $code) : string
    {
        $property = \App\Tools\IBlockProperty::getPropertyInfo($iblockId, $code, ['ID', 'NAME']);
        return $property['NAME'] ?: '';
    }


    /**
     * Проверка: является ли свойство списком
     *
     * @param int $iblockId id инфоблока
     * @param string $code символьный код свойства
     * @return bool
     */
    public static function isList(int $iblockId, string $code) : bool
    {
        $property = \App\Tools\IBlockProperty::getPropertyInfo($iblockId, $code, ['ID', 'PROPERTY_TYPE']);
        return $property['PROPERTY_TYPE'] === \Bitrix\Iblock\PropertyTable::TYPE_LIST;
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
    public static function getPropertyInfo(int $iblockId, string $code, array $select = ['*']): array {
        return \Bitrix\Iblock\PropertyTable::getRow([
            'filter' => [
                'IBLOCK_ID' => $iblockId,
                'CODE' => $code
            ],
            'select' => $select
        ]) ?? [];
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


    /**
     * Получение значения поля VALUE для значения свойства типа "Список" по его id
     *
     * @param int $id id элемента типа "Список"
     * @return string
     */
    public static function getPropertyEnumName(int $id) : string
    {
        $item = \Bitrix\Iblock\PropertyEnumerationTable::getList([
            'filter' => ['ID' => $id],
            'select' => ['ID', 'VALUE']
        ])->fetch();
        return $item['VALUE'] ?: '';
    }
}
