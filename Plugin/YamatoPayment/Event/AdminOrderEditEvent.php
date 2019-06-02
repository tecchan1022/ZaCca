<?php
/*
 * Copyright(c)2016, Yamato Financial Co.,Ltd. All rights reserved.
 * Copyright(c)2016, Yamato Credit finance Co.,Ltd. All rights reserved.
 */


namespace Plugin\YamatoPayment\Event;

use Eccube\Application;
use Eccube\Entity\Master\OrderStatus;
use Eccube\Entity\Order;
use Eccube\Event\EventArgs;
use Eccube\Event\TemplateEvent;
use Plugin\YamatoPayment\Entity\OrderExtension;
use Plugin\YamatoPayment\Entity\YamatoOrderPayment;
use Plugin\YamatoPayment\Entity\YamatoPaymentMethod;
use Plugin\YamatoPayment\Entity\YamatoOrderScheduledShippingDate;
use Plugin\YamatoPayment\Entity\YamatoShippingDelivSlip;
use Symfony\Component\Form\FormBuilder;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Validator\Constraints as Assert;

class AdminOrderEditEvent extends AbstractEvent
{
    /**
     * 受注編集画面：IndexInitializeイベント
     *
     * @param EventArgs $event
     */
    public function onAdminOrderEditIndexInitialize(EventArgs $event)
    {
        // パラメータ取得
        /** @var Order $TargetOrder */
        $TargetOrder = $event->getArgument('TargetOrder');
        /** @var FormBuilder $builder */
        $builder = $event->getArgument('builder');

        // 受注編集
        if ($TargetOrder->getId()) {

            // 配送伝票データ取得
            $delivSlipNumbers = array();
            $Shippings = $TargetOrder->getShippings();
            foreach ($Shippings as $Shipping) {
                /** @var YamatoShippingDelivSlip $YamatoShippingDelivSlip */
                $YamatoShippingDelivSlip = $this->app['yamato_payment.repository.yamato_shipping_deliv_slip']->find($Shipping->getId());
                $deliv_slip_number = ($YamatoShippingDelivSlip)
                    ? $YamatoShippingDelivSlip->getDelivSlipNumber() : null;
                $delivSlipNumbers[] = array(
                    'deliv_slip_number' => $deliv_slip_number
                );
            }

            // 配送伝票データセット
            $builder->get('YamatoShippings')->setData($delivSlipNumbers);

            // 予約商品出荷予定日データ取得
            /** @var YamatoOrderScheduledShippingDate $YamatoOrderScheduledShippingDate */
            $YamatoOrderScheduledShippingDate = $this->app['yamato_payment.repository.yamato_order_scheduled_shipping_date']->find($TargetOrder->getId());

            // 予約商品出荷予定日データセット
            if ($YamatoOrderScheduledShippingDate) {
                $scheduled_shipping_date = $YamatoOrderScheduledShippingDate->getScheduledshippingDate();
                $builder->get('scheduled_shipping_date')->setData($scheduled_shipping_date);
            }
        }

    }

    /**
     * 受注編集画面：IndexCompleteイベント
     *
     * @param EventArgs $event
     */
    public function onAdminOrderEditIndexComplete($event)
    {
        // 登録情報の取得
        /** @var Order $order */
        $order = $event->getArgument('TargetOrder');
        $orderId = $order->getId();

        // フォーム情報を取得
        $form = $event->getArgument('form');
        $Shippings = $form['Shippings']->getData();
        $YamatoShippings = $form['YamatoShippings']->getData();

        // 送り状番号の登録
        for ($index = 0; $index < count($Shippings); $index++) {

            // 配送ID を取得する
            $shipping_id = $Shippings[$index]['id'];

            // 送り状番号を取得する
            $deliv_slip_number = $YamatoShippings[$index]['deliv_slip_number'];
            if (is_null($deliv_slip_number)) {
//                continue;
            }

            $DelivSlip = $this->app['yamato_payment.repository.yamato_shipping_deliv_slip']->find($shipping_id);
            if (is_null($DelivSlip)) {
                $DelivSlip = new YamatoShippingDelivSlip();
                $DelivSlip->setId($shipping_id);
                $DelivSlip->setOrderId($orderId);
            }
            $DelivSlip->setDelivSlipNumber($deliv_slip_number);
            $this->app['orm.em']->persist($DelivSlip);
            $this->app['orm.em']->flush($DelivSlip);
        }

        // リクエストの取得
        $scheduled_shipping_date = $form['scheduled_shipping_date']->getData();

        // 出荷予定日の登録
        $entity = $this->app['yamato_payment.repository.yamato_order_scheduled_shipping_date']->find($orderId);
        if (is_null($entity)) {
            $entity = new YamatoOrderScheduledShippingDate();
        }

        $entity->setId($orderId);
        $entity->setScheduledshippingDate($scheduled_shipping_date);

        $this->app['orm.em']->persist($entity);
        $this->app['orm.em']->flush($entity);
    }

