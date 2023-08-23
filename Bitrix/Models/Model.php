<?php
namespace App\Models;

/**
 * Модель для таблицы, потомка \Bitrix\Main\Entity\DataManager
 * <h2>Старт</h2>
 * <ol>
 *     <li>Для работы нужно унаследоваться и объявить: <p><b>public static string $table = SomeTable::class</b></p></li>
 *     <li>Если нужно установить "высчитываемый" фильтр, например по id пользователя, то нужно переопределить метод
 *          <p><b>static::getCustomFilter();</b></p>
 *     </li>
 *     <li>Можно присоединить таблицу с пользовательскими полями UF_* (для некоторых сущностей реализовано по умолчанию, например, \Bitrix\Main\UserTable).
 *         Для этого нужно либо указать название таблицы:
 *         <p><b>static::$ufTable</b></p> либо переопределить метод если в названии есть какой-то id<p><b>static::getUfTableName()</b></p>
 *     </li>
 *     <li>Можно добавить JOIN'ы, переопределив метод:
 *          <p><b>static::getJoins();</b></p>
 *     </li>
 * </ol>
 *
 * <h2>Кэширование</h2>
 * <ol>
 *     <li>Объявляем название модуля ('main', 'sale', ...) и включаем кэширование:
 *         <p><b>protected static string $moduleName = 'iblock';</b></p>
 *         <p><b>protected static bool $useCache = true;</b></p>
 *     </li>
 *     <li>Объявляем события добавления, обновления, удаления:
 *         <p><b>protected static string $addEvent = 'OnFileSave';</b></p>
 *         <p><b>protected static string $updateEvent = 'OnAfterIBlockSectionUpdate';</b></p>
 *         <p><b>protected static string $deleteEvent = 'AfterCrmLeadDelete';</b></p>
 *     </li>
 *     <li>Можно добавить другие события, по которым будет очищаться весь кэш:
 *         <p><b>protected static array $clearCacheEventList = ['OnAfterCrmDealAdd', 'AfterCrmLeadUpdate'];</b></p>
 *     </li>
 *     <li>В init.php вызывать метод:
 *          <p><b>static::registerCacheEvents();</b></p>
 *     </li>
 * </ol>
 *
 * <h2>Пагинация</h2>
 * <h3>I. Постраничная</h3>
 * <ol>
 *     <li>Вызываем метод: <p><b>static::getPagination();</b></p></li>
 *     <li>Натягиваем на вёрстку</li>
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
 *
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

    /* @var array Дополнительные поля для фильтра в getListRaw (Можно установить с помощью метода static::setFilter()) */
    protected static array $filter = [];

    /* @var array Стандартные поля, которые нужно выбирать в getListRaw */
    protected static array $select = ['*', 'UF_*'];

    /** @var array|string[] Стандартная сортировка */
    protected static array $order = ['ID' => 'ASC'];

    /** @var string Название таблицы, в которой содержатся значения пользовательских полей UF_* */
    protected static string $ufTable;

    /**
     * Кэширование
     */

    /* @var string Название модуля, для которого будут слушаться события для очистки кэша */
    protected static string $moduleName = '';

    /* @var bool Сохранять ли в кэш поля объектов */
    protected static bool $useCache = false;

    /* @var int Время кэширования для одиночных элементов */
    protected static int $cacheTime = 86400;

    /* @var array Список событий, по которым будет очищаться кэш */
    protected static array $clearCacheEventList = [];

    /** @var string Событие добавления элемента */
    protected static string $addEvent = '';

    /** @var string Событие обновления элемента */
    protected static string $updateEvent = '';

    /** @var string Событие удаления элемента */
    protected static string $deleteEvent = '';

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
     * @return mixed
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
    public function setField(string $key, string $value)
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
     *
     * @return bool
     */
    final public function delete() : bool
    {
        return static::deleteById($this->getId());
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
        $key = static::getInstanceKey($id);
        return static::$instanceList[$key] ?? static::findBy('ID', $id);
    }

    /**
     * Получение объекта по значению определённого поля из таблицы
     *
     * @param array|string $key название поля таблицы или массив с несколькими полями и значениями
     * @param int|string|bool $value значение
     *
     * @return static|false
     */
    final public static function findBy(array|string $key, int|string|bool $value = null) : static|false
    {
        if(is_string($key) && isset($value)) {
            return current(static::getList([$key => $value])) ?? false;
        } elseif(is_array($key)) {
            return current(static::getList($key)) ?? false;
        }

        return false;
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
     * Получение списка элементов с учётом пагинации
     *
     * @param array $filter
     * @param array $order
     *
     * @return mixed
     */
    final public static function getPageItems(array $filter = [], array $order = []) : array
    {
        $currentPage = static::getCurrentPage();
        return static::getList($filter, $order, static::$itemsPerPage, ($currentPage - 1) * static::$itemsPerPage);
    }

    /**
     * Получение списка элементов в виде массива (кэшируемая)
     *
     * @param array $filter
     * @param array $order
     * @param int $limit
     * @param int $offset
     *
     * @return array<static>
     */
    final public static function getList(array $filter = [], array $order = [], int $limit = 0, int $offset = 0) : array
    {
        if(!static::$useCache) {
            return static::getListRaw($filter, $order, $limit, $offset);
        }

        $cache = \Bitrix\Main\Data\Cache::createInstance();
        $cacheKey = static::getCacheId($filter, $order, $limit, $offset);
        $cachePath = static::getCachePathForItemsList();

        // Для выборки по одному элементу свой путь для кэша, чтобы при добавлении/обновлении/удалении не очищать его
        if(count($filter) === 1 && isset($filter['ID']) && is_numeric($filter['ID'])) {
            $cacheKey = static::getCacheId($filter);
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
     * Простая выборка элементов из таблицы (не кэшируемая)
     *
     * @param array $filter
     * @param array $order
     * @param int $limit
     * @param int $offset
     *
     * @return array<static>
     */
    protected static function getListRaw(array $filter = [], array $order = [], int $limit = 0, int $offset = 0) : array
    {
        foreach(static::$filter as $key => $value) {
            if(!isset($filter[$key])) {
                $filter[$key] = $value;
            }
        }

        foreach(static::getCustomFilter() as $key => $value) {
            if(!isset($filter[$key])) {
                $filter[$key] = $value;
            }
        }

        foreach(static::$order as $key => $value) {
            if(!isset($order[$key])) {
                $order[$key] = $value;
            }
        }

        $select = static::$select;
        $joinList = static::getJoins();
        foreach($joinList as $join) {
            $columnName = $join->getName();
            $select[$columnName] = $columnName . '.*';
        }

        $params = [
            'filter' => $filter,
            'select' => $select,
            'order' => $order,
            'runtime' => $joinList
        ];

        if($limit > 0) {
            $params['limit'] = $limit;
        }
        if($offset > 0) {
            $params['offset'] = $offset;
        }

        $request = static::$table::getList($params);

        $items = [];
        $joinItems = []; // Дублирующий массив для красивого присоединения полей join'ов

        while($item = $request->fetch()) {
            $items[$item['ID']] = $item;
            if($joinList) {
                $joinItems[] = $item;
            }
        }

        if(empty($items)) {
            return [];
        }

        /**
         * Присоединение пользовательских полей UF_*
         */
        if(static::getUfTableName()) {
            $itemsIdList = array_column($items, 'ID');
            if($itemsIdList) {
                $ufValues = static::getUserFieldValues($itemsIdList);
                foreach($ufValues as $itemId => $fields) {
                    $items[$itemId] = array_merge($items[$itemId], $fields);
                }
            }
        }

        /*
         * Присоединение join'ов
         */
        if($joinItems) {
            static::attachJoins($items, $joinItems);
            unset($joinItems);
        }

        return static::makeInstanceList($items);
    }

    /**
     * Установка join'ов по-умолчанию
     *
     * <pre>
     * <b>Пример JOIN'a</b>
     * new \Bitrix\Main\ORM\Fields\Relations\Reference(
     *     'PROPERTIES',
     *     \Bitrix\Sale\Internals\BasketPropertyTable::class,
     *     \Bitrix\Main\ORM\Query\Join::on('this.ID', 'ref.BASKET_ID'),
     *     ['join_type' => 'left']
     * )
     * </pre>
     *
     * @return array<\Bitrix\Main\ORM\Fields\Relations\Reference>
     */
    protected static function getJoins() : array
    {
        return [];
    }

    /**
     * Установка фильтра по-умолчанию
     *
     * @return array Кастомный фильтр
     */
    protected static function getCustomFilter() : array
    {
        return [];
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
        if(empty($items)) {
            return [];
        }

        return array_map(static function($item) {
            $itemId = $item['ID'];
            $key = static::getInstanceKey($itemId);
            if(static::$instanceList[$key]) {
                return static::$instanceList[$key];
            }

            $instance = new static($itemId, $item);
            static::$instanceList[$key] = $instance;
            return $instance;
        }, $items);
    }

    /**
     * Получение ключа элемента для $instanceList
     *
     * @param int $id ID элемента
     *
     * @return string
     */
    final protected static function getInstanceKey(int $id) : string
    {
        return static::$table . '_' . $id;
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
    final public static function deleteById(int $id) : bool
    {
        $result = static::$table::delete($id);
        if($result->isSuccess()) {
            static::clearCache($id);
            return true;
        }

        return false;
    }

    /**
     * Пользовательские поля UF_*
     */

    /**
     * Получение значений пользовательских полей для элементов
     *
     * @param array $elementIdList Список id элементов, значения которых надо найти
     *
     * @return array
     */
    final protected static function getUserFieldValues(array $elementIdList) : array
    {
        $tableName = static::getUfTableName();
        if(empty($tableName) || empty($elementIdList)) {
            return [];
        }

        global $DB;
        $idString = implode(',', $elementIdList);
        $request = $DB->query("SELECT * FROM $tableName WHERE VALUE_ID IN ($idString)");
        $items = [];
        while($fields = $request->fetch()) {
            $itemId = $fields['VALUE_ID'];
            unset($fields['VALUE_ID']);

            foreach($fields as $key => $field) {
                $unserializedValue = unserialize($field);
                if($unserializedValue !== false) {
                    $fields[$key] = $unserializedValue;
                }
            }

            $items[$itemId] = $fields;
        }

        if(empty($items)) {
            return [];
        }

        // Получение полей типа "Список"
        $keys = array_keys(current($items));
        $userFields = \Bitrix\Main\UserFieldTable::getList([
            'filter' => [
                'FIELD_NAME' => $keys,
                'USER_TYPE_ID' => 'enumeration'
            ],
            'select' => ['FIELD_NAME']
        ])->fetchAll();
        $listFieldCodes = array_column($userFields, 'FIELD_NAME');

         // Сбор значений типа "Список"
        $listValueIdList = [];
        foreach($listFieldCodes as $code) {
            foreach($items as $item) {
                $value = $item[$code];
                if(empty($value)) {
                    continue;
                }

                $value = is_array($value) ? $value : [$value];
                $listValueIdList = array_merge($listValueIdList, $value);
            }
        }

        if(empty($listValueIdList)) {
            return $items;
        }

        // Получение значений типа "Список"
        $valueIdString = implode(',', $listValueIdList);
        $request = $DB->query("SELECT * FROM b_user_field_enum WHERE ID IN ($valueIdString)");
        $enumValues = [];
        while($value = $request->fetch()) {
            $enumValues[$value['ID']] = $value;
        }

        // Подстановка найденных значений
        foreach($items as &$item) {
            foreach($listFieldCodes as $code) {
                if(empty($item[$code])) {
                    continue;
                }

                $values = [];
                if(is_array($item[$code])) {
                    foreach($item[$code] as $valueId) {
                        $values[] = [
                            'ID' => $valueId,
                            'VALUE' => $enumValues[$valueId]['VALUE'],
                            'XML_ID' => $enumValues[$valueId]['XML_ID']
                        ];
                    }
                } else {
                    $valueId = $item[$code];
                    $values = [
                        'ID' => $valueId,
                        'VALUE' => $enumValues[$valueId]['VALUE'],
                        'XML_ID' => $enumValues[$valueId]['XML_ID']
                    ];
                }

                $item[$code] = $values;
            }
        }

        return $items;
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

        return '';
    }

    /**
     *
     * Пагинация
     *
     */

    /**
     * Инициализация js для функционала "Показать ещё"
     *
     * @return void
     */
    final public static function initShowMoreButton() : void
    {
        $currentPage = static::getCurrentPage();
        $lastPage = static::getLastPage();
        $buttonClass = static::$showMoreButtonClass;
        $wrapperClass = static::$showMoreWrapperClass;
        $itemClass = static::$showMoreItemClass;
        $pageVariable = static::$pageVariable;

        $script = "
            <script>
                const itemsList = new ItemsList($currentPage, $lastPage);
                itemsList.initShowMoreButton(
                    '$wrapperClass',
                    '$itemClass',
                    '$buttonClass',
                    '$pageVariable',
                    '$pageVariable'
                );
            </script>
        ";
        echo $script;
    }

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
     * @return int
     */
    final public static function getItemsCount(array $filter = []) : int
    {
        foreach(static::$filter as $key => $value) {
            if(!isset($filter[$key])) {
                $filter[$key] = $value;
            }
        }

        foreach(static::getCustomFilter() as $key => $value) {
            if(!isset($filter[$key])) {
                $filter[$key] = $value;
            }
        }

        return static::$table::getCount($filter);
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

        return max($pageNumber, 1);
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

        $itemCacheEvents = [];
        if(static::$updateEvent) {
            $itemCacheEvents[] = static::$updateEvent;
        }
        if(static::$deleteEvent) {
            $itemCacheEvents[] = static::$deleteEvent;
        }

        $eventManager = \Bitrix\Main\EventManager::getInstance();

        // Очистка кэша элементов
        foreach($itemCacheEvents as $event) {
            $eventManager->addEventHandler(static::$moduleName, $event, function($data) {
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

        // Очистка кэша выборок static::getList()
        if(static::$addEvent) {
            $itemCacheEvents[] = static::$addEvent;
        }
        $litesListCacheEvents = array_unique(array_merge(static::$clearCacheEventList, $itemCacheEvents));
        foreach($litesListCacheEvents as $event) {
            $eventManager->addEventHandler(static::$moduleName, $event, function($data) {
                static::clearCache();
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
    final protected static function getCacheId(array $filter, array $order = [], int $limit = 0, int $offset = 0) : string
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
        return static::$table::getTableName() . '_model_item_cache';
    }

    /**
     * Получение пути к кэшу для одиночных элементов
     *
     * @return string
     */
    protected static function getCachePathForItemsList() : string
    {
        return static::$table::getTableName() . '_model_list_cache';
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
     * Группировка и присоединение полей со связями 1:1 и 1:М
     *
     * @param array $items
     *
     * @return void
     */
    private static function attachJoins(array &$items, array $joinItems) : void
    {
        $itemProps = [];
        // Чистка полученных полей и присоединение связей 1:1
        foreach(static::getJoins() as $join) {
            $columnName = $join->getName();
            $joinType = $join->getJoinType();
            foreach($joinItems as $item) {
                foreach($item as $key => $value) {
                    /*
                     * TODO: Придумать как скипать дефолтные ключи из оригинальной таблицы, чтобы можно было спокойно
                     * использовать названия типа IBLOCK (сейчас нельзя т.к. попадёт, например IBLOCK_ID или IBLOCK_SECTION_ID)
                     */
                    if(strpos($key, $columnName) === false) {
                        continue;
                    }

                    $pureKey = str_replace($columnName, '', $key);
                    if($joinType === 'INNER') {
                        $items[$item['ID']][$columnName][$pureKey] = $value;
                    } else {
                        $itemProps[$item['ID']][$columnName][$pureKey][] = $value;
                    }
                    unset($items[$item['ID']][$key]);
                }
            }
        }

        // Присоединение связей 1:M
        foreach($itemProps as $itemId => $joinValues) {
            foreach($joinValues as $columnName => $columnValues) {
                foreach(current($columnValues) as $index => $value) {
                    // Использование id в качестве ключей элементов join'a, чтобы исключить дубликаты при связи 1:M или M:M
                    if(isset($columnValues['ID'])) {
                        $joinItemId = $columnValues['ID'][$index];
                    }
                    foreach($columnValues as $fieldKey => $fieldValues) {
                        if($joinItemId) {
                            $items[$itemId][$columnName][$joinItemId][$fieldKey] = $columnValues[$fieldKey][$index];
                        } else {
                            $items[$itemId][$columnName][$index][$fieldKey] = $columnValues[$fieldKey][$index];
                        }
                    }
                }
            }
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
