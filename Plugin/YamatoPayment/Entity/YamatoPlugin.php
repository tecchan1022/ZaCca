<?php
/*
 * Copyright(c)2016, Yamato Financial Co.,Ltd. All rights reserved.
 * Copyright(c)2016, Yamato Credit finance Co.,Ltd. All rights reserved.
 */


namespace Plugin\YamatoPayment\Entity;

use Eccube\Common\Constant;
use Eccube\Entity\AbstractEntity;
use Plugin\YamatoPayment\Util\CommonUtil;

class YamatoPlugin extends AbstractEntity
{
    /**
     * @return string
     */
    public function __toString()
    {
        return $this->getName();
    }

    /**
     * @var integer
     */
    private $id;

    /**
     * @var string
     */
    private $code;

    /**
     * @var string
     */
    private $name;

    /**
     * @var string
     */
    private $sub_data;

    /**
     * @var string
     */
    private $b2_data;

    /**
     * @var integer
     */
    private $auto_update_flg;

    /**
     * @var integer
     */
    private $del_flg;

    /**
     * @var \DateTime
     */
    private $create_date;

    /**
     * @var \DateTime
     */
    private $update_date;


    /**
     * コンストラクタ
     */
    public function __construct()
    {
        $this
            ->setAutoUpdateFlg(Constant::DISABLED)
            ->setDelFlg(Constant::DISABLED);
    }

    /**
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param string $code
     * @return $this
     */
    public function setCode($code)
    {
        $this->code = $code;

        return $this;
    }

    /**
     * @return string
     */
    public function getCode()
    {
        return $this->code;
    }

    /**
     * @param string $name
     * @return $this
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param array $data
     * @return $this
     */
    public function setSubData($data)
    {
        $this->sub_data = (!empty($data)) ? serialize($data) : null;

        return $this;
    }

    /**
     * @return array
     */
    public function getSubData()
    {
        $data = CommonUtil::unSerializeData($this->sub_data);
        if (empty($data)) {
            $data = array();
        }
        return $data;
    }

    /**
     * @param array $data
     * @return $this
     */
    public function setB2Data($data)
    {
        $this->b2_data = (!empty($data)) ? serialize($data) : null;

        return $this;
    }

    /**
     * @return array
     */
    public function getB2Data()
    {
        $data = CommonUtil::unSerializeData($this->b2_data);
        if (empty($data)) {
            $data = array();
        }
        return $data;
    }

    /**
     * @param integer $autoUpdateFlg
     * @return $this
     */
    public function setAutoUpdateFlg($autoUpdateFlg)
    {
        $this->auto_update_flg = $autoUpdateFlg;

        return $this;
    }

    /**
     * @return integer
     */
    public function getAutoUpdateFlg()
    {
        return $this->auto_update_flg;
    }

    /**
     * @param integer $delFlg
     * @return $this
     */
    public function setDelFlg($delFlg)
    {
        $this->del_flg = $delFlg;

        return $this;
    }

    /**
     * @return integer
     */
    public function getDelFlg()
    {
        return $this->del_flg;
    }

    /**
     * @param \DateTime $createDate
     * @return $this
     */
    public function setCreateDate($createDate)
    {
        $this->create_date = $createDate;

        return $this;
    }

    /**
     * @return \DateTime
     */
    public function getCreateDate()
    {
        return $this->create_date;
    }

    /**
     * @param \DateTime $updateDate
     * @return $this
     */
    public function setUpdateDate($updateDate)
    {
        $this->update_date = $updateDate;

        return $this;
    }

    /**
     * @return \DateTime
     */
    public function getUpdateDate()
    {
        return $this->update_date;
    }

}
