<?php
/*
 * Copyright(c)2016, Yamato Financial Co.,Ltd. All rights reserved.
 * Copyright(c)2016, Yamato Credit finance Co.,Ltd. All rights reserved.
 */


namespace Plugin\YamatoPayment\Controller\Mypage;

use Eccube\Application;
use Eccube\Entity\Customer;
use Plugin\YamatoPayment\Entity\OrderExtension;
use Plugin\YamatoPayment\Form\Type\RegistCreditType;
use Symfony\Component\HttpFoundation\Request;
use Plugin\YamatoPayment\Util\CommonUtil;

/**
 * マイページ カード情報変更画面 コントローラクラス
 */
class MypageCardEditController
{
    public $error = array();

    public function index(Application $app, Request $request)
    {
        $subData = $app['yamato_payment.util.plugin']->getUserSettings();
        $const = $app['config']['YamatoPayment']['const'];

        // オプションサービス未契約（0:契約済 1:未契約）
        // または、クレジットカード決済が有効でない
        if ($subData['use_option'] != '0'
            || !in_array($const['YAMATO_PAYID_CREDIT'], $subData['enable_payment_type'])
        ) {
            $error_title = '';
            $error_message = '現在のご契約内容ではマイページカード編集ページはご利用になれません。お手数をおかけいたしますが、店舗運営者へお問い合わせください。';
            return $app['view']->render('error.twig', array(
                'error_message' => $error_message, 'error_title' => $error_title
            ));
        }

        $errors = array();
        $errorsAdd = null;
        $tpl_is_success = null;
        $default_card_key = null;

        $customer = $app['user'];
        /** @var Customer $objCustomer */
        $objCustomer = $app['eccube.repository.customer']->find($customer->getId());

        $orderExtension = new OrderExtension();
        $orderExtension->setCustomer($objCustomer);

        $registCard = new RegistCreditType($app);
        $objClient = $app['yamato_payment.service.client.member'];

        // フォームの取得
        $addForm = $app['form.factory']
            ->createBuilder($registCard)
            ->getForm();

        $delForm = $app['form.factory']
            ->createBuilder()
            ->add('cardSeq', 'collection', array(
                'type' => 'checkbox',
                'prototype' => true,
                'allow_add' => true,
            ))
            ->add('card_key', 'hidden')
            ->getForm();

        // 支払方法情報を取得
        $payment_id = $app['yamato_payment.repository.yamato_payment_method']->findOneBy(array('memo03' => $const['YAMATO_PAYID_CREDIT']))->getId();
        $this->paymentExtension = $app['yamato_payment.util.payment']->getPaymentTypeConfig($payment_id);
        $this->paymentInfo = $this->paymentExtension->getArrPaymentConfig();

        if ('POST' === $request->getMethod()) {
            $addForm->handleRequest($request);
            $delForm->handleRequest($request);

            // カード情報登録
            if ($addForm->getData() != null) {
                if ($addForm->isValid()) {
                    $formData = $addForm->getData();
                    //カード情報登録
                    if ($this->doRegistCard($objCustomer->getId(), $formData, $this, $app)) {
                        $tpl_is_success = true;
                        // フォームの初期化
                        $addForm = $app['form.factory']
                            ->createBuilder($registCard)
                            ->getForm();
                    }
                    $errors = $this->error;
                } else {
                    $errors['error'] = '※ 入力内容に不備があります。内容をご確認ください。';
                }
            }

            // カード情報削除
            if ($delForm->getData() != null) {
                $formData = $delForm->getData();
                //お預かり情報削除
                if ($app['yamato_payment.util.payment']->doDeleteCard($objCustomer->getId(), $formData, $this)) {
                    $tpl_is_success = true;
                }
                $errors = $this->error;
            }
        }

        // お預かり情報照会実行
        $result = $objClient->doGetCard($objCustomer->getId(), array());
        $cardList = array();

        if (!$result) {
            $listErr = $objClient->getError();
            if (count($listErr) > 0) {
                $errors['payment'] = '※ お預かり照会でエラーが発生しました。<br />' . implode('<br />', $listErr);
            }

        } else {

            // お預かり情報取得
            $cardList = $app['yamato_payment.helper.credit_page']->getArrCardInfo($objClient->getResults());
            $cardList = $cardList['cardData'];

            // 初期化
            $registcard_list = null;
            $default_card_key = null;
            if (!is_null($cardList)) {
                $lastCreditDate = 0;
                foreach ($cardList as $key => $value) {
                    $cardList[$key]['card_key'] = $cardList[$key]['cardKey'];

                    // 削除後のみ、カード最終利用日が一番新しいカードキーを取得する
                    if ($delForm->getData() != null) {
                        // カードの最終利用日を取得
                        if ($lastCreditDate <= $cardList[$key]['lastCreditDate']) {
                            $default_card_key = $cardList[$key]['card_key'];
                            $lastCreditDate = $cardList[$key]['lastCreditDate'];
                        }
                    }
                }
            }
        }

        return $app['view']->render('YamatoPayment/mypage_card_edit.twig', array(
            'cardList' => $cardList,
            'form' => $addForm->createView(),
            'form2' => $delForm->createView(),
            'errorsAdd' => $errorsAdd,
            'errors' => $errors,
            'tpl_is_success' => $tpl_is_success,
            'default_card_key' => $default_card_key,
            'moduleSettings' => $this->getModuleSettings($app, $objCustomer->getId()),
        ));
    }

