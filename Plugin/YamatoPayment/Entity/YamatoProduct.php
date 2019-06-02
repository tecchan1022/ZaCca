<?php
/*
 * Copyright(c)2016, Yamato Financial Co.,Ltd. All rights reserved.
 * Copyright(c)2016, Yamato Credit finance Co.,Ltd. All rights reserved.
 */


namespace Plugin\YamatoPayment\Entity;

use Eccube\Entity\AbstractEntity;

/**
 * ヤマト決済 商品マスタ追加項目情報 エンティティクラス
 */
class YamatoProduct extends AbstractEntity
{
    /**
     * @var integer 商品ID
     */
    private $id;

    /**
     * @var \datetime 予約商品出荷予定日
     */
    private $reserve_date;

    /**
     * @var String 後払い不可フラグ
     */
    private $not_deferred_flg;

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
     * @param string $reserve_date YYYYMMDD
     * @return $this
     */
    public function setReserveDate($reserve_date)
    {
        $this->reserve_date = $reserve_date;

        return $this;
    }

    /**
     * @return string YYYYMMDD
     */
    public function getReserveDate()
    {
        return $this->reserve_date;
    }

    /**
     * @param bool $not_deferred_flg
     * @return $this
     */
    public function setNotDeferredFlg($not_deferred_flg)
    {
        $this->not_deferred_flg = ($not_deferred_flg) ? 1 : 0;

        return $this;
    }

    /**
     * @return bool
     */
    public function getNotDeferredFlg()
    {
        return ($this->not_deferred_flg == 1) ? true : false;
    }

}
