<?php
namespace App\Models;

\Bitrix\Main\Loader::includeModule('iblock');

/**
 * <h2>Модель инфоблока</h2>
 *
 * <h3>Для начала работы нужно</h3>
 * <ol>
 *     <li>Унаследоваться от данного класса</li>
 *     <li>Переопределить "<b>protected static string $iblockCode</b>"</li>
 *     <li>Если нужно использовать кэш, то установить  "<b>protected static bool $userCache = true</b>"</li>
 *     <li>Вызвать "<b>static::registerCacheEvents()</b>" в init.php, если включён кэш</li>
 * </ol>
 */
abstract class IblockModel implements \ArrayAccess
{
    /** @var string Символьный код инфоблока */
    protected static string $iblockCode;

    /** @var int ID инфоблока */
    protected static int $iblockId = 0;

    /** @var array Информация об инфоблоке */
    protected static array $iblock = [];

    /** @var array Массив с уже созданными объектами */
    public static array $instanceList = [];

    /** @var bool Использовать ли кэш */
    protected static bool $useCache = false;

    /** @var int Время хранения кэша */
    protected static int $cacheTime = 86400;

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
    final public static function find(int $id) : static|false
    {
        if(static::$instanceList[$id]) {
            return static::$instanceList[$id];
        }

        // Попытка достать данные из кэша
        if(static::$useCache) {
            $item = static::getFromCache($id);
            if($item) {
                $props = $item['PROPERTIES'];
                unset($item['PROPERTIES']);

                $instance = new static($id, $item, $props);
                static::$instanceList[$id] = $instance;
                return $instance;
            }
        }

        $item = current(static::getListRaw(['ID' => $id]));
        if(empty($item)) {
            return false;
        }

        static::saveToCache($item);
        static::$instanceList[$id] = $item;
        
        return $item;
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
    public static function getList(array $filter = [], array $order = ['ID' => 'ASC'], int $limit = 0, int $offset = 0) : array
    {
        if(isset($filter['ID'])) {
            return static::getListRaw($filter, $order, $limit, $offset);
        }

        // Попытка достать данные из кэша
        if(static::$useCache) {
            $items = static::getListFromCache($filter, $order, $limit, $offset);
            if($items) {
                foreach($items as $key => $item) {
                    $itemId = $item['ID'];
                    $props = $item['PROPERTIES'];
                    unset($item['PROPERTIES']);

                    $instance = new static($itemId, $item, $props);
                    $items[$key] = $instance;
                    if(empty(static::$instanceList[$itemId])) {
                        static::$instanceList[$itemId] = $instance;
                    }
                }

                return $items;
            }
        }

        $items = static::getListRaw($filter, $order, $limit, $offset);
        if(empty($items)) {
            return [];
        }

        static::saveListToCache($items, $filter, $order, $limit, $offset);
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
            if(static::$instanceList[$itemId]) {
                $items[$itemId] = static::$instanceList[$itemId];
                continue;
            }

            $props = $item->getProperties();
            $instance = new static($itemId, $fields, $props);
            $items[$itemId] = $instance;
            static::$instanceList[$itemId] = $instance;
        }

        return $items;
    }

    /**
     * Получение id инфоблока
     *
     * @return int
     */
    final public static function getIblockId() : int
    {
        if(empty(static::$iblockId)) {
            $iblock = static::getIblock();
            static::$iblockId = $iblock['ID'] ?? 0;
        }

        return static::$iblockId;
    }

