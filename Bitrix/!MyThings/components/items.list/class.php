<?php if(!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();

class ItemsListComponent extends \CBitrixComponent
{
    /**
     * Общие параметры
     */

    /** @var bool Правильно ли заполнены параметры */
    private bool $validParams = true;

    /** @var int ID инфоблока */
    private int $iblockId;

    /** @var string Символьный код инфоблока */
    private string $iblockCode;

    /** @var array Порядок, в котором должны выбраться элементы */
    private array $order = [];

    /** @var array Фильтр для выборки элементов */
    private array $filter = [];

    /** @var array Поля, которые нужно выбрать */
    private array $select = ['*'];

    /** @var int Ограничение выборки */
    private int $limit = 0;

    /**
     * Пагинация
     */

    /** @var bool Использовать ли пагинацию с числами */
    private bool $useNumberPagination = false;

    /** @var string Переменная, которая будет искаться в запросе для определения текущей страницы */
    private string $pageVariable = 'page';

    /** @var int Количество страниц, отображаемых перед и после текущей */
    private int $pageRange = 4;

    /** @var int номер страницы */
    private int $page = 1;

    /**
     * Пагинация "Показать ещё"
     */

    /** @var bool Использовать ли пагинацию "Показать ещё" */
    private bool $useShowMorePagination = false;

    /** @var string Класс для контейнера с элементами (карточками) */
    private string $itemListClass = 'js-iblock-item__wrapper';

    /** @var string Класс для элемента (карточки) */
    private string $itemClass = 'js-iblock-item';

    /** @var string Класс для кнопки "Показать ещё" */
    private string $showMoreButtonClass = 'js-iblock__show-more';

    /** @var string Название data-атрибута для кнопки "Показать ещё" */
    private string $dataAttributeName = 'page';

    /**
     * Кэширование
     */

    /** @var bool Использовать ли кэш */
    private bool $useCache = false;

    /** @var int Время хранения кэша */
    private int $cacheTime = 86400;

    /**
     * Общие переменные
     */

    /** @var \Bitrix\Main\HttpRequest Запрос, сделанный к текущей странице */
    protected $request;


    public function onPrepareComponentParams($arParams)
    {
        $this->validParams = $this->handleParamErrors($arParams);
        if(!$this->validParams) {
            return $arParams;
        }

        /* Получение инфоблока */
        $iblock = $this->getIblock($arParams['IBLOCK']);
        if(empty($iblock)) {
            ShowError("Инфоблок \"$arParams[IBLOCK]\" не найден");
            $this->validParams = false;
            return $arParams;
        }
        $this->iblockId = $iblock['ID'];
        $this->iblockCode = $iblock['CODE'];

        /* Обработка параметров для выборки */
        $searchParams = $arParams['SEARCH_PARAMS'];
        if($searchParams) {
            $this->order = $searchParams['ORDER'] ?? $this->order;
            $this->filter = $searchParams['FILTER'] ?? $this->filter;
            $this->select = $searchParams['SELECT'] ?? $this->select;
        }

        /* Инфоблок, из которого будут выбираться элементы */
        $this->filter['IBLOCK_ID'] = $this->iblockId;

        /* Пагинация */
        $paginationParams = $arParams['PAGINATION'];
        if($paginationParams) {
            $this->limit = $arParams['PAGINATION']['ITEMS_PER_PAGE'] ?? $this->limit;
            $this->useNumberPagination = $paginationParams['USE_NUMBER_PAGINATION'] ?? $this->useNumberPagination;
            $this->useShowMorePagination = $paginationParams['USE_SHOW_MORE_PAGINATION'] ?? $this->useNumberPagination;
            $this->pageVariable = $paginationParams['PAGE_VARIABLE'] ?: $this->pageVariable;
            $this->page = (int)$paginationParams['PAGE'] ?: $this->page;
        }

        /* Кэширование */
        $cacheParams = $arParams['CACHE'];
        if($cacheParams) {
            $this->useCache = $cacheParams['ENABLED'] ?? $this->useCache;
        }

        /* Объект запроса */
        $this->request = \Bitrix\Main\Context::getCurrent()->getRequest();

        return $arParams;
    }

    public function executeComponent()
    {
        if(!$this->validParams) {
            return;
        }

        /* Пагинация с числами */
        $this->page = $this->getPageNumber();
        if($this->useNumberPagination) {
            $this->arResult['PAGINATION'] = $this->getPagination();
        }

        /* Пагинация "Показать ещё" */
        if($this->useShowMorePagination) {
            \Bitrix\Main\Page\Asset::getInstance()->addJs($this->getPath() . '/js/items_list.js');
            \Bitrix\Main\Page\Asset::getInstance()->addCss($this->getPath() . '/css/style.css');
            $this->arResult['PAGINATION']['CURRENT_PAGE'] = $this->page;
            $this->arResult['PAGINATION']['SHOW_MORE'] = [
                'WRAPPER_CLASS' => $this->itemListClass,
                'ITEM_CLASS' => $this->itemClass,
                'BUTTON_CLASS' => $this->showMoreButtonClass,
                'DATA_ATTRIBUTE' => "data-$this->dataAttributeName=" . $this->page + 1
            ];
        }

        $this->arResult['ITEMS'] = $this->useCache ? $this->getItemsFromCache() : $this->getItems();
        $this->includeComponentTemplate();
        $this->runJs();
    }

    /**
     * Получение элементов инфоблока
     *
     * @return array
     */
    private function getItems() : array
    {
        $navStartParams = [];
        if($this->limit > 0) {
            $navStartParams['nTopCount'] = $this->limit;
        }
        if($this->page > 1 && $this->limit) {
            $navStartParams['nOffset'] = $this->page * $this->limit;
        }

        $request = \CIBlockElement::getList(
            $this->order,
            $this->filter,
            false,
            $navStartParams,
            $this->select
        );

        $items = [];
        while($item = $request->getNextElement()) {
            $fields = $item->getFields();
            $fields['PROPERTIES'] = $item->getProperties();
            $items[$fields['ID']] = $fields;
        }

        return $items;
    }

    /**
     * Прогрев кэша
     */
    private function warmupCache() : void
    {
        ini_set('memory_limit', '-1');
        ini_set('max_execution_time', 0);
        set_time_limit(0);

        $pagination = $this->getPagination();
        $lastPageNumber = $pagination['LAST_PAGE']['NUMBER'];
        for($page = 1; $page <= $lastPageNumber; $page++) {
            $this->page = $page;
            $this->getItemsFromCache();
        }
    }

    /**
     * Очистка кэша
     */
    private function clearCache()
    {
        $cache = \Bitrix\Main\Data\Cache::createInstance();
        $cachePath = $this->getCacheDir();
        $cache->cleanDir($cachePath);
    }

    /**
     * Получение элементов инфоблока из кэша, а если его нет, то получить и сохранить в кэш
     *
     * @return array
     */
    private function getItemsFromCache() : array
    {
        $cache = \Bitrix\Main\Data\Cache::createInstance();
        $cacheKey = $this->getCacheKey();
        $cachePath = $this->getCacheDir();

        if($cache->initCache($this->cacheTime, $cacheKey, $cachePath)) {
            return $cache->getVars() ?? [];
        }

        $items = $this->getItems();
        if(empty($items)) {
            return [];
        }

        $cache->startDataCache();
        $cache->endDataCache($items);
        return $items;
    }

    /**
     * Формирование массива для пагинации
     *
     * @return array
     */
    private function getPagination() : array
    {
        global $APPLICATION;
        $currentUri = $APPLICATION->GetCurUri();
        $uri = new \Bitrix\Main\Web\Uri($currentUri);
        $elementCount = $this->getIblockItemsCount();
        $lastPageNumber = (int)ceil($elementCount / ($this->limit ?: 1));

        // Редирект на последнюю страницу, если текущая страница больше последней
        if($this->page > $lastPageNumber) {
            LocalRedirect($uri->addParams(['page' => $lastPageNumber])->getPathQuery());
        }

        return [
            'CURRENT_PAGE' => $this->page,
            'ITEMS_PER_PAGE' => $this->limit,
            'ITEMS_COUNT' => $elementCount,
            'FIRST_PAGE' => [
                'IS_CURRENT' => $this->page === 1,
                'NUMBER' => 1,
                'URL' => $uri->addParams(['page' => 1])->getPathQuery()
            ],
            'LAST_PAGE' => [
                'IS_CURRENT' => $this->page === $lastPageNumber,
                'NUMBER' => $lastPageNumber,
                'URL' => $uri->addParams(['page' => $lastPageNumber])->getPathQuery()
            ],
            'ITEMS' => $this->getPaginationItems($uri->getPath(), $lastPageNumber)
        ];
    }

    /**
     * Получение массива со страницами
     *
     * @param string $basePath Ссылка, к которой будет добавляться параметр с номером страницы
     * @param int $lastPageNumber Номер последней страницы
     *
     * @return array
     */
    private function getPaginationItems(string $basePath, int $lastPageNumber) : array
    {
        $uri = new \Bitrix\Main\Web\Uri($basePath);
        $currentPage = [
            'IS_CURRENT' => true,
            'NUMBER' => $this->page,
            'URL' => $uri->addParams(['page' => $this->page])->getPathQuery()
        ];

        $previousPageList = [];
        $nextPageList = [];
        for($i = 1; $i <= $this->pageRange; $i++) {
            $previousPageNumber = $this->page - $i;
            if($previousPageNumber > 0) {
                $previousPageList[$previousPageNumber] = [
                    'IS_CURRENT' => false,
                    'NUMBER' => $previousPageNumber,
                    'URL' => $uri->addParams(['page' => $previousPageNumber])->getPathQuery()
                ];
            }

            $nextPageNumber = $this->page + $i;
            if($nextPageNumber <= $lastPageNumber) {
                $nextPageList[$nextPageNumber] = [
                    'IS_CURRENT' => false,
                    'NUMBER' => $nextPageNumber,
                    'URL' => $uri->addParams(['page' => $nextPageNumber])->getPathQuery()
                ];
            }
        }

        return array_reverse($previousPageList, true) + [$this->page => $currentPage] + $nextPageList;
    }

    /**
     * Подсчёт количества элементов в инфоблоке
     *
     * @return int
     */
    private function getIblockItemsCount() : int
    {
        return \Bitrix\Iblock\ElementTable::getCount(['IBLOCK_ID' => $this->iblockId], ['ttl' => 86400]);
    }

    /**
     * Получение текущей страницы
     *
     * @return int
     */
    private function getPageNumber() : int
    {
        $pageNumber = (int)$this->request->get($this->pageVariable) ?: (int)$this->request->getPost($this->pageVariable) ?: $this->page;
        return $pageNumber > 0 ? $pageNumber : 1;
    }

    /**
     * Обработчик ошибок заполнения параметров
     *
     * @param array $arParams массив $this->arParams
     *
     * @return bool
     */
    private function handleParamErrors(array $arParams) : bool
    {
        if(!isset($arParams['IBLOCK'])) {
            ShowError('Не указан параметр "IBLOCK". Можно указать ID или символьный код.');
            return false;
        }


        return true;
    }

    /**
     * Получение инфоблока
     *
     * @param int|string $iblockId ID или символьный код инфоблока
     *
     * @return array
     */
    private function getIblock(int|string $iblockId) : array
    {
        $filterVar = is_numeric($iblockId) ? 'ID' : 'CODE';
        $filter[$filterVar] = $iblockId;
        return \Bitrix\Iblock\IblockTable::getList([
            'filter' => $filter,
            'cache' => [
                'ttl' => 86400
            ]
        ])->fetch() ?? [];
    }

    /**
     * Получение id кэша
     *
     * @return string
     */
    private function getCacheKey() : string
    {
        return $this->iblockCode . '_' . serialize($this->filter) . '_' . serialize($this->order) . '_' . $this->limit . '_' . $this->page;
    }

    /**
     * Получение папки, в которой находится кэш
     *
     * @return string
     */
    private function getCacheDir() : string
    {
        return $this->iblockCode . '_items_list_cache';
    }

    /**
     * Запуск JS после подключения шаблона
     */
    private function runJs() : void
    {
    ?>
        <script>
            const itemsList = new ItemsList(<?=$this->arResult['PAGINATION']['CURRENT_PAGE'] ?? 1?>);
            <?php if($this->arResult['PAGINATION']['SHOW_MORE']): ?>
            itemsList.initShowMoreButton(
                '<?=$this->arResult['PAGINATION']['SHOW_MORE']['WRAPPER_CLASS']?>',
                '<?=$this->arResult['PAGINATION']['SHOW_MORE']['ITEM_CLASS']?>',
                '<?=$this->arResult['PAGINATION']['SHOW_MORE']['BUTTON_CLASS']?>',
                '<?=$this->dataAttributeName?>',
                '<?=$this->pageVariable?>'
            );
            <?php endif ?>
    </script>
    <?php
    }
}