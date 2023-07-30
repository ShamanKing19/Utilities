<?php
namespace App\Models;

use App\Tools\Log;

\Bitrix\Main\Loader::includeModule('iblock');

/**
 * <h2>Модель инфоблока</h2>
 *
 * <h3>Для начала работы нужно</h3>
 * <ol>
 *     <li>Унаследоваться от данного класса</li>
 *     <li>Переопределить "<b>protected static string $iblockCode;</b>"</li>
 *     <li>Если нужно инфоблок является торговым каталогом, то устанавливаем "<b>protected static bool $isCatalog = true;</b>"</li>
 *     <li>Если нужно использовать кэш, то установить  "<b>protected static bool $useCache = true;</b>"</li>
 *     <li>Вызвать "<b>static::registerCacheEvents();</b>" в init.php, если включён кэш</li>
 *     <li>Можно добавить коллбэки, которые будут вызваны при очистке кэша методом <b>static::addClearCacheCallback();</b></li>
 * </ol>
 */
abstract class IblockModel implements \ArrayAccess
{
    /** @var string Символьный код инфоблока */
    protected static string $iblockCode;

    /** @var bool Является ли инфоблок торговым каталогом */
    protected static bool $isCatalog = false;

    /** @var int ID инфоблока */
    protected static int $iblockId = 0;

    /** @var array Информация об инфоблоке */
    protected static array $iblock = [];

    /** @var array Массив с уже созданными объектами */
    public static array $instanceList = [];

    /** @var array Массив с символьными кодами и id инфоблоков */
    public static array $iblockCodeIdMap = [];

    /** @var bool Использовать ли кэш */
    protected static bool $useCache = false;

    /** @var int Время хранения кэша */
    protected static int $cacheTime = 86400;

    /** @var array Коллбэки, вызываемые при очистке кэша */
    protected static array $clearCacheCallbackList = [];

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

        $item = current(static::getList(['ID' => $id]));
        if(empty($item)) {
            return false;
        }

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