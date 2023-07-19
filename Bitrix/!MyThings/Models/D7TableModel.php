<?php
namespace App\Models;

/**
 * Модель для таблицы, потомка \Bitrix\Main\Entity\DataManager
 * <ol>
 *     <li>Для работы нужно унаследоваться и объявить: <p><b>public static string $table = SomeTable::class</b></p></li>
 *     <li>Для работы кэширования нужно объявить, например:
 *          <p><b>protected static string $moduleName = 'iblock'</b></p>
 *          <p><b>protected static bool $saveItemsCache = true</b></p>
 *     <li>Для очистки кэша нужно объявить массив с событиями, по которым он будет очищаться:
 *          <p><b>protected static array $clearCacheEventList = ['OnAfterCrmDealAdd', 'AfterCrmLeadUpdate', ...]</b></p>
 *     </li>
 *     <li>В init.php вызывать метод:
 *          <p><b>static::registerCacheEvents()</b></p>
 *     </li>
 * </ol>
 */
abstract class D7TableModel implements \ArrayAccess
{
    /**
     * Обязательные к заполнению поля
     */

    /* @var \Bitrix\Main\Entity\DataManager название класса (SomeTable::class), потомка \Bitrix\Main\Entity\DataManager */
    public static string $table;

    /**
     * Настраиваемые поля
     */

    /* @var array Дополнительные поля для фильтра в getListRaw (Можно установить с помощью метода static::setDefaultFilter()) */
    protected static array $defaultFilter = [];

    /* @var array Стандартные поля, которые нужно выбирать в getListRaw */
    protected static array $defaultSelect = ['*', 'UF_*'];

    /** @var array|string[] Стандартная сортировка */
    protected static array $defaultOrder = ['ID' => 'ASC'];

    /**
     * Кэширование
     */

    /* @var string Название модуля, для которого будут слушаться события для очистки кэша */
    protected static string $moduleName = '';

    /* @var bool Сохранять ли в кэш поля объектов */
    protected static bool $saveItemsCache = false;

    /* @var int Время кэширования для одиночных элементов */
    protected static int $cacheTime = 86400;

    /* @var array Список событий, по которым будет очищаться кэш */
    protected static array $clearCacheEventList = [];

    /**
     * Пагинация
     */


    /** @var string Переменная, которая будет искаться в запросе для определения текущей страницы */
    protected static string $pageVariable = 'page';

    /** @var int Количество страниц, отображаемых перед и после текущей */
    protected static int $pageRange = 4;

    /** @var int номер страницы */
    protected static int $page = 1;

    /** @var int Количество элементов на странице */
    protected static int $itemsPerPage = 10;

    /**
     * Свойства
     */

    /* @var array<static> Массив с уже созданными объектами */
    protected static array $instanceList = [];

    /* @var int $id Элемента таблицы */
    protected int $id;

    /* @var array Список полей элемента */
    protected array $fields;


    protected function __construct(int $id, array $fields)
    {
        $this->id = $id;
        $this->fields = $fields;
    }

    /**
     * Получение id элемента
     *
     * @return int
     */
    public function getId() : int
    {
        return $this->id;
    }

    /**
     * Получение полей элемента
     *
     * @return array
     */
    public function toArray() : array
    {
        return $this->fields;
    }

    /**
     * Получение значения поля
     *
     * @param string $key
     *
     * @return mixed|null
     */
    public function getField(string $key) : mixed
    {
        if(!array_key_exists($key, $this->fields) || is_null($this->fields[$key])) {
            return null;
        }

        return $this->fields[$key];
    }

    /**
     * Установка нового значения поля
     *
     * @param string $key
     * @param string $value
     *
     * @return static
     */
    public function setField(string $key, string $value) : static
    {
        $this->fields[$key] = $value;
        return $this;
    }

    /**
     * Обновление записи в базе с текущими полями
     *
     * @return bool
     */
    final public function save() : bool
    {
        $id = $this->getId();
        $success = static::$table::update($id, $this->fields) > 0;
        if($success) {
            static::clearCache($id);
        }
        return $success;
    }