    /**
     * 受注編集画面：Renderイベント
     *
     * @param TemplateEvent $event
     */
    public function onAdminOrderEditRender(TemplateEvent $event)
    {
        $source = $event->getSource();
        $parameters = $event->getParameters();
        /** @var Order $Order */
        $Order = $parameters['Order'];
        $orderId = $parameters['id'];

        // 新規
        if (is_null($orderId)) {
            // 支払方法選択からヤマト決済を削除する
            $source = $this->renderDeleteYamatoPayment($source);

        // 編集
        } else {

            // ヤマト決済情報取得
            /** @var YamatoOrderPayment $YamatoOrderPayment */
            $YamatoOrderPayment = $this->app['yamato_payment.repository.yamato_order_payment']
                ->find($Order->getId());

            // ヤマト決済用の支払方法取得
            /** @var YamatoPaymentMethod $YamatoPaymentMethod */
            $YamatoPaymentMethod = $this->app['yamato_payment.repository.yamato_payment_method']
                ->find($Order->getPayment()->getId());

            // ヤマト決済の場合
            if ($YamatoOrderPayment && $YamatoPaymentMethod) {
                // 追加パラメータを取得
                $addParams = $this->getAddParameters($YamatoOrderPayment, $YamatoPaymentMethod);
                // パラメータ追加
                $parameters = array_merge($parameters, $addParams);
                // テンプレート差し込み処理
                $source = $this->renderEdit($source);
            } else {
                // 支払方法選択からヤマト決済を削除する
                $source = $this->renderDeleteYamatoPayment($source);
            }

            // 決済状況管理画面から遷移した場合、戻るボタンは決済状況管理画面に戻す
            $referer = $this->app['request']->server->get("HTTP_REFERER");
            if (strpos($referer, $this->app->url('yamato_order_status')) !== false) {
                $search = '<p><a href="{{ url(\'admin_order\') }}">戻る</a></p>';
                $replace = '<p><a href="{{ path(\'yamato_order_status_page\', {\'page_no\': 1}) }}">戻る</a></p>';
                $source = str_replace($search, $replace, $source);
            }
        }

        // 送り状番号入力欄 を追加
        $snipet = file_get_contents(__DIR__ . '/../Resource/template/admin/Order/order_edit_deliv_slip.twig');

        $search = '<div class="extra-form">';
        $replace = $snipet . $search;
        $source = str_replace($search, $replace, $source);

        $event->setParameters($parameters);
        $event->setSource($source);
    }

    /**
     * 支払方法選択からヤマト決済を削除する
     *
     * @param string $source
     * @return string
     */
    private function renderDeleteYamatoPayment($source)
    {
        $listPaymentRemove = '';

        // ヤマト決済用の支払方法を取得する
        $payments = $this->app['yamato_payment.repository.yamato_payment_method']->findAll();
        foreach ($payments as $YamatoPaymentMethod) {
            // 支払方法選択から削除する
            /** @var YamatoPaymentMethod $YamatoPaymentMethod */
            $listPaymentRemove .= '$("#order_Payment option[value=' . $YamatoPaymentMethod->getId() . ']").remove();';
        }

        $snipet = <<< EOM
<script>
    $(function(){
        // remove yamato payment from payment.
        $listPaymentRemove
    });
</script>

EOM;
        $search = '{% endblock javascript %}';
        $replace = $snipet . $search;
        $source = str_replace($search, $replace, $source);

        return $source;
    }

