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


    /**
     * Получение всех родительских разделов
     *
     * @param int $sectionId id раздела, подразделы которого нужно получить
     * @param int $iblockId id инфоблока, которому принадлежит раздел
     * @param array $select поля, которые нужно выбрать
     *
     * @return array
     */
    public static function getParentSections(int $sectionId, int $iblockId, array $select = []): array
    {
        $sectionsRequest = \CIBlockSection::getNavChain($iblockId, $sectionId, $select);
        $sections = [];
        while($section = $sectionsRequest->getNext()) {
            if((int)$section['ID'] === $sectionId) {
                continue;
            }

            $sections[] = $section;
        }

        return $sections;
    }


    /**
     * Получение всех подразделов
     *
     * @param int $sectionId id раздела, подразделы которого нужно получить
     * @param int $iblockId id инфоблока, которому принадлежит раздел
     * @param array $select поля, которые нужно выбрать
     *
     * @return array
     */
    public static function getChildSections(int $sectionId, int $iblockId, array $select = []): array
    {
        $section = \CIBlockSection::getByID($sectionId)->getNext();
        $sectionsRequest = \CIBlockSection::getList(['left_margin' => 'asc'], [
                'IBLOCK_ID' => $iblockId,
                '>LEFT_MARGIN' => $section['LEFT_MARGIN'],
                '<RIGHT_MARGIN' => $section['RIGHT_MARGIN'],
                '>DEPTH_LEVEL' => $section['DEPTH_LEVEL']
            ],
            false,
            $select
        );

        $sections = [];
        while($subSection = $sectionsRequest->getNext()) {
            $sections[] = $subSection;
        }

        return $sections;
    }
}
