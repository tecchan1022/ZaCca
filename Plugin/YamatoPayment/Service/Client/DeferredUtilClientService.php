<?php
/*
 * Copyright(c)2016, Yamato Financial Co.,Ltd. All rights reserved.
 * Copyright(c)2016, Yamato Credit finance Co.,Ltd. All rights reserved.
 */


namespace Plugin\YamatoPayment\Service\Client;

use Eccube\Application;
use Eccube\Entity\MailHistory;
use Eccube\Entity\Order;
use Plugin\YamatoPayment\Entity\OrderExtension;
use Plugin\YamatoPayment\Entity\PaymentExtension;
use Plugin\YamatoPayment\Entity\YamatoShippingDelivSlip;
use Plugin\YamatoPayment\Util\CommonUtil;

/**
 * 決済モジュール 決済処理: 後払い各種取引処理
 */
class DeferredUtilClientService extends BaseClientService
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
     * @return array (bool リクエスト結果, int 登録成功数, int 登録失敗数)
     */
    public function doShipmentEntry(OrderExtension $OrderExtension)
    {
        //API設定
        $server_url = $this->getApiUrl('KAASL0010APIAction');

        //送信キー
        $listSendKey = array(
            'ycfStrCode',
            'orderNo',
            'paymentNo',
            'processDiv',
            'requestDate',
            'password',
        );

        $user_settings = $this->app['yamato_payment.util.plugin']->getSubData();
        $delivery_service_code = $user_settings["user_settings"]["delivery_service_code"];

        // 配送先情報取得
        $YamatoShippings = $this->app['yamato_payment.repository.yamato_shipping_deliv_slip']
            ->findBy(array(
                'order_id' => $OrderExtension->getOrder()->getId(),
            ));
        if (empty($YamatoShippings)) {
            if($listParam['delivery_service_code'] == '00') {
                return array(false, 0, 0);
            } else {
                // ヤマト以外の場合、出荷登録前の可能性がある
                $YamatoShippings = $this->app['yamato_payment.repository.yamato_shipping_deliv_slip']->getDelivSlipByShippings($OrderExtension->getOrder()->getShippings());
            }
        }

        //登録処理成功カウント
        $success_cnt = 0;
        //配送先ごとに出荷情報登録処理
        /** @var YamatoShippingDelivSlip $YamatoShipping */
        foreach ($YamatoShippings as $YamatoShipping) {
            $last_deliv_slip = $YamatoShipping->getLastDelivSlipNumber();

            //送信成功しているなら再送信しない
            if (!is_null($last_deliv_slip) && $YamatoShipping->getDelivSlipNumber() == $last_deliv_slip) {
                continue;
            }

            // 送信パラメタ取得
            $sendParams = $this->getSendData($listSendKey, array(), $OrderExtension);

            //処理区分が0:新規登録
//            $sendParams['processDiv'] = 0;
//            $sendParams['paymentNo'] = $YamatoShipping->getDelivSlipNumber();
            // 送り状番号設定
            if($delivery_service_code == '00') {
                $sendParams['processDiv'] = 0;
                $sendParams['paymentNo'] = $YamatoShipping->getDelivSlipNumber();
            } else {
                $sendParams['processDiv'] = 2;
                $sendParams['paymentNo'] = $OrderExtension->getOrder()->getId();
            }

            if (!is_null($last_deliv_slip)) {
                //処理区分が1:変更登録
                $sendParams['processDiv'] = 1;
                $sendParams['shipYmd'] = date('Ymd');
                $sendParams['beforePaymentNo'] = $last_deliv_slip;
            }

            // リクエスト送信
            if (!$this->sendRequest($server_url, $sendParams)) {
                return array(false, $success_cnt, (count($YamatoShippings) - $success_cnt));
            }

            //レスポンス値取得
            $results = (array)$this->getResults();
            //受注情報に決済情報をセット
            $this->paymentUtil->setOrderPayData($OrderExtension->getYamatoOrderPayment(), $results);
            //送信に成功した送り状番号を登録
            $this->updateLastDelivSlip($YamatoShipping);
            //登録処理成功カウント
            $success_cnt++;
        }

        //処理成功の場合、注文データの取引状況更新（送り状番号登録済）
        $this->paymentUtil->updateOrderPayStatus(
            $OrderExtension,
            $this->const['DEFERRED_STATUS_REGIST_DELIV_SLIP']
        );
        return array(true, $success_cnt, (count($YamatoShippings) - $success_cnt));
    }

    /**
     * 出荷情報取消
     *
     * @param OrderExtension $OrderExtension 注文情報
     * @return bool
     */
    public function doShipmentCancel(OrderExtension $OrderExtension)
    {
        //API設定
        $server_url = $this->getApiUrl('KAASL0010APIAction');

        //送信キー
        $listSendKey = array(
            'ycfStrCode',
            'orderNo',
            'processDiv',
            'requestDate',
            'password',
        );

        // 送信パラメタ取得
        $sendParams = $this->getSendData($listSendKey, array(), $OrderExtension);
        //処理区分を9:取消
        $sendParams['processDiv'] = 9;

        // リクエスト送信
        if (!$this->sendRequest($server_url, $sendParams)) {
            return false;
        }

        //レスポンス値取得
        $results = (array)$this->getResults();
        $results['action_status'] = $this->const['DEFERRED_STATUS_AUTH_OK'];

        //受注情報に決済情報をセット
        $this->paymentUtil->setOrderPayData($OrderExtension->getYamatoOrderPayment(), $results);

        //送信に成功した送り状番号を削除
        $this->deleteLastDelivSlip($OrderExtension->getOrder()->getId());

        return true;
    }

    /**
     * 決済取消(クロネコ代金後払い)
     *
     * @param OrderExtension $OrderExtension 注文情報
     * @return bool
     */
    public function doCancel(OrderExtension $OrderExtension)
    {
        // 後払い決済以外は対象外
        $YamatoOrderPayment = $OrderExtension->getYamatoOrderPayment();
        if ($YamatoOrderPayment->getMemo03() != $this->const['YAMATO_PAYID_DEFERRED']) {
            $msg = '与信取消エラー：与信取消に対応していない決済です。';
            $this->setError($msg);
            return false;
        }

        //API設定
        $server_url = $this->getApiUrl('KAACL0010APIAction');

        //送信キー
        $listSendKey = array(
            'ycfStrCode',
            'orderNo',
            'requestDate',
            'password'
        );

        // 送信パラメタ取得
        $sendParams = $this->getSendData($listSendKey, array(), $OrderExtension);

        // リクエスト送信
        if (!$this->sendRequest($server_url, $sendParams)) {
            return false;
        }

        // レスポンス値取得
        $results = (array)$this->getResults();
        $results['action_status'] = $this->const['DEFERRED_STATUS_AUTH_CANCEL'];
        $results['requestDate'] = $results['returnDate'];

        // 受注情報に決済情報をセット
        $this->paymentUtil->setOrderPayData($OrderExtension->getYamatoOrderPayment(), $results);

        return true;
    }

    /**
     * 与信結果取得.
     *
     * @param OrderExtension $OrderExtension 注文情報
     * @return bool
     */
    public function doGetAuthResult(OrderExtension $OrderExtension)
    {
        // API設定
        $server_url = $this->getApiUrl('KAARS0010APIAction');

        //送信キー
        $listSendKey = array(
            'ycfStrCode',
            'orderNo',
            'requestDate',
            'password'
        );

        // 送信パラメタ取得
        $sendParams = $this->getSendData($listSendKey, array(), $OrderExtension);

        // リクエスト送信
        if (!$this->sendRequest($server_url, $sendParams)) {
            return false;
        }

        // レスポンス値取得
        $results = (array)$this->getResults();
        if (isset($results['result']) && !is_null($results['result'])) {
            $results['action_status'] = $results['result'];
        }

        // 受注情報に決済情報をセット
        $this->paymentUtil->setOrderPayData($OrderExtension->getYamatoOrderPayment(), $results);

        return true;
    }

    /**
     * 取引状況取得.
     *
     * @param OrderExtension $OrderExtension 注文情報
     * @return bool
     */
    public function doGetOrderInfo(OrderExtension $OrderExtension)
    {
        // API設定
        $server_url = $this->getApiUrl('KAAST0010APIAction');

        // 送信キー
        $listSendKey = array(
            'ycfStrCode',
            'orderNo',
            'requestDate',
            'password'
        );

        // 送信パラメタ取得
        $sendParams = $this->getSendData($listSendKey, array(), $OrderExtension);

        // リクエスト送信
        if (!$this->sendRequest($server_url, $sendParams)) {
            return false;
        }

        // レスポンス値取得
        $results = (array)$this->getResults();
        if (isset($results['result']) && !is_null($results['result'])) {
            $results['action_status'] = $results['result'];
        }

        // 受注情報に決済情報をセット
        $this->paymentUtil->setOrderPayData($OrderExtension->getYamatoOrderPayment(), $results);

        return true;
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
     * 送信に成功した送り状番号を削除
     *
     * @param integer $orderId
     * @return void
     */
    private function deleteLastDelivSlip($orderId)
    {
        // 配送先拡張データ取得
        $YamatoShippings = $this->app['yamato_payment.repository.yamato_shipping_deliv_slip']
            ->findBy(array(
                'order_id' => $orderId
            ));

        // トランザクション開始
        $this->app['orm.em']->beginTransaction();

        /* @var YamatoShippingDelivSlip $YamatoShipping */
        foreach ($YamatoShippings as $YamatoShipping) {
            $YamatoShipping->setLastDelivSlipNumber(null);
            $this->app['orm.em']->persist($YamatoShipping);
        }

        // コミット
        $this->app['orm.em']->flush();
        $this->app['orm.em']->commit();
    }

    /**
     * 送信データを取得
     *
     * @param array $sendKey 送信キー
     * @param array $listParam その他パラメタ
     * @param OrderExtension $OrderExtension 注文情報
     * @param PaymentExtension $PaymentExtension 支払方法設定
     * @return array $sendData 送信データ
     */
    protected function getSendData(
        array $sendKey,
        array $listParam,
        OrderExtension $OrderExtension = null,
        PaymentExtension $PaymentExtension = null
    ) {
        if (is_null($OrderExtension)) {
            $OrderExtension = new OrderExtension();
        }
        if (is_null($PaymentExtension)) {
            $PaymentExtension = new PaymentExtension();
        }
        $encoding = 'UTF-8';

        // 受注情報取得
        $Order = $OrderExtension->getOrder();
        if (is_null($Order)) {
            $Order = new Order();
        }
        // 受注ID
        $orderId = $Order->getId();
        if (is_null($orderId)) {
            // ダミー注文の受注IDは$orderInfoにしか入っていない...
            $orderId = $OrderExtension->getOrder()->getId();
        }

        // 支払情報取得 (plg_yamato_order_payment#memo05)
        $paymentData = $OrderExtension->getPaymentData();

        // 支払方法設定取得 (plg_yamato_payment_method#memo05)
        $paymentConfig = $PaymentExtension->getArrPaymentConfig();

        // 住所取得
        $address = mb_convert_kana($Order->getPref()->getName() . $Order->getAddr01() . '　' . $Order->getAddr02(),'KVAS', $encoding);

        // 送信データの取得
        $sendData = array();
        foreach ($sendKey as $key) {
            switch ($key) {
                case 'ycfStrCode':
                    $sendData[$key] = $this->userSettings['ycf_str_code'];
                    break;
                case 'orderNo':
                    $sendData[$key] = $orderId;
                    break;
                case 'requestDate':
                    $sendData[$key] = date('YmdHis');
                    break;
                case 'password':
                    $sendData[$key] = $this->userSettings['ycf_str_password'];
                    break;
                case 'shipYmd':
                    $sendData[$key] = date('Ymd', strtotime('+' . $this->userSettings['ycf_ship_ymd'] . 'day', $Order->getCreateDate()->getTimestamp()));
                    break;
                case 'postCode':
                    $sendData[$key] = $Order->getZip01() . $Order->getZip02();
                    break;
                case 'address1':
                    $sendData[$key] = mb_substr($address, 0, 25, $encoding);
                    break;
                case 'address2':
                    $sendData[$key] = null;
                    if (mb_substr($address, 25, 25, $encoding) != '') {
                        $sendData[$key] = mb_substr($address, 25, 25, $encoding);
                    }
                    break;
                case 'totalAmount':
                    $sendData[$key] = $Order->getPaymentTotal();
                    break;
                case 'sendDiv':
                    $sendData[$key] = ($this->paymentUtil->getsendDiv($Order, $Order->getShippings()) != 2)? 0 : 1;
                    if (isset($listParam[$key])) {
                        $sendData[$key] = $listParam[$key];
                    }
                    break;
                case 'billPostCode':
                    $sendData[$key] = '';
                    break;
                default:
                    //優先順位
                    //$listParam > $paymentData > $listPaymentInfo > $userSettings
                    if (isset($listParam[$key])) {
                        $sendData[$key] = $listParam[$key];
                    } elseif (isset($paymentData[$key])) {
                        $sendData[$key] = $paymentData[$key];
                    } elseif (isset($paymentConfig[$key])) {
                        $sendData[$key] = $paymentConfig[$key];
                    } elseif (isset($this->userSettings[$key])) {
                        $sendData[$key] = $this->userSettings[$key];
                    }
                    break;
            }
        }
        return $sendData;
    }


    /**
     * 金額変更
     *
     * @param OrderExtension $OrderExtension 注文情報
     * @return bool
     */
    public function doChangePrice(OrderExtension $OrderExtension)
    {
        // 後払い決済以外は対象外
        $YamatoOrderPayment = $OrderExtension->getYamatoOrderPayment();
        if ($YamatoOrderPayment->getMemo03() != $this->const['YAMATO_PAYID_DEFERRED']) {
            $msg = '金額変更に対応していない決済です。';
            $this->setError($msg);
            return false;
        }

        //決済設定情報取得
        $paymentInfo = $this->paymentUtil->getPaymentTypeConfig(
            $OrderExtension->getOrder()->getPayment()->getId()
        );

        //API設定
        $server_url = $this->getApiUrl('KAAKK0010APIAction');

        //送信キー
        $listSendKey = array(
            'ycfStrCode',
            'orderNo',
            'shipYmd',
            'postCode',
            'address1',
            'address2'
        );

        $Items = $this->app['yamato_payment.util.payment']->getOrderDetailDeferred($OrderExtension->getOrder());

        //受注詳細ごとに商品情報取得処理
        $listParam = array();
        list($listSendKey, $listParam) = $this->getProductData($Items, $listSendKey, $listParam);

        $listSendKey[] = 'totalAmount';
        $listSendKey[] = 'sendDiv';
        $listSendKey[] = 'billPostCode';
        $listSendKey[] = 'password';
        $listSendKey[] = 'requestDate';

        // 送り先区分：sendDivはブランク
        $listParam['sendDiv'] = '';

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
     * 請求書再発行
     *
     * @param OrderExtension $OrderExtension 注文情報
     * @param integer $request_type ご依頼内容
     * @return bool
     */
    public function doInvoiceReissue(OrderExtension $OrderExtension, $request_type)
    {
        // 後払い決済以外は対象外
        $YamatoOrderPayment = $OrderExtension->getYamatoOrderPayment();
        if ($YamatoOrderPayment->getMemo03() != $this->const['YAMATO_PAYID_DEFERRED']) {
            $msg = '金額変更に対応していない決済です。';
            $this->setError($msg);
            return false;
        }

        //決済設定情報取得
        $paymentInfo = $this->paymentUtil->getPaymentTypeConfig(
            $OrderExtension->getOrder()->getPayment()->getId()
        );

        //API設定
        $server_url = $this->getApiUrl('KAARR0010APIAction');

        //送信キー
        $listSendKey = array(
            'ycfStrCode',
            'orderNo',
            'password',
            'requestContents',
            'reasonReissue',
            'reasonReissueEtc',
            'shipYmd',
            'sendDiv',
            'postCode',
            'address1',
            'address2'
        );

        // 送り先区分：ブランク
        $listParam['sendDiv'] = '';

        $listParam['requestContents'] = $request_type;
        $listParam['reasonReissue'] = '';
        $listParam['reasonReissueEtc'] = '';
        if ($request_type == 1) {
            $listParam['reasonReissue'] = 6;
            $listParam['reasonReissueEtc'] = '不明';
        }

        $Items = $this->app['yamato_payment.util.payment']->getOrderDetailDeferred($OrderExtension->getOrder());

        //受注詳細ごとに商品情報取得処理
        list($listSendKey, $listParam) = $this->getProductData($Items, $listSendKey, $listParam);

        $listSendKey[] = 'billPostCode';

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

        //処理成功 かつ 請求書再発行の場合、請求書再発行通知メール送信
        if ($request_type == $this->const['DEFERRED_INVOICE_REISSUE']) {
            $this->sendInvoiceReissueMail($OrderExtension);
        }

        return true;
    }

    /**
     * 受注詳細ごとに商品情報取得処理
     *
     * @param array $Items 注文明細情報
     * @param array $listSendKey 送信キー
     * @param array $listParam その他情報
     * @return array
     */
    protected function getProductData($Items, $listSendKey, $listParam)
    {
        foreach ($Items as $key=>$val) {
            $seq = $key + 1;
            $listSendKey[] = 'itemName'.$seq;
            $listSendKey[] = 'itemCount'.$seq;
            $listSendKey[] = 'unitPrice'.$seq;
            $listSendKey[] = 'subTotal'.$seq;
            $val['itemName'] = CommonUtil::convertProhibitedKigo($val['itemName']);
            $listParam['itemName'.$seq] = mb_substr(mb_convert_kana($val['itemName'], 'KVAS', 'UTF-8'), 0, 30, 'UTF-8');
            $listParam['itemCount'.$seq] = $val['itemCount'];
            $listParam['unitPrice'.$seq] = $val['unitPrice'];
            $listParam['subTotal'.$seq] = $val['subTotal'];
        }

        return array($listSendKey, $listParam);
    }

    /**
     * 請求書再発行通知メール送信
     *
     * @param OrderExtension $OrderExtension 注文情報
     */
    protected function sendInvoiceReissueMail($OrderExtension)
    {
        $MailData = array(
            'header' => $this->userSettings['ycf_invoice_reissue_mail_header'],
            'footer' => $this->userSettings['ycf_invoice_reissue_mail_footer'],
            'subject' => '請求書再発行のお知らせ',
            'mail_address' => $this->userSettings['ycf_invoice_reissue_mail_address']
        );

        // 請求書再発行通知メール送信
        $this->app['eccube.service.mail']->sendAdminOrderMail($OrderExtension->getOrder(), $MailData);

        // 送信履歴を保存.
        $body = $this->app->renderView('Mail/order.twig', array(
            'header' => $MailData['header'],
            'footer' => $MailData['footer'],
            'Order' => $OrderExtension->getOrder(),
        ));

        // 決済情報差し込み処理
        $body = $this->app['yamato_payment.event.mail']->insertOrderMailBody(
            $body,
            $OrderExtension->getOrder()
        );
        $MailHistory = new MailHistory();
        $MailHistory
            ->setSubject('[' . $this->app['eccube.repository.base_info']->get()->getShopName() . '] ' . $MailData['subject'])
            ->setMailBody($body)
            ->setMailTemplate($this->app['eccube.repository.mail_template']->find(1))
            ->setSendDate(new \DateTime())
            ->setOrder($OrderExtension->getOrder());
        $this->app['orm.em']->persist($MailHistory);
        $this->app['orm.em']->flush($MailHistory);
    }

}
