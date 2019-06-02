<?php
/*
 * Copyright(c)2016, Yamato Financial Co.,Ltd. All rights reserved.
 * Copyright(c)2016, Yamato Credit finance Co.,Ltd. All rights reserved.
 */


namespace Plugin\YamatoPayment\Controller;

use Eccube\Application;
use Eccube\Common\Constant;
use Eccube\Controller\AbstractController;
use Eccube\Entity\MailHistory;
use Eccube\Entity\Order;
use Plugin\YamatoPayment\Entity\OrderExtension;
use Plugin\YamatoPayment\Form\Type\CvsType;
use Plugin\YamatoPayment\Form\Type\RegistCreditType;
use Plugin\YamatoPayment\Form\Type\ThreeDTranType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use Plugin\YamatoPayment\Util\CommonUtil;

/**
 * 支払方法選択 コントローラクラス
 */
class PaymentController extends AbstractController
{
    public $dataReturn = array();
    public $error = array();
    public $threeDsecure;

    /**
     * 購入フロー(支払方法選択) ヤマト用の決済画面遷移処理
     *
     * @param $app
     * @param $request
     * @return string 遷移先の画面情報
     */
    public function index(Application $app, Request $request)
    {
        $cartService = $app['eccube.service.cart'];

        // 前のページで正しく登録手続きが行われた記録があるか判定
        if (!$app['yamato_payment.util.plugin']->isPrePage()) {
            $app->addError('不正なページ移動です。');
            return $app->redirect($app->url('shopping_error'));
        }

        // カートの商品種別チェック
        $result = $app['yamato_payment.util.payment']->checkCartProductType();
        if (!$result) {
            // カート画面へ戻る
            return $app->redirect($app->url('cart'));
        }

        // カートチェック
        if (!$cartService->isLocked()) {
            return $app->redirect($app->url('cart'));
        }

        // 受注情報存在チェック
        /** @var Order $order */
        $order = $app['eccube.service.shopping']->getOrder();
        if (!$order) {
            $app->addError('front.shopping.order.error');
            return $app->redirect($app->url('shopping_error'));
        }

        $em = $app['orm.em'];
        // 商品公開ステータスチェック、商品制限数チェック、在庫チェック
        $check = $app['eccube.service.shopping']->isOrderProduct($em, $order);
        if (!$check) {
            $app->addError('front.shopping.stock.error');
            return $app->redirect($app->url('shopping_error'));
        }

        // 支払方法の設定情報を取得する（決済モジュール管理対象である場合、内部識別コードを同時に設定する）
        $paymentExtension = $app['yamato_payment.util.payment']->getPaymentTypeConfig($order->getPayment()->getId());
        $paymentCode = $paymentExtension->getPaymentCode();
        $paymentInfo = $paymentExtension->getArrPaymentConfig();

        // ヤマト支払方法 設定データ（plg_yamato_payment_method # memo05）がない場合は、初期化
        if (empty($paymentInfo)) {
            $paymentInfo = array();
            $paymentInfo['use_securitycd'] = null;
            $paymentInfo['enable_customer_regist'] = false;
            $paymentInfo['pay_way'] = array();
            $paymentInfo['conveni'] = array();
        }

        // 受注データとヤマト支払方法 設定データ（plg_yamato_payment_method # memo05）を設定
        $orderExtension = new OrderExtension();
        $orderExtension->setOrder($order);
        $orderExtension->setPaymentData($paymentInfo);

        // 決済処理
        switch ($paymentCode) {
            // クレジットカード
            case $app['config']['YamatoPayment']['const']['YAMATO_PAYCODE_CREDIT']:
                return $this->creditProcess($order, $app, $request);
                break;

            // コンビニ
            case $app['config']['YamatoPayment']['const']['YAMATO_PAYCODE_CVS']:
                return $this->cvsProcess($order, $paymentInfo, $app, $request);
                break;

            // 後払い
            case $app['config']['YamatoPayment']['const']['YAMATO_PAYCODE_DEFERRED']:
                return $this->deferredProcess($order, $app, $request);
                break;

            default:
                break;
        }
        return null;
    }

