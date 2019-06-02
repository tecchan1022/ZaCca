<?php
/*
 * Copyright(c)2016, Yamato Financial Co.,Ltd. All rights reserved.
 * Copyright(c)2016, Yamato Credit finance Co.,Ltd. All rights reserved.
 */


namespace Plugin\YamatoPayment\Service\Client;

use Eccube\Application;
use Plugin\YamatoPayment\Entity\OrderExtension;
use Plugin\YamatoPayment\Entity\PaymentExtension;
use Plugin\YamatoPayment\Util\CommonUtil;

/**
 * 決済モジュール 決済処理 クレジットカード
 */
class CreditClientService extends BaseClientService
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
     * 3Dセキュア実行を行う.
     *
     * @param integer $order_id 受注ID
     * @param array $listParam その他情報
     * @param PaymentExtension $PaymentExtension 支払方法情報
     * @return bool
     */
    public function doSecureTran($order_id, $listParam, PaymentExtension $PaymentExtension)
    {
        $this->setResults($listParam);
        $OrderExtension = $this->paymentUtil->getOrderPayData($order_id);
        $YamatoOrderPayment = $OrderExtension->getYamatoOrderPayment();
        $memo05 = null;

        //決済データ確認
        if (!empty($YamatoOrderPayment)) {
            $memo05 = $YamatoOrderPayment->getMemo05();
        }
        if (!empty($memo05)) {
            $payData = $memo05;
        } else {
            $error_title = '3Dセキュア認証遷移エラー';
            $error_message = "決済データが受注情報に見つかりませんでした。";
            return $this->app['view']->render('error.twig', array(
                'error_message' => $error_message, 'error_title' => $error_title
            ));
        }

        //取引ID確認
        if (isset($payData['order_no']) && $payData['order_no'] != $order_id) {
            $error_title = '3Dセキュア認証遷移エラー';
            $error_message = "取引IDが一致しませんでした。";
            return $this->app['view']->render('error.twig', array(
                'error_message' => $error_message, 'error_title' => $error_title
            ));
        }

        // 送信キー
        $sendKey = array(
            'function_div',
            'trader_code',
            'order_no',
            'comp_cd',
            'card_no',
            'card_exp',
            'item_price',
            'item_tax',
            'cust_cd',
            'shop_id',
            'term_cd',
            'crd_res_cd',
            'res_ve',
            'res_pa',
            'res_code',
            'three_d_inf',
            'three_d_tran_id',
            'send_dt',
            'hash_value',
            'three_d_token',
        );

        // 3DセキュアURL
        $function_div = 'A02';
        $server_url = $this->getApiUrl($function_div);

        //機能区分
        $listParam['function_div'] = $function_div;
        //決済状況を「3Dセキュア認証中」で記録
        $listParam['action_status'] = $this->const['YAMATO_ACTION_STATUS_3D_WAIT'];
        //カード番号
        $listParam['card_no'] = $listParam['CARD_NO'];
        //有効期限
        $listParam['card_exp'] = $listParam['CARD_EXP'];
        //3Dトークン
        $listParam['threeDToken'] = $payData['threeDToken'];

        //3Dセキュア実行
        $ret = $this->sendOrderRequest($server_url, $sendKey, $order_id, $listParam, $PaymentExtension);

        return $ret;
    }

    /**
     * 3Dセキュア実行を行う.(トークン決済使用)
     *
     * @param integer $order_id 受注ID
     * @param array $listParam その他情報
     * @param PaymentExtension $PaymentExtension 支払方法情報
     * @return bool
     */
    public function doSecureTranToken($order_id, $listParam, PaymentExtension $PaymentExtension)
    {
        $this->setResults($listParam);
        $OrderExtension = $this->paymentUtil->getOrderPayData($order_id);
        $YamatoOrderPayment = $OrderExtension->getYamatoOrderPayment();
        $memo05 = null;

        //決済データ確認
        if (!empty($YamatoOrderPayment)) {
            $memo05 = $YamatoOrderPayment->getMemo05();
        }
        if (!empty($memo05)) {
            $payData = $memo05;
        } else {
            $error_title = '3Dセキュア認証遷移エラー';
            $error_message = "決済データが受注情報に見つかりませんでした。";
            return $this->app['view']->render('error.twig', array(
                    'error_message' => $error_message, 'error_title' => $error_title
            ));
        }

        //取引ID確認
        if (isset($payData['order_no']) && $payData['order_no'] != $order_id) {
            $error_title = '3Dセキュア認証遷移エラー';
            $error_message = "取引IDが一致しませんでした。";
            return $this->app['view']->render('error.twig', array(
                    'error_message' => $error_message, 'error_title' => $error_title
            ));
        }

        $sendKey = array(
                'function_div',
                'trader_code',
                'order_no',
                'comp_cd',
                'token',
                'card_exp',
                'item_price',
                'item_tax',
                'cust_cd',
                'shop_id',
                'term_cd',
                'crd_res_cd',
                'res_ve',
                'res_pa',
                'res_code',
                'three_d_inf',
                'three_d_tran_id',
                'send_dt',
                'hash_value',
                'three_d_token'
        );

        // 3DセキュアURL
        $function_div = 'A09';
        $server_url = $this->getApiUrl($function_div);

        //機能区分
        $listParam['function_div'] = $function_div;
        //決済状況を「3Dセキュア認証中」で記録
        $listParam['action_status'] = $this->const['YAMATO_ACTION_STATUS_3D_WAIT'];
        // 決済トークン
        $listParam['token'] = $listParam['TOKEN'];
        //有効期限
        $listParam['card_exp'] = $listParam['CARD_EXP'];
        //3Dトークン
        $listParam['threeDToken'] = $payData['threeDToken'];

        //3Dセキュア実行
        $ret = $this->sendOrderRequest($server_url, $sendKey, $order_id, $listParam, $PaymentExtension);

        return $ret;
    }


    /**
     * クレジット決済を行う.
     *
     * @param OrderExtension $OrderExtension 注文情報
     * @param array $listParam その他情報
     * @param PaymentExtension $PaymentExtension 支払方法情報
     * @return bool
     */
    public function doPaymentRequest(OrderExtension $OrderExtension, $listParam, PaymentExtension $PaymentExtension)
    {
        $listPaymentConfig = $PaymentExtension->getArrPaymentConfig();
        $YamatoOrderPayment = $OrderExtension->getYamatoOrderPayment();
        $Order = $OrderExtension->getOrder();
        if (!empty($YamatoOrderPayment)) {
            $memo05 = $YamatoOrderPayment->getMemo05();
            if (!empty($memo05)) {
                $OrderExtension->setPaymentData($memo05);
            }
        }

        // 会員IDの取得
        $customerId = '0';
        if (is_null($Order)) {
            $customerId = $OrderExtension->getCustomer()->getId();
        } else {
            if (!is_null($Order->getCustomer())) {
                $customerId = $Order->getCustomer()->getId();
            }
        }

        // 送信キー
        $sendKey = array(
            'function_div',
            'trader_code',
            'device_div',
            'order_no',
            'settle_price',
            'buyer_name_kanji',
            'buyer_tel',
            'buyer_email',
            'auth_div',
            'pay_way',
            'option_service_div',
        );

        // クレジット決済URL
        $function_div = 'A01';
        $server_url = $this->getApiUrl($function_div);

        // 機能区分
        $listParam['function_div'] = $function_div;
        // 決済ステータスを「決済手続き中」で記録
        $listParam['action_status'] = $this->const['YAMATO_ACTION_STATUS_WAIT'];

        /*
         * オプションサービス区分 00：通常受注 01：オプションサービス受注
         * (条件)
         * オプションサービス契約済み ：use_option==0(必須)
         * 購入時カード預かり         ：register_card==1
         * 預かりカードでの購入       ：use_registed_card==1
         * 予約商品購入               ：tpl_is_reserve==true
         */
        if ($this->userSettings['use_option'] == '0'
            && ($listParam['register_card'] == '1'
                || $listParam['use_registed_card'] == '1'
                || $listParam['tpl_is_reserve'] == true)
        ) {
            $listParam['option_service_div'] = '01';
        } else {
            $listParam['option_service_div'] = '00';
        }

        // 支払方法設定：本人認証サービス(3Dセキュア) 0:利用しない 1:利用する
        $listParam['auth_div'] = ($listPaymentConfig['TdFlag'] == '0') ? '2' : '1';

        // 3Dセキュア未加入・迂回時(3Dセキュア未加入/デバイスPC[デバイスコード:2]以外の場合)
        if ((isset($listParam['info_use_threeD'])
                && $listParam['info_use_threeD'] == $this->const['YAMATO_3D_EXCLUDED'])
            || CommonUtil::getDeviceDivision() != 2
        ) {
            $listParam['auth_div'] = '2';
        }

        //カード情報
        //オプションサービス区分が「00:通常受注」、または「01:オプションサービス受注」かつお預かりカード登録時
        if ($listParam['option_service_div'] == '00'
            || ($listParam['option_service_div'] == '01'
                && $listParam['register_card'] == '1')
        ) {
            $sendKey[] = 'card_code_api';                //カード会社コード(API用)
            $sendKey[] = 'card_no';                      //カード番号
            $sendKey[] = 'card_owner';                   //カード名義人
            $sendKey[] = 'card_exp';                     //カード有効期限
        }

        //セキュリティコード
        //認証区分が「2：３Ｄセキュアなし、セキュリティコード認証あり」
        if ($listParam['auth_div'] == '2') {
            $sendKey[] = 'security_code';                //セキュリティコード
        }

        //加盟店ECサイトURL
        //認証区分が「1：３Ｄセキュアあり、セキュリティコード認証なし」
        if ($listParam['auth_div'] == '1') {
            $sendKey[] = 'trader_ec_url';                //加盟点ECサイトURL
            $listParam['trader_ec_url'] = $this->app->url('yamato_shopping_payment', array('mode' => '3dTran'));
            //決済ステータスを「3Dセキュア認証中」で記録
            $listParam['action_status'] = $this->const['YAMATO_ACTION_STATUS_3D_WAIT'];
        }

        //オプションサービス区分が「01:オプションサービス受注」
        if ($listParam['option_service_div'] == '01') {
            $sendKey[] = 'member_id';                     //会員ＩＤ
            $sendKey[] = 'authentication_key';            //認証キー
            $sendKey[] = 'check_sum';                     //チェックサム
            $listParam['member_id'] = CommonUtil::getMemberId($customerId);
            $listParam['authentication_key'] = CommonUtil::getAuthenticationKey($customerId);
            $listParam['check_sum'] = CommonUtil::getCheckSum($listParam, $this->userSettings);
        }

        //オプションサービスで登録されているクレジットカード情報を利用
        if ($listParam['option_service_div'] == '01' && $listParam['use_registed_card'] == '1') {
            $sendKey[] = 'card_key';                      //カード識別キー
            $sendKey[] = 'last_credit_date';              //最終利用日時
            //最終利用日時をセット
            $listParam['lastCreditDate'] = $this->getLastCreditDate($customerId, $listParam['card_key']);
        }

        //予約商品購入の場合
        if ($listParam['option_service_div'] == '01' && $listParam['tpl_is_reserve'] == true) {
            $sendKey[] = 'scheduled_shipping_date';       //出荷予定日
            //出荷予定日取得
            $maxScheduledShippingDate = $this->paymentUtil->getMaxScheduledShippingDate($OrderExtension->getOrderID());
            //パラメータ用に整形してセット
            $listParam['scheduled_shipping_date'] = CommonUtil::getFormatedDate($maxScheduledShippingDate);
        }

        $ret = $this->sendOrderRequest(
            $server_url,
            $sendKey,
            $OrderExtension->getOrder()->getId(),
            $listParam,
            $PaymentExtension
        );

        return $ret;
    }

    /**
     * クレジットトークン決済を行う.
     *
     * @param OrderExtension $OrderExtension 注文情報
     * @param array $listParam その他情報
     * @param PaymentExtension $PaymentExtension 支払方法情報
     * @return bool
     */
    public function doPaymentTokenRequest(OrderExtension $OrderExtension, $listParam, PaymentExtension $PaymentExtension)
    {
        $listPaymentConfig = $PaymentExtension->getArrPaymentConfig();
        $YamatoOrderPayment = $OrderExtension->getYamatoOrderPayment();
        $Order = $OrderExtension->getOrder();
        if (!empty($YamatoOrderPayment)) {
            $memo05 = $YamatoOrderPayment->getMemo05();
            if (!empty($memo05)) {
                $OrderExtension->setPaymentData($memo05);
            }
        }

        // 会員IDの取得
        $customerId = '0';
        if (is_null($Order)) {
            $customerId = $OrderExtension->getCustomer()->getId();
        } else {
            if (!is_null($Order->getCustomer())) {
                $customerId = $Order->getCustomer()->getId();
            }
        }

        // 送信キー
        $sendKey = array(
                'function_div',
                'trader_code',
                'device_div',
                'order_no',
                'settle_price',
                'buyer_name_kanji',
                'buyer_tel',
                'buyer_email',
                'pay_way',
                'card_code_api',
                'token'
        );

        // クレジットトークン決済URL
        $function_div = 'A08';
        $server_url = $this->getApiUrl($function_div);

        // 機能区分
        $listParam['function_div'] = $function_div;
        // 決済ステータスを「決済手続き中」で記録
        $listParam['action_status'] = $this->const['YAMATO_ACTION_STATUS_WAIT'];

        // 支払方法設定：本人認証サービス(3Dセキュア) 0:利用しない 1:利用する
        $listParam['auth_div'] = ($listPaymentConfig['TdFlag'] == '0') ? '2' : '3';

        // 3Dセキュア未加入・迂回時(3Dセキュア未加入/デバイスPC[デバイスコード:2]以外の場合)
        $device = CommonUtil::getDeviceDivision();
        if ((isset($listParam['info_use_threeD']) && $listParam['info_use_threeD'] == $this->const['YAMATO_3D_EXCLUDED']) || ($device != 2 && $device != 1)) {
            $listParam['auth_div'] = '2';
        }

        //セキュリティコード
        //認証区分が「2：３Ｄセキュアなし、セキュリティコード認証あり」
        if ($listParam['auth_div'] == '2') {
            $sendKey[] = 'security_code';                //セキュリティコード
        }

        //加盟店ECサイトURL
        //認証区分が「2：３Ｄセキュアなし」
        if ($listParam['auth_div'] != '2') {
            $sendKey[] = 'trader_ec_url';                //加盟点ECサイトURL
            $listParam['trader_ec_url'] = $this->app->url('yamato_shopping_payment', array('mode' => '3dTran'));
            //決済ステータスを「3Dセキュア認証中」で記録
            $listParam['action_status'] = $this->const['YAMATO_ACTION_STATUS_3D_WAIT'];
        }

        /*
         * オプションサービス区分 00：通常受注 01：オプションサービス受注
        * (条件)
        * オプションサービス契約済み ：use_option==0(必須)
        * 購入時カード預かり         ：register_card==1
        * 預かりカードでの購入       ：use_registed_card==1
        * 予約商品購入               ：tpl_is_reserve==true
        */
        if ($this->userSettings['use_option'] == '0'
                && ($listParam['register_card'] == '1'
                        || $listParam['use_registed_card'] == '1'
                        || $listParam['tpl_is_reserve'] == true)
        ) {
            $listParam['option_service_div'] = '01';
        } else {
            $listParam['option_service_div'] = '00';
        }

        //予約商品購入の場合
        if ($listParam['option_service_div'] == '01' && $listParam['tpl_is_reserve'] == true) {
            $sendKey[] = 'scheduled_shipping_date';       //出荷予定日
            //出荷予定日取得
            $maxScheduledShippingDate = $this->paymentUtil->getMaxScheduledShippingDate($OrderExtension->getOrderID());
            //パラメータ用に整形してセット
            $listParam['scheduled_shipping_date'] = CommonUtil::getFormatedDate($maxScheduledShippingDate);
        }

        // カード会社コード
        // トークン決済の場合はカード会社コード9:VISAで固定
        $listParam['card_code_api'] = '9';

        // 決済トークン
        $listParam['token'] = $listParam['webcollectToken'];

        $ret = $this->sendOrderRequest(
                $server_url,
                $sendKey,
                $OrderExtension->getOrder()->getId(),
                $listParam,
                $PaymentExtension
        );

        return $ret;
    }

    /**
     * 使用カードの最終利用日取得
     *
     * @param integer $customer_id
     * @param integer $card_key
     * @return string $last_credit_date
     */
    private function getLastCreditDate($customer_id, $card_key)
    {
        $service = $this->app['yamato_payment.service.client.member'];
        // お預かり情報照会
        if (!$service->doGetCard($customer_id)) {
            $this->pluginUtil->printErrorLog($service->getError());
            $error_title = 'お預かり照会エラー';
            $error_message = "この手続きは無効となりました。";
            return $this->app['view']->render('error.twig', array(
                'error_message' => $error_message, 'error_title' => $error_title
            ));
        }
        $results = $this->getArrCardInfo($service->getResults());
        return $results['cardData'][($card_key - 1)]['lastCreditDate'];
    }

    /**
     * カード預かり情報取得（整理済）
     * 預かり情報１件の場合と２件以上の場合で配列の構造を合わせる
     *
     * @param array $cardInfo
     * @return array
     */
    private function getArrCardInfo($cardInfo)
    {
        if (isset($cardInfo['cardUnit']) && $cardInfo['cardUnit'] == '1') {
            $listTmp = array();
            foreach ($cardInfo as $key => $val) {
                if ($key == 'cardData') {
                    $listTmp[$key][0] = $val;
                } else {
                    $listTmp[$key] = $val;
                }
            }
            $results = $listTmp;
        } else {
            $results = $cardInfo;
        }
        return $results;
    }
}