    /**
     * 更新のTwig差し込み処理
     *
     * @param string $source
     * @return string
     */
    private function renderEdit($source)
    {
        /* 決済情報を追加 */
        $snipet = file_get_contents(__DIR__ . '/../Resource/template/admin/Order/order_edit_payment.twig');

        $search = '<div id="customer_info_box"  class="box accordion">';
        $replace = $snipet . $search;
        $source = str_replace($search, $replace, $source);

        /* JavaScript追加 */
        $snipet = file_get_contents(__DIR__ . '/../Resource/template/admin/Order/order_edit_javascript.twig');

        $search = '{% endblock javascript %}';
        $replace = $snipet . $search;
        $source = str_replace($search, $replace, $source);

        return $source;
    }

    /**
     * 追加パラメータを取得する
     *
     * @param YamatoOrderPayment $YamatoOrderPayment
     * @param YamatoPaymentMethod $YamatoPaymentMethod
     * @return array 追加パラメータ
     */
    private function getAddParameters($YamatoOrderPayment, $YamatoPaymentMethod)
    {
        // 決済情報の取得
        $objUtil = $this->app['yamato_payment.util.payment'];
        $OrderExtension = $objUtil->getOrderPayData($YamatoOrderPayment->getId());
        $PaymentData = $OrderExtension->getPaymentData();

        $paymentStatus = $objUtil->getPaymentStatus();
        $deferredStatus = $objUtil->getDeferredStatus();
        $deferredExamResult = $objUtil->getCreditResult();
        $convenienceStores = $objUtil->getConveni();

        // 決済操作ボタンの取得
        $buttons = $this->paymentOperationButtonsCondition(
            $YamatoOrderPayment->getId(),
            $YamatoPaymentMethod->getMemo03(),
            $YamatoOrderPayment->getMemo04(),
            $YamatoOrderPayment->getMemo06()
        );

        return array(
            'appConst' => $this->app['config']['YamatoPayment']['const'],
            'yamato_payid' => $YamatoPaymentMethod->getMemo03(),
            'orderExtGetPaymentStatus' => $YamatoOrderPayment->getMemo04(),
            'orderExtGetPaymentData' => $YamatoOrderPayment->getMemo05(),
            'orderExtGetPaymentExamResult' => $YamatoOrderPayment->getMemo06(),
            'orderExtGetPaymentLog' => $YamatoOrderPayment->getMemo09(),
            'paymentStatus' => $paymentStatus,
            'paymentData' => $PaymentData,
            'deferredStatus' => $deferredStatus,
            'deferredExamResult' => $deferredExamResult,
            'convenienceStores' => $convenienceStores,
            'buttons' => $buttons,
            'userSettings' => $this->app['yamato_payment.util.plugin']->getUserSettings(),
        );

    }

