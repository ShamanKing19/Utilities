<?php
namespace App\Search;

use App\Bitrix\ElementSinglePropertyValueTable;
use App\Tools\Log;
use Bitrix\Main\Entity\ExpressionField;
use Bitrix\Main\Entity\ReferenceField;
use Bitrix\Main\LoaderException;

class Main {
    /**
     * Ищет по тексту в инфоблоке.
     *
     * @param $query string - поисковый запрос. может содержать специфичные для Sphinx операторы ( * ) - будет передан прямо в Sphinx.
     *                     рекомендуется передавать запрос вида "*" . $searchQuery . "*"
     * @param mixed $iBlockId - инфоблок (default: 1)
     * @param mixed $sectionId int - ID раздела в каталоге, в рамках которого искать
     * @param int $limit - количество выводимого результата (default: 5)
     * @param string $module
     * @param array $aSort
     * @param bool $onlyProducts
     * @return array - список ID найденных элементов
     *
     * @see CSearchSphinx::Escape() - оператор или (|) экранируется в самых недрах :(
     * @see CSearchSphinx::search()
     */
    public static function fullTextSearchInIBlock(
        string $query,
        $iBlockId = false,
        $sectionId = false,
        int $limit = 4,
        string $module = 'iblock',
        array $aSort = [],
        bool $onlyProducts = true
    ): array
    {
        \Bitrix\Main\Loader::includeModule("search");
        $obSearch = new \CSearch;

        if(empty($aSort)) {
            $aSort = [
                'CUSTOM_RANK' => 'DESC',
                'RANK' => 'DESC'
            ];
        }

        $filter = [
            'QUERY' => $query,
            'SITE_ID' => "s1",
            'MODULE_ID' => $module,
        ];
        
        if($onlyProducts) {
            $filter['!ITEM_ID'] = 'S%';
        }

        if($iBlockId && $iBlockId > 0) {
            $filter['PARAM2'] = (int)$iBlockId;
        }

        if ($sectionId) {
            $filter['PARAMS'] = [
                'iblock_section' => $sectionId
            ];
        }

        $aParamsEx = [
            'STEMMING' => false,
        ];

        $obSearch->SetLimit($limit);
        $obSearch->Search($filter, $aSort, $aParamsEx);

        $itemsId = [];
        while ($row = $obSearch->fetch()) {
            $itemsId[] = $row;
        }

        return $itemsId;
    }

    /**
     * @param $word string фраза для поиска
     * @param int $rank ранк возможности совпадения
     * @param bool $switch
     *
     * @return array
     *
     * @throws LoaderException
     * @throws \Bitrix\Main\ArgumentException
     * @throws \Bitrix\Main\ObjectPropertyException
     * @throws \Bitrix\Main\SystemException
     */
    public static function searchSimilarSectionByWord(string $word, int $rank = 1, $switch = false): array
    {
        $idList = [];
        $findSections = self::fullTextSearchInIBlock($word, \App\Tools\IBlock::getIdByCode(MMB_CATALOG_IBLOCK_CODE), onlyProducts: false);
        foreach($findSections as $sec) {
            if((int)$sec['RANK'] >= $rank) {
                $idList[] = str_replace('S', '', $sec['ITEM_ID']);
            }
        }

        if(!count($idList) && !$switch) {
            return self::searchSimilarSectionByWord(self::switcherToRus($word), $rank, true);
        }

        return $idList;
    }

