<?php
/*
 * Copyright(c)2016, Yamato Financial Co.,Ltd. All rights reserved.
 * Copyright(c)2016, Yamato Credit finance Co.,Ltd. All rights reserved.
 */


namespace Plugin\YamatoPayment\Service\Client;

use Eccube\Application;
use Eccube\Common\Constant;
use Plugin\YamatoPayment\Entity\OrderExtension;
use Plugin\YamatoPayment\Util\CommonUtil;

/**
 * 決済モジュール 決済処理 クレジットカードのお預かり処理
 */
class MemberClientService extends BaseClientService
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
     * クレジットカードお預かり情報照会
     *
     * @param integer $customer_id 顧客ID
     * @param array $listParam パラメタ
     * @return bool
     */
    public function doGetCard($customer_id, $listParam = array())
    {
        //オプションサービスを契約していない場合はお預かり照会を行わない（0:契約済 1:未契約）
        if ($this->userSettings['use_option'] != '0') {
            return false;
        }
        //非会員の場合はお預かり照会を行わない
        if (is_null($customer_id) || $customer_id == '0') {
            return false;
        }

        //API設定
        $function_div = 'A03';
        $server_url = $this->getApiUrl($function_div);

        //送信キー
        $listSendKey = array(
            'function_div',
            'trader_code',
            'member_id',
            'authentication_key',
            'check_sum',
        );

        // 送信パラメタ取得
        $sendParams = $this->getSendData($listSendKey, $listParam);

        // 個別パラメタ設定
        $sendParams['function_div'] = $function_div;
        $sendParams['member_id'] = $customer_id;
        $sendParams['authentication_key'] = $customer_id;
        $sendParams['check_sum'] = CommonUtil::getCheckSum($sendParams, $this->userSettings);

        // リクエスト送信
        return $this->sendRequest($server_url, $sendParams);
    }

    /**
     * クレジットカードお預かり情報登録
     * @param integer $customer_id 顧客ID
     * @param array $listParam パラメタ
     * @return bool
     */
    public function doRegistCard($customer_id, $listParam = array())
    {
        //非会員の場合はお預かり情報登録を行わない
        if (is_null($customer_id) || $customer_id == '0') {
            return false;
        }

        //API設定
        $function_div = 'A01';
        $server_url = $this->getApiUrl($function_div);

        //送信キー
        $listSendKey = array(
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
            'card_code_api',
            'card_no',
            'card_owner',
            'card_exp',
            'security_code',
            'member_id',
            'authentication_key',
            'check_sum',
        );

        // ダミー注文作成
        $OrderExtension = $this->getDummyOrder($customer_id);

        // 送信パラメタ取得
        $sendParams = $this->getSendData($listSendKey, $listParam, $OrderExtension);

        // 個別パラメータ設定
        $sendParams['function_div'] = $function_div;
        $sendParams['auth_div'] = '2';                //3Dセキュア利用しない
        $sendParams['pay_way'] = '1';                 //支払回数は強制的に「一括払い」
        $sendParams['option_service_div'] = '01';     //登録時のため 01:オプションサービス受注
        $sendParams['member_id'] = $customer_id;
        $sendParams['authentication_key'] = $customer_id;
        $sendParams['check_sum'] = CommonUtil::getCheckSum($sendParams, $this->userSettings);

        // リクエスト送信
        return $this->sendRequest($server_url, $sendParams);
    }

    /**
     * クレジットカードお預かり情報登録
     * @param integer $customer_id 顧客ID
     * @param array $listParam パラメタ
     * @return bool
     */
    public function doRegistCardToken($customer_id, $listParam = array())
    {
        //非会員の場合はお預かり情報登録を行わない
        if (is_null($customer_id) || $customer_id == '0') {
            return false;
        }

        //API設定
        $function_div = 'A08';
        $server_url = $this->getApiUrl($function_div);

        //送信キー
        $listSendKey = array(
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
                'card_code_api',
                'token'
        );

        // ダミー注文作成
        $OrderExtension = $this->getDummyOrder($customer_id);

        // 送信パラメタ取得
        $sendParams = $this->getSendData($listSendKey, $listParam, $OrderExtension);

        // 個別パラメータ設定
        $sendParams['function_div'] = $function_div;
        $sendParams['auth_div'] = '2';                //3Dセキュア利用しない
        $sendParams['pay_way'] = '1';                 //支払回数は強制的に「一括払い」
        $sendParams['option_service_div'] = '01';     //登録時のため 01:オプションサービス受注
        $sendParams['member_id'] = $customer_id;
        $sendParams['authentication_key'] = $customer_id;
        $sendParams['check_sum'] = CommonUtil::getCheckSum($sendParams, $this->userSettings);

        // カード会社コード
        // トークン決済の場合はカード会社コード9:VISAで固定
        $sendParams['card_code_api'] = '9';

        // 決済トークン
        $sendParams['token'] = $listParam['webcollectToken'];

        // リクエスト送信
        return $this->sendRequest($server_url, $sendParams);
    }

    /**
     * クレジットカードお預かり情報削除
     *
     * @param integer $customer_id 顧客ID
     * @param array $listParam パラメタ
     * @return bool
     */
    public function doDeleteCard($customer_id, $listParam = array())
    {
        //非会員の場合はお預かり情報削除を行わない
        if (is_null($customer_id) || $customer_id == '0') {
            return false;
        }

        //API設定
        $function_div = 'A05';
        $server_url = $this->getApiUrl($function_div);

        //送信キー
        $listSendKey = array(
            'function_div',
            'trader_code',
            'member_id',
            'authentication_key',
            'check_sum',
            'card_key',
            'last_credit_date',
        );

        // 送信パラメタ取得
        $sendParams = $this->getSendData($listSendKey, $listParam);

        //個別パラメタ設定
        $sendParams['function_div'] = $function_div;
        $sendParams['member_id'] = $customer_id;
        $sendParams['authentication_key'] = $customer_id;
        $sendParams['check_sum'] = CommonUtil::getCheckSum($sendParams, $this->userSettings);
        $sendParams['card_key'] = $listParam['cardKey'];   //預かり削除は事前に照会を行うため、照会レスポンスのキーを利用

        // リクエスト送信
        return $this->sendRequest($server_url, $sendParams);
    }

    /**
     * ダミー注文取得（カード情報登録用）.
     *
     * @param integer $customer_id 会員ID
     * @return OrderExtension ダミー注文データ
     */
    private function getDummyOrder($customer_id)
    {
        /* @var \Eccube\Entity\Customer $Customer */
        $Customer = $this->app['eccube.repository.customer']->find($customer_id);

        // ダミー注文情報作成
        $Order = $this->app['eccube.service.shopping']->getNewOrder($Customer);
        $Order->setPaymentTotal(1); //1円与信
        $Order->setDelFlg(Constant::ENABLED);

        $this->app['orm.em']->persist($Order);
        $this->app['orm.em']->flush();

        // 注文拡張情報へセット
        $OrderExtension = new OrderExtension();
        $OrderExtension->setOrderID($Order->getId());
        $OrderExtension->setOrder($Order);

        // 以後の処理で使用するため一時的に保存
        $this->order_id = $Order->getId();
        $this->OrderExtension = $OrderExtension;

        return $OrderExtension;
    }

    /**
     *
     * クレジット情報保存時に発生する1円決済を決済取り消しする。
     *
     * @param integer $orderId
     * @return boolean
     */
    function doCancelDummyOrder($orderId, $listParam=array(), $paymentExtension=null, $OrderExtension = null){
        // API送信用の値を取得する。

        //API設定
        $server_url = $this->getApiUrl('A06');
        //送信キー
        $arrSendKey = array(
                'function_div',
                'trader_code',
                'order_no'
        );
        //機能区分
        $listParam['function_div'] = 'A06';

        //ダミー受注のキャンセル処理
        return $this->sendOrderRequest($server_url, $arrSendKey, $orderId, $listParam, $paymentExtension, $OrderExtension);
    }
}
