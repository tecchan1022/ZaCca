<?php
/*
 * Copyright(c)2016, Yamato Financial Co.,Ltd. All rights reserved.
 * Copyright(c)2016, Yamato Credit finance Co.,Ltd. All rights reserved.
 */


namespace Plugin\YamatoPayment\Entity;

use Eccube\Entity\AbstractEntity;

/**
 * 支払方法データ拡張 クラス
 */
class PaymentExtension extends AbstractEntity
{
    /**
     * @var YamatoPaymentMethod ヤマト支払方法情報
     */
    private $YamatoPaymentMethod;
    
    /**
     * @var integer 支払方法コード
     */
    private $paymentCode;
    
    /**
     * @var array ヤマト支払方法 設定データ[memo05]
     */
    private $paramPaymentConfig;

    /**
     * @param YamatoPaymentMethod $YamatoPaymentMethod
     * @return $this
     */
    public function setYamatoPaymentMethod($YamatoPaymentMethod)
    {
        $this->YamatoPaymentMethod = $YamatoPaymentMethod;

        return $this;
    }

    /**
     * @return YamatoPaymentMethod
     */
    public function getYamatoPaymentMethod()
    {
        return $this->YamatoPaymentMethod;
    }

    /**
     * @param integer $paymentCode
     * @return $this
     */
    public function setPaymentCode($paymentCode)
    {
        $this->paymentCode = $paymentCode;

        return $this;
    }

    /**
     * @return integer
     */
    public function getPaymentCode()
    {
        return $this->paymentCode;
    }

    /**
     * @param array $paramPaymentConfig
     * @return $this
     */
    public function setArrPaymentConfig($paramPaymentConfig)
    {
        $this->paramPaymentConfig = $paramPaymentConfig;

        return $this;
    }

    /**
     * @return array
     */
    public function getArrPaymentConfig()
    {
        return $this->paramPaymentConfig;
    }

}