    /**
     * Удаление записи из базы
     */
    final public function delete() : void
    {
        static::deleteById($this->getId());
    }

    /**
     * Получение объекта по id
     *
     * @param int $id id сделки
     *
     * @return static|false
     */
    final public static function find(int $id) : static|false
    {
        return static::$instanceList[$id] ?? static::findBy('ID', $id);
    }

    /**
     * Получение объекта по значению определённого поля из таблицы
     *
     * @param string $key название поля таблицы
     * @param int|string|bool $value значение
     *
     * @return static|false
     */
    final public static function findBy(string $key, mixed $value) : static|false
    {
        return current(static::getList([$key => $value])) ?? false;
    }

    /**
     * Получение списка элементов для текущей страницы
     *
     * @return array<static>
     */
    final public static function getCurrentPageList(array $filter = [], array $order = []) : array
    {
        $offset = (static::getCurrentPage() - 1) * static::$itemsPerPage;
        return static::getList($filter, $order, static::$itemsPerPage, $offset);
    }

    /**
     * Получение списка элементов в виде массива (кэшируемая)
     *
     * @param array $filter фильтр
     *
     * @return array<static>
     */
    final public static function getList(array $filter = [], array $order = [], int $limit = 0, int $offset = 0) : array
    {
        if(!static::$saveItemsCache) {
            return static::getListRaw($filter, $order, $limit, $offset);
        }

        $cache = \Bitrix\Main\Data\Cache::createInstance();
        $cacheKey = static::getCacheId($filter, $order, $limit, $offset);
        $cachePath = static::getCachePathForItemsList();

        // Для выборки по одному элементу свой путь для кэша, чтобы при добавлении/обновлении/удалении не очищать его
        if(isset($filter['ID']) && count($filter) === 1) {
            $cacheKey = static::getCacheId($filter);
            $cachePath = static::getCachePath();
        }

        if($cache->initCache(static::$cacheTime, $cacheKey, $cachePath)) {
            $items = $cache->getVars();
            return static::makeInstanceList($items);
        }

        $items = static::getListRaw($filter, $order, $limit, $offset);
        if(empty($items)) {
            return [];
        }

        $cache->startDataCache();
        $cache->endDataCache(array_map(fn($item) => $item->toArray(), $items));
        return $items;
    }

    /**
     * Простая выборка элементов из таблицы (не кэшируемая)
     *
     * @param array $filter фильтр
     * @param array $order
     * @param int $limit
     * @param int $offset
     *
     * @return array<static>
     */
    public static function getListRaw(array $filter = [], array $order = [], int $limit = 0, int $offset = 0) : array
    {
        foreach(static::$defaultFilter as $key => $value) {
            if(!isset($filter[$key])) {
                $filter[$key] = $value;
            }
        }

        foreach(static::$defaultOrder as $key => $value) {
            if(!isset($order[$key])) {
                $order[$key] = $value;
            }
        }

        $params = [
            'filter' => $filter,
            'select' => static::$defaultSelect,
            'order' => $order
        ];

        if($limit > 0) {
            $params['limit'] = $limit;
        }
        if($offset > 0) {
            $params['offset'] = $offset;
        }

        $items = static::$table::getList($params)->fetchAll();
        return static::makeInstanceList($items);
    }

    /**
     * Установка фильтра по-умолчанию
     *
     * @param array $filter Фильтр для таблицы
     */
    public static function setDefaultFilter(array $filter) : void
    {
        static::$defaultFilter = $filter;
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
        return array_map(function($item) {
            $itemId = $item['ID'];
            if(static::$instanceList[$itemId]) {
                return static::$instanceList[$itemId];
            }

            $instance = new static($itemId, $item);
            static::$instanceList[$itemId] = $instance;
            return $instance;
        }, $items);
    }