    public static function switcherToRus($value): string
    {
        $converter = [
            'f' => 'а',	',' => 'б',	'd' => 'в',	'u' => 'г',	'l' => 'д',	't' => 'е',	'`' => 'ё',
            ';' => 'ж',	'p' => 'з',	'b' => 'и',	'q' => 'й',	'r' => 'к',	'k' => 'л',	'v' => 'м',
            'y' => 'н',	'j' => 'о',	'g' => 'п',	'h' => 'р',	'c' => 'с',	'n' => 'т',	'e' => 'у',
            'a' => 'ф',	'[' => 'х',	'w' => 'ц',	'x' => 'ч',	'i' => 'ш',	'o' => 'щ',	'm' => 'ь',
            's' => 'ы',	']' => 'ъ',	"'" => "э",	'.' => 'ю',	'z' => 'я',

            'F' => 'А',	'<' => 'Б',	'D' => 'В',	'U' => 'Г',	'L' => 'Д',	'T' => 'Е',	'~' => 'Ё',
            ':' => 'Ж',	'P' => 'З',	'B' => 'И',	'Q' => 'Й',	'R' => 'К',	'K' => 'Л',	'V' => 'М',
            'Y' => 'Н',	'J' => 'О',	'G' => 'П',	'H' => 'Р',	'C' => 'С',	'N' => 'Т',	'E' => 'У',
            'A' => 'Ф',	'{' => 'Х',	'W' => 'Ц',	'X' => 'Ч',	'I' => 'Ш',	'O' => 'Щ',	'M' => 'Ь',
            'S' => 'Ы',	'}' => 'Ъ',	'"' => 'Э',	'>' => 'Ю',	'Z' => 'Я',

            '@' => '"',	'#' => '№',	'$' => ';',	'^' => ':',	'&' => '?',	'/' => '.',	'?' => ',',
        ];

        return strtr($value, $converter);
    }

    /**
     * @param int $limit лимит количества товаров в выводе
     * @param bool $includeSections
     * @param bool $withoutProps
     * @return array
     *
     * @throws \Bitrix\Main\ArgumentException
     * @throws \Bitrix\Main\ObjectException
     * @throws \Bitrix\Main\ObjectPropertyException
     * @throws \Bitrix\Main\SystemException
     */
    public static function mostOrderedProducts(
        int $limit = 4,
        bool $includeSections = false,
        bool $withoutProps = false
    ): array
    {
        global $DB;

        $dateFormat = $DB->DateFormatToPHP(\CLang::GetDateFormat("SHORT"));
        $dateInterval = date($dateFormat, strtotime("-6 months"));

        $res = \Bitrix\Sale\Internals\BasketTable::getList([
            'order' => [
                'PRODUCT_COUNT' => 'DESC'
            ],
            'filter' => [
                'PRODUCT.IBLOCK.IBLOCK_ID' => \App\Tools\IBlock::getIdByCode(MMB_CATALOG_IBLOCK_CODE),
                '!ORDER.STATUS_ID' => 'Z',
                '!ORDER.CANCELED' => 'Y',
                '>=ORDER.DATE_INSERT' => new \Bitrix\Main\Type\DateTime($dateInterval),
                'PRODUCT.IBLOCK.ACTIVE' => 'Y',
                'PROPERTY.DEFECTED' => false,
                'PROPERTY.ARCHIVE' => false,
                'PROPERTY.FOR_MANAGERS_ONLY' => false,
                //'!PROPERTY.HIDE_FROM_CATALOG' => 1,
            ],
            'select' => [
                'PRODUCT_ID',
                'PRODUCT_COUNT',
                'PRODUCT.NAME',
                'PRODUCT.IBLOCK.IBLOCK_SECTION_ID',
            ],
            'group' => [
                'PRODUCT_ID'
            ],
            'limit' => $limit,
            'runtime' => [
                new ExpressionField(
                    'PRODUCT_COUNT',
                    'ROUND(SUM(%s))',
                    'QUANTITY'
                ),
                new ReferenceField(
                    'PROPERTY',
                    ElementSinglePropertyValueTable::class,
                    ['this.PRODUCT_ID' => 'ref.IBLOCK_ELEMENT_ID']
                ),
            ],
        ]);

        $result = [];

        while($element = $res->fetch()) {
            $result[] = \App\Catalog\Product::getById($element['PRODUCT_ID']);
        }

        $sections = [];
        if($includeSections) {
            foreach($result as $product) {
                $sections[] = $product['IBLOCK_SECTION_ID'];
            }

            $sections = array_unique($sections);
            $limitSectionsList = array_slice($sections, 0, $limit);

            if($limitSectionsList) {
                $sections = self::searchRealSectionByIdList($limitSectionsList);
            }
        }

        return $includeSections ? [
            'PRODUCTS' => $result,
            'SECTIONS' => $sections
        ] : $result;
    }

