<?php

namespace Plugin\GoogleAnalyticsSimpleSetup\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * GoogleAnalyticsSs
 */
class GoogleAnalyticsSs extends \Plugin\GoogleAnalyticsSimpleSetup\Entity\AbstractEntity
{
    /**
     * @var integer
     */
    private $pluginId;

    /**
     * @var string
     */
    private $pluginCode;

    /**
     * @var string
     */
    private $pluginName;

    /**
     * @var string
     */
    private $configData;

    /**
     * @var integer
     */
    private $delFlg;

    /**
     * @var \DateTime
     */
    private $createDate;

    /**
     * @var \DateTime
     */
    private $updateDate;


    /**
     * Get pluginId
     *
     * @return integer 
     */
    public function getPluginId()
    {
        return $this->pluginId;
    }

    /**
     * Set pluginCode
     *
     * @param string $pluginCode
     * @return GoogleAnalyticsSs
     */
    public function setPluginCode($pluginCode)
    {
        $this->pluginCode = $pluginCode;

        return $this;
    }

    /**
     * Get pluginCode
     *
     * @return string 
     */
    public function getPluginCode()
    {
        return $this->pluginCode;
    }

    /**
     * Set pluginName
     *
     * @param string $pluginName
     * @return GoogleAnalyticsSs
     */
    public function setPluginName($pluginName)
    {
        $this->pluginName = $pluginName;

        return $this;
    }

    /**
     * Get pluginName
     *
     * @return string 
     */
    public function getPluginName()
    {
        return $this->pluginName;
    }

    /**
     * Set configData
     *
     * @param string $configData
     * @return GoogleAnalyticsSs
     */
    public function setConfigData($configData)
    {
        $this->configData = $configData;

        return $this;
    }

    /**
     * Get configData
     *
     * @return string 
     */
    public function getConfigData()
    {
        return $this->configData;
    }

    /**
     * Set delFlg
     *
     * @param integer $delFlg
     * @return GoogleAnalyticsSs
     */
    public function setDelFlg($delFlg)
    {
        $this->delFlg = $delFlg;

        return $this;
    }

    /**
     * Get delFlg
     *
     * @return integer 
     */
    public function getDelFlg()
    {
        return $this->delFlg;
    }

    /**
     * Set createDate
     *
     * @param \DateTime $createDate
     * @return GoogleAnalyticsSs
     */
    public function setCreateDate($createDate)
    {
        $this->createDate = $createDate;

        return $this;
    }

    /**
     * Get createDate
     *
     * @return \DateTime 
     */
    public function getCreateDate()
    {
        return $this->createDate;
    }

    /**
     * Set updateDate
     *
     * @param \DateTime $updateDate
     * @return GoogleAnalyticsSs
     */
    public function setUpdateDate($updateDate)
    {
        $this->updateDate = $updateDate;

        return $this;
    }

    /**
     * Get updateDate
     *
     * @return \DateTime 
     */
    public function getUpdateDate()
    {
        return $this->updateDate;
    }
}
