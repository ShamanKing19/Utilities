<?php
namespace App\Models;

use App\Tools\Log;

\Bitrix\Main\Loader::includeModule('iblock');

/**
 * <h1>Модель инфоблока</h2>
 *
 * <h2>Для начала работы нужно</h3>
 * <ol>
 *     <li>Унаследоваться от данного класса</li>
 *     <li>Переопределить "<b>protected static string $iblockCode;</b>"</li>
 *     <li>Если нужно инфоблок является торговым каталогом, то устанавливаем "<b>protected static bool $isCatalog = true;</b>"</li>
 *     <li>Если нужно использовать кэш, то установить  "<b>protected static bool $useCache = true;</b>"</li>
 *     <li>Вызвать "<b>static::registerCacheEvents();</b>" в init.php, если включён кэш</li>
 *     <li>Можно добавить коллбэки, которые будут вызваны при очистке кэша методом <b>static::addClearCacheCallback();</b></li>
 * </ol>
 *
 * <h2>Пагинация</h2>
 * <h3>I. Постраничная</h3>
 * <ol>
 *     <li>Вызываем метод и натягиваем на вёрстку<p><b>static::getPagination();</b> </p></li>
 *     <li>Вызываем метод для получения элементов<p><b>static::getListByPage();</b></p></li>
 * </ol>
 *
 * <h3>II. "Показать ещё"</h3>
 * <ol>
 *     <li>Подключаем <b>show_more.js</b></li>
 *     <li>Натягиваем на вёрстку классы:
 *         <p><b>static::$showMoreWrapperClass;</b></p>
 *         <p><b>static::$showMoreItemClass;</b></p>
 *         <p><b>static::$showMoreButtonClass;</b></p>
 *     </li>
 *     <li>На кнопку добавляем:
 *         <p><b>data-static::$pageVariable="static::getNextPage()";</b></p>
 *     </li>
 *     <li>Вызываем метод уже после вёрстки:
 *         <p><b>static::initShowMoreButton()</b></p>
 *     </li>
 * </ol>
 */
abstract class IblockModel implements \ArrayAccess
{
    /**
     * Настраиваемые поля
     */

    /** @var string Символьный код инфоблока */
    protected static string $iblockCode;

    /** @var bool Является ли инфоблок торговым каталогом */
    protected static bool $isCatalog = false;

    /**
     * Служебные поля
     */

    /** @var int ID инфоблока */
    protected static int $iblockId = 0;

    /** @var array Информация об инфоблоке */
    protected static array $iblock = [];

    /** @var array Массив с уже созданными объектами */
    public static array $instanceList = [];

    /** @var array Массив с символьными кодами и id инфоблоков */
    public static array $iblockCodeIdMap = [];

    /**
     * Кэширование
     */

    /** @var bool Использовать ли кэш */
    protected static bool $useCache = false;

    /** @var int Время хранения кэша */
    protected static int $cacheTime = 86400;

    /** @var array Коллбэки, вызываемые при очистке кэша */
    protected static array $clearCacheCallbackList = [];

    /**
     * Пагинация
     */

    /** @var string Переменная, которая будет искаться в запросе для определения текущей страницы */
    public static string $pageVariable = 'page';

    /** @var int Количество страниц, отображаемых перед и после текущей */
    protected static int $pageRange = 4;

    /** @var int номер страницы */
    protected static int $page = 1;

    /** @var int Количество элементов на странице */
    protected static int $itemsPerPage = 10;

    /** @var string Класс для кнопки "Показать ещё" */
    public static string $showMoreButtonClass = 'js-show-more--button';

    /** @var string Класс для контейнера с элементами */
    public static string $showMoreWrapperClass = 'js-show-more--wrapper';

    /** @var string Класс для элемента */
    public static string $showMoreItemClass = 'js-show-more--item';

    /**
     * Поля экземпляров класса
     */

    /** @var int ID элемента */
    protected int $id;

    /** @var array Поля */
    protected array $fields;

    /** @var array Свойства */
    protected array $props;


    protected function __construct(int $id, array $fields, array $props = [])
    {
        $this->id = $id;
        $this->fields = $fields;
        $this->props = $props;
    }

