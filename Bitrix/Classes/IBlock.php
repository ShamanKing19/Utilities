<?php
namespace App;

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
     * @param int|string $iblockId id или код инфоблока
     *
     * @return string
     * @throws \Bitrix\Main\ArgumentException
     * @throws \Bitrix\Main\ObjectPropertyException
     * @throws \Bitrix\Main\SystemException
     */
    public static function getIblockType($iblockId) : string
    {
        if (!is_numeric($iblockId)) {
            $iblockId = self::getIdByCode($iblockId);
        }

        $iblock = \CIBlock::GetList([], ['ID' => $iblockId])->fetch();
        $iblockType = $iblock['IBLOCK_TYPE_ID'];

        return $iblockType ?? '';
    }

    /**
     * Функция обновления UrlRewrite правила для каталога
     *
     * @throws \Bitrix\Main\ArgumentNullException
     * @throws \Bitrix\Main\ArgumentException
     * @throws \Bitrix\Main\ObjectPropertyException
     * @throws \Bitrix\Main\SystemException
     */
    public static function updateUrlRewriteCatalog(): bool
    {
        $catalogIBlockId = self::getIdByCode(MMB_CATALOG_IBLOCK_CODE);
        $siteId = \CIBlock::GetArrayByID($catalogIBlockId, 'LID');
        if (!strlen($siteId)) {
            return false;
        }

        $arCodes = ['catalog', 'search'];
        $rsSect = \CIBlockSection::GetList([], ['IBLOCK_ID' => $catalogIBlockId]);
        while($arSect = $rsSect->Fetch()) {
            $explode = explode('/', trim($arSect['CODE'], '/'));
            $arCodes[] = array_shift($explode);
        }

        $arCodes = array_unique($arCodes);

        $arResultList = \Bitrix\Main\UrlRewriter::getList($siteId,
            [
                'PATH' => '/catalog/index.php',
                'ID' => 'mmb:catalog',
            ]
        );

        if(count($arResultList)) {
            $arResultList = $arResultList[0];
            $condOld = $arResultList['CONDITION'];
            $arResultList['CONDITION'] = '#^/(' . implode('|', $arCodes) . ')/#';

            \Bitrix\Main\UrlRewriter::update($siteId, ['CONDITION' => $condOld], $arResultList);

            return true;
        }

        return false;
    }
}