    /**
     * 決済操作ボタンの取得
     *
     * @param integer $orderId 受注ID
     * @param string $paymentMethod 決済種別ID
     * @param string $paymentStatus 決済状況
     * @param string $paymentAuth 審査結果
     * @return array $availableOperations 表示ボタンの情報
     */
    private function paymentOperationButtonsCondition($orderId, $paymentMethod, $paymentStatus, $paymentAuth)
    {
        $availableOperations = array();

        $consts = $this->app['config']['YamatoPayment']['const'];
        $pluginUtil = $this->app['yamato_payment.util.plugin'];
        $yamatoShippingDelivSlipRepo = $this->app['yamato_payment.repository.yamato_shipping_deliv_slip'];

        // オプション契約
        $is_opiton_on = $pluginUtil->getUserSettings('use_option') == '0' ? true : false;
        // 予約商品販売
        $is_advance_sale_on = $pluginUtil->getUserSettings('advance_sale') == 0 ? true : false;

        // 送り状番号の登録状態判定
        $is_slip_on = $yamatoShippingDelivSlipRepo->isSlippingOn($orderId);

        // 他社配送設定
        $user_settings = $pluginUtil->getSubData();
        $deliveryServiceCode = $user_settings["user_settings"]["delivery_service_code"];

        switch ($paymentMethod) {
            case $consts['YAMATO_PAYID_CREDIT']:

                // 取引情報照会 ボタンは無条件で表示
                $availableOperations['GET_INFO'] = 1;

                if (!empty($paymentStatus)) {
                    // 送り状番号が該当注文配送先すべてに登録されている かつ、ステータスが与信完了
                    // 他社配送の場合はステータスが与信完了
                    if ((($deliveryServiceCode == '00' && $is_slip_on) || $deliveryServiceCode == '99') && $paymentStatus == $consts['YAMATO_ACTION_STATUS_COMP_AUTH']) {
                        // 出荷情報登録ボタン
                        $availableOperations['SHIPMENT_REGIST'] = 1;
                    }

                    // 送り状番号が該当注文配送先すべてに登録されている かつ、ステータスが精算確定待ち
                    if ((($deliveryServiceCode == '00' && $is_slip_on) || $deliveryServiceCode == '99') && $paymentStatus == $consts['YAMATO_ACTION_STATUS_WAIT_SETTLEMENT']) {
                        // 出荷情報取消ボタン
                        $availableOperations['SHIPMENT_CANCEL'] = 1;
                    }

                    // ユーザーオプション有り かつ、予約商品購入 かつ、ステータスが予約受付完了
                    if ((($deliveryServiceCode == '00' && $is_slip_on) || $deliveryServiceCode == '99') && $is_advance_sale_on && $paymentStatus == $consts['YAMATO_ACTION_STATUS_COMP_RESERVE']) {
                        // 出荷予定日変更ボタン
                        $availableOperations['CHANGE_RESERVE'] = 1;
                    }

                    // ステータスが精算確定以外
                    if ($paymentStatus != $consts['YAMATO_ACTION_STATUS_COMMIT_SETTLEMENT']) {
                        // 決済取消ボタン
                        $availableOperations['CANCEL'] = 1;
                    }

                    // ステータスが取消以外
                    if ($paymentStatus != $consts['YAMATO_ACTION_STATUS_CANCEL']) {
                        // 金額変更ボタン
                        $availableOperations['CHANGE_PRICE'] = 1;
                    }

                    // ステータスが与信完了か取消
                    if($paymentStatus == $consts['YAMATO_ACTION_STATUS_COMP_AUTH'] || $paymentStatus == $consts['YAMATO_ACTION_STATUS_CANCEL']) {
                        // 再与信ボタン
                        $availableOperations['REAUTH'] = 1;
                    }
                }

                break;

            case $consts['YAMATO_PAYID_CVS']:

                // 取引情報照会 ボタンは無条件で表示
                $availableOperations['GET_INFO'] = 1;

                break;

            case $consts['YAMATO_PAYID_DEFERRED']:

                // 全配送先の送信成功した送り状番号が保持されているかどうか判定
                $is_exist_last_deliv = $yamatoShippingDelivSlipRepo->isAllExistLastDelivSlip($orderId);

                // 取引情報取得 ボタンは無条件で表示
                $availableOperations['DEFERRED_GET_INFO'] = 1;

                // 与信結果取得 ボタンは無条件で表示
                $availableOperations['DEFERRED_GET_AUTH'] = 1;

                // 買手情報一括登録CSV ボタンは無条件で表示
                $availableOperations['CSV'] = 1;

                // 請求内容変更・請求書再発行 ボタンは無条件で表示
                $availableOperations['DEFERRED_INVOICE_REISSUE'] = 1;

                // 請求書再発行取下げ ボタンは無条件で表示
                $availableOperations['DEFERRED_INVOICE_REISSUE_WITHDRAWN'] = 1;

                if (!empty($paymentStatus)) {
                    // 送り状番号が該当注文配送先すべてに登録されている かつ、後払い用審査結果がご利用可
                    if ((($deliveryServiceCode == '00' && $is_slip_on) || $deliveryServiceCode == '99') && $paymentAuth == $consts['DEFERRED_AVAILABLE']) {
                        // 出荷情報登録ボタン
                        $availableOperations['DEFERRED_SHIPMENT_REGIST'] = 1;
                    }

                    // 全配送先の送信成功した送り状番号が保持されている かつ、ステータスが送り状番号登録済み
                    if ($is_exist_last_deliv && $paymentStatus == $consts['DEFERRED_STATUS_REGIST_DELIV_SLIP']) {
                        // 出荷情報取消ボタン
                        $availableOperations['DEFERRED_SHIPMENT_CANCEL'] = 1;
                    }

                    // ステータスが取消済み以外
                    if ($paymentStatus != $consts['DEFERRED_STATUS_AUTH_CANCEL']) {
                        // 与信取消ボタン
                        $availableOperations['DEFERRED_AUTH_CANCEL'] = 1;
                    }

                    // 後払い用審査結果がご利用可がご利用可 かつ、ステータスが取消済み・入金済み以外
                    if ($paymentAuth == $consts['DEFERRED_AVAILABLE'] &&
                        $paymentStatus != $consts['DEFERRED_STATUS_AUTH_CANCEL'] && $paymentStatus != $consts['DEFERRED_STATUS_PAID']) {
                        // 金額変更ボタン
                        $availableOperations['DEFERRED_CHANGE_PRICE'] = 1;
                    }
                }
                break;
            default:
                break;
        }

        return $availableOperations;
    }