    /**
     * Получение id
     *
     * @return int
     */
    final public function getId() : int
    {
        return $this->id;
    }

    /**
     * Получение массива полей
     *
     * @return array
     */
    final public function toArray() : array
    {
        $fields = $this->fields;
        $fields['PROPERTIES'] = $this->props;
        return $fields;
    }

    /**
     * Получение массива свойств
     *
     * @return array
     */
    final public function getProps() : array
    {
        return $this->props;
    }

    /**
     * Получение элемента по id
     *
     * @param int $id ID элемента инфоблока
     *
     * @return static|false
     */
    final public static function find(int $id)
    {
        if(static::$instanceList[$id]) {
            return static::$instanceList[$id];
        }

        $item = current(static::getList(['ID' => $id]));
        if(empty($item)) {
            return false;
        }

        return $item;
    }

    /**
     * Получение элементов инфоблока постранично
     *
     * @param array $filter Фильтр для \CIBlockElement::getList()
     * @param array $order Сортировка ['KEY_1' => 'ASC', 'KEY_2' => 'DESC']
     *
     * @return array<static>
     */
    final public static function getListByPage(array $filter = [], array $order = []) : array
    {
        return static::getList($filter, $order, static::$itemsPerPage, (static::getCurrentPage() - 1) * static::$itemsPerPage);
    }

    /**
     * Получение элементов инфоблока и кеширование выборки
     *
     * @param array $filter Фильтр для \CIBlockElement::getList()
     * @param array $order Сортировка ['KEY_1' => 'ASC', 'KEY_2' => 'DESC']
     * @param int $limit Ограничение выборки
     * @param int $offset Сдвиг
     *
     * @return array<static>
     */
    public static function getList(array $filter = [], array $order = [], int $limit = 0, int $offset = 0) : array
    {
        if(!static::$useCache) {
            static::getListRaw($filter, $order, $limit, $offset);
        }

        $cache = \Bitrix\Main\Data\Cache::createInstance();
        $cacheKey = static::getCacheKey($filter, $order, $limit, $offset);
        $cachePath = static::getListCachePath();

        // Для выборки по одному элементу свой путь для кэша, чтобы при добавлении/обновлении/удалении не очищать его
        if(count($filter) === 1 && isset($filter['ID']) && is_numeric($filter['ID'])) {
            $cachePath = static::getCachePath();
        }

        if($cache->initCache(static::$cacheTime, $cacheKey, $cachePath)) {
            $items = $cache->getVars();
            $cache->abortDataCache();
            return static::makeInstanceList($items);
        }

        $items = static::getListRaw($filter, $order, $limit, $offset);
        if(empty($items)) {
            return [];
        }

        $cache->startDataCache();
        $cache->endDataCache(array_map(static fn($item) => $item->toArray(), $items));

        return $items;
    }

    /**
     * Получение элементов инфоблока
     *
     * @param array $filter Фильтр для \CIBlockElement::getList()
     * @param array $order Сортировка ['KEY_1' => 'ASC', 'KEY_2' => 'DESC']
     * @param int $limit Ограничение выборки
     * @param int $offset Сдвиг
     *
     * @return array<static>
     */
    protected static function getListRaw(array $filter = [], array $order = ['ID' => 'ASC'], int $limit = 0, int $offset = 0) : array
    {
        $filter['IBLOCK_ID'] = static::getIblockId();
        $navStartParams = [];
        if($limit > 0) {
            $navStartParams['nTopCount'] = $limit;
        }
        if($offset) {
            $navStartParams['nOffset'] = $offset;
        }

        $request = \CIBlockElement::getList($order, $filter, false, $navStartParams, ['*']);

        $items = [];
        while($item = $request->getNextElement()) {
            $fields = $item->getFields();
            $itemId = $fields['ID'];
            foreach($fields as $key => $field) {
                if(mb_strpos($key, '~') === 0) {
                    unset($fields[$key]);
                }
            }

            if(static::$instanceList[$itemId]) {
                $items[$itemId] = static::$instanceList[$itemId];
                continue;
            }

            $props = $item->getProperties();
            $instance = new static($itemId, $fields, $props);
            $items[$itemId] = $instance;
        }

        if(static::$isCatalog) {
            $catalogRequest = \Bitrix\Catalog\ProductTable::getList(['filter' => ['ID' => array_keys($items)]]);
            while($catalogItem = $catalogRequest->fetch()) {
                $items[$catalogItem['ID']]['PRODUCT_INFO'] = $catalogItem;
            }
        }

        foreach($items as $item) {
            $itemId = $item['ID'];
            if(empty(static::$instanceList[$itemId])) {
                static::$instanceList[$itemId] = $item;
            }
        }

        return $items;
    }

