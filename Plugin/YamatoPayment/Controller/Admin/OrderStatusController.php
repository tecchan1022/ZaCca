<?php
/*
 * Copyright(c)2016, Yamato Financial Co.,Ltd. All rights reserved.
 * Copyright(c)2016, Yamato Credit finance Co.,Ltd. All rights reserved.
 */


namespace Plugin\YamatoPayment\Controller\Admin;

use Eccube\Application;
use Eccube\Controller\AbstractController;
use Eccube\Entity\Master\OrderStatus;
use Plugin\YamatoPayment\Entity\YamatoOrderPayment;
use Symfony\Component\HttpFoundation\Request;

class OrderStatusController extends AbstractController
{

    protected $title;
    protected $subtitle;
    /** @var Application $app */
    protected $app;

    public function __construct()
    {
        $this->title = '受注管理';
        $this->subtitle = '決済状況管理';
    }

    /**
     * 決済状況管理 画面の表示・登録処理
     *
     * @param Application $app
     * @param Request $request
     * @param int $page_no
     * @return string
     */
    public function index(Application $app, Request $request, $page_no = null)
    {
        // 初期化
        $this->app = $app;
        $const = $this->app['config']['YamatoPayment']['const'];
        $changePaymentStatusErrors = array();
        $searchData = array();

        // フォームの作成
        $builder = $app['form.factory']->createBuilder('yamato_order_status');
        $form = $builder->getForm();

        // session 情報の取得
        $session = $request->getSession();

        // 登録処理
        if ('POST' === $request->getMethod()) {

            $form->handleRequest($request);
            if ($form->isValid()) {

                // フォーム情報を取得
                $data = $form->getData();

                // session のデータ保持
                $session->set('yamato.order.status', $data);
                $searchData = $session->get('yamato.order.status');

                $page_no = 1;
            }
        } else {
            if (is_null($page_no)) {
                // session を削除
                $session->remove('yamato.order.status');
                $session->remove('yamato.order.status.error');
            } else {
                $searchData = $session->get('yamato.order.status');
                $changePaymentStatusErrors = $session->get('yamato.order.status.error');

                if (!is_null($searchData)) {
                    // session 情報での更新
                    $form->get('current_payment_status')->setData($searchData['current_payment_status']);
                    $form->get('current_payment_type')->setData($searchData['current_payment_type']);
                    $form->get('order_date_start')->setData($searchData['order_date_start']);
                    $form->get('order_date_end')->setData($searchData['order_date_end']);
                }

                if (!is_null($changePaymentStatusErrors)) {
                    // session を削除
                    $session->remove('yamato.order.status.error');
                }
            }
        }

        // 対応状況から｢購入処理中｣｢決済処理中｣を除く一覧を取得
        $listOrderStatus = array();
        $orderStatuses = $app['eccube.repository.order_status']->findAllArray();
        foreach ($orderStatuses as $key => $value) {
            if ($key != $this->app['config']['order_pending'] && $key != $this->app['config']['order_processing']) {
                $listOrderStatus[$key] = $value['name'];
            }
        }

        // 決済方法の一覧を取得
        $listPaymentType = array();
        $paymentTypes = $app['eccube.repository.payment']->findAllArray();
        foreach ($paymentTypes as $key => $value) {
            $listPaymentType[$key] = $value['method'];
        }
        ksort($listPaymentType);

        // 出荷登録可能な 支払方法ID を取得する
        $listShippingRegistPaymentIds = array();
        $YamatoPaymentMethod = $app['yamato_payment.repository.yamato_payment_method']->findAll();
        if (!is_null($YamatoPaymentMethod)) {
            foreach ($YamatoPaymentMethod as $payment) {
                if ($payment['memo03'] == $const['YAMATO_PAYID_CREDIT'] || $payment['memo03'] == $const['YAMATO_PAYID_DEFERRED']) {
                    $listShippingRegistPaymentIds[] = $payment['id'];
                }
            }
        }

        // 決済状況の名称一覧を取得する
        $listCreditPaymentStatuses = $app['yamato_payment.util.payment']->getCreditPaymentStatus();
        $listCvsPaymentStatuses = $app['yamato_payment.util.payment']->getCvsPaymentStatus();
        $listDeferredPaymentStatuses = $app['yamato_payment.util.payment']->getDeferredStatus();

        // 受注一覧を取得
        $yamatoOrderPaymentRepo = $app['yamato_payment.repository.yamato_order_payment'];
        $Order = $yamatoOrderPaymentRepo->getOrderBySearchDataForAdmin($searchData);

        // 表示件数
        $pageMaxis = $app['eccube.repository.master.page_max']->findAll();
        $pcount = $request->get('page_count');
        $page_count = empty($pcount) ? $app['config']['default_page_count'] : $pcount;

        // ページング
        $pagination = $app['paginator']()->paginate(
            $Order,
            is_null($page_no) ? 1 : $page_no,
            $page_count
        );

        $user_settings = $this->app['yamato_payment.util.plugin']->getSubData();
        $deliveryServiceCode = $user_settings["user_settings"]["delivery_service_code"];

        return $app['view']->render('YamatoPayment/Resource/template/admin/Order/order_status.twig', array(
            'form' => $form->createView(),
            'maintitle' => $this->title,
            'subtitle' => $this->subtitle,
            'appConst' => $const,
            'OrderStatuses' => $listOrderStatus,
            'PaymentTypes' => $listPaymentType,
            'ShippingRegistPaymentIds' => $listShippingRegistPaymentIds,
            'CreditPaymentStatuses' => $listCreditPaymentStatuses,
            'CvsPaymentStatuses' => $listCvsPaymentStatuses,
            'DeferredPaymentStatuses' => $listDeferredPaymentStatuses,
            'changePaymentStatusErrors' => $changePaymentStatusErrors,
            'pagination' => $pagination,
            'pageMaxis' => $pageMaxis,
            'page_count' => $page_count,
            'deliveryServiceCode' => $deliveryServiceCode,
        ));
    }