    /**
     * 受注編集画面：Beforeイベント
     *
     * @param GetResponseEvent $event
     */
    public function onRouteAdminOrderEditRequest(GetResponseEvent $event)
    {
        $request = $this->app['request'];
        $const = $this->app['config']['YamatoPayment']['const'];

        if ($request->getMethod() !== 'POST' || $request->get('mode_type') == '' ) {
            return;
        }

        $order_id = $request->get('id');
        $mode = $request->get('mode_type');

        // 決済情報を取得
        $orderExtension = $this->app['yamato_payment.util.payment']->getOrderPayData($order_id);
        if (!$orderExtension) {
            throw new NotFoundHttpException();
        }

        // エラーチェック
        $error_message = $this->checkError($orderExtension, $mode);
        if (!empty($error_message)) {
            $this->app->addDanger($error_message, 'admin');
            $response = $this->app->redirect(($this->app->url('admin_order_edit', array('id' => $order_id))));
            $event->setResponse($response);
            return;
        }

        $objClient = $this->app['yamato_payment.service.client.util'];
        $objDeferredClient = $this->app['yamato_payment.service.client.deferred_util'];

        $extraMsg = '';
        switch ($mode) {
            case 'yamato_shipment_regist':
                // 出荷情報登録
                list($ret, $arrSuccessSlip) = $objClient->doShipmentEntry($orderExtension);
                if (!$ret) {
                    //複数配送時出荷情報登録ロールバック
                    $objClient->doShipmentRollback($orderExtension, $arrSuccessSlip);
                }
                break;
            case 'yamato_shipment_cancel':
                // 出荷情報取消
                $ret = $objClient->doShipmentCancel($orderExtension);
                break;
            case 'yamato_get_info':
                // 取引情報照会
                $ret = $objClient->doGetTradeInfo($orderExtension);
                break;
            case 'yamato_change_scheduled_shipping_date':
                // 出荷予定日変更
                $ret = $objClient->doChangeDate($orderExtension);
                break;
            case 'yamato_cancel':
                // 決済取消
                $ret = $objClient->doCreditCancel($orderExtension);
                if ($ret) {
                    // 対応状況をキャンセルにする
                    $order_status = $this->app['config']['order_cancel'];
                    /** @var OrderStatus $OrderStatus */
                    $OrderStatus = $this->app['eccube.repository.order_status']->find($order_status);
                    $this->app['eccube.repository.order']->changeStatus($order_id, $OrderStatus);
                }
                break;
            case 'yamato_change_price':
                // 金額変更（クレジットカード決済）
                $ret = $objClient->doCreditChangePrice($orderExtension);
                break;
            case 'yamato_deferred_get_info':
                // 取引状況取得
                $ret = $objDeferredClient->doGetOrderInfo($orderExtension);
                break;
            case 'yamato_deferred_auth_cancel':
                // 与信取消
                $ret = $objDeferredClient->doCancel($orderExtension);
                break;
            case 'yamato_deferred_get_auth':
                // 与信結果取得
                $ret = $objDeferredClient->doGetAuthResult($orderExtension);
                break;
            case 'yamato_deferred_shipment_regist':
                // 出荷情報登録
                list($ret, $success_cnt, $failure_cnt) = $objDeferredClient->doShipmentEntry($orderExtension);
                $extraMsg = "\n登録成功：$success_cnt 件\n登録失敗：$failure_cnt 件";
                if($ret == false) {
                    $extraMsg = implode(', ',$objDeferredClient->getError()).' '.$extraMsg;
                }
                break;
            case 'yamato_deferred_shipment_cancel':
                // 出荷情報取消
                $ret = $objDeferredClient->doShipmentCancel($orderExtension);
                break;
            case 'yamato_deferred_change_price':
                // 金額変更（クロネコ代金後払い決済）
                $ret = $objDeferredClient->doChangePrice($orderExtension);
                break;
            case 'yamato_deferred_invoice_reissue':
                // 請求内容変更・請求書再発行
                $ret = $objDeferredClient->doInvoiceReissue($orderExtension, $const['DEFERRED_INVOICE_REISSUE']);
                break;
            case 'yamato_deferred_invoice_reissue_withdrawn':
                // 請求書再発行取下げ
                $ret = $objDeferredClient->doInvoiceReissue($orderExtension, $const['DEFERRED_INVOICE_REISSUE_WITHDRAWN']);
                break;
            case 'yamato_reauth' :
                // 再与信
                $ret = $objClient->doReauth($orderExtension);
                if ($ret) {
                    // 対応状況を新規受付にする
                    $order_status = $this->app['config']['order_new'];
                    /** @var OrderStatus $OrderStatus */
                    $OrderStatus = $this->app['eccube.repository.order_status']->find($order_status);
                    $this->app['eccube.repository.order']->changeStatus($order_id, $OrderStatus);
                } else {
                    $extraMsg = "\n".implode(', ',$objClient->getError());
                }
                break;
            default:
                $ret = false;
                break;
        }

        if ($ret) {
            $this->app->addSuccess('決済操作が完了しました。' . $extraMsg, 'admin');
        } else {
            $this->app->addDanger('決済操作でエラーが発生しました。' . $extraMsg, 'admin');
        }

        $response = $this->app->redirect(($this->app->url('admin_order_edit', array('id' => $order_id))));
        $event->setResponse($response);
        return;
    }

