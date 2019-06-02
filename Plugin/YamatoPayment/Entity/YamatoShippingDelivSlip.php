<?php
/*
 * Copyright(c)2016, Yamato Financial Co.,Ltd. All rights reserved.
 * Copyright(c)2016, Yamato Credit finance Co.,Ltd. All rights reserved.
 */


namespace Plugin\YamatoPayment\Entity;

use Eccube\Entity\AbstractEntity;

/**
 * ヤマト決済 配送伝票番号情報 エンティティクラス
 */
class YamatoShippingDelivSlip extends AbstractEntity
{
    /**
     * @var integer
     */
    private $id;

    /**
     * @var integer
     */
    private $order_id;
    
    /**
     * @var string
     */
    private $deliv_slip_number;

    /**
     * @var string
     */
    private $last_deliv_slip_number;

    /**
     * @var string
     */
    private $deliv_slip_url;

    /**
     * @param integer $id
     * @return $this
     */
    public function setId($id)
    {
        $this->id = $id;

        return $this;
    }

    /**
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param integer $id
     * @return $this
     */
    public function setOrderId($id)
    {
        $this->order_id = $id;

        return $this;
    }

    /**
     * @return integer
     */
    public function getOrderId()
    {
        return $this->order_id;
    }

    /**
     * @param string $number
     * @return $this
     */
    public function setDelivSlipNumber($number)
    {
        $this->deliv_slip_number = $number;

        return $this;
    }

    /**
     * @return string
     */
    public function getDelivSlipNumber()
    {
        return $this->deliv_slip_number;
    }

    /**
     * @param string $number
     * @return $this
     */
    public function setLastDelivSlipNumber($number)
    {
        $this->last_deliv_slip_number = $number;

        return $this;
    }

    /**
     * @return string
     */
    public function getLastDelivSlipNumber()
    {
        return $this->last_deliv_slip_number;
    }

    /**
     * @param string $delivSlipUrl
     * @return $this
     */
    public function setDelivSlipUrl($delivSlipUrl)
    {
        $this->deliv_slip_url = $delivSlipUrl;

        return $this;
    }

    /**
     * @return string
     */
    public function getDelivSlipUrl()
    {
        return $this->deliv_slip_url;
    }

}
