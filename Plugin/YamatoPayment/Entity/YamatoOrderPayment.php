<?php
/*
 * Copyright(c)2016, Yamato Financial Co.,Ltd. All rights reserved.
 * Copyright(c)2016, Yamato Credit finance Co.,Ltd. All rights reserved.
 */


namespace Plugin\YamatoPayment\Entity;

use Eccube\Entity\AbstractEntity;
use Plugin\YamatoPayment\Util\CommonUtil;

/**
 * ヤマト決済 受注支払情報 エンティティクラス
 */
class YamatoOrderPayment extends AbstractEntity
{
    /**
     * @var integer
     */
    private $id;

    /**
     * @var string
     */
    private $memo01;

    /**
     * @var string
     */
    private $memo02;

    /**
     * @var string
     */
    private $memo03;

    /**
     * @var string
     */
    private $memo04;

    /**
     * @var string
     */
    private $memo05;

    /**
     * @var string
     */
    private $memo06;

    /**
     * @var string
     */
    private $memo07;

    /**
     * @var string
     */
    private $memo08;

    /**
     * @var string
     */
    private $memo09;

    /**
     * @var string
     */
    private $memo10;


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
     * @param string $memo01
     * @return $this
     */
    public function setMemo01($memo01)
    {
        $this->memo01 = $memo01;

        return $this;
    }

    /**
     * @return string
     */
    public function getMemo01()
    {
        return $this->memo01;
    }

    /**
     * @param array $data
     * @return $this
     */
    public function setMemo02($data)
    {
        $this->memo02 = (!empty($data)) ? serialize($data) : null;

        return $this;
    }

    /**
     * @return array
     */
    public function getMemo02()
    {
        $data = CommonUtil::unSerializeData($this->memo02);
        if (empty($data)) {
            $data = array();
        }
        return $data;
    }

    /**
     * @param string $memo03
     * @return $this
     */
    public function setMemo03($memo03)
    {
        $this->memo03 = $memo03;

        return $this;
    }

    /**
     * @return string
     */
    public function getMemo03()
    {
        return $this->memo03;
    }

    /**
     * @param string $memo04
     * @return $this
     */
    public function setMemo04($memo04)
    {
        $this->memo04 = $memo04;

        return $this;
    }

    /**
     * @return string
     */
    public function getMemo04()
    {
        return $this->memo04;
    }

    /**
     * @param array $data
     * @return $this
     */
    public function setMemo05($data)
    {
        $this->memo05 = (!empty($data)) ? serialize($data) : null;

        return $this;
    }

    /**
     * @return array
     */
    public function getMemo05()
    {
        $data = CommonUtil::unSerializeData($this->memo05);
        if (empty($data)) {
            $data = array();
        }
        return $data;
    }

    /**
     * @param string $memo06
     * @return $this
     */
    public function setMemo06($memo06)
    {
        $this->memo06 = $memo06;

        return $this;
    }

    /**
     * @return string
     */
    public function getMemo06()
    {
        return $this->memo06;
    }

    /**
     * @param string $memo07
     * @return $this
     */
    public function setMemo07($memo07)
    {
        $this->memo07 = $memo07;

        return $this;
    }

    /**
     * @return string
     */
    public function getMemo07()
    {
        return $this->memo07;
    }

    /**
     * @param string $memo08
     * @return $this
     */
    public function setMemo08($memo08)
    {
        $this->memo08 = $memo08;

        return $this;
    }

    /**
     * @return string
     */
    public function getMemo08()
    {
        return $this->memo08;
    }

    /**
     * @param array $data
     * @return $this
     */
    public function setMemo09($data)
    {
        $this->memo09 = (!empty($data)) ? serialize($data) : null;

        return $this;
    }

    /**
     * @return array
     */
    public function getMemo09()
    {
        $data = CommonUtil::unSerializeData($this->memo09);
        if (empty($data)) {
            $data = array();
        }
        return $data;
    }

    /**
     * @param string $memo10
     * @return $this
     */
    public function setMemo10($memo10)
    {
        $this->memo10 = $memo10;

        return $this;
    }

    /**
     * @return string
     */
    public function getMemo10()
    {
        return $this->memo10;
    }

}
