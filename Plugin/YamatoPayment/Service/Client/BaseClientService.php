<?php
/*
 * Copyright(c)2016, Yamato Financial Co.,Ltd. All rights reserved.
 * Copyright(c)2016, Yamato Credit finance Co.,Ltd. All rights reserved.
 */


namespace Plugin\YamatoPayment\Service\Client;

use Eccube\Application;
use Eccube\Entity\Order;
use Eccube\Util\Str;
use Guzzle\Service\Client;
use Guzzle\Http\Exception\ClientErrorResponseException;
use Guzzle\Http\Exception\ServerErrorResponseException;
use Plugin\YamatoPayment\Util\PluginUtil;
use Plugin\YamatoPayment\Entity\OrderExtension;
use Plugin\YamatoPayment\Entity\PaymentExtension;
use Plugin\YamatoPayment\Util\CommonUtil;
use Plugin\YamatoPayment\Util\PaymentUtil;

/**
 * 決済モジュール 決済処理 基底クラス
 */
class BaseClientService
{
    public $error = array();
    public $results = null;
    /**
     * @var \Eccube\Application
     */
    protected $app;
    /**
     * @var array 定数配列
     */
    protected $const;
    /**
     * @var array プラグイン設定
     */
    protected $userSettings;
    /**
     * @var PaymentUtil ペイメントユーティリティ
     */
    protected $paymentUtil;
    /**
     * @var PluginUtil プラグインユーティリティ
     */
    protected $pluginUtil;

    /**
     * コンストラクタ
     *
     * @param Application $app
     */
    public function __construct(Application $app)
    {
        $this->app = $app;
        $this->const = $app['config']['YamatoPayment']['const'];
        $this->paymentUtil = $app['yamato_payment.util.payment'];
        $this->pluginUtil = $app['yamato_payment.util.plugin'];
        $this->userSettings = $this->pluginUtil->getUserSettings();
    }

