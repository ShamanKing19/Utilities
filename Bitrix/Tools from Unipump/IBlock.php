<?php

namespace App\Tools;

class IBlock
{
    /**
     * Получаем ID инфоблока по символьному коду
     *
     * @param string $code Символьный код инфоблока
     * @return int|bool
     * @throws \Bitrix\Main\ArgumentException
     * @throws \Bitrix\Main\ObjectPropertyException
     * @throws \Bitrix\Main\SystemException
     * @throws \Exception
     */
    public static function getIdByCode(string $code)
    {
        if (!\Bitrix\Main\Loader::includeModule('iblock')) {
            return false;
        }

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
     * @param int|string $iblockId id инфоблока
     *
     * @return string
     * @throws \Bitrix\Main\ArgumentException
     * @throws \Bitrix\Main\ObjectPropertyException
     * @throws \Bitrix\Main\SystemException
     */
    public static function getIblockType($iblockId) : string
    {
        $iblock = \CIBlock::GetList([], ['ID' => $iblockId])->fetch();
        $iblockType = $iblock['IBLOCK_TYPE_ID'];
        return $iblockType ?? '';
    }
}