    /**
     * クレジット決済画面
     *
     * @param Order $order 受注データ
     * @param Application $app
     * @param Request $request
     * @return string 遷移先の画面情報
     */
    public function creditProcess($order, $app, $request)
    {
        // 初期化
        $registcard_list = null;
        $default_card_key = null;
        $tpl_delete_card_success = null;

        /*
         * 予約商品販売機能の使用有無を判定する
         *     設定ファイルの「予約販売」の利用の有無を取得
         *     カート内商品が「予約販売対象商品」か判定する
         */
        //予約商品存在確認
        $tpl_is_reserv_service = $app['yamato_payment.util.payment']->isReservedOrder($order);

        /*
         * 預かりカードの機能の使用有無を判定する
         *    設定ファイルの「オプションサービス」の有無を取得
         *    登録カードの情報を取得する
         */
        $tpl_is_option_service = $app['yamato_payment.util.payment']->isOption();

        // 会員情報の取得
        $Customer = $order->getCustomer();

        // 会員情報がある、かつオプションサービス有りの場合
        if (!is_null($Customer) && $tpl_is_option_service) {
            //お預かり情報を取得
            list($registcard_list, $default_card_key) = $this->doGetCard($request->request->all(), $Customer->getId(), $app);
        }

        // 支払方法の設定情報を取得する（決済モジュール管理対象である場合、内部識別コードを同時に設定する）
        $paymentExtension = $app['yamato_payment.util.payment']->getPaymentTypeConfig($order->getPayment()->getId());
        $paymentCode = $paymentExtension->getPaymentCode();
        $paymentInfo = $paymentExtension->getArrPaymentConfig();
        if (empty($paymentInfo)) {
            $paymentInfo = array(
                'use_securitycd' => null,
                'enable_customer_regist' => false,
                'pay_way' => array(),
            );
        }

        $mode = $request->query->get('mode');
        if ($mode == '3dTran') {
            // 3Dセキュア返却項目用フォームを取得
            $ThreeDTran = new ThreeDTranType();
            $form = $app['form.factory']
                ->createBuilder($ThreeDTran)
                ->getForm();
        } else {
            // クレジットカード用のフォームを取得
            $creditForm = new RegistCreditType($app, $paymentInfo);
            $form = $app['form.factory']
                ->createBuilder($creditForm, $registcard_list)
                ->getForm();
            $mode = $request->request->get('mode');
        }

        // クレジット決済処理
        if ('POST' === $request->getMethod()) {

            $form->handleRequest($request);
            // フォームの入力チェック
            if ($form->isValid()) {

                // 設定値のチェック
                $response = $this->prepareOrderData($order, $app, $paymentCode);
                if (!is_null($response)) {
                    return $response;
                }

                // フォームデータ取得
                $formData = $form->getData();
                $formData = array_merge($formData, $_POST);

                if ($mode == '') {
                    $mode = (empty($formData['mode'])) ? 'next' : $formData['mode'];
                }

                // クレジット決済の支払方法を取得
                if (isset($_POST['type_submit']) && $_POST['type_submit'] == 'regist') {
                    $formData['Method'] = $formData['pay_way'];
                }

                // 決済処理（API処理）
                if($app['session']->has('yamato_payment.yfc_multi_atack') == false) {
                    $app['session']->set('yamato_payment.yfc_multi_atack', false);
                }
                $objPageHelper = $app['yamato_payment.helper.credit_page'];
                $objPageHelper->modeAction($request->request->all(), $mode, $formData, $order, $this);

                //ACSリダイレクト設定
                //ACS-URL(正確にはHTMLデータ)のcharsetがShift_JISのため文字コード変換
                if (isset($this->threeDsecure)) {
                    header('Content-Type: text/html; charset=Shift_JIS');
                    return new Response($this->threeDsecure);

                }

                if ($objPageHelper->isComplete) {
                    // 受注情報の手数料がnullの場合、0を設定する
                    if (is_null($order->getCharge())) {
                        $order->setCharge(0);
                        $app->flush();
                    }

                    $app['yamato_payment.service.client.credit']->removeMultiAtack();

                    // 購入完了処理
                    $this->completeOrder($order, $app);
                    return $app->redirect($app->url('shopping_complete'));
                } else {
                    // 不正利用チェック
                    if($app['session']->get('yamato_payment.yfc_multi_atack')) {
                        // カートクリア
                        $app['eccube.service.cart']->clear()->save();

                        $app['yamato_payment.service.client.credit']->removeMultiAtack();
                        $app->addError('この手続きは無効となりました。最初からやり直してください。');
                        return $app->redirect($app->url('shopping_error'));
                    }
                }

                // 3Dセキュア決済エラーの場合
                if ($mode == '3dTran') {
                    // クレジットカード用のフォームを再取得
                    $creditForm = new RegistCreditType($app, $paymentInfo);
                    $form = $app['form.factory']
                        ->createBuilder($creditForm, $registcard_list)
                        ->getForm();
                }

                // 選択カードを削除した場合
                if ($mode == 'deleteCard' && empty($this->error['payment'])) {
                    //お預かり情報を再取得
                    list($registcard_list, $default_card_key) = $this->doGetCard($request->request->all(), $Customer->getId(), $app);

                    $tpl_delete_card_success = true;
                }

            } else {
                $this->error['payment'] = '※ 入力内容に不備があります。内容をご確認ください。';
            }
        }

        //モードが空で預かりを取得できた場合（初期表示）
        if ($mode == '' && isset($registcard_list) && !isset($this->error['payment'])) {
            $form->get('use_registed_card')->setData(true);
        }

        return $app['view']->render('YamatoPayment/shopping_payment.twig', array(
            'block_file_path' => 'Block/YamatoPayment/payment_credit.twig',
            'form' => $form->createView(),
            'optionService' => $tpl_is_option_service,
            'reservService' => $tpl_is_reserv_service,
            'cardListData' => $registcard_list,
            'error' => $this->error,
            'paymentInfo' => $paymentInfo,
            'title' => $order->getPaymentMethod(),
            'customer' => $Customer,
            'default_card_key' => $default_card_key,
            'tpl_delete_card_success' => $tpl_delete_card_success,
            'moduleSettings' => $this->getModuleSettings($app, $paymentInfo, $order),
        ));
    }

