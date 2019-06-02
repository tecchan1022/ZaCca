<?php
/*
 * Copyright(c)2016, Yamato Financial Co.,Ltd. All rights reserved.
 * Copyright(c)2016, Yamato Credit finance Co.,Ltd. All rights reserved.
 */


namespace Plugin\YamatoPayment\Service\Client;

use Eccube\Application;
use Eccube\Entity\Order;
use Plugin\YamatoPayment\Entity\PaymentExtension;

/**
 * 決済モジュール 決済処理: コンビニ決済
 */
class CvsClientService extends BaseClientService
{
    /**
     * コンストラクタ
     *
     * @param Application $app
     */
    public function __construct(Application $app)
    {
        parent::__construct($app);
    }

    /**
     * コンビニ決済を行う.
     *
     * @param Order $Order
     * @param array $listParam
     * @param PaymentExtension $paymentInfo
     * @return bool
     */
    public function doPaymentRequest(Order $Order, $listParam, PaymentExtension $paymentInfo)
    {
        $orderId = $Order->getId();

        // 送信キー
        $sendKey = array(
            'function_div',
            'trader_code',
            'device_div',
            'order_no',
            'goods_name',
            'settle_price',
            'buyer_name_kanji',
            'buyer_name_kana',
            'buyer_tel',
            'buyer_email',
        );

        // コンビニ決済URL
        $function_div = $this->const['CONVENI_FUNCTION_DIV_' . $listParam['cvs']];
        $server_url = $this->getApiUrl($function_div);

        //機能区分
        $listParam['function_div'] = $function_div;
        //決済ステータスを「決済手続き中」で記録
        $listParam['action_status'] = $this->const['YAMATO_ACTION_STATUS_WAIT'];

        $ret = $this->sendOrderRequest($server_url, $sendKey, $orderId, $listParam, $paymentInfo);
        return $ret;
    }

}