    public static function searchRealSectionByIdList(array $idList): array
    {
        if(!count($idList)) {
            return [];
        }

        $arFilter = [
            'IBLOCK_ID' => \App\Tools\IBlock::getIdByCode(MMB_CATALOG_IBLOCK_CODE),
            'GLOBAL_ACTIVE' => 'Y',
            'IBLOCK_ACTIVE' => 'Y',
            'ID' => $idList
        ];

        $dbList = \CIBlockSection::GetList([], $arFilter, false, [
            'ID',
        ]);


        $menuIdList = [];
        while($item = $dbList->GetNext()) {
            $menuIdList[] = $item['ID'];
        }

        $menuIdList = array_unique($menuIdList);

        return self::searchSectionByIdList($menuIdList);
    }

    public static function getBestPhraseByWord(string $word = '', $filter = [], $limit = 4): array
    {
        $result = [];
        $query = [
            'limit' => $limit,
            'order' => ['COUNT' => 'DESC'],
        ];

        if($word) {
            $query['filter']['WORD'] = '%'.$word.'%';
        }

        if($filter) {
            $query['filter'] = $filter;
        }

        if($limit) {
            $query['limit'] = $limit;
        }

        $res = \App\Search\Table::getList($query);

        while($item = $res->fetch()) {
            $result[] = $item;
        }

        return $result;
    }

    /**
     * @param array $idList
     * @return array
     */
    public static function searchSectionByIdList(array $idList): array
    {
        if(!count($idList)) {
            return [];
        }

        $arFilter = [
            'IBLOCK_ID' => \App\Tools\IBlock::getIdByCode(MMB_CATALOG_IBLOCK_CODE),
            'GLOBAL_ACTIVE' => 'Y',
            'IBLOCK_ACTIVE' => 'Y',
            'UF_SHOW_IN_MENU' => 1,
            '<=DEPTH_LEVEL' => 3,
            'ID' => $idList
        ];

        $sefTemplate = [
            'SEF_BASE_URL' => '/',
            'SECTION_PAGE_URL' => '#CODE#/'
        ];

        $dbList = \CIBlockSection::GetList([], $arFilter, false, [
            'ID',
            'NAME',
            'CODE',
            'LEFT_MARGIN',
            'RIGHT_MARGIN',
            'IBLOCK_ID',
            'DEPTH_LEVEL'
        ]);

        $dbList->SetUrlTemplates("", $sefTemplate["SEF_BASE_URL"] . $sefTemplate["SECTION_PAGE_URL"]);

        $key = 0;
        $result = [];
        while($item = $dbList->GetNext()) {
            $result[$key] = $item;
            $result[$key]['PARENT'] = (int)$item['DEPTH_LEVEL'] > 1 ? 
                self::getParentBySectionId($item['ID'], (int)$item['DEPTH_LEVEL']) : [];

            $key++;
        }

        return $result;
    }

    public static function getParentBySectionId($id, int $depth) {
        $tt = \CIBlockSection::GetList([], ['ID' => $id], false, [
            'ID',
            'NAME',
            'DEPTH_LEVEL',
            'IBLOCK_SECTION_ID'
        ]);

        $as = $tt->GetNext();
        static $a;
        if((int)$as['DEPTH_LEVEL'] <= ($depth - 1)) {
            $a = $as;
        } else {
            self::getParentBySectionId($as['IBLOCK_SECTION_ID'], $as['DEPTH_LEVEL']);
        }

        return $a;
    }
}