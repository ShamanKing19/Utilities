<?php
namespace App\Models;

class Section extends Model
{
    public static string $table = \Bitrix\Iblock\SectionTable::class;
    protected static bool $useCache = true;
    protected static string $moduleName = 'iblock';
    protected static string $addEvent = 'OnAfterIBlockSectionAdd';
    protected static string $updateEvent = 'OnAfterIBlockSectionUpdate';
    protected static string $deleteEvent = 'OnAfterIBlockSectionDelete';

    /** @var array Массив с информацией об инфоблоках */
    private static array $iblockList = [];

    /** @var array Массив с родительскими разделами */
    private static array $parentSectionList = [];


    protected function __construct(int $id, array $fields)
    {
        parent::__construct($id, $fields);
        $this->fields['SECTION_PAGE_URL'] = $this->getLink();
    }

    /**
     * Получение id инфоблока
     *
     * @return int
     */
    public function getIblockId() : int
    {
        return $this->getField('IBLOCK_ID');
    }

    /**
     * Получение SEO информации
     *
     * @return array
     */
    public function getSeoInfo() : array
    {
        $iblockId = $this->fields['IBLOCK_ID'];
        $seoInfo = new \Bitrix\Iblock\InheritedProperty\SectionValues($iblockId, $this->getId());
        return $seoInfo->getValues() ?? [];
    }

    /**
     * Получение ссылки на раздел
     *
     * @return string
     */
    public function getLink() : string
    {
        $iblock = $this->getIblock();
        $template = $iblock['SECTION_PAGE_URL'];
        $patterns = [
            '#SITE_DIR#' => SITE_DIR,
            '#SERVER_NAME#' => SITE_SERVER_NAME,
            '#IBLOCK_TYPE_ID#' => $iblock['IBLOCK_TYPE_ID'],
            '#IBLOCK_ID#' => $iblock['ID'],
            '#IBLOCK_CODE#' => $iblock['CODE'],
            '#IBLOCK_EXTERNAL_ID#' => $iblock['XML_ID'],
            '#ID#' => $this->getId(),
            '#SECTION_ID#' => $this->getId(),
            '#CODE#' => $this->getField('CODE'),
            '#SECTION_CODE#' => $this->getField('CODE'),
            '#EXTERNAL_ID#' => $this->getField('XML_ID')
        ];

        // Для улучшения производительности
        $sectionCodePathPosition = mb_strpos($template, '#SECTION_CODE_PATH#');
        if($sectionCodePathPosition !== false) {
            $parentSections = $this->getParentSections();
            $sectionCodeList = array_column(array_map(static fn($section) => $section->toArray(), $parentSections), 'CODE');
            $path = implode('/', $sectionCodeList) . '/' . $this->getField('CODE');
            $path = $sectionCodePathPosition === 0 ? '/' . $path : $path;
            $template = str_replace('#SECTION_CODE_PATH#', $path, $template);
        }

        foreach($patterns as $pattern => $value) {
            $template = str_replace($pattern, $value, $template);
        }

        return str_replace('//', '/', $template);
    }

    /**
     * Получение id товаров раздела
     *
     * @param bool $onlyFromCurrentSection Найти товары только из текущего раздела
     *
     * @return array
     */
    public function getProductIdList(bool $onlyFromCurrentSection = false) : array
    {
        $sectionIdList = [$this->getId()];
        if(!$onlyFromCurrentSection) {
            $childSections = array_map(static fn($item) => $item->toArray(), $this->getChildSections());
            $sectionIdList = array_merge($sectionIdList, array_column($childSections, 'ID'));
        }

        $items = \Bitrix\Iblock\SectionElementTable::getList([
            'filter' => ['IBLOCK_SECTION_ID' => $sectionIdList]
        ])->fetchAll();

        return array_unique(array_column($items, 'IBLOCK_ELEMENT_ID') ?? []) ?? [];
    }

    /**
     * Получение подразделов
     *
     * @param bool $onlyFromCurrentSection Найти разделы только первого уровня
     * @param array $order Сортировка
     *
     * @return array<self>
     */
    public function getChildSections(bool $onlyFromCurrentSection = false, array $order = ['LEFT_MARGIN' => 'ASC']) : array
    {
        $filter = [
            'IBLOCK_ID' => $this->getIblockId(),
            '>LEFT_MARGIN' => $this->getField('LEFT_MARGIN'),
            '<RIGHT_MARGIN' => $this->getField('RIGHT_MARGIN'),
            '>DEPTH_LEVEL' => $this->getField('DEPTH_LEVEL')
        ];

        if($onlyFromCurrentSection) {
            unset($filter['>DEPTH_LEVEL']);
            $filter['DEPTH_LEVEL'] = $this->getField('DEPTH_LEVEL') + 1;
        }

        return self::getList($filter, $order);
    }

    /**
     * Получение всех родительских разделов
     *
     * @return array<self>
     */
    public function getParentSections() : array
    {
        $parentSectionId = $this->getField('IBLOCK_SECTION_ID');
        if(empty($parentSectionId)) {
            return [];
        }

        if(isset(self::$parentSectionList[$parentSectionId])) {
            return self::$parentSectionList[$parentSectionId];
        }

        $filter = [
            'IBLOCK_ID' => $this->getIblockId(),
            '<LEFT_MARGIN' => $this->getField('LEFT_MARGIN'),
            '>RIGHT_MARGIN' => $this->getField('RIGHT_MARGIN'),
            '<DEPTH_LEVEL' => $this->getField('DEPTH_LEVEL')
        ];
        $sectionList = self::getList($filter);
        if($sectionList) {
            self::$parentSectionList[$parentSectionId] = $sectionList;
        }

        return $sectionList;
    }

    /**
     * Получение информации об инфоблоке данного раздела
     *
     * @return array
     */
    public function getIblock() : array
    {
        $iblockId = $this->getIblockId();
        if(isset(self::$iblockList[$iblockId])) {
            return self::$iblockList[$iblockId];
        }

        $iblock = \Bitrix\Iblock\IblockTable::getList([
            'filter' => ['ID' => $this->getIblockId()]
        ])->fetch();
        return self::$iblockList[$iblockId] = $iblock;
    }

    /**
     * Получение названия таблицы, в которой лежат значения пользовательских полей UF_*
     *
     * @return string
     */
    protected static function getUfTableName() : string
    {
        if(isset(static::$ufTable)) {
            return static::$ufTable;
        }

        $catalogIblockId = \App\Tools\IBlock::getIdByCode(UP_CATALOG_IBLOCK_CODE);
        return static::$ufTable = 'b_uts_iblock_' . $catalogIblockId . '_section';
    }
}
