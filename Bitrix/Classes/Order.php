<?php
namespace App;


class Order
{
    private \Bitrix\Sale\Order $order;


    public function __construct(int $orderId) 
    {
        $this->order = self::getById($orderId);
    }


    /**
     * @param $id id заказа
     */
    public function getById(int $id) : \Bitrix\Sale\Order
    {
        return \Bitrix\Sale\Order::load($id);
    }


    /**
     * Формирование ссылки на оплату
     * @return string
     */
    public function getPaymentUrl() : string
    {
        $paymentCollection = $this->order->getPaymentCollection();
        $payment = $paymentCollection[0];
        if(empty($payment)) {
            return '';
        }

        $service = \Bitrix\Sale\PaySystem\Manager::getObjectById($payment->getPaymentSystemId());
        $context = \Bitrix\Main\Application::getInstance()->getContext();
        $service->initiatePay($payment, $context->getRequest());

        $result = $service->initiatePay($payment, $context->getRequest(), \Bitrix\Sale\PaySystem\BaseServiceHandler::STRING);
        $paymentUrl = $result->getPaymentUrl();
        return $paymentUrl ?: '';
    }


    /**
     * Получение доступных платёжных систем
     * @return array
     */
    public static function getPaySystemList()
    {
        $request = \Bitrix\Sale\PaySystem\Manager::getList([
            'filter' => ['!=PAY_SYSTEM_ID' => 1]
        ]);

        $paySystems = [];
        while($paySystem = $request->fetch()) {
            $paySystems[$paySystem['PAY_SYSTEM_ID']] = $paySystem;
        }

        return $paySystems;
    }


    /**
     * Получение количества активных заказов текущего пользователя
     * @return int
     */
    public static function getActiveCount() : int
    {
        return count(self::getActiveList([], ['ID']));
    }


    /**
     * Получение активных заказов текущего пользователя
     *
     * @param array $filter
     * @param array|string[] $select
     * @return array
     */
    public static function getActiveList(array $filter = [], array $select = ['*']) : array
    {
        $filter['!STATUS_ID'] = ['F', 'Z', 'T'];
        $filter['CANCELED'] = 'N';
        return self::getList($filter, $select);
    }


    /**
     * Получение списка заказов текущего пользователя
     *
     * @param array $filter
     * @param array|string[] $select
     * @return array
     */
    public static function getList(array $filter = [], array $select = ['*']) : array
    {
        global $USER;
        $userId = $USER->getId();
        if(!$USER->isAuthorized()) {
            return [];
        }

        $filter['USER_ID'] = $userId;

        $orders = [];
        $request = \Bitrix\Sale\OrderTable::getList([
            'filter' => $filter,
            'select' => $select
        ]);

        while($order = $request->fetch()) {
            $orders[] = $order;
        }

        return $orders;
    }


    /**
     * Получение статусов заказов
     *
     * @param array $excludeIds id заказов, которые нужно исключить
     * @return array
     */
    public static function getStatusList(array $excludeIds = []) : array
    {
        $statusRequest = \Bitrix\Sale\Internals\StatusTable::getList([
            'order' => ['SORT'=>'ASC'],
            'filter' => [
                'LOGIC' => 'OR',
                [
                    '=TYPE' => 'O',
                    '!ID' => $excludeIds
                ]
            ]
        ]);

        $statusLangRequest = \Bitrix\Sale\Internals\StatusLangTable::getList([
            'filter' => [
                'LID' => getCurrentLanguage(),
            ],
        ]);

        $langStatuses = [];
        while ($status = $statusLangRequest->Fetch()) {
            $langStatuses[$status['STATUS_ID']] = $status;
        }

        $statuses = [];
        while ($status = $statusRequest->Fetch()) {
            $status += $langStatuses[$status['ID']];
            $statuses[$status['ID']] = $status;
        }

        return $statuses;
    }


    /**
     * Получение свойств заказа из таблицы
     *
     * @param int $orderId id заказа
     * @return array
     */
    public static function getOrderPropertyList(int $orderId) : array
    {
        $orderPropsRequest = \Bitrix\Sale\Internals\OrderPropsValueTable::getList([
            'filter' => ['ORDER_ID' => $orderId]
        ]);
        $orderProperties = [];
        while ($orderProperty = $orderPropsRequest->fetch()) {
            $orderProperties[$orderProperty['CODE']] = $orderProperty;
        }
        return $orderProperties;
    }

    /**
     * Создание заказа
     * 
     * @param int $personTypeId id типа покупателя (1 - физ. лицо, 2 - юр. лицо)
     * @param string $name Имя покупателя
     * @param string $phone Номер телфона
     * @param string $email Почта покупателя
     * @param string $comment Комментарий к заказу
     * @param array $products Массив с id товара и его количеством [$id => $quantity] 
     * @return array Результат добавления
     */
    public static function create(
        int $personTypeId,
        string $name,
        string $phone,
        string $email,
        string $comment,
        array $products,
        int $storeId,

    ) {
        $basket = \Bitrix\Sale\Basket::create(SITE_ID);
        $productList = $this->getProductsInfo($products);

        foreach($products as $productId => $quantity)
        {
            $productFields = [
                'PRODUCT_ID' => $productId,
                'QUANTITY' => $quantity,
                'NAME' => $productList[$productId]['NAME'],
                'PRICE' => $productList[$productId]['PRICE']['DISCOUNT_PRICE'],
                'PRODUCT_PROVIDER_CLASS' => '\Bitrix\Catalog\Product\CatalogProvider',
            ];

            $item = $basket->createItem("catalog", $productId);
            $item->setFields($productFields);
        }

        global $USER;
        $order = Bitrix\Sale\Order::create(SITE_ID, $USER->GetID());
        $order->setBasket($basket);
        $order->setPersonTypeId($personTypeId);
        $order->setField('USER_DESCRIPTION', $comment);

        $propertyCollection = $order->getPropertyCollection();
        $arProperties = $propertyCollection->getArray();

        // ! Названия кастомный свойств заказаы 
        $propertyValues = [
            'F_STORE' => $storeId,
            'F_EMAIL' => $email,
            'F_PHONE' => $phone,
            'F_FIO' => $name
        ];

        foreach($arProperties['properties'] as $property) {
            $prop = $propertyCollection->getItemByOrderPropertyId($property['ID']);
            $propCode = $prop->getField('CODE');
            $propValue = $propertyValues[$propCode];
            $prop->setValue($propValue);
        }

        return $$order->save();
    }


    /**
     * Получение типов доставки
     */
    public static function getShipmentTypes() : array
    {
        return \Bitrix\Sale\Delivery\Services\Manager::getActiveList();
    }


    /**
     * Получение оплат для заказов
     * @param $orderIdList список id заказов
     */
    public static function getPayments(array $orderIdList) : array
    {
        $payments = [];
        $request = \Bitrix\Sale\Payment::getList([
            'filter' => [
                'ORDER_ID' => $orderIdList,
            ],
        ]);

        while($res = $request->Fetch()) {
            $payments[$res['ID']] = $res;
        }

        return $payments;
    }


    /**
     * Получение информации о свойстве корзины по символьному коду
     * @param $code символьный код свойства заказа
     */
    public static function getOrderPropertyInfo(string $code) : array
    {
        $request = \Bitrix\Sale\Internals\OrderPropsTable::getList([
            'filter' => ['CODE' => $code]
        ]);

        return $request->fetch() ?: []; 
    }
}