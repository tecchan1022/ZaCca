<?php
/*
 * Copyright(c)2016, Yamato Financial Co.,Ltd. All rights reserved.
 * Copyright(c)2016, Yamato Credit finance Co.,Ltd. All rights reserved.
 */


namespace Plugin\YamatoPayment\Entity;

use Eccube\Entity\Order;
use Eccube\Entity\Customer;

/**
 * 受注データ拡張 クラス
 */
class OrderExtension
{
    /**
     * @var Order 受注データ
     */
    private $Order;

    /**
     * @var Customer 会員データ
     */
    private $Customer;

    /**
     * @var YamatoOrderScheduledShippingDate ヤマト受注支払情報
     */
    private $YamatoOrderScheduledShippingDate;

    /**
     * @var YamatoOrderPayment ヤマト受注支払情報
     */
    private $YamatoOrderPayment;

    /**
     * @var integer 受注ID
     */
    private $OrderID;
    
    /**
     * @var array 支払情報
     *   ・以下の配列情報を保持している
     *       - use_securitycd
     *       - enable_customer_regist
     *       - pay_way
     *       - conveni
     */
    private $paramPaymentData;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->paramPaymentData = array();
    }

    /**
     * @return Order
     */
    public function getOrder()
    {
        return $this->Order;
    }

    /**
     * @param Order $order
     * @return $this
     */
    public function setOrder(Order $order)
    {
        $this->Order = $order;
        return $this;
    }

    /**
     * @return Customer
     */
    public function getCustomer()
    {
        return $this->Customer;
    }

    /**
     * @param Customer $customer
     * @return $this
     */
    public function setCustomer(Customer $customer)
    {
        $this->Customer = $customer;
        return $this;
    }

    /**
     * @return YamatoOrderScheduledShippingDate
     */
    public function getYamatoOrderScheduledShippingDate()
    {
        return $this->YamatoOrderScheduledShippingDate;
    }

    /**
     * @param YamatoOrderScheduledShippingDate $YamatoOrderScheduledShippingDate
     * @return $this
     */
    public function setYamatoOrderScheduledShippingDate(YamatoOrderScheduledShippingDate $YamatoOrderScheduledShippingDate)
    {
        $this->YamatoOrderScheduledShippingDate = $YamatoOrderScheduledShippingDate;
        return $this;
    }

    /**
     * @return YamatoOrderPayment
     */
    public function getYamatoOrderPayment()
    {
        return $this->YamatoOrderPayment;
    }

    /**
     * @param YamatoOrderPayment $orderPayment
     * @return $this
     */
    public function setYamatoOrderPayment(YamatoOrderPayment $orderPayment)
    {
        $this->YamatoOrderPayment = $orderPayment;
        return $this;
    }

    /**
     * @return integer
     */
    public function getOrderID()
    {
        return $this->OrderID;
    }

    /**
     * @param integer $OrderID
     * @return $this
     */
    public function setOrderID($OrderID)
    {
        $this->OrderID = $OrderID;
        return $this;
    }

    /**
     * @return array
     */
    public function getPaymentData()
    {
        return $this->paramPaymentData;
    }

    /**
     * @param array $paramPaymentData
     * @return $this
     */
    public function setPaymentData($paramPaymentData)
    {
        $this->paramPaymentData = $paramPaymentData;
        return $this;
    }

}