    /**
     * Формирует массив сущностей из простого массива с полями
     *
     * @param array $items Массив элементов
     *
     * @return array<static>
     */
    final protected static function makeInstanceList(array $items) : array
    {
        return array_map(static function($item) {
            $itemId = $item['ID'];
            if(static::$instanceList[$itemId]) {
                return static::$instanceList[$itemId];
            }

            $props = $item['PROPERTIES'];
            unset($item['PROPERTIES']);

            $instance = new static($itemId, $item, $props);
            static::$instanceList[$itemId] = $instance;
            return $instance;
        }, $items);
    }

    /**
     * Получение id инфоблока
     *
     * @return int
     */
    final public static function getIblockId() : int
    {
        if(empty(static::$iblockCodeIdMap[static::$iblockCode])) {
            $iblock = static::getIblock();
            static::$iblockCodeIdMap[static::$iblockCode] = $iblock['ID'] ?? 0;
        }

        return static::$iblockCodeIdMap[static::$iblockCode];
    }

    /**
     * Получение инфоблока
     *
     * @return array
     */
    final public static function getIblock() : array
    {
        return \Bitrix\Iblock\IblockTable::getList([
            'filter' => ['CODE' => static::$iblockCode],
            'cache' => ['ttl' => 86400]
        ])->fetch() ?? [];
    }

    /**
     *
     * Кэширование
     *
     */

    /**
     * Регистрация событий очистки кэша
     */
    final public static function registerCacheEvents() : void
    {
        $eventManager = \Bitrix\Main\EventManager::getInstance();

        // Чистка кэша отдельных элементов
        $clearCacheEventList = ['OnAfterIBlockElementUpdate', 'OnAfterIBlockElementDelete'];
        foreach($clearCacheEventList as $event) {
            $eventManager->addEventHandler('iblock', $event, function($data) {
                if(is_numeric($data)) {
                    $elementId = (int)$data;
                } elseif(is_array($data)) {
                    $elementId = (int)$data['ID'];
                } else {
                    return;
                }

                static::clearCache($elementId);
            });
        }

        // Чистка кэша выборок
        $clearCacheEventList[] = 'OnAfterIBlockElementAdd';
        foreach($clearCacheEventList as $event) {
            $eventManager->addEventHandler('iblock', $event, function($data) {
                if((int)$data['IBLOCK_ID'] === static::getIblockId()) {
                    static::clearListCache();
                    array_map(static fn($callback) => $callback(), static::$clearCacheCallbackList);
                }
            });
        }
    }

    /**
     * Очистка кэша выборок
     *
     * @return void
     */
    final protected static function clearListCache() : void
    {
        $cache = \Bitrix\Main\Data\Cache::createInstance();
        $cache->cleanDir(static::getListCachePath());
    }

    /**
     * Очистка кэша
     *
     * @param int $id ID элемента
     */
    /**
     * Очистка кэша. Если указан id, чистится кэш всех выборок и кэш элемента с переданным id
     */
    final public static function clearCache(int $id = 0) : void
    {
        $cache = \Bitrix\Main\Data\Cache::createInstance();
        $itemsListCachePath = static::getListCachePath();
        $cache->cleanDir($itemsListCachePath);
        if($id !== 0) {
            $cacheId = static::getCacheKey(['ID' => $id]);
            $path = static::getCachePath();
            $cache->clean($cacheId, $path);
        }
    }