    /**
     * コンビニ決済画面
     *
     * @param Order $order 受注データ
     * @param array $paymentInfo 支払情報
     * @param Application $app
     * @param Request $request
     * @return string 遷移先の画面情報
     */
    public function cvsProcess($order, $paymentInfo, $app, $request)
    {
        $CvsType = new CvsType($app);
        $form = $app['form.factory']
            ->createBuilder($CvsType)
            ->getForm();

        // ご注文完了処理
        if ('POST' === $request->getMethod()) {
            $form->handleRequest($request);

            if ($form->isValid()) {

                // フォームデータ取得
                $formData = $form->getData();

                // 支払方法の設定情報を取得する（決済モジュール管理対象である場合、内部識別コードを同時に設定する）
                $paymentExtension = $app['yamato_payment.util.payment']->getPaymentTypeConfig($order->getPayment()->getId());
                $paymentCode = $paymentExtension->getPaymentCode();

                // 設定値のチェック
                $response = $this->prepareOrderData($order, $app, $paymentCode);
                if (!is_null($response)) {
                    return $response;
                }

                // 決済処理（API処理）
                $objPageHelper = $app['yamato_payment.helper.cvs_page'];
                $objPageHelper->modeAction($formData, $order, $paymentExtension, $this);

                if ($objPageHelper->isComplete) {
                    // 購入完了処理
                    $this->completeOrder($order, $app);
                    return $app->redirect($app->url('shopping_complete'));
                }
            } else {
                $this->error['payment'] = '※ 入力内容に不備があります。内容をご確認ください。';
            }
        }

        // コンビニ決済画面の表示
        return $app['view']->render('YamatoPayment/shopping_payment.twig',
            array(
                'block_file_path' => 'Block/YamatoPayment/payment_conveni.twig',
                'title' => $order->getPaymentMethod(),
                'paymentInfo' => $paymentInfo,
                'error' => $this->error,
                'form' => $form->createView(),
            ));
    }

