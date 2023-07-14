<?php
namespace App;

class Order
{
    private \Bitrix\Sale\Order $order;

    /** @var array Поля заказа в виде массива */
    private array $fields;

    /** @var array Свойства заказа */
    private array $props;

    /** @var array Массив со всеми созданными объектами заказа */
    private static array $instanceList = [];


    private function __construct(\Bitrix\Sale\Order $order)
    {
        $this->order = $order;
        $this->fields = $this->order->toArray();
        $this->props = $this->fields['PROPERTIES'] ?? [];
    }

    /**
     * Получение id заказа
     *
     * @return int
     */
    public function getId() : int
    {
        return $this->fields['ID'];
    }

    /**
     * Получение номера заказа
     *
     * @return string
     */
    public function getNumber() : string
    {
        return $this->fields['ACCOUNT_NUMBER'];
    }

    /**
     * Приведение к массиву
     *
     * @return array
     */
    public function toArray() : array
    {
        return $this->fields;
    }

    /**
     * Получение свойств заказа
     *
     * @return array
     */
    public function getProperties() : array
    {
        return $this->props;
    }

    /**
     * Получение стоимости заказа
     *
     * @return float
     */
    public function getPrice() : float
    {
        return (float)$this->fields['PRICE'];
    }

    /**
     * Получение стоимости доставки
     *
     * @return float
     */
    public function getDeliveryPrice() : float
    {
        return (float)$this->fields['PRICE_DELIVERY'];
    }

    /**
     * Проверка: оплачен ли заказ
     *
     * @return bool
     */
    public function isPayed() : bool
    {
        return $this->fields['PAYED'] === 'Y';
    }

    /**
     * Проверка: отменён ли заказ
     *
     * @return bool
     */
    public function isCancelled() : bool
    {
        return $this->fields['CANCELED'] === 'Y';
    }

    /**
     * Получение информации о статусе заказа
     *
     * @return array
     */
    public function getStatus() : array
    {
        $statusId = $this->fields['STATUS_ID'];
        return self::getStatusList(['ID' => $statusId])[$statusId] ?? [];
    }

    /**
     * Получение информации о покупателе
     *
     * @return array
     */
    public function getUser() : array
    {
        $userId = $this->fields['USER_ID'];
        $user = \Bitrix\Main\UserTable::getList(['filter' => ['ID' => $userId], 'select' => ['*', 'UF_*']])->fetch();
        return $user ?? [];
    }

    /**
     * Формирование ссылки на оплату
     *
     * @return string
     */
    public function getPaymentUrl() : string
    {
        $paymentCollection = $this->order->getPaymentCollection();
        /* @var \Bitrix\Sale\Payment $payment */
        $payment = $paymentCollection[0];
        if(empty($payment)) {
            return '';
        }

        $service = \Bitrix\Sale\PaySystem\Manager::getObjectById($payment->getPaymentSystemId());
        $context = \Bitrix\Main\Application::getInstance()->getContext();
        $result = $service->initiatePay($payment, $context->getRequest(), \Bitrix\Sale\PaySystem\BaseServiceHandler::STRING);
        $paymentUrl = $result->getPaymentUrl();
        return $paymentUrl ?: '';
    }

    /**
     * Поиск объекта заказа
     *
     * @param int $id id заказа
     *
     * @return self|false
     */
    public static function find(int $id) : self|false
    {
        if(empty(self::$instanceList[$id])) {
            $order = \Bitrix\Sale\Order::load($id);
            if(is_null($order)) {
                return false;
            }

            self::$instanceList[$id] = new self($order);
        }

        return self::$instanceList[$id];
    }

    /**
     * Получение доступных платёжных систем
     *
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
     *
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
     *
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
     *
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
     *
     * @return array
     */
    public static function getStatusList(array $filter = [], array $excludeIds = []) : array
    {
        $defaultFilter = [
            'LANG.LID' => strtoupper(\Bitrix\Main\Application::getInstance()->getContext()->getLanguage()),
            [
                'LOGIC' => 'OR',
                '=TYPE' => 'O',
                '!ID' => $excludeIds
            ]
        ];
        if($filter) {
            foreach($filter as $key => $value) {
                $defaultFilter[$key] = $value;
            }
        }

        $statusRequest = \Bitrix\Sale\Internals\StatusTable::getList([
            'order' => ['SORT'=>'ASC'],
            'filter' => $defaultFilter,
            'select' => ['*', 'STATUS_ID' => 'LANG.STATUS_ID', 'LID' => 'LANG.LID', 'NAME' => 'LANG.NAME', 'DESCRIPTION' => 'LANG.DESCRIPTION'],
            'runtime' => [
                new \Bitrix\Main\ORM\Fields\Relations\Reference(
                    'LANG',
                    \Bitrix\Sale\Internals\StatusLangTable::class,
                    \Bitrix\Main\ORM\Query\Join::on('this.ID', 'ref.STATUS_ID'),
                )
            ]
        ]);

        $statuses = [];
        while($status = $statusRequest->fetch()) {
            $statuses[$status['ID']] = $status;
        }

        return $statuses;
    }

    /**
     * Получение свойств заказа из таблицы
     *
     * @param int $orderId id заказа
     *
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
     * Получение типов доставки
     */
    public static function getShipmentTypes() : array
    {
        return \Bitrix\Sale\Delivery\Services\Manager::getActiveList();
    }

    /**
     * Получение оплат для заказов
     *
     * @param array $orderIdList список id заказов
     *
     * @return array
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
     *
     * @param string $code символьный код свойства заказа
     */
    public static function getOrderPropertyInfo(string $code) : array
    {
        $request = \Bitrix\Sale\Internals\OrderPropsTable::getList([
            'filter' => ['CODE' => $code]
        ]);

        return $request->fetch() ?: []; 
    }
}