    /**
     * Получение инфоблока
     *
     * @return array
     */
    final public static function getIblock() : array
    {
        if(empty(static::$iblock)) {
            static::$iblock = \Bitrix\Iblock\IblockTable::getList([
                'filter' => ['CODE' => static::$iblockCode],
                'cache' => ['ttl' => 86400]
            ])->fetch() ?? [];
        }

        return static::$iblock;
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
            $eventManager->addEventHandler('iblock', $event, function($data) use($event) {
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
            $eventManager->addEventHandler('iblock', $event, function($data) use($event) {
                static::clearListCache();
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
     * Получение выборки из кэша
     *
     * @param array $filter Фильтр для \CIBlockElement::getList()
     * @param array $order Сортировка ['KEY_1' => 'ASC', 'KEY_2' => 'DESC']
     * @param int $limit Ограничение выборки
     * @param int $offset Сдвиг
     *
     * @return array|false
     */
    final protected static function getListFromCache(array $filter, array $order, int $limit, int $offset) : array|false
    {
        $cache = \Bitrix\Main\Data\Cache::createInstance();
        $cacheKey = static::getListCacheKey($filter, $order, $limit, $offset);
        if($cache->initCache(static::$cacheTime, $cacheKey, static::getListCachePath())) {
            return $cache->getVars();
        }

        return false;
    }

    /**
     * Кеширование выборки
     *
     * @param array<static> $items Элементы, которые нужно кешировать
     * @param array $filter Фильтр для \CIBlockElement::getList()
     * @param array $order Сортировка ['KEY_1' => 'ASC', 'KEY_2' => 'DESC']
     * @param int $limit Ограничение выборки
     * @param int $offset Сдвиг
     *
     * @return bool
     */
    final protected static function saveListToCache(array $items, array $filter, array $order, int $limit, int $offset) : bool
    {
        $cache = \Bitrix\Main\Data\Cache::createInstance();
        $cacheKey = static::getListCacheKey($filter, $order, $limit, $offset);

        if(!$cache->initCache(static::$cacheTime, $cacheKey, static::getListCachePath())) {
            $items = array_map(fn($item) => $item->toArray(), $items);
            $cache->startDataCache();
            $cache->endDataCache($items);
            return true;
        }

        return false;
    }

    /**
     * Получение идентификатора кэша для выборки
     *
     * @param array $filter Фильтр для \CIBlockElement::getList()
     * @param array $order Сортировка ['KEY_1' => 'ASC', 'KEY_2' => 'DESC']
     * @param int $limit Ограничение выборки
     * @param int $offset Сдвиг
     *
     * @return string
     */
    final protected static function getListCacheKey(array $filter, array $order, int $limit, int $offset) : string
    {
        return static::$iblockCode . '_' . serialize($filter) . '_' . serialize($order) . '_' . $limit . '_' . $offset;
    }

    /**
     * Получение папки хранения кэша для выборок
     *
     * @return string
     */
    final protected static function getListCachePath() : string
    {
        return static::$iblockCode . '_list_cache';
    }

    /**
     * Очистка кэша
     *
     * @param int $id ID элемента
     */
    final public static function clearCache(int $id = 0) : void
    {
        $cache = \Bitrix\Main\Data\Cache::createInstance();
        $cachePath = static::getCachePath();
        if($id) {
            $cache->clean(static::getCacheKey($id), $cachePath);
        } else {
            $cache->cleanDir($cachePath);
        }
    }

    /**
     * Получение элемента из кэша
     *
     * @param int $id ID элемента инфоблока
     *
     * @return array|false
     */
    final protected static function getFromCache(int $id) : array|false
    {
        $cache = \Bitrix\Main\Data\Cache::createInstance();
        $cacheKey = static::getCacheKey($id);

        if($cache->initCache(static::$cacheTime, $cacheKey, static::getCachePath())) {
            return $cache->getVars();
        }

        return false;
    }

    /**
     * Сохранение элемента в кэш
     *
     * @param static $item
     *
     * @return bool
     */
    final protected static function saveToCache($item) : bool
    {
        $cache = \Bitrix\Main\Data\Cache::createInstance();
        $cacheKey = static::getCacheKey($item->getId());

        if(!$cache->initCache(static::$cacheTime, $cacheKey, static::getCachePath())) {
            $cache->startDataCache();
            $cache->endDataCache($item->toArray());
            return true;
        }

        return false;
    }

    /**
     * Получение идентификатора кэша
     *
     * @param int $id
     *
     * @return string
     */
    final protected static function getCacheKey(int $id) : string
    {
        return static::$iblockCode . '_' . $id;
    }

    /**
     * Получение папки хранения кэша
     *
     * @return string
     */
    final protected static function getCachePath() : string
    {
        return static::$iblockCode . '_cache';
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