    /**
     * 後払い決済画面
     *
     * @param Order $order 受注データ
     * @param Application $app
     * @param Request $request
     * @return string 遷移先の画面情報
     */
    public function deferredProcess($order, $app, $request)
    {
        $tpl_payment_onload = true;

        $form = $app['form.factory']
            ->createBuilder()
            ->getForm();

        // ご注文完了処理
        if ('POST' === $request->getMethod()) {

            $form->handleRequest($request);

            if ($form->isValid()) {

                // 支払方法の設定情報を取得する（決済モジュール管理対象である場合、内部識別コードを同時に設定する）
                $paymentExtension = $app['yamato_payment.util.payment']->getPaymentTypeConfig($order->getPayment()->getId());
                $paymentCode = $paymentExtension->getPaymentCode();

                // 設定値のチェック
                $response = $this->prepareOrderData($order, $app, $paymentCode);
                if (!is_null($response)) {
                    return $response;
                }

                $objPageHelper = $app['yamato_payment.helper.deferred_page'];

                // フォームデータ生成
                $formData = $objPageHelper->createFormData($order);

                // 入力内容チェック
                $this->error = $objPageHelper->checkError($formData, $order);

                if (empty($this->error)) {

                    // API送信用パラメータに変換する
                    $objPageHelper->convertFormDataToSendParam($formData);

                    // 決済処理（API処理）
                    $objPageHelper->modeAction($formData, $order, $paymentExtension, $this);

                    if (!empty($this->error)) {
                        $tpl_payment_onload = false;
                    }

                    if ($objPageHelper->isComplete) {
                        // 購入完了処理
                        $this->completeOrder($order, $app);
                        return $app->redirect($app->url('shopping_complete'));
                    }
                } else {
                    $tpl_payment_onload = false;
                }
            } else {
                $tpl_payment_onload = false;
            }
        }

        // 後払い決済画面の表示
        return $app['view']->render('YamatoPayment/shopping_payment.twig',
            array(
                'block_file_path' => 'Block/YamatoPayment/payment_deferred.twig',
                'title' => $order->getPaymentMethod(),
                'tpl_payment_onload' => $tpl_payment_onload,
                'error' => $this->error,
                'form' => $form->createView(),
            ));
    }

    /**
     * 戻るボタンの処理
     *
     * @param $app
     * @return RedirectResponse
     */
    public function goBack(Application $app)
    {
        $cartService = $app['eccube.service.cart'];
        // カートチェック
        if (!$cartService->isLocked()) {
            // カートが存在しない、カートがロックされていない時はエラー
            return $app->redirect($app->url('cart'));
        }

        // カートチェック
        if (count($cartService->getCart()->getCartItems()) <= 0) {
            // カートが存在しない時はエラー
            return $app->redirect($app->url('cart'));
        }

        // ステータスの変更
        $order = $app['eccube.repository.order']->findOneBy(array('pre_order_id' => $app['eccube.service.cart']->getPreOrderId()));
        // 受注情報を更新（購入処理中として更新する）
        $order->setOrderStatus($app['eccube.repository.order_status']->find($app['config']['order_processing']));
        $app['orm.em']->flush();

        // リダイレクトで画面遷移
        return $app->redirect($app->url('shopping'));
    }