    /**
     * お預かり情報登録
     * @param integer $customer_id 顧客ID
     * @param array $listParam パラメタ
     * @param object $objPage 呼出元ページオブジェクト
     * @param Application $app
     * @return bool
     */
    function doRegistCard($customer_id, $listParam, $objPage, $app)
    {
        //お預かり情報照会
        $objClient = $app['yamato_payment.service.client.member'];
        $result = $objClient->doGetCard($customer_id, $listParam);

        // オプションサービスを契約していない または 非会員の場合
        if (!$result) {
            $listErr = $objClient->getError();
            $objPage->error['error'] = '※ お預かり照会でエラーが発生しました。<br />' . implode('<br />', $listErr);
            return false;
        }

        // カード預かり情報取得
        $listResults = $this->getArrCardInfo($objClient->getResults());

        //登録数上限チェック（3件）
        if ($listResults['cardUnit'] >= $app['config']['YamatoPayment']['const']['CREDIT_SAVE_LIMIT']) {
            $listErr = $objClient->getError();
            $objPage->error['error2'] = '※ カードお預かりは' . $app['config']['YamatoPayment']['const']['CREDIT_SAVE_LIMIT'] . '件までとなっております。<br />' . implode('<br />', $listErr);
            return false;
        }

        //カード情報登録
        $result = $objClient->doRegistCardToken($customer_id, $listParam);
        if (!$result) {
            $listErr = $objClient->getError();
            $objPage->error['error2'] = '※ カード情報登録でエラーが発生しました。<br />' . implode('<br />', $listErr);
            return false;
        }

        // 与信チェックのために行った1円決済を取り消す
        $objClient->doCancelDummyOrder($objClient->order_id, $listParam, $this->paymentExtension, $objClient->OrderExtension);

        return true;
    }

    /**
     * カード預かり情報取得（整理済）
     *
     * 預かり情報１件の場合と２件以上の場合で配列の構造を合わせる
     * @param array $listCardInfos
     * @return array
     */
    function getArrCardInfo($listCardInfos = array())
    {
        // 預かり情報1件の場合
        if (isset($listCardInfos['cardUnit']) && $listCardInfos['cardUnit'] == '1') {
            $listTmp = array();
            foreach ($listCardInfos as $key => $val) {
                if ($key == 'cardData') {
                    $listTmp[$key][0] = $val;
                } else {
                    $listTmp[$key] = $val;
                }
            }
            $listResults = $listTmp;
            // ２件以上の場合
        } else {
            $listResults = $listCardInfos;
        }

        return $listResults;
    }

    /**
     * クレジット決済用パラメーターを取得
     * @param application $app
     * @param integer $customerId
     * @return array
     */
    private function getModuleSettings($app, $customerId) {
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

            // マイページでは3Dセキュアは使用しない
            $this->paymentInfo['TdFlag'] = '0';

            // 3Dセキュアの利用およびセキュリティコードの利用によって認証区分を設定
            $moduleSettings['auth_div'] = $this->paymentInfo['TdFlag'] == '0' ? '2' : '3';
            $moduleSettings['useSecurityCode'] = $const['USE_SECURITY_CODE'];
            if($moduleSettings['useSecurityCode'] == '0') {
                if($moduleSettings['auth_div'] == '3') {
                    $moduleSettings['auth_div'] = '1';
                } else {
                    $moduleSettings['auth_div'] = '0';
                }
            }

            // チェックサム取得
            $moduleSettings['member_id'] = '';
            $moduleSettings['authentication_key'] = '';
            $moduleSettings['no_member_check_sum'] = CommonUtil::getCheckSumForToken($moduleSettings, $listMdlSetting);
            $moduleSettings['member_id'] = CommonUtil::getMemberId($customerId);
            $moduleSettings['authentication_key'] = CommonUtil::getAuthenticationKey($customerId);
            $moduleSettings['check_sum'] = CommonUtil::getCheckSumForToken($moduleSettings, $listMdlSetting);
        }

        return $moduleSettings;
    }
}