    /**
     * Получение идентификатора кэша
     *
     * @param array $filter Фильтр для \CIBlockElement::getList()
     * @param array $order Сортировка ['KEY_1' => 'ASC', 'KEY_2' => 'DESC']
     * @param int $limit Ограничение выборки
     * @param int $offset Сдвиг
     *
     * @return string
     */
    final protected static function getCacheKey(array $filter, array $order = [], int $limit = 0, int $offset = 0) : string
    {
        return static::$iblockCode . '_' . md5(serialize($filter) . '_' . serialize($order) . '_' . $limit . '_' . $offset);
    }

    /**
     * Получение папки хранения кэша
     *
     * @return string
     */
    final protected static function getCachePath() : string
    {
        return static::$iblockCode . '_iblock_model_cache';
    }

    /**
     * Получение папки хранения кэша для выборок
     *
     * @return string
     */
    final protected static function getListCachePath() : string
    {
        return static::$iblockCode . '_iblock_model_list_cache';
    }

    /**
     * Добавление коллбэка, который будет вызван при очистке кэша
     *
     * @param callable $fn
     *
     * @return void
     */
    public static function addClearCacheCallback(callable $fn) : void
    {
        static::$clearCacheCallbackList[] = $fn;
    }

    /**
     *
     * Пагинация
     *
     */

    /**
     * Формирование массива для пагинации
     *
     * @return array
     */
    final public static function getPagination() : array
    {
        global $APPLICATION;
        $currentPage = static::getCurrentPage();
        $lastPageNumber = static::getLastPage();
        $currentUri = $APPLICATION->GetCurUri();
        $uri = new \Bitrix\Main\Web\Uri($currentUri);
        $itemsCount = static::getItemsCount();

        // Редирект на последнюю страницу, если текущая страница больше последней
        if($currentPage > $lastPageNumber && $itemsCount > 0) {
            LocalRedirect($uri->addParams(['page' => $lastPageNumber])->getPathQuery());
        }

        $result = [
            'CURRENT_PAGE' => static::getCurrentPage(),
            'ITEMS_PER_PAGE' => static::$itemsPerPage,
            'ITEMS_COUNT' => $itemsCount,
            'FIRST_PAGE' => [
                'IS_CURRENT' => $currentPage === 1,
                'NUMBER' => 1,
                'URL' => $uri->addParams(['page' => 1])->getPathQuery()
            ],
            'LAST_PAGE' => [
                'IS_CURRENT' => $currentPage === $lastPageNumber,
                'NUMBER' => $lastPageNumber,
                'URL' => $uri->addParams(['page' => $lastPageNumber])->getPathQuery()
            ],
            'SHOW_MORE' => [
                'BUTTON_CLASS' => static::$showMoreButtonClass,
                'WRAPPER_CLASS' => static::$showMoreWrapperClass,
                'ITEM_CLASS' => static::$showMoreItemClass
            ],
            'ITEMS' => static::getPaginationItems($uri->getPath(), $lastPageNumber)
        ];

        if($currentPage > 1) {
            $result['PREVIOUS_PAGE'] = [
                'IS_CURRENT' => false,
                'NUMBER' => $currentPage - 1,
                'URL' => $uri->addParams(['page' => $currentPage - 1])->getPathQuery()
            ];
        }
        if($currentPage < $lastPageNumber) {
            $result['NEXT_PAGE'] = [
                'IS_CURRENT' => false,
                'NUMBER' => $currentPage + 1,
                'URL' => $uri->addParams(['page' => $currentPage + 1])->getPathQuery()
            ];
        }

        return $result;
    }

    /**
     * Получение массива со страницами
     *
     * @param string $basePath Ссылка, к которой будет добавляться параметр с номером страницы
     * @param int $lastPageNumber Номер последней страницы
     *
     * @return array
     */
    final protected static function getPaginationItems(string $basePath, int $lastPageNumber) : array
    {
        $uri = new \Bitrix\Main\Web\Uri($basePath);
        $page = static::getCurrentPage();
        $currentPage = [
            'IS_CURRENT' => true,
            'NUMBER' => $page,
            'URL' => $uri->addParams(['page' => $page])->getPathQuery()
        ];

        $previousPageList = [];
        $nextPageList = [];
        for($i = 1; $i <= static::$pageRange; $i++) {
            $previousPageNumber = $page - $i;
            if($previousPageNumber > 0) {
                $previousPageList[$previousPageNumber] = [
                    'IS_CURRENT' => false,
                    'NUMBER' => $previousPageNumber,
                    'URL' => $uri->addParams(['page' => $previousPageNumber])->getPathQuery()
                ];
            }

            $nextPageNumber = $page + $i;
            if($nextPageNumber <= $lastPageNumber) {
                $nextPageList[$nextPageNumber] = [
                    'IS_CURRENT' => false,
                    'NUMBER' => $nextPageNumber,
                    'URL' => $uri->addParams(['page' => $nextPageNumber])->getPathQuery()
                ];
            }
        }

        return array_reverse($previousPageList, true) + [$page => $currentPage] + $nextPageList;
    }

