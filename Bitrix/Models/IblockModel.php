<?php
namespace App\Models;

\Bitrix\Main\Loader::includeModule('iblock');

/**
 * <h1>Модель инфоблока</h2>
 *
 * <h2>Для начала работы нужно</h3>
 * <ol>
 *     <li>Унаследоваться от данного класса</li>
 *     <li>Переопределить "<b>protected static string $iblockCode;</b>"</li>
 *     <li>Если инфоблок является торговым каталогом, можно сразу подгрузить информацию о товаре "<b>protected static bool $addCatalogInfo = true;</b>"</li>
 *     <li>Если инфоблок является торговым каталогом, можно сразу подгрузить информацию об остатках на складе "<b>protected static bool $addStoreInfo = true;</b>"</li>
 *     <li>Если у инфоблока есть торговые предложения, можно сразу подгрузить их "<b>protected static bool $addSkuInfo = true;</b>"</li>
 *     <li>Торговые предложения можно преобразовать к другой модели, указав "<b>protected static bool $skuModel = SomeSkuModel::class;</b>"</li>
 *     <li>Если нужно использовать кэш, то установить  "<b>protected static bool $useCache = true;</b>"</li>
 *     <li>Вызвать "<b>static::registerCacheEvents();</b>" в init.php, если включён кэш</li>
 *     <li>Можно добавить коллбэки, которые будут вызваны при очистке кэша методом <b>static::addClearCacheCallback();</b></li>
 * </ol>
 *
 * <h2>Пагинация</h2>
 * <h3>I. Постраничная</h3>
 * <ol>
 *     <li>Вызываем метод и натягиваем на вёрстку<p><b>static::getPagination();</b> </p></li>
 *     <li>Если есть дефолтный фильтр, например, по региону, устанавливаем его где-нибудь в init.php<p><b>static::setDefaultFilter();</b></p></li>
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
 *         <p><b>data-static::$pageVariable="static::getNextPage()"</b></p>
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

    /** @var bool Добавлять ли к элементам информацию о товарах (только для "Торгового каталога") */
    protected static bool $addCatalogInfo = false;

    /** @var bool Добавлять ли к элементам информацию об остатках на складе (только для "Торгового каталога") */
    protected static bool $addStoreInfo = false;

    /** @var bool Подгружать ли торговые предложения */
    protected static bool $addSkuInfo = false;

    /** @var IblockModel|string Модель, объектами которой должны быть торговые предложения */
    protected static string $skuModel;

    /** @var array Фильтр по умолчанию */
    protected static array $defaultFilter = [];

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

    /** @var string Ошибка при создании записи */
    private static string $creationError;

    /** @var string Папка с загрузками по умолчанию */
    protected static string $uploadDir = 'upload';

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

    /** @var array Информация о товаре */
    protected array $catalogInfo;

    /** @var array Информация о наличии товара на складах */
    protected array $storeAmount;

    /** @var array Торговые предложения */
    protected array $sku;

    /** @var mixed Свойства и значения, которые будут перезаписаны в БД при вызове метода save() */
    private array $propsToUpdate = [];


    protected function __construct(int $id, array $fields, array $props = [], array $catalogInfo = [], array $storeAmount = [], array $sku = [])
    {
        $this->id = $id;
        $this->fields = $fields;
        $this->props = $props;
        if($catalogInfo) {
            $this->catalogInfo = $catalogInfo;
        }
        if($storeAmount) {
            $this->storeAmount = $storeAmount;
        }
        if($sku) {
            $this->sku = $sku;
        }
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
     * Получение названия
     *
     * @return string
     */
    final public function getName() : string
    {
        return $this->fields['NAME'] ?? '';
    }

    /**
     * Получение ссылки на детальную страницу
     *
     * @return string
     */
    final public function getLink() : string
    {
        return $this->fields['DETAIL_PAGE_URL'] ?? '';
    }

    /**
     * Получение превью картинки товара
     *
     * @param int $width ширина
     * @param int $height длина
     *
     * @return array
     */
    public function getPreviewImage(int $width = 0, int $height = 0): array
    {
        $imageId = (int)$this->fields['PREVIEW_PICTURE'];
        if(empty($imageId)) {
            return [
                'SRC' => '',
                'WIDTH' => $width,
                'HEIGHT' => $height
            ];
        }

        if($width === 0 || $height === 0) {
            $image = \Bitrix\Main\FileTable::getById($imageId)->fetch();
            return [
                'SRC' => '/' . implode('/', [static::$uploadDir, $image['SUBDIR'], $image['FILE_NAME']]),
                'WIDTH' => $image['WIDTH'],
                'HEIGHT' => $image['HEIGHT']
            ];
        }

        $image = \CFile::resizeImageGet($imageId, ['width' => $width, 'height' => $height], BX_RESIZE_IMAGE_PROPORTIONAL, true);
        return [
            'SRC' => $image['src'],
            'WIDTH' => $image['width'],
            'HEIGHT' => $image['height']
        ];
    }

    /**
     * Получение детальной картинки товара
     *
     * @param int $width ширина
     * @param int $height длина
     *
     * @return array
     */
    public function getDetailImage(int $width = 0, int $height = 0): array
    {
        $imageId = (int)$this->fields['DETAIL_PICTURE'];
        if(empty($imageId)) {
            return [
                'SRC' => '',
                'WIDTH' => $width,
                'HEIGHT' => $height
            ];
        }

        if($width === 0 || $height === 0) {
            $image = \Bitrix\Main\FileTable::getById($imageId)->fetch();
            return [
                'SRC' => $image['SRC'],
                'WIDTH' => $image['SRC'],
                'HEIGHT' => $image['SRC']
            ];
        }

        $image = \CFile::resizeImageGet($imageId, ['width' => $width, 'height' => $height], BX_RESIZE_IMAGE_PROPORTIONAL, true);
        return [
            'SRC' => $image['src'],
            'WIDTH' => $image['width'],
            'HEIGHT' => $image['height']
        ];
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
        if(isset($this->catalogInfo)) {
            $fields['CATALOG_INFO'] = $this->catalogInfo;
        }
        if(isset($this->storeAmount)) {
            $fields['STORE_INFO'] = $this->storeAmount;
        }

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
     * Получение значения поля
     *
     * @param string $key Символьный код поля
     *
     * @return mixed
     */
    final public function getField(string $key) : mixed
    {
        return $this->fields[$key];
    }

    /**
     * Получение значения свойства
     *
     * @param string $key Символьный код свойства
     *
     * @return mixed
     */
    final public function getProperty(string $key) : mixed
    {
        if(empty($this->props[$key])) {
            return null;
        }

        $property = $this->props[$key];
        if($property['PROPERTY_TYPE'] === 'L') {
            return [
                'VALUE' => $property['VALUE'],
                'VALUE_ENUM' => $property['VALUE_ENUM'],
                'VALUE_ENUM_ID' => $property['VALUE_ENUM_ID'],
                'VALUE_XML_ID' => $property['VALUE_XML_ID'],
            ];
        }

        $unserializedValue = unserialize($property['VALUE']);
        return $unserializedValue === false ? $property['VALUE'] : $unserializedValue;
    }

    /**
     * Установка значения полю
     *
     * @param string $key Символьный код поля
     * @param mixed $value Значение поля
     *
     * @return void
     */
    final public function setField(string $key, $value) : void
    {
        $this->fields[$key] = $value;
    }

    /**
     * Установка значения свойства для последующего обновления в БД
     *
     * @param string $propertyCode Символьный код свойства
     * @param mixed $value Новое значение свойства (Для типа "Список" нужно указать ID(обязательно int), либо XML_ID(обязательно string) значения)
     *
     * @return bool
     */
    final public function setProperty(string $propertyCode, $value) : bool
    {
        $property = $this->props[$propertyCode];
        if($property['PROPERTY_TYPE'] === \Bitrix\Iblock\PropertyTable::TYPE_LIST) {
            return $this->setListProperty($propertyCode, $value);
        }

        if($property['PROPERTY_TYPE'] === \Bitrix\Iblock\PropertyTable::TYPE_FILE) {
            return $this->setFileProperty($propertyCode, $value);
        }

        $this->props[$propertyCode]['VALUE'] = $value;
        $this->propsToUpdate[$propertyCode] = $value;

        return true;
    }

    /**
     * Установка значения свойства типа "Список" для последующего обновления в БД
     *
     * @param string $propertyCode Символьный код свойства
     * @param mixed $value ID(обязательно int), либо XML_ID(обязательно string), а для множественного свойства всё то же, но в массиве
     *
     * @return bool
     */
    final private function setListProperty(string $propertyCode, $value) : bool
    {
        $property = $this->props[$propertyCode];
        $isMultiple = $property['MULTIPLE'] === 'Y';
        $filter = ['PROPERTY_ID' => $property['ID']];
        if($isMultiple) {
            $value = is_array($value) ? $value : [$value];
            if(array_filter($value, static fn($val) => is_numeric($val))) {
                $filter['ID'] = $value;
            } elseif(array_filter($value, static fn($val) => is_string($val))) {
                $filter['XML_ID'] = $value;
            } else {
                return false;
            }

        } elseif(is_numeric($value)) {
            $filter['ID'] = $value;
        } elseif(is_string($value)) {
            $filter['XML_ID'] = $value;
        } else {
            return false;
        }

        $listProperty = \Bitrix\Iblock\PropertyEnumerationTable::getList([
            'filter' => $filter,
            'select' => ['ID', 'VALUE', 'XML_ID']
        ])->fetchAll();
        if(empty($listProperty)) {
            return false;
        }

        if($isMultiple) {
            $this->props[$propertyCode]['VALUE'] = array_column($listProperty, 'VALUE');
            $this->props[$propertyCode]['VALUE_ENUM'] = array_column($listProperty, 'VALUE');
            $this->props[$propertyCode]['VALUE_ENUM_ID'] = array_column($listProperty, 'ID');
            $this->props[$propertyCode]['VALUE_XML_ID'] = array_column($listProperty, 'XML_ID');
            $this->propsToUpdate[$propertyCode] = array_column($listProperty, 'ID');
        } else {
            $this->props[$propertyCode]['VALUE'] = current($listProperty)['VALUE'];
            $this->props[$propertyCode]['VALUE_ENUM'] = current($listProperty)['VALUE_ENUM'];
            $this->props[$propertyCode]['VALUE_ENUM_ID'] = current($listProperty)['VALUE_ENUM_ID'];
            $this->props[$propertyCode]['VALUE_XML_ID'] = current($listProperty)['VALUE_XML_ID'];
            $this->propsToUpdate[$propertyCode] = current($listProperty)['ID'];
        }

        return true;
    }

    /**
     * Установка значения свойства типа "файл" для последующего обновления в БД
     *
     * @param string $propertyCode Символьный код свойства
     * @param int|array<int>|array<array> $value Массив с id файлов, id файла или массив, полученный через \CFile::makeFileArray()
     *
     * @return bool
     */
    final private function setFileProperty(string $propertyCode, $value) : bool
    {
        if(empty($value)) {
            $this->propsToUpdate[$propertyCode] = ['VALUE' => '', 'DESCRIPTION' => ''];
            $this->props[$propertyCode]['VALUE'] = [];
            return true;
        }

        if(is_numeric($value)) {
            $this->propsToUpdate[$propertyCode] = ['VALUE' => \CFile::makeFileArray($value), 'DESCRIPTION' => ''];
            $this->props[$propertyCode]['VALUE'] = $value;
            return true;
        }

        if(is_array($value)) {
            $this->propsToUpdate[$propertyCode] = [];
            $this->props[$propertyCode]['VALUE'] = [];
            foreach($value as $fileId) {
                $fileArray = is_int($fileId) ? \CFile::makeFileArray($fileId) : $fileId;
                if($fileArray) {
                    $this->propsToUpdate[$propertyCode][] = ['VALUE' => $fileArray, 'DESCRIPTION' => ''];
                    $this->props[$propertyCode]['VALUE'][] = $fileId;
                }
            }

            return true;
        }

        return false;
    }

    /**
     * Сохранение объекта в базу, либо обновление, если он уже есть
     * <p><b>Будут обновлены ВСЕ стандартные поля и свойства, установленные через setProperty()</b></p>
     *
     * @return static|bool
     */
    public function save()
    {
        if((int)$this->fields['ID']) {
            return static::updateById((int)$this->fields['ID'], $this->fields, $this->propsToUpdate);
        }

        return static::create($this->fields, $this->props, $this->catalogInfo ?? []);
    }

    /**
     * Обновление полей
     *
     * @param array $fields Стандартные поля
     * @param array $props Свойства элемента инфоблока (ключ - символьный код свойства)
     *
     * @return bool
     */
    public function update(array $fields, array $props = []) : bool
    {
        return static::updateById($this->getId(), $fields, $props);
    }

    /**
     * Удаление элемента
     *
     * @return bool
     */
    public function delete() : bool
    {
        return static::deleteById($this->getId());
    }

    /**
     * Получение информации о товаре
     *
     * @return array
     */
    public function getCatalogInfo() : array
    {
        if(isset($this->catalogInfo)) {
            return $this->catalogInfo;
        }

        return $this->catalogInfo = current(static::getCatalogListInfo([$this->getId()])) ?: [];
    }

    /**
     * Получение информации об остатках на складе
     *
     * @return array<array> Массивы складов со структурой \Bitrix\Catalog\StoreTable и информацией о наличии
     */
    public function getStoreInfo() : array
    {
        if(isset($this->storeAmount)) {
            return $this->storeAmount;
        }

        return $this->storeAmount = current(static::getStoreListInfo([$this->getId()])) ?: [];
    }

    /**
     * Получение торговых предложений
     *
     * @return array<array|IblockModel>
     */
    public function getSkuList() : array
    {
        if(isset($this->sku)) {
            return $this->sku;
        }

        return $this->sku = current(static::getSkuItemsList([$this->getId()])) ?: [];
    }

    /**
     * Получение элемента по id
     *
     * @param int $id ID элемента инфоблока
     *
     * @return static|null
     */
    final public static function find(int $id) : ?static
    {
        if(static::$instanceList[$id]) {
            return static::$instanceList[$id];
        }

        $item = current(static::getList(['ID' => $id]));
        if(empty($item)) {
            return null;
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
        return static::getList($filter, $order, static::$itemsPerPage, static::getCurrentPage());
    }

    /**
     * Получение элементов инфоблока и кеширование выборки
     *
     * @param array $filter Фильтр для \CIBlockElement::getList()
     * @param array $order Сортировка ['KEY_1' => 'ASC', 'KEY_2' => 'DESC']
     * @param int $limit Ограничение выборки
     * @param int $page Страница
     *
     * @return array<static>
     */
    public static function getList(array $filter = [], array $order = [], int $limit = 0, int $page = 0) : array
    {
        if(!static::$useCache) {
            static::getListRaw($filter, $order, $limit, $page);
        }

        $cache = \Bitrix\Main\Data\Cache::createInstance();
        $cacheKey = static::getCacheKey($filter, $order, $limit, $page);
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

        $items = static::getListRaw($filter, $order, $limit, $page);
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
     * @param array $customFilter Фильтр для \CIBlockElement::getList()
     * @param array $order Сортировка ['KEY_1' => 'ASC', 'KEY_2' => 'DESC']
     * @param int $limit Ограничение выборки
     * @param int $page Сдвиг
     *
     * @return array<static>
     */
    protected static function getListRaw(array $customFilter = [], array $order = ['ID' => 'ASC'], int $limit = 0, int $page = 0) : array
    {
        $filter = static::$defaultFilter;
        $filter['IBLOCK_ID'] = static::getIblockId();
        foreach($customFilter as $key => $value) {
            $filter[$key] = $value;
        }

        $navStartParams = [];
        if($limit > 0) {
            $navStartParams['nPageSize'] = $limit;
        }
        if($page) {
            $navStartParams['iNumPage'] = $page;
        }

        $request = \CIBlockElement::getList($order, $filter, false, $navStartParams, ['*']);

        /* Получение полей и свойств */
        $items = [];
        $itemsIdList = [];
        while($item = $request->getNextElement()) {
            $fields = $item->getFields();
            $itemId = $fields['ID'];
            foreach($fields as $key => $field) {
                if(mb_strpos($key, '~') === 0) {
                    unset($fields[$key]);
                }
            }

            $itemsIdList[] = $itemId;
            if(static::$instanceList[$itemId]) {
                continue;
            }

            $items[$itemId] = [
                'FIELDS' => $fields,
                'PROPERTIES' => $item->getProperties()
            ];
        }

        /* Получение информации о товаре */
        if(static::$addCatalogInfo && $itemsIdList) {
            $catalogInfoList = static::getCatalogListInfo($itemsIdList);
            foreach($catalogInfoList as $catalogItem) {
                $items[$catalogItem['ID']]['CATALOG_INFO'] = $catalogItem;
            }

            unset($catalogInfoList);
        }

        /* Получение информации о наличии товара на складах */
        if(static::$addStoreInfo && $itemsIdList) {
            $amountList = static::getStoreListInfo($itemsIdList);
            foreach($itemsIdList as $itemId) {
                $items[$itemId]['STORE_INFO'] = $amountList[$itemId] ?: [];
            }

            unset($amountList);
        }

        /* Получение торговых предложений */
        if(static::$addSkuInfo && $itemsIdList) {
            $allSkuList = static::getSkuItemsList($itemsIdList);
            foreach($allSkuList as $itemId => $skuList) {
                $items[$itemId]['SKU'] = $skuList;
            }

            unset($allSkuList);
        }

        $result = [];
        foreach($itemsIdList as $itemId) {
            $item = $items[$itemId];
            if(empty($item)) {
                $result[$itemId] = static::$instanceList[$itemId];
                continue;
            }

            $instance = new static($itemId, $item['FIELDS'], $item['PROPERTIES'] ?? [], $item['CATALOG_INFO'] ?? [], $item['STORE_INFO'] ?? [], $item['SKU'] ?? []);
            static::$instanceList[$itemId] = $instance;
            $result[$itemId] = $instance;
        }

        unset($items);
        return $result;
    }

    /**
     * Фильтр для выборок по умолчанию
     *
     * @param array $filter
     *
     * @return void
     */
    public static function setDefaultFilter(array $filter) : void
    {
        static::$defaultFilter = $filter;
    }

    /**
     * Удаление элемента по id
     *
     * @param int $id ID Элемента инфоблока
     *
     * @return bool
     */
    public static function deleteById(int $id) : bool
    {
        return \CIBlockElement::delete($id);
    }

    /**
     * Обновление элемента инфоблока
     * TODO: Добавить возможность обновления значений товара
     *
     * @param int $id ID элемента инфоблока
     * @param array $fields Стандартные поля
     * @param array $props Свойства элемента инфоблока (ключ - символьный код свойства)
     *
     * @return bool
     */
    public static function updateById(int $id, array $fields, array $props = []) : bool
    {
        $element = new \CIBlockElement();
        if($props) {
            $iblockId = static::getIblockId();
            \CIBlockElement::setPropertyValuesEx($id, $iblockId, $props);
        }

        return $element->update($id, $fields);
    }

    /**
     * Создание элемента инфоблока
     *
     * @param array $fields Стандартные поля элемента инфоблока
     * @param array $props Свойства элемента инфоблока (ключ - символьный код свойства) ['KEY' => 'VALUE']]
     * @param array $catalogInfo Свойства товара // TODO: Implement
     *
     *
     * @return static|false
     *
     * @throws \Exception
     */
    public static function create(array $fields, array $props = [], array $catalogInfo = [])
    {
        if(empty($fields['NAME'])) {
            throw new \Exception('Не указано название элемента');
        }

        $element = new \CIBlockElement();
        $iblockId = static::getIblockId();
        $fields['IBLOCK_ID'] = $iblockId;

        if(empty($fields['CODE'])) {
            $fields['CODE'] = $element->generateMnemonicCode($fields['NAME'], $iblockId);
        }
        if($props) {
            $fields['PROPERTY_VALUES'] = $props;
        }

        $elementId = $element->add($fields);
        if(empty($elementId)) {
            static::$creationError = $element->LAST_ERROR;
            return false;
        }

        return static::find($elementId);
    }

    /**
     * Получение последней ошибки при создании элемента
     *
     * @return string
     */
    public static function getCreationError() : string
    {
        if(isset(static::$creationError)) {
            return static::$creationError;
        }

        return '';
    }

    /**
     * Создание объекта модели
     *
     * @param array $fields Стандартные поля элемента инфоблока
     * @param array $props Свойства элемента инфоблока со структурой \CIBlockElement::getList()->getNextElement()->getProperties();
     * @param array $catalogInfo Свойства товара
     * @param array $storeAmount Наличие товара на складах
     *
     * @return static
     */
    public static function makeInstance(array $fields, array $props = [], array $catalogInfo = [], array $storeAmount = [])
    {
        return new static((int)$fields['ID'], $fields, $props, $catalogInfo, $storeAmount);
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
        return array_map(static function($fields) {
            $itemId = $fields['ID'];
            if(static::$instanceList[$itemId]) {
                return static::$instanceList[$itemId];
            }

            $props = $fields['PROPERTIES'];
            unset($fields['PROPERTIES']);

            if($fields['CATALOG_INFO']) {
                unset($fields['CATALOG_INFO']);
            }

            if($fields['STORE_INFO']) {
                unset($fields['STORE_INFO']);
            }

            if($fields['SKU']) {
                unset($fields['SKU']);
            }

            return static::$instanceList[$itemId] = static::makeInstance($fields, $props);
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
        $currentPage = static::getCurrentPage();
        $lastPageNumber = static::getLastPage();
        $currentUri = $GLOBALS['APPLICATION']->GetCurUri();
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
     * @param array $customFilter
     *
     * @return int
     */
    final public static function getItemsCount(array $customFilter = []) : int
    {
        $filter = static::$defaultFilter;
        $filter['IBLOCK_ID'] = static::getIblockId();
        foreach($customFilter as $key => $value) {
            $filter[$key] = $value;
        }
        
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
     * Инициализация js для функционала "Показать ещё"
     *
     * @return void
     */
    final public static function initShowMoreButton() : void
    {
        \CJSCore::Init(['jquery2']);
        $lastPage = static::getLastPage();
        $buttonClass = static::$showMoreButtonClass;
        $wrapperClass = static::$showMoreWrapperClass;
        $itemClass = static::$showMoreItemClass;
        $pageVariable = static::$pageVariable;

        echo "
            <script>
            const itemsList = new ItemsList($lastPage);
            itemsList.initShowMoreButton(
                '$wrapperClass',
                '$itemClass',
                '$buttonClass',
                '$pageVariable',
                '$pageVariable'
            );
            </script>
        ";
    }

    /**
     *
     * Значения свойств для фильтра
     *
     */

    /**
     * Получение списка свойств и всех существующих значений
     *
     * @param array $filter
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
        if(!$connection->isTableExists($tableName)) {
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
        if(!$connection->isTableExists($tableName)) {
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
     * Получение информации о товаре
     *
     */

    /**
     * Получение информации о товарах
     *
     * @param array $productIdList
     *
     * @return array
     *
     * <pre>
     * [
     *     PRODUCT_ID_1 => Массив со структурой таблицы \Bitrix\Catalog\ProductTable,
     *     PRODUCT_ID_2 => ...
     * ]
     * </pre>
     */
    final public static function getCatalogListInfo(array $productIdList = []) : array
    {
        $params = [];
        if($productIdList) {
            $params['filter'] = ['ID' => $productIdList];
        }

        $request = \Bitrix\Catalog\ProductTable::getList($params);

        $items = [];
        while($item = $request->fetch()) {
            $items[$item['ID']] = $item;
        }

        return $items;
    }

    /**
     * Получение информации о наличии товара на складах
     *
     * @param array $productIdList
     *
     * @return array
     *
     * <pre>
     * [
     *     PRODUCT_ID_1 => [
     *         STORE_ID_1 => [Массив со структурой таблицы \Bitrix\Catalog\StoreTable],
     *         STORE_ID_2 => [Массив со структурой таблицы \Bitrix\Catalog\StoreTable],
     *         STORE_ID_3 => [Массив со структурой таблицы \Bitrix\Catalog\StoreTable]
     *     ],
     *     PRODUCT_ID_2 => [...]
     * ]
     * </pre>
     */
    final public static function getStoreListInfo(array $productIdList) : array
    {
        $storeRequest = \Bitrix\Catalog\StoreTable::getList([
            'filter' => ['ACTIVE' => 'Y'],
            'select' => ['ID', 'NAME' => 'TITLE', 'CODE', 'ACTIVE', 'ADDRESS', 'DESCRIPTION', 'XML_ID', 'IS_DEFAULT']
        ]);

        $amountList = [];
        while($store = $storeRequest->fetch()) {
            $store['QUANTITY'] = 0;
            $store['RESERVED'] = 0;
            foreach($productIdList as $productId) {
                $amountList[$productId][$store['ID']] = $store;
            }
        }

        $amountRequest = \Bitrix\Catalog\StoreProductTable::getList(['filter' => ['PRODUCT_ID' => $productIdList]]);
        while($amount = $amountRequest->fetch()) {
            $amountList[$amount['PRODUCT_ID']][$amount['STORE_ID']]['QUANTITY'] = $amount['AMOUNT'];
            $amountList[$amount['PRODUCT_ID']][$amount['STORE_ID']]['RESERVED'] = $amount['QUANTITY_RESERVED'];
        }

        return $amountList;
    }

    /**
     * Получение торговых предложений
     *
     * @param array $productIdList
     *
     * @return array<array> [itemId => [$sku1, $sku2, ...]]
     */
    final public static function getSkuItemsList(array $productIdList) : array
    {
        $skuListRaw = \CCatalogSKU::getOffersList($productIdList, static::getIblockId(), [], ['ID', 'PARENT_ID']); // Здесь есть PARENT_ID
        if(empty($skuListRaw)) {
            return [];
        }

        $productSkuMap = [];
        $skuIdList = [];
        foreach($skuListRaw as $skuItemList) {
            foreach($skuItemList as $sku) {
                $skuIdList[] = $sku['ID'];
                $productSkuMap[$sku['ID']] = $sku['PARENT_ID'];
            }
        }

        $skuRequest = \CIBlockElement::getList([], ['ID' => $skuIdList], false, false, ['*']);
        $itemsList = [];
        while($sku = $skuRequest->getNextElement()) {
            $fields = $sku->getFields();
            $props = $sku->getProperties();

            $itemId = $productSkuMap[$fields['ID']];
            if(isset(static::$skuModel)) {
                $itemsList[$itemId][$fields['ID']] = static::$skuModel::makeInstance($fields, $props);
            } else {
                $fields['PROPERTIES'] = $props;
                $itemsList[$itemId][$fields['ID']] = $fields;
            }
        }

        return $itemsList;
    }

    /**
     *
     * Реализация интерфейса ArrayAccess
     *
     */

    public function offsetSet($offset, $value)
    {
        if(is_null($offset)) {
            $this->fields[] = $value;
        } else {
            $this->fields[$offset] = $value;
        }
    }

    public function offsetExists($offset)
    {
        return isset($this->fields[$offset]);
    }

    public function offsetUnset($offset)
    {
        unset($this->fields[$offset]);
    }

    public function offsetGet($offset)
    {
        return $this->fields[$offset] ?? $this->props[$offset] ?? null;
    }
}