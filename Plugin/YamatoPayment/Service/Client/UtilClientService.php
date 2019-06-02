<?php
/*
 * Copyright(c)2016, Yamato Financial Co.,Ltd. All rights reserved.
 * Copyright(c)2016, Yamato Credit finance Co.,Ltd. All rights reserved.
 */


namespace Plugin\YamatoPayment\Service\Client;

use Eccube\Application;
use Plugin\YamatoPayment\Entity\OrderExtension;
use Plugin\YamatoPayment\Entity\YamatoShippingDelivSlip;
use Plugin\YamatoPayment\Util\CommonUtil;

/**
 * 決済モジュール 決済処理: 各種取引処理
 */
class UtilClientService extends BaseClientService
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
     * 出荷情報登録
     *
     * @param OrderExtension $OrderExtension 注文情報
     * @return bool
     */
    public function doShipmentEntry(OrderExtension $OrderExtension)
    {
        $orderId = $OrderExtension->getOrder()->getId();

        //決済設定情報取得
        $paymentInfo = $this->paymentUtil->getPaymentTypeConfig(
            $OrderExtension->getOrder()->getPayment()->getId()
        );

        //API設定
        $function_div = 'E01';
        $server_url = $this->getApiUrl($function_div);
        //送信キー
        $listSendKey = array(
            'function_div',
            'trader_code',
            'order_no',
            'delivery_service_code',
            'slip_no',
        );

        // 個別パラメタ
        $listParam = array();
        $listParam['function_div'] = $function_div;

        $user_settings = $this->app['yamato_payment.util.plugin']->getSubData();
        $listParam['delivery_service_code'] = $user_settings["user_settings"]["delivery_service_code"];

        // 配送先情報取得
        $YamatoShippings = $this
            ->app['yamato_payment.repository.yamato_shipping_deliv_slip']
            ->findBy(array(
                'order_id' => $orderId,
            ));

        if (empty($YamatoShippings)) {
            if($listParam['delivery_service_code'] == '00') {
                return array(false, array());
            } else {
                // ヤマト以外の場合、出荷登録前の可能性がある
                $YamatoShippings = $this->app['yamato_payment.repository.yamato_shipping_deliv_slip']->getDelivSlipByShippings($OrderExtension->getOrder()->getShippings());
            }
        }

        // 配送先ごとに出荷情報登録処理
        // 処理成功分送り状番号保持用配列
        $listSuccessSlip = array();
        foreach ($YamatoShippings as $YamatoShipping) {
            /** @var YamatoShippingDelivSlip $YamatoShipping */
            // 送り状番号設定
            if($listParam['delivery_service_code'] == '00') {
                $listParam['slip_no'] = $YamatoShipping->getDelivSlipNumber();
            } else {
                $listParam['slip_no'] = $orderId;
            }

            // リクエスト送信
            if (!$this->sendOrderRequest(
                $server_url,
                $listSendKey,
                $OrderExtension->getOrder()->getId(),
                $listParam,
                $paymentInfo)
            ) {
                return array(false, $listSuccessSlip);
            }

            $listSuccessSlip[] = $listParam['slip_no'];

            if($listParam['delivery_service_code'] == '00') {
                //荷物問い合わせURL保持
                $this->lfRegistSlipUrl($YamatoShipping);
            } else {
                // 他社配送の場合
                $this->updateLastDelivSlip($YamatoShipping);
                break;
            }
        }

        //処理成功の場合、注文データの取引状況更新（精算確定待ちへ）
        $this->paymentUtil->updateOrderPayStatus(
            $OrderExtension,
            $this->const['YAMATO_ACTION_STATUS_WAIT_SETTLEMENT']
        );
        return array(true, $listSuccessSlip);
    }

    /**
     * 出荷情報取消
     *
     * @param OrderExtension $OrderExtension 注文情報
     * @return bool
     */
    public function doShipmentCancel(OrderExtension $OrderExtension)
    {
        //決済設定情報取得
        $paymentInfo = $this->paymentUtil->getPaymentTypeConfig(
            $OrderExtension->getOrder()->getPayment()->getId()
        );

        //クレジットカード決済以外は対象外
        $YamatoOrderPayment = $OrderExtension->getYamatoOrderPayment();
        if ($YamatoOrderPayment->getMemo03() != $this->const['YAMATO_PAYID_CREDIT']) {
            $msg = '出荷情報取消に対応していない決済です。';
            $this->setError($msg);
            return false;
        }

        //API設定
        $function_div = 'E02';
        $server_url = $this->getApiUrl($function_div);
        $orderId = $OrderExtension->getOrder()->getId();

        //送信キー
        $listSendKey = array(
            'function_div',
            'trader_code',
            'order_no',
            'slip_no'
        );

        //個別パラメタ
        $listParam = array();
        $listParam['function_div'] = $function_div;
        $user_settings = $this->app['yamato_payment.util.plugin']->getSubData();
        $listParam['delivery_service_code'] = $user_settings["user_settings"]["delivery_service_code"];

        // 配送先情報取得
        $YamatoShippings = $this
            ->app['yamato_payment.repository.yamato_shipping_deliv_slip']
            ->findBy(array(
                'order_id' => $orderId,
            ));
        if (empty($YamatoShippings)) {
            if($listParam['delivery_service_code'] == '00') {
                return false;
            } else {
                // ヤマト以外の場合、出荷登録前の可能性がある
                $YamatoShippings = $this->app['yamato_payment.repository.yamato_shipping_deliv_slip']->getDelivSlipByShippings($OrderExtension->getOrder()->getShippings());
            }
        }

        //配送先ごとに出荷情報取消処理
        foreach ($YamatoShippings as $YamatoShipping) {
            /** @var YamatoShippingDelivSlip $YamatoShipping */
            if($listParam['delivery_service_code'] == '00') {
                $listParam['slip_no'] = $YamatoShipping->getDelivSlipNumber();
            } else {
                $listParam['slip_no'] = $orderId;
            }
            if (!$this->sendOrderRequest(
                $server_url,
                $listSendKey,
                $OrderExtension->getOrder()->getId(),
                $listParam,
                $paymentInfo)
            ) {
                return false;
            }
            if($listParam['delivery_service_code'] != '00') {
                break;
            }
        }

        //処理成功の場合、注文データの取引状況更新(与信完了へ)
        $this->paymentUtil->updateOrderPayStatus(
            $OrderExtension,
            $this->const['YAMATO_ACTION_STATUS_COMP_AUTH']
        );
        return true;
    }

    /**
     * 出荷情報登録ロールバック
     *
     * 出荷情報登録処理（複数配送）で失敗した際
     * それまでに成功した登録済送り状番号の取消処理をする
     *
     * @param OrderExtension $OrderExtension 注文情報
     * @param array $listSuccessSlip
     * @return void
     */
    public function doShipmentRollback(OrderExtension $OrderExtension, $listSuccessSlip)
    {
        //成功出荷情報が0件の場合は処理しない
        if (count($listSuccessSlip) === 0) {
            return;
        }

        //決済設定情報取得
        $paymentInfo = $this->paymentUtil->getPaymentTypeConfig(
            $OrderExtension->getOrder()->getPayment()->getId()
        );

        //API設定
        $function_div = 'E02';
        $server_url = $this->getApiUrl($function_div);
        //送信キー
        $listSendKey = array(
            'function_div',
            'trader_code',
            'order_no',
            'slip_no'
        );

        //個別パラメタ
        $listParam = array();
        $listParam['function_div'] = $function_div;

        //配送先ごとの処理
        foreach ($listSuccessSlip as $slip) {
            $listParam['slip_no'] = $slip;
            $this->sendOrderRequest(
                $server_url,
                $listSendKey,
                $OrderExtension->getOrder()->getId(),
                $listParam,
                $paymentInfo
            );
        }
    }

    /**
     * 出荷予定日変更(予約商品購入のみ)
     *
     * @param OrderExtension $OrderExtension 注文情報
     * @return bool
     */
    public function doChangeDate(OrderExtension $OrderExtension)
    {
        $YamatoOrderScheduledShippingDate = $OrderExtension->getYamatoOrderScheduledShippingDate();
        $YamatoOrderPayment = $OrderExtension->getYamatoOrderPayment();

        //クレジットカード決済以外、予約商品未購入注文は対象外
        if ($YamatoOrderPayment->getMemo03() != $this->const['YAMATO_PAYID_CREDIT']
            || !$this->paymentUtil->isReservedOrder($OrderExtension->getOrder())
        ) {
            $msg = '出荷予定日変更に対応していない注文です。';
            $this->setError($msg);
            return false;
        }

        //決済設定情報取得
        $paymentInfo = $this->paymentUtil->getPaymentTypeConfig(
            $OrderExtension->getOrder()->getPayment()->getId()
        );

        //API設定
        $function_div = 'E03';
        $server_url = $this->getApiUrl($function_div);

        //送信キー
        $listSendKey = array(
            'function_div',
            'trader_code',
            'order_no',
            'scheduled_shipping_date'
        );

        //個別パラメタ
        $listParam = array();
        $listParam['function_div'] = $function_div;
        $listParam['scheduled_shipping_date'] = CommonUtil::getFormatedDate(
            $YamatoOrderScheduledShippingDate->getScheduledshippingDate()
        );

        // リクエスト送信
        return $this->sendOrderRequest(
            $server_url,
            $listSendKey,
            $OrderExtension->getOrder()->getId(),
            $listParam,
            $paymentInfo
        );
    }

    /**
     * 決済取消(クレジット決済)
     *
     * @param OrderExtension $OrderExtension 注文情報
     * @return bool
     */
    public function doCreditCancel(OrderExtension $OrderExtension)
    {
        //クレジットカード決済以外は対象外
        $YamatoOrderPayment = $OrderExtension->getYamatoOrderPayment();
        if ($YamatoOrderPayment->getMemo03() != $this->const['YAMATO_PAYID_CREDIT']) {
            $msg = '決済キャンセル・返品エラー：キャンセル・返品処理に対応していない決済です。';
            $this->setError($msg);
            return false;
        }

        //決済設定情報取得
        $paymentInfo = $this->paymentUtil->getPaymentTypeConfig(
            $OrderExtension->getOrder()->getPayment()->getId()
        );

        //API設定
        $function_div = 'A06';
        $server_url = $this->getApiUrl($function_div);

        //送信キー
        $listSendKey = array(
            'function_div',
            'trader_code',
            'order_no'
        );

        //個別パラメタ
        $listParam = array();
        $listParam['function_div'] = $function_div;

        // リクエスト送信
        if (!$this->sendOrderRequest(
            $server_url,
            $listSendKey,
            $OrderExtension->getOrder()->getId(),
            $listParam,
            $paymentInfo)
        ) {
            return false;
        }

        //処理成功の場合、注文データの取引状況更新（取消へ）
        $this->paymentUtil->updateOrderPayStatus(
            $OrderExtension,
            $this->const['YAMATO_ACTION_STATUS_CANCEL']
        );
        return true;
    }

    /**
     * 金額変更
     *
     * @param OrderExtension $OrderExtension 注文情報
     * @return bool
     */
    public function doCreditChangePrice(OrderExtension $OrderExtension)
    {
        //クレジットカード決済以外は対象外
        $YamatoOrderPayment = $OrderExtension->getYamatoOrderPayment();
        if ($YamatoOrderPayment->getMemo03() != $this->const['YAMATO_PAYID_CREDIT']) {
            $msg = '金額変更に対応していない決済です。';
            $this->setError($msg);
            return false;
        }

        //決済設定情報取得
        $paymentInfo = $this->paymentUtil->getPaymentTypeConfig(
            $OrderExtension->getOrder()->getPayment()->getId()
        );

        //API設定
        $function_div = 'A07';
        $server_url = $this->getApiUrl($function_div);

        //送信キー
        $listSendKey = array(
            'function_div',
            'trader_code',
            'order_no',
            'new_price'
        );

        //個別パラメタ
        $listParam = array();
        $listParam['function_div'] = $function_div;

        // リクエスト送信
        if (!$this->sendOrderRequest(
            $server_url,
            $listSendKey,
            $OrderExtension->getOrder()->getId(),
            $listParam,
            $paymentInfo)
        ) {
            return false;
        }

        //処理成功の場合、決済金額の更新をおこなう
        $this->paymentUtil->updateOrderSettlePrice($OrderExtension);

        return true;
    }

    /**
     * 決済状況取得.
     *
     * 対応状況の更新を実行
     * (1)レスポンスの処理結果が0件ではない
     * (2)レスポンスと注文番号が同じ
     * (3)レスポンス値と注文データの決済手段が同じ
     *
     * @param OrderExtension $OrderExtension 注文情報
     * @return bool
     */
    public function doGetTradeInfo(OrderExtension $OrderExtension)
    {
        // 決済設定情報取得
        $paymentInfo = $this->paymentUtil->getPaymentTypeConfig(
            $OrderExtension->getOrder()->getPayment()->getId()
        );

        // API設定
        $function_div = 'E04';
        $server_url = $this->getApiUrl($function_div);

        // 送信キー
        $listSendKey = array(
            'function_div',
            'trader_code',
            'order_no'
        );

        // パラメータ設定
        $listParam = array();
        $listParam['function_div'] = $function_div;

        // リクエスト送信
        if (!$this->sendOrderRequest(
            $server_url,
            $listSendKey,
            $OrderExtension->getOrder()->getId(),
            $listParam,
            $paymentInfo)
        ) {
            return false;
        }

        //レスポンス値取得
        $results = (array)$this->getResults();

        //注文データ更新処理（取引状況）
        return $this->updateOrderInfo($OrderExtension, $results);
    }

    /**
     * 注文データ更新（取引情報照会）
     *
     * 取引情報照会で得られた情報を注文データへ更新する.
     * 条件は以下の通り
     *
     * (1)照会レスポンス結果が0件ではない
     * (2)注文番号が同じ
     * (3)支払方法が同じ
     *
     *
     * 支払い方法は以下情報を利用
     *
     * 【取引情報照会レスポンス】
     * 0:クレジットカード, 1:ネットコンビニ, 2:ネットバンク, 3:電子マネー
     *
     * 【注文データ保持情報】
     * 10:クレジットカード決済
     * 30:コンビニ決済
     * 42～47：電子マネー決済
     * 52：ネットバンク決済
     *
     * @param OrderExtension $OrderExtension 注文情報
     * @param array $results 取引情報照会レスポンス
     * @return boolean
     */
    private function updateOrderInfo(OrderExtension $OrderExtension, $results = array())
    {
        // 受注決済情報取得
        $YamatoOrderPayment = $OrderExtension->getYamatoOrderPayment();

        /*
         * (1)照会レスポンス結果が0件ではない
         * (2)注文番号が同じ
         * (3)支払方法が同じ
         */
        if ($results['resultCount'] > 0
            && $results['resultData']['orderNo'] == $OrderExtension->getOrder()->getId()
            && (($results['resultData']['settleMethodDiv'] == '0'
                    && $YamatoOrderPayment->getMemo03() == $this->const['YAMATO_PAYID_CREDIT'])
                || ($results['resultData']['settleMethodDiv'] == '1'
                    && $YamatoOrderPayment->getMemo03() == $this->const['YAMATO_PAYID_CVS'])
                || ($results['resultData']['settleMethodDiv'] == '2'
                    && $YamatoOrderPayment->getMemo03() == $this->const['YAMATO_PAYID_NETBANK'])
                || ($results['resultData']['settleMethodDiv'] == '3'
                    && $YamatoOrderPayment->getMemo03() >= $this->const['YAMATO_PAYID_EDY']
                    && $YamatoOrderPayment->getMemo03() <= $this->const['YAMATO_PAYID_MOBILEWAON']))
        ) {
            //取引状況更新
            $results['action_status'] = $results['resultData']['statusInfo'];
            //決済ログの記録
            $this->paymentUtil->setOrderPayData($YamatoOrderPayment, $results);
            return true;
        }
        return false;
    }

    /**
     * 決済再与信(クレジット決済)
     *
     * @param OrderExtension $OrderExtension 注文情報
     * @return bool
     */
    public function doReauth(OrderExtension $OrderExtension)
    {
        //クレジットカード決済以外は対象外
        $YamatoOrderPayment = $OrderExtension->getYamatoOrderPayment();
        if ($YamatoOrderPayment->getMemo03() != $this->const['YAMATO_PAYID_CREDIT']) {
            $msg = '決済再与信エラー：再与信処理に対応していない決済です。';
            $this->setError($msg);
            return false;
        }

        //決済設定情報取得
        $paymentInfo = $this->paymentUtil->getPaymentTypeConfig(
                $OrderExtension->getOrder()->getPayment()->getId()
        );

        //API設定
        $function_div = 'A11';
        $server_url = $this->getApiUrl($function_div);

        //送信キー
        $listSendKey = array(
                'function_div',
                'trader_code',
                'order_no'
        );

        //個別パラメタ
        $listParam = array();
        $listParam['function_div'] = $function_div;

        // リクエスト送信
        if (!$this->sendOrderRequest(
                $server_url,
                $listSendKey,
                $OrderExtension->getOrder()->getId(),
                $listParam,
                $paymentInfo)
        ) {
            return false;
        }

        //処理成功の場合、注文データの取引状況更新（与信完了へ）
        $this->paymentUtil->updateOrderPayStatus(
                $OrderExtension,
                $this->const['YAMATO_ACTION_STATUS_COMP_AUTH']
        );
        return true;
    }

    /**
     * 荷物問い合わせURL更新
     *
     * 出荷情報登録レスポンスで取得した荷物問い合わせURLをDBに保持
     *
     * @param YamatoShippingDelivSlip $YamatoShipping
     * @return void
     */
    private function lfRegistSlipUrl($YamatoShipping)
    {
        $listResults = $this->getResults();
        $YamatoShipping->setDelivSlipUrl($listResults['slipUrlPc']);
        // 更新
        $this->app['orm.em']->persist($YamatoShipping);
        $this->app['orm.em']->flush();
    }

    /**
     * 送信に成功した送り状番号を登録
     *
     * @param YamatoShippingDelivSlip $YamatoShipping
     * @return void
     */
    private function updateLastDelivSlip($YamatoShipping)
    {
        $YamatoShipping->setLastDelivSlipNumber($YamatoShipping->getDelivSlipNumber());
        // 更新
        $this->app['orm.em']->persist($YamatoShipping);
        $this->app['orm.em']->flush();
    }

    /**
     * グローバルＩＰアドレス照会
     *
     * @return false|strings
     */
    public function doGetGlobalIpAddress()
    {
        // API設定
        $function_div = 'H01';
        $server_url = $this->getApiUrl($function_div);

        // 送信キー
        $listSendKey = array(
                'function_div',
                'trader_code'
        );

        // パラメータ設定
        $listParam = array();
        $listParam['function_div'] = $function_div;

        $sendData = $this->getSendData($listSendKey, $listParam);

        $ret = $this->sendRequest($server_url, $sendData);

        // リクエスト送信
        if ($ret == false) {
            return false;
        }

        //レスポンス値取得
        $results = (array)$this->getResults();

        //注文データ更新処理（取引状況）
        return $results['ipAddress'];
    }

}
