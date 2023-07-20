<?php
namespace App\Models;

/**
 * Модель для таблицы, потомка \Bitrix\Main\Entity\DataManager
 * <ol>
 *     <li>Для работы нужно унаследоваться и объявить: <p><b>public static string $table = SomeTable::class</b></p></li>
 *     <li>Если нужно установить "высчитываемый" фильтр, например по id пользователя, то можно вызвать один раз в init.php
 *          <p><b>static::setFilter(['USER_ID' => $USER->getId()])</b></p>
 *     </li>
 *     <li>Можно добавить JOIN (пока что только 1:1) методом
 *          <p><b>static::addJoin()</b></p>
 *     </li>
 *     <li>Для работы кэширования нужно объявить, например:
 *          <p><b>protected static string $moduleName = 'iblock'</b></p>
 *          <p><b>protected static bool $useCache = true</b></p>
 *     <li>Для очистки кэша нужно объявить массив с событиями, по которым он будет очищаться:
 *          <p><b>protected static array $clearCacheEventList = ['OnAfterCrmDealAdd', 'AfterCrmLeadUpdate', ...]</b></p>
 *     </li>
 *     <li>В init.php вызывать метод:
 *          <p><b>static::registerCacheEvents()</b></p>
 *     </li>
 * </ol>
 *
 * <h2>Пагинация</h2>
 * <h3>I. Постраничная</h3>
 * <ol>
 *     <li>Вызываем метод: <p><b>static::getPagination()</b></p></li>
 *     <li>Натягиваем на вёрстку</li>
 * </ol>
 *
 * <h3>II. "Показать ещё"</h3>
 * <ol>
 *     <li>Натягиваем на вёрстку классы:
 *         <p><b>static::$showMoreWrapperClass()</b></p>
 *         <p><b>static::$showMoreItemClass()</b></p>
 *         <p><b>static::$showMoreButtonClass()</b></p>
 *     </li>
 *     <li>На кнопку добавляем:
 *         <p><b>data-\App\Models\Order::$pageVariable="$pagination['NEXT_PAGE']['NUMBER']"</b></p>
 *     </li>
 *     <li>Вызываем метод уже после вёрстки:
 *         <p><b>static::initShowMoreButton()</b></p>
 *     </li>
 * </ol>
 *
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

    /* @var array Дополнительные поля для фильтра в getListRaw (Можно установить с помощью метода static::setFilter()) */
    protected static array $filter = [];

    /* @var array Стандартные поля, которые нужно выбирать в getListRaw */
    protected static array $select = ['*', 'UF_*'];

    /** @var array|string[] Стандартная сортировка */
    protected static array $order = ['ID' => 'ASC'];

    /** @var array Join'ы к таблице */
    protected static array $runtime = [];

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
        if(!static::$useCache) {
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
            $cache->abortDataCache();
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
        foreach(static::$filter as $key => $value) {
            if(!isset($filter[$key])) {
                $filter[$key] = $value;
            }
        }

        foreach(static::$order as $key => $value) {
            if(!isset($order[$key])) {
                $order[$key] = $value;
            }
        }

        /** @var \Bitrix\Main\ORM\Fields\Relations\Reference $join */
        foreach(static::$runtime as $join) {
            $columnName = $join->getName();
            static::$select[$columnName] = $columnName . '.*';
        }

        $params = [
            'filter' => $filter,
            'select' => static::$select,
            'order' => $order,
            'runtime' => static::$runtime
        ];

        /**
         * TODO: С JOIN'ами 1:M И M:M работает неправильно, так что когда есть JOIN с join_type="left" или "right"
         * лучше делать всю выборку, группировать её, а потом уже ограничивать array_splice($items, $offset, $limit)
         */
        if($limit > 0) {
            $params['limit'] = $limit;
        }
        if($offset > 0) {
            $params['offset'] = $offset;
        }

        $request = static::$table::getList($params);
        $items = [];
        while($item = $request->fetch()) {
            $items[$item['ID']] = $item;
        }

        // Группировка полей, полученных через runtime в массив и установка адекватных ключей
        foreach(static::$runtime as $join) {
            $columnName = $join->getName();
            foreach($items as &$item) {
                foreach($item as $key => $value) {
                    if(strpos($key, $columnName) === false) {
                        continue;
                    }

                    $pureKey = str_replace($columnName, '', $key);
                    $item[$columnName][$pureKey] = $value;
                    unset($item[$key]);
                }
            }
        }

        return static::makeInstanceList($items);
    }

    /**
     * Установка фильтра по-умолчанию (Вызывать один раз в init.php)
     *
     * @param array $filter Фильтр для таблицы
     */
    final public static function setFilter(array $filter) : void
    {
        static::$filter = $filter;
    }

    /**
     * Добавление JOIN'ов к запросам (Работает корректно только со связью 1:1)
     *
     * @param string $columnName Название Колонки
     * @param string $tableClass Название класса таблицы
     * @param string $localKey Ключ из этой таблицы, по которому будут присоединяться поля
     * @param string $foreignKey Ключ таблицы, с которой производим JOIN
     * @param string $joinType Тип JOIN'а (inner/left/right)
     *
     * @return void
     */
    final public static function addJoin(string $columnName, string $tableClass, string $localKey, string $foreignKey, string $joinType = 'inner') : void
    {
        static::$runtime[] = new \Bitrix\Main\ORM\Fields\Relations\Reference(
            $columnName,
            $tableClass,
            \Bitrix\Main\ORM\Query\Join::on("this.$localKey", "ref.$foreignKey"),
            ['join_type' => $joinType]
        );
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
        return $pageNumber > 0 ? $pageNumber : 1;
    }

    /**
     * Получение номера последней страницы
     *
     * @return int
     */
    final protected static function getLastPage() : int
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