    /**
     * 決済状況 変更処理
     *
     * @param Application $app
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function changeStatus(Application $app, Request $request)
    {
        // フォームの作成
        $builder = $app['form.factory']->createBuilder('yamato_order_status');
        $form = $builder->getForm();

        if ('POST' === $request->getMethod()) {

            // フォーム情報の取得
            $form->handleRequest($request);
            $data = $form->getData();

            $orderRepo = $app['eccube.repository.order'];

            // 対応状況変更
            $listOrderIds = $request->request->get('ids');
            foreach ($listOrderIds as $orderId => $value) {
                $orderRepo->changeStatus($orderId, $data['status']);
            }

            // sessionのデータ保持
            $session = $request->getSession();
            $session->set('yamato.order.status', $data);

            $app->addSuccess('admin.register.complete', 'admin');
        }

        return $app->redirect($app->url('yamato_order_status_page', array(
                'page_no' => 1)
        ));
    }

    /**
     * 決済状況 削除処理
     *
     * @param Application $app
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function delete(Application $app, Request $request)
    {
        // フォームの作成
        $builder = $app['form.factory']->createBuilder('yamato_order_status');
        $form = $builder->getForm();

        if ('POST' === $request->getMethod()) {

            // フォーム情報の取得
            $form->handleRequest($request);
            $data = $form->getData();

            // 対応状況削除
            $listOrderIds = $request->request->get('ids');
            $ret = $this->deleteOrderData($listOrderIds, $app);
            if ($ret) {
                $app->addSuccess('admin.delete.complete', 'admin');
            } else {
                $app->addError('admin.delete.failed', 'admin');
            }

            // sessionのデータ保持
            $session = $request->getSession();
            $session->set('yamato.order.status', $data);
        }

        return $app->redirect($app->url('yamato_order_status_page', array(
                'page_no' => 1)
        ));
    }

    /**
     * 受注テーブルの論理削除
     *
     * @param array $listOrderIds 削除対象の受注ID
     * @param Application $app
     * @return bool 処理の成否
     */
    function deleteOrderData($listOrderIds, Application $app)
    {
        // 受注ID 情報がなければ以下を処理しない
        if (!isset($listOrderIds) || !is_array($listOrderIds)) {
            return false;
        }

        $app['orm.em']->getConnection()->beginTransaction();
        try {
            // 受注ID に紐づく受注データの del_flg を "1" に変更する
            foreach ($listOrderIds as $orderId => $value) {
                $Order = $app['eccube.repository.order']->find($orderId);

                if ($Order) {
                    $Order->setDelFlg(1);
                    $app['orm.em']->persist($Order);
                    $app['orm.em']->flush();
                }
            }
        } catch (\Exception $ex) {
            // DB処理中に問題が発生した場合は、元に戻して処理を終了する
            $app['orm.em']->getConnection()->rollback();
            return false;
        }

        $app['orm.em']->getConnection()->commit();

        return true;
    }

