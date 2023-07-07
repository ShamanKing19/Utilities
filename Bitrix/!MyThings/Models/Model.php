<?php
namespace App\Api\Models;

/**
 * Модель для таблицы, потомка \Bitrix\Main\Entity\DataManager
 * <pre>
 * 1. Для работы нужно унаследоваться и объявить:
 *    public static string $table = SomeTable::class;
 * 2. Для работы кэширования нужно объявить, например:
 *    protected static string $moduleName = 'iblock';
 *    protected static bool $saveItemsCache = true;
 *    
 *    Для очистки кэша нужно объявить массив с событиями, по которым он будет очищаться:
 *    protected static array $clearCacheEventList = ['OnAfterIblockElementAdd', 'OnAfterIblockElementUpdate', ...];
 *    
 *    И в init.php вызывать метод:
 *    static::registerCacheEvents();
 * </pre>
 */
abstract class Model implements \ArrayAccess
{
    /**
     * Обязательные к заполнению поля
     */

    /* @var \Bitrix\Main\Entity\DataManager название класса (SomeTable::class), потомка \Bitrix\Main\Entity\DataManager */
    public static string $table;

    /**
     * Настраиваемые поля
     */

    /* @var array Дополнительные поля для фильтра в getListRaw */
    protected static array $defaultFilter = [];

    /* @var array Стандартные поля, которые нужно выбирать в getListRaw */
    protected static array $defaultSelect = ['*', 'UF_*'];

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
     * Сохранение проекта с текущеми полями в базу
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
    final public static function findBy(string $key, $value) : static|false
    {
        return current(static::getItems([$key => $value])) ?? false;
    }

    /**
     * Получение списка элементов в виде объектов модели
     *
     * @param array $filter фильтр
     * @param string[] $select поля, которые нужно достать
     *
     * @return array<static>
     */
    final public static function getItems(array $filter = []) : array
    {
        $items = static::getList($filter);
        return array_map(function($item) {
            $object = new static($item['ID'], $item);
            static::$instanceList[$item['ID']] = $object;
            return $object;
        }, $items);
    }

    /**
     * Получение списка элементов в виде массива (кешируемая)
     *
     * @param array $filter фильтр
     *
     * @return array
     */
    final public static function getList(array $filter = []) : array
    {
        if(!static::$saveItemsCache) {
            return static::getListRaw($filter);
        }

        $cache = \Bitrix\Main\Data\Cache::createInstance();
        $cacheKey = static::getCacheId($filter);
        $cachePath = static::getCachePathForItemsList();

        // Для выборки по одному элементу свой путь для кэша, чтобы при добавлении/обновлении/удалении не очищать его
        if(isset($filter['ID']) && count($filter) === 1) {
            $cachePath = static::getCachePath();
        }

        if($cache->initCache(static::$cacheTime, $cacheKey, $cachePath)) {
            return $cache->getVars() ?? [];
        }

        $items = static::getListRaw($filter);
        if(empty($items)) {
            return [];
        }

        $cache->startDataCache();
        $cache->endDataCache($items);
        return $items;
    }

    /**
     * Простая выборка элементов из таблицы (не кэшируемая)
     *
     * @param array $filter фильтр
     * @param string[] $customSelect поля, которые нужно достать
     *
     * @return array
     */
    final public static function getListRaw(array $filter = [], array $customSelect = []) : array
    {
        foreach(static::$defaultFilter as $key => $value) {
            if(!isset($filter[$key])) {
                $filter[$key] = $value;
            }
        }

        $select = static::$defaultSelect;
        if($customSelect) {
            $select = $customSelect;
        }

        $request = static::$table::getList([
            'filter' => $filter,
            'select' => $select
        ]);

        $items = [];
        while($item = $request->fetch()) {
            $items[$item['ID']] = $item;
        }

        return $items;
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
     * Регистрация событий очистки экша
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
    protected static function getCacheId(array $filter) : string
    {
        return static::$table::getTableName() . '_' . serialize($filter);
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
     * Реализация интерфейса ArrayAccess
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