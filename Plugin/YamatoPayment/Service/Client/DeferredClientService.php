<?php
/*
 * Copyright(c)2016, Yamato Financial Co.,Ltd. All rights reserved.
 * Copyright(c)2016, Yamato Credit finance Co.,Ltd. All rights reserved.
 */


namespace Plugin\YamatoPayment\Service\Client;

use Eccube\Application;
use Plugin\YamatoPayment\Entity\OrderExtension;
use Plugin\YamatoPayment\Entity\PaymentExtension;

/**
 * 決済モジュール 決済処理: 後払い
 */
class DeferredClientService extends BaseClientService
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
     * 決済実行を行う.
     *
     * @param OrderExtension $OrderExtension
     * @param array $listParam
     * @param PaymentExtension $paymentInfo
     * @return bool
     */
    public function doPaymentRequest(OrderExtension $OrderExtension, array $listParam, PaymentExtension $paymentInfo)
    {
        //API設定
        $server_url = $this->getApiUrl('KAARA0010APIAction');

        return $this->lfSendOrderRequest(
            $server_url,
            $OrderExtension,
            $listParam,
            $paymentInfo
        );
    }

    /**
     * 注文情報リクエスト
     *
     * @param string $url API URL
     * @param OrderExtension $OrderExtension 注文情報
     * @param array $listParam その他パラメタ
     * @param PaymentExtension $paymentInfo 支払方法設定
     * @return bool
     */
    protected function lfSendOrderRequest(
        $url,
        OrderExtension $OrderExtension,
        $listParam,
        PaymentExtension $paymentInfo
    ) {
        //リクエスト送信
        $ret = $this->sendRequest($url, $listParam);
        if ($ret) {
            $results = (array)$this->getResults();
        } else {
            $results = array();
            $results['error'] = $this->getError();
        }

        //決済情報設定
        $results['order_no'] = $listParam['orderNo'];

        //審査結果
        if (isset($results['result']) && !is_null($results['result'])) {
            $results['result_code'] = $results['result'];

            //取引状況
            if ($results['result'] == $this->const['DEFERRED_AVAILABLE']) {
                $results['action_status'] = $this->const['DEFERRED_STATUS_AUTH_OK'];
            }
        }
        //決済金額総計
        if (isset($listParam['totalAmount']) && !is_null($listParam['totalAmount'])) {
            $results['totalAmount'] = $listParam['totalAmount'];
        }

        //決済ログの記録
        $YamatoOrderPayment = $this->paymentUtil->setOrderPayData($OrderExtension->getYamatoOrderPayment(), $results);

        //審査結果判定
        if (isset($results['result']) && $results['result'] != '0') {
            //ご利用可以外
            return false;
        }

        if (!empty($this->error)) {
            return false;
        }
        // 成功時のみ表示用データの構築
        $this->paymentUtil->setOrderPaymentViewData($YamatoOrderPayment, $results, $paymentInfo);

        return true;
    }

}
