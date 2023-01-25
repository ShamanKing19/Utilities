<?php
namespace App\Tools;

class IBlockProperty
{
    /**
     * Получаем свойство по символьному коду
     * 
     * @param int $iBlockId ID инфоблока
     * @param string $propertyCode Символьный код свойства
     * @param bool $enum Свойство типа список
     * @return array|false|mixed
     * @throws \Bitrix\Main\ArgumentException
     * @throws \Bitrix\Main\LoaderException
     * @throws \Bitrix\Main\ObjectPropertyException
     * @throws \Bitrix\Main\SystemException
     */
    public static function getPropertyIdByCode(int $iBlockId, string $propertyCode, bool $enum = false) {
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
}
