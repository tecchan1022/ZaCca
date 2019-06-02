<?php
/*
 * Copyright(c)2016, Yamato Financial Co.,Ltd. All rights reserved.
 * Copyright(c)2016, Yamato Credit finance Co.,Ltd. All rights reserved.
 */


namespace Plugin\YamatoPayment\Entity;

use Eccube\Entity\AbstractEntity;

/**
 * ヤマト決済 受注出荷予定日情報 エンティティクラス
 */
class YamatoOrderScheduledShippingDate extends AbstractEntity
{
    /**
     * @var integer
     */
    private $id;
    
    /**
     * @var \datetime
     */
    private $scheduled_shipping_date;

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
     * @param string $scheduled_shipping_date YYYYMMDD
     * @return $this
     */
    public function setScheduledshippingDate($scheduled_shipping_date)
    {
        $this->scheduled_shipping_date = $scheduled_shipping_date;

        return $this;
    }

    /**
     * @return string YYYYMMDD
     */
    public function getScheduledshippingDate()
    {
        return $this->scheduled_shipping_date;
    }

}