    /**
     * Подсчёт количества элементов
     *
     * @param array $filter
     *
     * @return int
     */
    final public static function getItemsCount(array $filter = []) : int
    {
        $filter['IBLOCK_ID'] = static::getIblockId();
        return \CIBlockElement::getList([], $filter, false, false, ['ID'])->selectedRowsCount();
    }

    /**
     * Получение текущей страницы
     *
     * @return int
     */
    final public static function getCurrentPage() : int
    {
        $request = \Bitrix\Main\Context::getCurrent()->getRequest();
        $pageNumber = (int)$request->get(static::$pageVariable) ?: (int)$request->getPost(static::$pageVariable) ?: 1;
        return $pageNumber > 0 ? $pageNumber : 1;
    }

    /**
     * Получение следующей страницы
     *
     * @return int
     */
    final public static function getNextPage() : int
    {
        return min(static::getCurrentPage() + 1, static::getLastPage());
    }

    /**
     * Получение номера последней страницы
     *
     * @return int
     */
    final public static function getLastPage() : int
    {
        return (int)ceil(static::getItemsCount() / (static::$itemsPerPage ?: 1));
    }

    /**
     *
     * Значения свойства для фильтра
     *
     */

    /**
     * Получение списка свойств и всех существующих значений
     *
     * @return array
     */
    public static function getFilterValuesList(array $filter = []) : array
    {
        $itemsIdList = [];
        if($filter) {
            $filter['IBLOCK_ID'] = static::getIblockId();
            $itemsRequest = \CIBlockElement::getList([], $filter, false, false, ['ID']);
            while($item = $itemsRequest->fetch()) {
                $itemsIdList[] = $item['ID'];
            }
        }

        /* Сбор свойств инфоблока */
        $propertyRequest = \Bitrix\IBlock\PropertyTable::getList([
            'filter' => [
                'IBLOCK_ID' => static::getIblockId(),
                'ACTIVE' => 'Y',
            ],
            'select' => ['ID', 'CODE', 'NAME', 'PROPERTY_TYPE', 'MULTIPLE', 'USER_TYPE']
        ]);
        $propertyList = [];
        while($property = $propertyRequest->fetch()) {
            $propertyList[$property['ID']] = $property;
        }

        /* Случай, когда не найдено товаров, соответствующих фильтру */
        if($filter && empty($itemsIdList)) {
            return $propertyList;
        }

        /* Сбор значений одиночных свойств */
        $singlePropertyValueList = static::getSinglePropertyValues($itemsIdList);

        /* Сбор значений множественных свойств */
        $multiplePropertyValueList = static::getMultiplePropertyValues($itemsIdList);

        /* Присоединение значений к свойствам */
        $propertyEnumValueList = [];
        foreach($propertyList as &$property) {
            $propertyId = $property['ID'];
            if($singlePropertyValueList[$propertyId]) {
                $property['VALUES'] = $singlePropertyValueList[$propertyId];
            }
            if($multiplePropertyValueList[$propertyId]) {
                $property['VALUES'] = $multiplePropertyValueList[$propertyId];
                sort($property['VALUES']);
            }
            if($property['PROPERTY_TYPE'] === 'L' && isset($property['VALUES'])) {
                $propertyEnumValueList = array_merge($propertyEnumValueList, $property['VALUES']);
            }
        }

        /* Присоединение значений типа "Список" */
        if($propertyEnumValueList) {
            $enums = \Bitrix\Iblock\PropertyEnumerationTable::getList([
                'filter' => ['ID' => $propertyEnumValueList],
                'select' => ['ID', 'PROPERTY_ID', 'VALUE', 'XML_ID']
            ])->fetchAll();

            foreach($enums as $enum) {
                $propertyList[$enum['PROPERTY_ID']]['VALUE_NAME'][$enum['ID']] = $enum['VALUE'];
                $propertyList[$enum['PROPERTY_ID']]['XML_ID'][$enum['ID']] = $enum['XML_ID'];
            }
        }

        return $propertyList;
    }