    /**
     * Создание элемента
     *
     * @param array $fields поля, которые нужно сохранить
     *
     * @return static|false
     */
    final public static function create(array $fields) : static|false
    {
        $result = static::$table::add($fields);
        $itemId = $result->getId();
        if($itemId) {
            static::clearCache();
            return new static($itemId, $fields);
        }

        return false;
    }

    /**
     * Удаление элемента
     *
     * @param int $id id элемента
     */
    final public static function deleteById(int $id) : void
    {
        static::$table::delete($id);
        static::clearCache($id);
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
    public static function getPagination() : array
    {
        global $APPLICATION;
        $currentUri = $APPLICATION->GetCurUri();
        $uri = new \Bitrix\Main\Web\Uri($currentUri);
        
        $currentPage = static::getCurrentPage();
        $elementCount = static::getItemsCount();
        $lastPageNumber = (int)ceil($elementCount / (static::$itemsPerPage ?: 1));

        // Редирект на последнюю страницу, если текущая страница больше последней
        if($currentPage > $lastPageNumber) {
            LocalRedirect($uri->addParams(['page' => $lastPageNumber])->getPathQuery());
        }

        $result = [
            'CURRENT_PAGE' => static::getCurrentPage(),
            'ITEMS_PER_PAGE' => static::$itemsPerPage,
            'ITEMS_COUNT' => $elementCount,
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
    protected static function getPaginationItems(string $basePath, int $lastPageNumber) : array
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
     * // TODO: Добавить фильтр
     *
     * @return int
     */
    public static function getItemsCount() : int
    {
        return static::$table::getCount(static::$defaultFilter);
    }

    /**
     * Получение текущей страницы
     *
     * @return int
     */
    protected static function getCurrentPage() : int
    {
        $request = \Bitrix\Main\Context::getCurrent()->getRequest();
        $pageNumber = (int)$request->get(static::$pageVariable) ?: (int)$request->getPost(static::$pageVariable) ?: 1;
        return $pageNumber > 0 ? $pageNumber : 1;
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
        if(empty(static::$moduleName)) {
            return;
        }

        $eventManager = \Bitrix\Main\EventManager::getInstance();
        foreach(static::$clearCacheEventList as $event) {
            $eventManager->addEventHandler(static::$moduleName, $event, function($data) use($event) {
                if(is_numeric($data)) {
                    $elementId = (int)$data;
                } elseif(is_array($data)) {
                    $elementId = (int)$data['ID'];
                } else {
                    return;
                }

                static::clearCache($elementId ?? 0);
            });
        }
    }

    /**
     * Получение id кэша по фильтру
     *
     * @param array $filter фильтр для запроса
     *
     * @return string
     */
    protected static function getCacheId(array $filter, array $order = [], int $limit = 0, int $offset = 0) : string
    {
        return static::$table::getTableName() . '_' . serialize($filter) . '_' . serialize($order) . '_' . $limit . '_' . $offset;
    }

    /**
     * Получение пути к кэшу для одиночных элементов
     *
     * @return string
     */
    protected static function getCachePath() : string
    {
        return static::$table::getTableName() . '_items_cache';
    }

    /**
     * Получение пути к кэшу для одиночных элементов
     *
     * @return string
     */
    protected static function getCachePathForItemsList() : string
    {
        return static::$table::getTableName() . '_items_list_cache';
    }

    /**
     * Очистка кэша. Если указан id, чистится кэш всех выборок и кэш элемента с переданным id
     */
    final public static function clearCache(int $id = 0) : void
    {
        $cache = \Bitrix\Main\Data\Cache::createInstance();
        $itemsListCachePath = static::getCachePathForItemsList();
        $cache->cleanDir($itemsListCachePath);
        if($id !== 0) {
            $cacheId = static::getCacheId(['ID' => $id]);
            $path = static::getCachePath();
            $cache->clean($cacheId, $path);
        }
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
        return $this->fields[$offset] ?? null;
    }
}