?>
<style>
    .item-list__hidden {
        display: none !important;
    }
</style>
<script>
    class ItemsList
    {
        hiddenClass = 'item-list__hidden';

        /**
         * @param {Number} currentPage Номер текущей страницы
         * @param {Number} lastPage Номер последней страницы
         */
        constructor(currentPage, lastPage) {
            this.currentPage = currentPage;
            this.lastPage = lastPage;
        }

        /**
         * Инициализация функционала кнопки "Показать ещё"
         *
         * @param wrapperClass Класс контейнера с элементами
         * @param itemClass Класс элемента
         * @param buttonClass Класс кнопки
         * @param pageAttributeName Название data-атрибута, в котором хранится номер следующей страницы
         * @param urlPageParamName Название переменной пагинации в ссылке
         */
        initShowMoreButton(wrapperClass, itemClass, buttonClass, pageAttributeName, urlPageParamName) {
            const wrapper = document.querySelector(`.${wrapperClass}`);
            if(!wrapper) {
                console.error(`Не найден контейнер с классом "${wrapperClass}"`);
                return;
            }

            // TODO: Находить ближайшую кнопку (если будет несколько компонентов на странице, оно сломается)
            const button = document.querySelector(`.${buttonClass}`);
            if(!button) {
                console.error(`Не найдено кнопки с классом "${buttonClass}"`);
                return;
            }

            const items = wrapper.querySelectorAll(`.${itemClass}`);
            if(!items) {
                console.error(`Не найдено элементов с классом "${itemClass}"`);
                return;
            }

            button.addEventListener('click' ,async (e) => {
                button.classList.add(this.hiddenClass);

                const nextPage = Number(button.dataset[pageAttributeName]);
                if(!nextPage) {
                    console.error(`Атрибут "data-${pageAttributeName}" не найден`);
                    return;
                }

                const url = new URL(location.href);
                url.searchParams.set(urlPageParamName, nextPage.toString());
                const response = await this.sendAjax(url.href, {}, 'get');
                if(!response) {
                    return;
                }

                const html = document.createElement('div');
                html.innerHTML = response;

                const nextPageWrapper = html.querySelector(`.${wrapperClass}`);
                if(!nextPageWrapper) {
                    return;
                }

                const nextPageItems = nextPageWrapper.querySelectorAll(`.${itemClass}`);
                if(nextPageItems.length === 0) {
                    return;
                }

                // Подстановка элементов в конец контейнера
                nextPageItems.forEach(item => wrapper.appendChild(item));

                // Перемещение кнопки
                if(nextPage <= this.lastPage) {
                    button.dataset[pageAttributeName] = nextPage + 1;
                    button.classList.remove(this.hiddenClass);
                }
            })
        }

        /**
         * Отправка ajax запроса
         *
         * @param {String} path путь до ajax обработчика
         * @param {Object} data данные
         * @param {String} method тип запроса
         * @return {Promise<*>}
         */
        async sendAjax(path, data, method = 'post') {
            return $.ajax({
                url: path,
                method: method,
                data: data,
                success: function(response) {
                    return response.data;
                },
                error: function(error) {
                    return false;
                }
            });
        }
    }
</script>
