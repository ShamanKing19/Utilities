<?php

namespace App\Tools;

use Bitrix\Iblock\SectionTable;

class Section
{
    /**
     * Получаем ID раздела инфоблока по символьному коду и инфоблоку
     *
     * @param string $code Символьный код раздела инфоблока
     * @param int $iBlockId ID инфоблока
     * @return int|bool
     * @throws \Bitrix\Main\ArgumentException
     * @throws \Bitrix\Main\ObjectPropertyException
     * @throws \Bitrix\Main\SystemException
     */
    public static function getIdByCode(string $code, int $iBlockId)
    {
        $section = SectionTable::getRow([
            'filter' => [
                'CODE' => $code,
                'IBLOCK_ID' => $iBlockId
            ],
            'select' => [
                'ID'
            ]
        ]);

        if($section) {
            return (int)$section['ID'];
        }

        return false;
    }
}
