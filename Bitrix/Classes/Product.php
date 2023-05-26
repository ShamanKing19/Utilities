<?php
namespace App;

class Product
{
    public static string $cachePath = 'products_cache';
    private static string $cachePrefix = 'product_';
    private static string $iblockCode;
    private static int $iblockId;

    /* @var array[] */
    private array $product;

    /* @var array[] */
    private array $props;
    

    public function __construct(int $productId)
    {
        $this->product = $this->getFromDb($productId);
        if($this->product && $this->product['PROPERTIES']) {
            $this->props = $this->product['PROPERTIES'];
        }
    }


    /**
     * Получение всех полей товара в виде массива
     * @return array|array[]
     */
    public function toArray() : array
    {
        return $this->product;
    }


    /**
     * Получение id товара
     * @return int
     */
    public function getId() : int
    {
        return (int)$this->product['ID'];
    }


    /**
     * Получение id раздела, в котором находится товар
     * @return int
     */
    public function getSectionId() : int
    {
        return (int)$this->product['IBLOCK_SECTION_ID'];
    }


    /**
     * Получение названия товара
     * @return string
     */
    public function getName() : string
    {
        return $this->product['NAME'];
    }


    /**
     * Получение ссылки на товар
     * @return string
     */
    public function getLink() : string
    {
        return $this->product['DETAIL_PAGE_URL'];
    }


    /**
     * Получение превью картинки товара
     *
     * @param int $width ширина
     * @param int $height длина
     * @return array
     */
    #[ArrayShape([
        'SRC' => 'string',
        'WIDTH' => 'string',
        'HEIGHT' => 'string'
    ])]
    public function getPreviewImage(int $width = 0, int $height = 0): array
    {
        $imageId = (int)$this->product['PREVIEW_PICTURE'];
        if(empty($imageId) && $this->product['DETAIL_PICTURE']) {
            return $this->getDetailImage($width, $height);
        }
        if(empty($imageId)) {
            return [
                'SRC' => '',
                'WIDTH' => $width,
                'HEIGHT' => $height
            ];
        }

        if($width === 0 || $height === 0) {
            $image = \App\File::getById($imageId); // Если нет \App\File, то можно CFile::getPath($imageId);
            return [
                'SRC' => $image['SRC'],
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
     * @return array
     */
    #[ArrayShape([
        'SRC' => 'string',
        'WIDTH' => 'string',
        'HEIGHT' => 'string'
    ])]
    public function getDetailImage(int $width = 0, int $height = 0): array
    {
        $imageId = (int)$this->product['DETAIL_PICTURE'];
        if(empty($imageId)) {
            return [
                'SRC' => '',
                'WIDTH' => $width,
                'HEIGHT' => $height
            ];
        }

        if($width === 0 || $height === 0) {
            $image = \App\File::getById($imageId); // Если нет \App\File, то можно CFile::getPath($imageId);
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
     * Проверка: есть ли товар в корзине с учётом соглашения, если оно указано
     *
     * @param int $agreementId id соглашения
     * @return bool
     */
    public function isInCart() : bool
    {
        $registry = \Bitrix\Sale\Registry::getInstance(\Bitrix\Sale\Registry::REGISTRY_TYPE_ORDER);
        /** @var Sale\Basket $basketClass */
        $basketClass = $registry->getBasketClassName();
        $basketItem = $basketClass::getList([
            'filter' => [
                'FUSER_ID' => \Bitrix\Sale\Fuser::getId(),
                '=LID' => SITE_ID,
                'ORDER_ID' => null,
                'PRODUCT_ID' => $this->getId()
            ],
            'select' => ['ID']
        ])->fetch();

        return !empty($basketItem);
    }


    /**
     * Проверка: можно ли подписаться на товар
     * @return bool
     */
    public function canSubscribe() : bool
    {
        return $this->product['PRODUCT_INFO']['SUBSCRIBE'] === 'Y';
    }


    /**
     * Проверка: можно ли купить товар, если его нет в наличии
     * @return bool
     */
    public function canPreorder() : bool
    {
        global $USER;
        $userId = $USER->getId();
        return $this->product['PRODUCT_INFO']['CAN_BUY_ZERO'] === 'Y';
    }


    /**
     * Очистка кэша товара
     * @param int $productId id товара
     */
    public static function cleanCache(int $productId) : void
    {
        $cache = new \CPHPCache;
        $cache->Clean(self::getCacheId($productId), \App\Product::$cachePath);
    }


    /**
     * Получение всех полей товара из базы
     *
     * @param int $productId ID товара
     * @return array
     */
    private function getFromDb(int $productId) : array
    {
        \CModule::IncludeModule('iblock');
        if($productId <= 0) {
            return [];
        }

        $cache = new \CPHPCache();
        $cacheTime = 86400;
        $cacheId = self::getCacheId($productId);

        $result = [];
        if($cache->startDataCache($cacheTime, $cacheId, self::$cachePath)) {
            $iblockId = \App\Tools\IBlock::getIdByCode($this->iblockCode);
            $arFilter = [
                'IBLOCK_ID' => $iblockId,
                'ID' => $productId,
            ];
            $productRequest = \CIBlockElement::getList([], $arFilter, false, false, ['*']);
            if($productElement = $productRequest->getNextElement()) {
                $productInfo = \Bitrix\Catalog\ProductTable::getList([
                    'filter' => ['ID' => $productId],
                    'select' => ['*', 'UF_*']
                ])->fetch();

                $fields = $productElement->getFields();
                $props = $productElement->getProperties();
                if($productInfo) {
                    $fields['PRODUCT_INFO'] = $productInfo;
                }

                $fields['PROPERTIES'] = $props;
                $result = $fields;
                $cache->EndDataCache([
                    'arProduct' => $fields
                ]);
            }
        } else {
            $result = $cache->getVars();
            $result = $result['arProduct'];
        }

        return $result;
    }


    /**
     * Получение id кэша для товара
     *
     * @param int $productId id товара
     * @return string
     */
    private static function getCacheId(int $productId) : string
    {
        return self::$cachePrefix . $productId;
    }
}