    /**
     * 決済操作時のエラーチェック
     *
     * @param OrderExtension $orderExtension
     * @param string $mode 処理内容
     * @return string エラーメッセージ
     */
    protected function checkError($orderExtension, $mode)
    {
        $const = $this->app['config']['YamatoPayment']['const'];
        $paymentUtil = $this->app['yamato_payment.util.payment'];
        $orderId = $orderExtension->getOrder()->getId();

        // 出荷予定日情報
        $OrderScheduledShippingDate = $orderExtension->getYamatoOrderScheduledShippingDate();

        // 受注決済情報
        $YamatoOrderPayment = $orderExtension->getYamatoOrderPayment();

        // 送り状番号リポジトリ
        $shippingDelivSlipRepo = $this->app['yamato_payment.repository.yamato_shipping_deliv_slip'];

        switch ($mode) {
            case 'yamato_shipment_regist':
                // 出荷情報登録エラーチェック（クレジット決済）
                $errorMsg = $paymentUtil->checkErrorShipmentEntryForCredit($orderId);
                if (!empty($errorMsg)) {
                    return $errorMsg;
                }
                break;
            case 'yamato_shipment_cancel':
                // 対応支払方法チェック
                if (!$paymentUtil->isCreditOrder($YamatoOrderPayment)) {
                    return '操作に対応していない決済です。';
                }
                // 取引状況チェック（精算確定はエラー)
                if ($YamatoOrderPayment->getMemo04() == $const['YAMATO_ACTION_STATUS_COMMIT_SETTLEMENT']) {
                    return '操作に対応していない取引状況です。';
                }
                // 送り状番号の登録状態を確認する
                if (!$shippingDelivSlipRepo->isSlippingOn($orderId)) {
                    return '送り状番号が登録されていない配送先が存在します。';
                }
                break;
            case 'yamato_get_info':
                break;
            case 'yamato_change_scheduled_shipping_date':
                // 対応支払方法チェック
                if (!$paymentUtil->isCreditOrder($YamatoOrderPayment)) {
                    return '操作に対応していない決済です。';
                }
                // 取引状況チェック（予約受付完了以外はエラー）
                if ($YamatoOrderPayment->getMemo04() != $const['YAMATO_ACTION_STATUS_COMP_RESERVE']) {
                    return '操作に対応していない取引状況です。';
                }
                // 出荷予定日必須チェック
                if (is_null($OrderScheduledShippingDate)
                    || is_null($OrderScheduledShippingDate->getScheduledshippingDate()))
                {
                    return '出荷予定日が設定されていません。';
                }
                break;
            case 'yamato_cancel':
                // 対応支払方法チェック
                if (!$paymentUtil->isCreditOrder($YamatoOrderPayment)) {
                    return '操作に対応していない決済です。';
                }
                // 取引状況チェック（精算確定はエラー)
                if ($YamatoOrderPayment->getMemo04() == $const['YAMATO_ACTION_STATUS_COMMIT_SETTLEMENT']) {
                    return '操作に対応していない取引状況です。';
                }
                break;
            case 'yamato_change_price':
                // 対応支払方法チェック
                if (!$paymentUtil->isCreditOrder($YamatoOrderPayment)) {
                    return '操作に対応していない決済です。';
                }
                // 取引状況チェック（取消済みはエラー)
                if ($YamatoOrderPayment->getMemo04() == $const['YAMATO_ACTION_STATUS_CANCEL']) {
                    return '操作に対応していない取引状況です。';
                }
                break;
            case 'yamato_deferred_get_info':
                if (!$paymentUtil->isDeferredOrder($YamatoOrderPayment)) {
                    return '操作に対応していない決済です。';
                }
                break;
            case 'yamato_deferred_auth_cancel':
                if (!$paymentUtil->isDeferredOrder($YamatoOrderPayment)) {
                    return '操作に対応していない決済です。';
                }
                // 取引状況チェック（取消済みはエラー)
                if ($YamatoOrderPayment->getMemo04() == $const['DEFERRED_STATUS_AUTH_CANCEL']) {
                    return '操作に対応していない取引状況です。';
                }
                break;
            case 'yamato_deferred_get_auth':
                if (!$paymentUtil->isDeferredOrder($YamatoOrderPayment)) {
                    return '操作に対応していない決済です。';
                }
                break;
            case 'yamato_deferred_shipment_regist':
                // 出荷情報登録エラーチェック（後払い決済）
                $errorMsg = $paymentUtil->checkErrorShipmentEntryForDeferred($orderId);
                if (!empty($errorMsg)) {
                    return $errorMsg;
                }
                break;
            case 'yamato_deferred_shipment_cancel':
                if (!$paymentUtil->isDeferredOrder($YamatoOrderPayment)) {
                    return '操作に対応していない決済です。';
                }
                // 取引状況チェック（送り状番号登録済以外はエラー）
                if ($YamatoOrderPayment->getMemo04() != $const['DEFERRED_STATUS_REGIST_DELIV_SLIP']) {
                    return '操作に対応していない取引状況です。';
                }
                // 全配送先の送信に成功した送り状番号が保持されているかを確認
                if (!$shippingDelivSlipRepo->isAllExistLastDelivSlip($orderId)) {
                    return '出荷情報登録されていない配送先が存在します。';
                }
                break;
            case 'yamato_reauth' :
                return $paymentUtil->checkErrorReauthForCredit($orderId);
                break;
            default:
                break;
        }

        return null;
    }
}