    /**
     * Получение значений множественных свойств инфоблока
     *
     * @param array $itemsIdList ID элементов, свойства которых нужно найти
     *
     * <pre>
     *  'PROPERTY_ID' => [
     *      'VALUE' => [valueId1, valueId2, ...],
     *      'NAME' => [
     *          valueId1 => 'name1',
     *          valueId2 => 'name2',
     *          ...
     *       ],
     *      'XML_ID' => [
     *          valueId1 => 'xml_id1',
     *          valueId2 => 'xml_id2',
     *          ...
     *       ],
     *  ]
     * </pre>
     *
     * @return array
     */
    protected static function getMultiplePropertyValues(array $itemsIdList = []) : array
    {
        global $DB;
        $tableName = 'b_iblock_element_prop_m' . static::getIblockId();
        $connection = \Bitrix\Main\Application::getConnection();
        $tableExists = $connection->isTableExists($tableName);
        if(!$tableExists) {
            $tableName = 'b_iblock_element_property';
        }

        $sql = "SELECT * FROM $tableName";
        if($itemsIdList) {
            $idListString = implode(',', $itemsIdList);
            $sql .= " WHERE IBLOCK_ELEMENT_ID IN ($idListString)";
        }

        $query = $DB->query($sql);
        $propertyValueList = [];
        while($prop = $query->fetch()) {
            $propertyId = $prop['IBLOCK_PROPERTY_ID'];
            $value = $prop['VALUE'];
            if(isset($value) && !in_array($value, $propertyValueList[$propertyId])) {
                $propertyValueList[$propertyId][] = $value;
            }
        }

        return $propertyValueList;
    }

    /**
     * Получение значений одиночных свойств инфоблока
     *
     * @param array $itemsIdList ID элементов, свойства которых нужно найти
     *
     * <pre>
     *  'PROPERTY_ID' => [value1, value2, ...]
     * </pre>
     *
     * @return array
     */
    final protected static function getSinglePropertyValues(array $itemsIdList = []) : array
    {
        global $DB;
        $tableName = 'b_iblock_element_prop_s' . static::getIblockId();
        $connection = \Bitrix\Main\Application::getConnection();
        $tableExists = $connection->isTableExists($tableName);
        if(!$tableExists) {
            return [];
        }

        $sql = "SELECT * FROM $tableName";
        if($itemsIdList) {
            $idListString = implode(',', $itemsIdList);
            $sql .= " WHERE IBLOCK_ELEMENT_ID IN ($idListString)";
        }

        $query = $DB->query($sql);
        $propertyValueList = [];
        while($itemProps = $query->fetch()) {
            foreach($itemProps as $key => $value) {
                if(!isset($value) || strpos($key, 'PROPERTY_') === false) {
                    continue;
                }

                $propertyId = str_replace('PROPERTY_', '', $key);
                if(!in_array($value, $propertyValueList[$propertyId])) {
                    $propertyValueList[$propertyId][] = $value;
                }
            }
        }

        foreach($propertyValueList as &$values) {
            sort($values);
        }

        return $propertyValueList;
    }

    /**
     *
     * Реализация интерфейса ArrayAccess
     *
     */

    public function offsetSet($offset, $value) {
        if(is_null($offset)) {
            $this->fields[] = $value;
        } else {
            $this->fields[$offset] = $value;
        }
    }

    public function offsetExists($offset) {
        return isset($this->fields[$offset]);
    }

    public function offsetUnset($offset) {
        unset($this->fields[$offset]);
    }

    public function offsetGet($offset) {
        return $this->fields[$offset] ?? $this->props[$offset] ?? null;
    }
}