    /**
     * 決済状況 変更処理
     *
     * @param Application $app
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function changePaymentStatus(Application $app, Request $request)
    {
        // フォームの作成
        $builder = $app['form.factory']->createBuilder('yamato_order_status');
        $form = $builder->getForm();

        if ('POST' === $request->getMethod()) {

            // フォーム情報の取得
            $form->handleRequest($request);
            $data = $form->getData();

            $listOrderIds = $request->request->get('ids');

            // エラーチェック
            $changePaymentStatusErrors = $this->checkErrorOrder($listOrderIds, $data['payment_status'], $app);
            if (empty($changePaymentStatusErrors)) {
                // 決済状況変更処理
                $changePaymentStatusErrors = $this->doChangePaymentStatus($listOrderIds, $data['payment_status'], $app);
                if (empty($changePaymentStatusErrors)) {
                    $app->addSuccess('admin.register.complete', 'admin');
                } else {
                    $app->addError('admin.register.failed', 'admin');
                }
            } else {
                $app->addError('admin.register.failed', 'admin');
            }

            // sessionのデータ保持
            $session = $request->getSession();
            $session->set('yamato.order.status', $data);
            $session->set('yamato.order.status.error', $changePaymentStatusErrors);
        }

        return $app->redirect($app->url('yamato_order_status_page', array(
            'page_no' => 1)
        ));
    }

    /**
     * エラーチェック
     *
     * @param array $listOrderIds 対象受注ID
     * @param string $mode 決済モード
     * @param Application $app
     * @return array エラーメッセージ
     */
    public function checkErrorOrder($listOrderIds, $mode, Application $app)
    {
        $listErrMsg = array();
        $paymentUtil = $app['yamato_payment.util.payment'];

        foreach ($listOrderIds as $orderId => $value) {

            $msg = '注文番号 ' . $orderId . ' : ';

            // 受注ID から 支払方法ID を取得
            $Order = $app['eccube.repository.order']->find($orderId);
            $payment_id = $Order->getPayment()->getId();

            // 支払方法がヤマト決済かどうか判定する
            $YamatoPaymentMethod = $app['yamato_payment.repository.yamato_payment_method']->find($payment_id);
            if (is_null($YamatoPaymentMethod)) {
                $listErrMsg[] = $msg . '操作に対応していない決済です。';
                continue;
            }

            // 決済情報を取得
            /** @var YamatoOrderPayment $YamatoOrderPayment */
            $YamatoOrderPayment = $app['yamato_payment.repository.yamato_order_payment']->find($orderId);

            // 対応支払方法チェック
            if (!$paymentUtil->isCreditOrder($YamatoOrderPayment)
                && !$paymentUtil->isDeferredOrder($YamatoOrderPayment))
            {
                $listErrMsg[] = $msg . '操作に対応していない決済です。';
                continue;
            }

            // 出荷情報登録処理
            if ($mode == 'commit') {
                // クレジット決済
                if ($paymentUtil->isCreditOrder($YamatoOrderPayment)) {
                    // 出荷情報登録エラーチェック
                    $errorMsg = $paymentUtil->checkErrorShipmentEntryForCredit($orderId);
                    if (!empty($errorMsg)) {
                        $listErrMsg[] = $msg . $errorMsg;
                        continue;
                    }
                }

                // 後払い決済
                if ($paymentUtil->isDeferredOrder($YamatoOrderPayment)) {
                    // 出荷情報登録エラーチェック
                    $errorMsg = $paymentUtil->checkErrorShipmentEntryForDeferred($orderId);
                    if (!empty($errorMsg)) {
                        $listErrMsg[] = $msg . $errorMsg;
                        continue;
                    }
                }
            }
            // 再与信処理
            else if ($mode == 'reauth') {
                // 再与信エラーチェック
                $errorMsg = $paymentUtil->checkErrorReauthForCredit($orderId);
                if (!empty($errorMsg)) {
                    $listErrMsg[] = $msg . $errorMsg;
                    continue;
                }
            }
        }

        return $listErrMsg;
    }