    /**
     * 購入完了処理
     *
     * @param Order $order
     * @param Application $app
     */
    protected function completeOrder($order, $app)
    {
        // 受注データ更新
        $this->updateOrder($order, $app);

        // メール送信
        $this->sendOrderMail($order, $app);

        // カートクリア
        $app['eccube.service.cart']->clear()->save();

        // 受注IDをセッションにセット
        $app['session']->set('eccube.front.shopping.order.id', $order->getId());
    }

    /**
     * 受注データ更新
     *
     * @param Order $order
     * @param Application $app
     */
    protected function updateOrder($order, $app)
    {
        $em = $app['orm.em'];
        $em->getConnection()->beginTransaction();

        $order->setOrderDate(new \DateTime());

        $app['eccube.service.shopping']->setStockUpdate($em, $order);
        if ($app->isGranted('ROLE_USER')) {
            // 会員の場合、購入金額を更新
            $app['eccube.service.shopping']->setCustomerUpdate($order, $app->user());
        }

        if (version_compare(Constant::VERSION, '3.0.10', '>=')) {
            // 受注完了を他プラグインへ通知する.
            $app['eccube.service.shopping']->notifyComplete($order);
        }

        $em->flush();
        $em->getConnection()->commit();
    }

    /**
     * 決済処理の事前チェック処理
     *
     * @param order $order
     * @param application $app
     * @param integer $paymentCode
     * @return Response|null
     */
    public function prepareOrderData($order, $app, $paymentCode)
    {
        // 受注情報が購入処理中となっているか確認
        $orderStatus = $order->getOrderStatus()->getId();

        if ($orderStatus != $app['config']['order_processing']) {
            switch ($orderStatus) {
                case  $app['config']['order_new']:
                case  $app['config']['order_pre_end']:
                    return $app->redirect($app->url('shopping_complete'));
                    break;
                case  $app['config']['order_pay_wait']:
                    // リンク型遷移での戻りは各ヘルパーに処理させる場合があるため、リダイレクトしない。
                    return $app->redirect($app->url('shopping_complete'));
                    break;
                default:
                    if (!is_null($orderStatus)) {
                        $error_title = 'エラー';
                        $error_message = "注文情報が無効です。この手続きは無効となりました。";
                        return $app['view']->render('error.twig', array(
                            'error_message' => $error_message,
                            'error_title' => $error_title
                        ));
                    }
                    break;
            }
        }
        if (empty($paymentCode)) {
            $error_title = 'エラー';
            $error_message = "注文情報の決済方法と決済モジュールの設定が一致していません。この手続きは無効となりました。管理者に連絡をして下さい。";
            return $app['view']->render('error.twig', array(
                'error_message' => $error_message,
                'error_title' => $error_title
            ));
        }

        return null;
    }

    /**
     * Send order mail
     * @param Order $Order
     * @param Application $app
     */
    private function sendOrderMail($Order, $app)
    {
        if (version_compare(Constant::VERSION, '3.0.10', '>=')) {
            $app['eccube.service.shopping']->sendOrderMail($Order);
        } else {
            $app['eccube.service.mail']->sendOrderMail($Order);

            // 送信履歴を保存.
            $MailTemplate = $app['eccube.repository.mail_template']->find(1);

            $body = $app->renderView($MailTemplate->getFileName(), array(
                'header' => $MailTemplate->getHeader(),
                'footer' => $MailTemplate->getFooter(),
                'Order' => $Order,
            ));

            // 決済情報差し込み処理
            $body = $app['yamato_payment.event.mail']->insertOrderMailBody(
                $body,
                $Order
            );

            $MailHistory = new MailHistory();
            $MailHistory
                ->setSubject('[' . $app['eccube.repository.base_info']->get()->getShopName() . '] ' . $MailTemplate->getSubject())
                ->setMailBody($body)
                ->setMailTemplate($MailTemplate)
                ->setSendDate(new \DateTime())
                ->setOrder($Order);
            $app['orm.em']->persist($MailHistory);
            $app['orm.em']->flush($MailHistory);
        }
    }