    /**
     * 送信用データ取得
     *
     * @param array $sendKey 送信項目
     * @param array $listParam その他情報
     * @param OrderExtension $OrderExtension 注文情報
     * @param PaymentExtension $PaymentExtension 支払方法情報
     * @return array
     */
    protected function getSendData(
        array $sendKey,
        array $listParam,
        OrderExtension $OrderExtension = null,
        PaymentExtension $PaymentExtension = null
    )
    {
        if (is_null($OrderExtension)) {
            $OrderExtension = new OrderExtension();
        }
        if (is_null($PaymentExtension)) {
            $PaymentExtension = new PaymentExtension();
        }

        // 受注情報取得
        $order = $OrderExtension->getOrder();
        if (is_null($order)) {
            $order = new Order();
        }
        // 受注ID
        $orderId = $order->getId();
        if (is_null($orderId)) {
            // ダミー注文の受注IDは$orderInfoにしか入っていない...
            $orderId = $OrderExtension->getOrderID();
        }

        // 支払情報取得 (plg_yamato_order_payment#memo05)
        $paymentData = $OrderExtension->getPaymentData();

        // 支払方法設定取得 (plg_yamato_payment_method#memo05)
        $paymentConfig = $PaymentExtension->getArrPaymentConfig();

        $sendData = array();
        foreach ($sendKey as $key) {
            switch ($key) {
                case 'trader_code':
                    $sendData[$key] = $this->userSettings['shop_id'];
                    break;
                case 'device_div':
                    $sendData[$key] = CommonUtil::getDeviceDivision();
                    break;
                case 'order_no':
                    $sendData[$key] = $orderId;
                    break;
                case 'settle_price':
                case 'new_price':
                    $sendData[$key] = $order->getPaymentTotal();
                    break;
                case 'buyer_name_kanji':
                    $sendData[$key] = CommonUtil::convertProhibitedChar($order->getName01() . '　' . $order->getName02());
                    break;
                case 'buyer_name_kana':
                    $sendData[$key] = CommonUtil::convertProhibitedChar($order->getKana01() . '　' . $order->getKana02());
                    break;
                case 'buyer_tel':
                    $sendData[$key] = $order->getTel01() . '-' . $order->getTel02() . '-' . $order->getTel03();
                    break;
                case 'buyer_email':
                    $sendData[$key] = $order->getEmail();
                    break;
                case 'goods_name':
                    $sendData[$key] = $this->getItemName($order);
                    break;
                case 'card_code_api':
                    if(empty($listParam[$key])) {
                        $sendData[$key] = (isset($listParam['card_no']))
                            ? $this->getCardCode($listParam['card_no'], $this->userSettings['exec_mode'])
                            : '';
                    } else {
                        $sendData[$key] = $listParam[$key];
                    }
                    break;
                case 'reserve_1':
                    $sendData[$key] = 'EC-CUBE' . $this->pluginUtil->getPluginVersion();
                    break;
                case 'last_credit_date':
                    $sendData[$key] = (isset($listParam['lastCreditDate']))
                        ? $listParam['lastCreditDate']
                        : '';
                    break;
                case 'card_exp':
                    $card_exp = isset($listParam['card_exp']) ? $listParam['card_exp'] : '';
                    $card_exp_month = isset($listParam['card_exp_month']) ? $listParam['card_exp_month'] : '';
                    $card_exp_year = isset($listParam['card_exp_year']) ? $listParam['card_exp_year'] : '';
                    $sendData[$key] = (!empty($card_exp))
                        ? $card_exp
                        : $card_exp_month . $card_exp_year;
                    break;
                //3Dセキュア用
                case 'comp_cd':
                case 'item_price':
                case 'item_tax':
                case 'cust_cd':
                case 'shop_id':
                case 'term_cd':
                case 'crd_res_cd':
                case 'res_ve':
                case 'res_pa':
                case 'res_code':
                case 'send_dt':
                case 'hash_value':
                    $paramName = strtoupper($key);
                    $sendData[$key] = (isset($listParam[$paramName]))
                        ? $listParam[$paramName]
                        : '';
                    break;
                case 'three_d_inf':
                    $sendData[$key] = (isset($listParam['3D_INF']))
                        ? $listParam['3D_INF']
                        : '';
                    break;
                case 'three_d_tran_id':
                    $sendData[$key] = (isset($listParam['3D_TRAN_ID']))
                        ? $listParam['3D_TRAN_ID']
                        : '';
                    break;
                case 'three_d_token':
                    $sendData[$key] = (isset($listParam['threeDToken']))
                        ? $listParam['threeDToken']
                        : '';
                    break;
                default:
                    //優先順位
                    //$listParam > $paymentData > $paymentConfig > $this->userSettings
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
     * 商品名取得
     *
     * @param Order $Order 受注データ
     * @return string 商品名
     */
    protected function getItemName($Order)
    {
        $OrderDetails = $Order->getOrderDetails();
        if ($OrderDetails->count() == 0) {
            return '';
        }
        /* @var \Eccube\Entity\OrderDetail $OrderDetail */
        $OrderDetail = $OrderDetails[0];
        $ret = $OrderDetail->getProductName();
        $ret = CommonUtil::convertProhibitedKigo($ret);
        $ret = CommonUtil::convertProhibitedChar($ret);
        $ret = CommonUtil::subString($ret, (int)$this->const['ITEM_NAME_LEN']);
        return $ret;
    }

    /**
     * 決済手段区分を取得する
     *
     *  //  1  ＵＣ
     *  //  2  ダイナース
     *  //  3  ＪＣＢ
     *  //  4  ＤＣ
     *  //  5  三井住友クレジット
     *  //  6  ＵＦＪ
     *  //  7  クレディセゾン
     *  //  8  ＮＩＣＯＳ
     *  //  9  ＶＩＳＡ
     *  // 10  ＭＡＳＴＥＲ
     *  // 11  イオンクレジット
     *  // 12  アメックス
     *  // 13  ＴＯＰ＆カード
     *
     * @param integer $card_no
     * @param string $exec_mode 動作モード 0:テスト環境、1:本番環境
     * @return integer
     */
    protected function getCardCode($card_no, $exec_mode = '0')
    {
        //初期値
        $retCode = 99;
        //  2  ダイナース
        if (preg_match('/^30[0-5]\d+$|^3095\d+$|^36\d+$|^3[8-9]\d+$/', $card_no)) {
            $retCode = 2;
        }
        //  3  ＪＣＢ
        if (preg_match('/^352[8-9]\d+$|^35[3-8]\d+$/', $card_no)) {
            $retCode = 3;
        }
        //  9  ＶＩＳＡ
        if (preg_match('/^4\d+$/', $card_no)) {
            $retCode = 9;
        }
        // 10  ＭＡＳＴＥＲ
        if (preg_match('/^5\d+$/', $card_no)) {
            $retCode = 10;
        }
        // 12  アメックス
        if (preg_match('/^34\d+$|^37\d+$/', $card_no)) {
            $retCode = 12;
        }
        // ダミーカードはVISAとして返す
        if ($exec_mode == '0' && preg_match('/^0000\d+$/', $card_no)) {
            $retCode = 9;
        }
        return $retCode;
    }

    /**
     * 注文情報リクエスト送信
     *
     * @param string $url 宛先URL
     * @param array $sendKey 送信項目
     * @param integer $order_id 受注ID
     * @param array $listParam その他情報
     * @param PaymentExtension $PaymentExtension 支払方法情報
     * @return bool
     */
    protected function sendOrderRequest($url, $sendKey, $order_id, $listParam, PaymentExtension $PaymentExtension, $OrderExtension = null)
    {
        //受注情報取得
        $isDummy = true;
        if(is_null($OrderExtension)) {
            $OrderExtension = $this->paymentUtil->getOrderPayData($order_id);
            $isDummy = false;
        }
        $OrderExtension->setOrderID($order_id);

        // 送信データ作成
        $sendData = $this->getSendData($sendKey, $listParam, $OrderExtension, $PaymentExtension);

        // リクエスト送信
        $ret = $this->sendRequest($url, $sendData);
        if ($ret) {
            $results = (array)$this->getResults();
            unset($results['threeDAuthHtml']);
        } else {
            $results = array();
            $results['error'] = $this->getError();
        }

        // 決済情報設定
        $results['order_no'] = $order_id;

        // 送信パラメータのマージ
        $paramNames = array(
            'function_div',
            'settle_price',
            'device_div',
            'option_service_div',
            'auth_div',
            'pay_way',
            'card_key',
            'card_code_api',
            'scheduled_shipping_date',
            'slip_no',
            'new_price',
        );
        foreach ($paramNames as $paramName) {
            if (isset($sendData[$paramName]) && !is_null($sendData[$paramName])) {
                $results[$paramName] = $sendData[$paramName];
            }
        }

        // 送信パラメータのマージ
        $paramNames = array(
            'action_status',
            'cvs',
        );
        foreach ($paramNames as $paramName) {
            if (isset($listParam[$paramName]) && !is_null($listParam[$paramName])) {
                $results[$paramName] = $listParam[$paramName];
            }
        }

        if($isDummy == false) {
            //決済ログの記録
            $YamatoOrderPayment = $this->paymentUtil->setOrderPayData($OrderExtension->getYamatoOrderPayment(), $results);
        }

        if (!empty($this->error)) {
            return false;
        }

        if($isDummy == false) {
            // 成功時のみ表示用データの構築
            $this->paymentUtil->setOrderPaymentViewData($YamatoOrderPayment, $results, $PaymentExtension);
        }

        return true;
    }

    /**
     * 汎用リクエスト送信
     *
     * @param string $url 宛先URL
     * @param array $sendParams 送信データ
     * @return bool
     */
    protected function sendRequest($url, $sendParams)
    {
        $this->clear();

        //送信パラメタのロギング
        $this->pluginUtil->printDebugLog('SendRequest:' . $url);
        $this->pluginUtil->printDebugLog($sendParams);

        $listData = array();
        foreach ($sendParams as $key => $value) {
            // UTF-8以外は文字コード変換を行う
            $encode = mb_detect_encoding($value);
            $listData[$key] = ($encode != 'UTF-8') ? mb_convert_encoding($value, 'UTF-8', $encode) : $value;
        }

        // 通信実行
        try {
            $curlOptions = array('CURLOPT_SSLVERSION' => 0);
            $requestOptions = array('connect_timeout' => $this->const['YAMATO_API_HTTP_TIMEOUT']);

            $client = new Client();
            $client->setConfig(array('curl.options' => $curlOptions));
            $request = $client->post($url, array(), $listData, $requestOptions);
            $response = $request->send();

        } catch (\Exception $e) {
            $msg = '通信エラー: ' . $e->getMessage() . "\n";
            $msg .= $e->getTraceAsString();
            $this->setError($msg);
            return false;
        }

        // ステータス取得
        $r_code = $response->getStatusCode();
        $this->pluginUtil->printDebugLog('Response:' . $r_code);

        // レスポンス取得
        $response_body = $response->getBody(true);
        if (is_null($response_body)) {
            $msg = 'レスポンスデータエラー: レスポンスがありません。';
            $this->setError($msg);
            return false;
        }

        // レスポンス解析
        $listRet = $this->parseResponse($response_body);
        $this->setResults($listRet);
        if (!empty($this->error)) {
            return false;
        }
        return true;
    }

    /**
     * エラーメッセージ設定
     *
     * @param string $msg
     */
    protected function setError($msg)
    {
        $this->pluginUtil->printErrorLog('Error ' . $msg);
        $this->error[] = $msg;
    }

    /**
     * エラーメッセージ取得
     *
     * @return array
     */
    public function getError()
    {
        return $this->error;
    }

    /**
     * レスポンスを解析する
     *
     * @param string $string レスポンス
     * @return array 解析結果
     */
    protected function parseResponse($string)
    {
        $string = trim($string);
        $listRet = $this->app['serializer']->decode($string, 'xml');
        if (isset($listRet['errorCode']) && !empty($listRet['errorCode'])) {
            $error_message = $this->getErrorMessageByErrorCode($listRet['errorCode']);
            $this->setError($error_message);
        }
        return $listRet;
    }

    /**
     * エラーコードに対応するエラーメッセージを取得する
     *
     * @param string $errorCode エラーコード
     * @return string エラーメッセージ
     */
    public function getErrorMessageByErrorCode($errorCode)
    {
        $errMsgList = $this->app['yamato_payment.error_message'];
        return isset($errMsgList[$errorCode]) ? $errMsgList[$errorCode] : "エラーコード: ".$errorCode;
    }

    /**
     * 通信結果を設定する
     *
     * @param array $results
     */
    protected function setResults($results)
    {
        $this->pluginUtil->printDebugLog($results);
        $this->results = $results;
    }

    /**
     * 通信結果を設定する
     *
     * @return array $results
     */
    public function getResults()
    {
        return $this->results;
    }

    /**
     * ヤマト決済API URLを取得
     *
     * @param string $code APIコード
     * @return string API URL
     */
    protected function getApiUrl($code)
    {
        $api_url = ($this->userSettings['exec_mode'] == '1') ? 'api.url' : 'api.test.gateway';
        return isset($this->const[$api_url][$code]) ? $this->const[$api_url][$code] : '';
    }

    /**
     * 初期化する.
     */
    protected function clear()
    {
        $this->error = array();
        $this->results = null;
    }

    /**
     * 不正なアタックを検知し一定回数を超えたかをチェック
     * @return boolean
     */
    public function checkMultiAtack(GetResponseEvent $event = null) {
        // 現在の失敗数を取得
        if($this->app['session']->has('yamato_payment.yfc_multi_atack_count')) {
            $yfc_multi_atack_count = $this->app['session']->get('yamato_payment.yfc_multi_atack_count');
        } else {
            $yfc_multi_atack_count = 0;
        }

        // 今回を加算
        $yfc_multi_atack_count++;

        // 規定の回数を超えているか確認
        if($yfc_multi_atack_count > $this->const['YAMATO_MULTI_ATACK_PERMIT_COUNT']) {
            $this->app['session']->set('yamato_payment.yfc_multi_atack', true);
            return true;
        }

        // 超えていない場合はウェイトをかける
        $this->app['session']->set('yamato_payment.yfc_multi_atack_count', $yfc_multi_atack_count);
        sleep($this->const['YAMATO_MULTI_ATACK_WAIT']);
        return false;
    }

    /**
     * 不正アタックのセッションをリセット
     */
    public function removeMultiAtack() {
        $this->app['session']->set('yamato_payment.yfc_multi_atack_count', 0);
        $this->app['session']->set('yamato_payment.yfc_multi_atack', false);
    }
}