    /**
     * 決済状況変更
     *
     * @param array $listOrderIds 対象受注ID
     * @param string $mode 決済モード
     * @param Application $app
     * @return array エラーメッセージ
     */
    public function doChangePaymentStatus($listOrderIds, $mode, $app)
    {
        $listErrorMsg = array();
        $const = $app['config']['YamatoPayment']['const'];

        $objClient = $app['yamato_payment.service.client.util'];
        $objDeferredClient = $app['yamato_payment.service.client.deferred_util'];

        foreach ($listOrderIds as $orderId => $value) {

            // 決済情報を取得
            $OrderExtension = $app['yamato_payment.util.payment']->getOrderPayData($orderId);
            $orderPaymentId = $OrderExtension->getYamatoOrderPayment()->getMemo03();

            // 決済状況変更処理
            $ret = false;

            // 出荷情報登録処理
            if ($mode == 'commit') {
                // 出荷登録
                if ($orderPaymentId == $const['YAMATO_PAYID_DEFERRED']) {
                    // クロネコ代金後払い決済用
                    $listRet = $objDeferredClient->doShipmentEntry($OrderExtension);
                    $ret = $listRet[0];
                } else {
                    // webコレクト決済用
                    list($ret, $listSuccessSlip) = $objClient->doShipmentEntry($OrderExtension);
                    if (!$ret) {
                        //複数配送時出荷情報登録ロールバック
                        $objClient->doShipmentRollback($OrderExtension, $listSuccessSlip);
                        $extraMsg = "\n".implode(', ',$objClient->getError());
                    }
                }
            } else if ($mode === 'cancel') {
                // 取消
                if ($orderPaymentId == $const['YAMATO_PAYID_DEFERRED']) {
                    // クロネコ代金後払い決済用
                    $ret = $objDeferredClient->doCancel($OrderExtension);
                } else {
                    // webコレクト決済用
                    $ret = $objClient->doCreditCancel($OrderExtension);
                    if ($ret) {
                        // 対応状況をキャンセルにする
                        $order_status = $app['config']['order_cancel'];
                        /** @var OrderStatus $OrderStatus */
                        $OrderStatus = $app['eccube.repository.order_status']->find($order_status);
                        $app['eccube.repository.order']->changeStatus($orderId, $OrderStatus);
                    } else {
                        $extraMsg = "\n".implode(', ',$objClient->getError());
                    }
                }
            } else if ($mode == 'reauth') {
                // 再与信
                if ($orderPaymentId == $const['YAMATO_PAYID_DEFERRED']) {
                    // クロネコ代金後払い決済は何もしない
                } else {
                    $ret = $objClient->doReauth($OrderExtension);
                    if ($ret) {
                        // 対応状況を新規受付にする
                        $order_status = $app['config']['order_new'];
                        /** @var OrderStatus $OrderStatus */
                        $OrderStatus = $app['eccube.repository.order_status']->find($order_status);
                        $app['eccube.repository.order']->changeStatus($orderId, $OrderStatus);
                    } else {
                        $extraMsg = "\n".implode(', ',$objClient->getError());
                    }
                }
            }

            if(!$ret){
                $listErrorMsg[] = '※ 以下の注文番号で 決済操作エラー が発生しました。';
                $listErrorMsg[] = '注文番号 ' . $orderId . ($extraMsg ? ' '.$extraMsg : '');
                return $listErrorMsg;
            }
        }

        return $listErrorMsg;
    }
}