    /**
     * お預かりカード情報取得処理
     * @param string $request_all
     * @param integer $customer_id 顧客ID
     * @param Application $app
     * @return array
     */
    public function doGetCard($request_all, $customer_id, $app)
    {
        $objClient = $app['yamato_payment.service.client.member'];
        // お預かり照会実行
        $result = $objClient->doGetCard($customer_id, $request_all);

        if (!$result) {
            $listErr = $objClient->getError();
            if (isset($listErr)) {
                $this->error['payment'] = '※ お預かり照会でエラーが発生しました。<br />' . implode('<br />', $listErr);
            }
            return array(null, null);
        }

        $cardLists = $app['yamato_payment.helper.credit_page']->getArrCardInfo($objClient->getResults());

        // 初期化
        $registcard_list = null;
        $default_card_key = null;
        if (!is_null($cardLists)) {
            $lastCreditDate = 0;
            foreach ((array)$cardLists['cardData'] as $cardList) {
                $card['data'] = 'カード番号：' . $cardList['maskingCardNo'] . '　有効期限：'
                    . substr($cardList['cardExp'], 2) . '年'
                    . substr($cardList['cardExp'], 0, 2) . '月';
                $card['card_key'] = $cardList['cardKey'];
                $card['lastCreditDate'] = $cardList['lastCreditDate'];
                $registcard_list[] = $card;

                // カードの最終利用日を取得
                if ($lastCreditDate <= $cardList['lastCreditDate']) {
                    $default_card_key = $cardList['cardKey'];
                    $lastCreditDate = $cardList['lastCreditDate'];
                }
            }
        }

        return array($registcard_list, $default_card_key);
    }

    /**
     * クレジット決済画面表示用パラメーターを取得
     * @param application $app
     * @param array $paymentInfo
     * @param order $order
     * @return array
     */
    private function getModuleSettings($app, $paymentInfo, $order) {
        $moduleSettings = array();
        $listMdlSetting = $app['yamato_payment.util.plugin']->getUserSettings();

        if($listMdlSetting) {
            $moduleSettings = $listMdlSetting;
            $const = $app['config']['YamatoPayment']['const'];

            // トークン決済用URL取得 exec_mode = 0 : テスト、1 : 本番
            if($moduleSettings['exec_mode'] == '0') {
                $moduleSettings['tokenUrl'] = $const['TOKEN_URL_0'];
            } else {
                $moduleSettings['tokenUrl'] = $const['TOKEN_URL_1'];
            }

            // 3Dセキュアの利用およびセキュリティコードの利用によって認証区分を設定
            $moduleSettings['auth_div'] = $paymentInfo['TdFlag'] == '0' ? '2' : '3';
            $moduleSettings['useSecurityCode'] = $const['USE_SECURITY_CODE'];
            if($moduleSettings['useSecurityCode'] == '0') {
                if($moduleSettings['auth_div'] == '3') {
                    $moduleSettings['auth_div'] = '1';
                } else {
                    $moduleSettings['auth_div'] = '0';
                }
            }

            // 会員情報とチェックサム取得
            $Customer = $order->getCustomer();
            if($Customer) {
                $customerId = $order->getCustomer()->getId();
            } else {
                $customerId = '0';
            }
            $moduleSettings['member_id'] = '';
            $moduleSettings['authentication_key'] = '';
            $moduleSettings['no_member_check_sum'] = CommonUtil::getCheckSumForToken($moduleSettings, $listMdlSetting);
            $moduleSettings['member_id'] = CommonUtil::getMemberId($customerId);
            $moduleSettings['authentication_key'] = CommonUtil::getAuthenticationKey($customerId);
            $moduleSettings['check_sum'] = CommonUtil::getCheckSumForToken($moduleSettings, $listMdlSetting);

            $moduleSettings['autoRegist'] = is_null($paymentInfo['autoRegist']) ? '0' : $paymentInfo['autoRegist'];
            $moduleSettings['saveLimit'] = $app['config']['YamatoPayment']['const']['CREDIT_SAVE_LIMIT'];
        }

        return $moduleSettings;
    }
}
