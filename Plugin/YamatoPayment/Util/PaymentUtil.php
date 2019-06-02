<?php
/*
 * Copyright(c)2016, Yamato Financial Co.,Ltd. All rights reserved.
 * Copyright(c)2016, Yamato Credit finance Co.,Ltd. All rights reserved.
 */


namespace Plugin\YamatoPayment\Util;

use Eccube\Application;
use Eccube\Entity\Order;
use Eccube\Entity\OrderDetail;
use Plugin\YamatoPayment\Entity\OrderExtension;
use Plugin\YamatoPayment\Entity\YamatoOrderPayment;
use Plugin\YamatoPayment\Entity\PaymentExtension;
use Plugin\YamatoPayment\Entity\YamatoOrderScheduledShippingDate;
use Plugin\YamatoPayment\Entity\YamatoPaymentMethod;
use Plugin\YamatoPayment\Entity\YamatoProduct;

/**
 * 決済モジュール用 汎用関数クラス
 */
class PaymentUtil
{
    private $app;
    private $const;

    /**
     * コンストラクタ
     *
     * @param $app
     */
    public function __construct(Application $app)
    {
        $this->app = $app;
        $this->const = $this->app['config']['YamatoPayment']['const'];
    }

    /**
     * 決済方法の名前一覧を取得する
     *
     * @return array 決済方法一覧
     */
    public function getPaymentTypeNames()
    {
        return array(
            $this->const['YAMATO_PAYID_CREDIT'] => $this->const['YAMATO_PAYNAME_CREDIT'],
            $this->const['YAMATO_PAYID_CVS'] => $this->const['YAMATO_PAYNAME_CVS'],
            $this->const['YAMATO_PAYID_DEFERRED'] => $this->const['YAMATO_PAYNAME_DEFERRED'],
        );
    }

    /**
     * 決済方式の内部名一覧を取得する
     *
     * @return array 支払方法コード
     */
    public function getPaymentTypeCodes()
    {
        return array(
            $this->const['YAMATO_PAYID_CREDIT'] => $this->const['YAMATO_PAYCODE_CREDIT'],
            $this->const['YAMATO_PAYID_CVS'] => $this->const['YAMATO_PAYCODE_CVS'],
            $this->const['YAMATO_PAYID_DEFERRED'] => $this->const['YAMATO_PAYCODE_DEFERRED'],
        );
    }

    /**
     * 支払種別一覧を取得する
     *
     * @return array 支払回数
     */
    public function getCreditPayMethod()
    {
        return array(
            '1' => '一括払い',
            '2' => '分割2回払い',
            '3' => '分割3回払い',
            '5' => '分割5回払い',
            '6' => '分割6回払い',
            '10' => '分割10回払い',
            '12' => '分割12回払い',
            '15' => '分割15回払い',
            '18' => '分割18回払い',
            '20' => '分割20回払い',
            '24' => '分割24回払い',
            '0' => 'リボ払い',
        );
    }

    /**
     * コンビニの名称一覧を取得する
     *
     * @return array コンビニ情報
     */
    public function getConveni()
    {
        return array(
            $this->const['CONVENI_ID_SEVENELEVEN'] => $this->const['CONVENI_NAME_SEVENELEVEN'],
            $this->const['CONVENI_ID_LAWSON'] => $this->const['CONVENI_NAME_LAWSON'],
            $this->const['CONVENI_ID_FAMILYMART'] => $this->const['CONVENI_NAME_FAMILYMART'],
            $this->const['CONVENI_ID_SEICOMART'] => $this->const['CONVENI_NAME_SEICOMART'],
            $this->const['CONVENI_ID_MINISTOP'] => $this->const['CONVENI_NAME_MINISTOP'],
//            $this->const['CONVENI_ID_CIRCLEK'] => $this->const['CONVENI_NAME_CIRCLEK'],
        );
    }

    /**
     * 電子マネー決済の名称一覧を取得する
     *
     * @return array
     */
    public function getEmoney()
    {
        return array(
            $this->const['EMONEY_METHOD_RAKUTENEDY'] => $this->const['EMONEY_NAME_RAKUTENEDY'],
            $this->const['EMONEY_METHOD_M_RAKUTENEDY'] => $this->const['EMONEY_NAME_M_RAKUTENEDY'],
            $this->const['EMONEY_METHOD_SUICA'] => $this->const['EMONEY_NAME_SUICA'],
            $this->const['EMONEY_METHOD_M_SUICA'] => $this->const['EMONEY_NAME_M_SUICA'],
            $this->const['EMONEY_METHOD_WAON'] => $this->const['EMONEY_NAME_WAON'],
            $this->const['EMONEY_METHOD_M_WAON'] => $this->const['EMONEY_NAME_M_WAON'],
        );
    }

    /**
     * Webコレクト決済状況の名称一覧を取得
     *
     * @return array 決済状況
     */
    public function getPaymentStatus()
    {
        return array(
            $this->const['YAMATO_ACTION_STATUS_SEND_REQUEST'] => '決済依頼済み',
            $this->const['YAMATO_ACTION_STATUS_COMP_REQUEST'] => '決済申込完了',
            $this->const['YAMATO_ACTION_STATUS_PROMPT_REPORT'] => '入金完了（速報）',
            $this->const['YAMATO_ACTION_STATUS_DIFINIT_REPORT'] => '入金完了（確報）',
            $this->const['YAMATO_ACTION_STATUS_COMP_AUTH'] => '与信完了',
            $this->const['YAMATO_ACTION_STATUS_COMP_RESERVE'] => '予約受付完了',
            $this->const['YAMATO_ACTION_STATUS_NG_CUSTOMER'] => '購入者都合エラー',
            $this->const['YAMATO_ACTION_STATUS_NG_SHOP'] => '加盟店都合エラー',
            $this->const['YAMATO_ACTION_STATUS_NG_PAYMENT'] => '決済機関都合エラー',
            $this->const['YAMATO_ACTION_STATUS_NG_SYSTEM'] => 'その他システムエラー',
            $this->const['YAMATO_ACTION_STATUS_NG_RESERVE'] => '予約販売与信エラー',
            $this->const['YAMATO_ACTION_STATUS_NG_REQUEST_CANCEL'] => '決済依頼取消エラー',
            $this->const['YAMATO_ACTION_STATUS_NG_CHANGE_PAYMENT'] => '金額変更NG',
            $this->const['YAMATO_ACTION_STATUS_NG_TRANSACTION'] => '決済中断',
            $this->const['YAMATO_ACTION_STATUS_WAIT'] => '決済手続き中',
            $this->const['YAMATO_ACTION_STATUS_WAIT_SETTLEMENT'] => '精算確定待ち',
            $this->const['YAMATO_ACTION_STATUS_COMMIT_SETTLEMENT'] => '精算確定',
            $this->const['YAMATO_ACTION_STATUS_CANCEL'] => '取消',
            $this->const['YAMATO_ACTION_STATUS_3D_WAIT'] => '3Dセキュア認証中',
        );
    }

    /**
     * クレジットカード決済状況の名称一覧を取得
     *
     * @return array 決済状況
     */
    public function getCreditPaymentStatus()
    {
        return array(
            $this->const['YAMATO_ACTION_STATUS_COMP_AUTH'] => '与信完了',
            $this->const['YAMATO_ACTION_STATUS_COMP_RESERVE'] => '予約受付完了',
            $this->const['YAMATO_ACTION_STATUS_NG_CUSTOMER'] => '購入者都合エラー',
            $this->const['YAMATO_ACTION_STATUS_NG_SHOP'] => '加盟店都合エラー',
            $this->const['YAMATO_ACTION_STATUS_NG_PAYMENT'] => '決済機関都合エラー',
            $this->const['YAMATO_ACTION_STATUS_NG_SYSTEM'] => 'その他システムエラー',
            $this->const['YAMATO_ACTION_STATUS_NG_RESERVE'] => '予約販売与信エラー',
            $this->const['YAMATO_ACTION_STATUS_NG_REQUEST_CANCEL'] => '決済依頼取消エラー',
            $this->const['YAMATO_ACTION_STATUS_NG_CHANGE_PAYMENT'] => '金額変更NG',
            $this->const['YAMATO_ACTION_STATUS_NG_TRANSACTION'] => '決済中断',
            $this->const['YAMATO_ACTION_STATUS_WAIT'] => '決済手続き中',
            $this->const['YAMATO_ACTION_STATUS_WAIT_SETTLEMENT'] => '精算確定待ち',
            $this->const['YAMATO_ACTION_STATUS_COMMIT_SETTLEMENT'] => '精算確定',
            $this->const['YAMATO_ACTION_STATUS_CANCEL'] => '取消',
            $this->const['YAMATO_ACTION_STATUS_3D_WAIT'] => '3Dセキュア認証中',
        );
    }

    /**
     * コンビニ決済状況の名称一覧を取得
     *
     * @return array 決済状況
     */
    public function getCvsPaymentStatus()
    {
        return array(
            $this->const['YAMATO_ACTION_STATUS_SEND_REQUEST'] => '決済依頼済み',
            $this->const['YAMATO_ACTION_STATUS_COMP_REQUEST'] => '決済申込完了',
            $this->const['YAMATO_ACTION_STATUS_PROMPT_REPORT'] => '入金完了（速報）',
            $this->const['YAMATO_ACTION_STATUS_DIFINIT_REPORT'] => '入金完了（確報）',
            $this->const['YAMATO_ACTION_STATUS_NG_CUSTOMER'] => '購入者都合エラー',
            $this->const['YAMATO_ACTION_STATUS_NG_PAYMENT'] => '決済機関都合エラー',
            $this->const['YAMATO_ACTION_STATUS_NG_SYSTEM'] => 'その他システムエラー',
            $this->const['YAMATO_ACTION_STATUS_NG_TRANSACTION'] => '決済中断',
            $this->const['YAMATO_ACTION_STATUS_WAIT'] => '決済手続き中',
        );
    }

    /**
     * クロネコ代金後払い決済 与信結果取得
     */
    public function getCreditResult()
    {
        return array(
            $this->const['DEFERRED_AVAILABLE'] => 'ご利用可',
            $this->const['DEFERRED_NOT_AVAILABLE'] => 'ご利用不可',
            $this->const['DEFERRED_OVER_LIMIT'] => '限度額超過',
            $this->const['DEFERRED_UNDER_EXAM'] => '審査中',
        );
    }

    /**
     * クロネコ代金後払い決済 取引情報取得
     */
    public function getDeferredStatus()
    {
        return array(
            $this->const['DEFERRED_STATUS_AUTH_OK'] => '承認済み',
            $this->const['DEFERRED_STATUS_AUTH_CANCEL'] => '取消済み',
            $this->const['DEFERRED_STATUS_REGIST_DELIV_SLIP'] => '送り状番号登録済み',
            $this->const['DEFERRED_STATUS_RESEARCH_DELIV'] => '配送要調査',
            $this->const['DEFERRED_STATUS_SEND_WARNING'] => '警報メール送信済み',
            $this->const['DEFERRED_STATUS_SALES_OK'] => '売上確定',
            $this->const['DEFERRED_STATUS_SEND_BILL'] => '請求書発行済み',
            $this->const['DEFERRED_STATUS_PAID'] => '入金済み',
        );
    }

    /**
     * マスター：タイムコード 一覧取得
     */
    public function getDelivTimeCode()
    {
        return $this->const['DELIV_TIMECODE'];
    }

    /**
     * マスター：動作モード 一覧取得
     */
    public function getExecMode()
    {
        return array(
            0 => 'テスト環境',
            1 => '本番環境',
        );
    }

    /**
     * マスター：オプションサービス 一覧取得
     */
    public function getUseOption()
    {
        return array(
            0 => '契約済み',
            1 => '未契約',
        );
    }

    /**
     * マスター：利用 一覧取得
     */
    public function getUtilization()
    {
        return array(
            0 => '利用する',
            1 => '利用しない',
        );
    }

    /**
     * マスター：利用 一覧取得
     */
    public function getUtilizationFlg()
    {
        return array(
            0 => '利用しない',
            1 => '利用する',
        );
    }

    /**
     * マスター：請求書同梱 一覧取得
     */
    public function getSendDivision()
    {
        return array(
            0 => '同梱しない',
            1 => '同梱する',
        );
    }

    /**
     * マスター：出力 一覧取得
     */
    public function getOutput()
    {
        return array(
            1 => '出力する',
            0 => '出力しない',
        );
    }

    /**
     * マスター：送り状種別 一覧取得
     */
    public function getDelivSlipType()
    {
        return array(
            0 => '発払い',
            2 => 'コレクト',
            3 => 'DM便',
            4 => 'タイムサービス',
            5 => '着払い',
            6 => 'メール便速達サービス',
            7 => 'ネコポス',
            8 => '宅急便コンパクト',
            9 => 'コンパクトコレクト',
        );
    }

    /**
     * マスター：クール便区別 一覧取得
     */
    public function getCool()
    {
        return array(
            0 => '通常',
            1 => 'クール冷凍',
            2 => 'クール冷蔵'
        );
    }

    /**
     * マスター：ハイフンの有無 一覧取得
     */
    public function getHyphen()
    {
        return array(
            1 => 'ハイフンあり',
            0 => 'ハイフンなし',
        );
    }

    /**
     * マスター：ご依頼主出力 一覧取得
     */
    function getRequestOutput()
    {
        return array(
            0 => '注文者情報',
            1 => 'SHOPマスター基本情報',
            2 => '特定商取引法'
        );
    }

    /**
     * マスター：配送コード 一覧取得
     */
    public function getDeliveryCode()
    {
        return array(
            '00' => 'ヤマト配送',
            '99' => 'ヤマト配送以外',
        );
    }

    /**
     * マスター：他社配送設定一覧取得
     */
    public function getDeliveryServiceCode()
    {
        return array(
                '00' => 'ヤマト',
                '99' => '他社配送',
        );
    }

    /**
     * マスター：B2取込フォーマット 一覧取得
     */
    public function getB2ImportFormat()
    {
        return array(
            0 => '2項目',
            1 => '95項目以上（B2）'
        );
    }

    /**
     * 受注支払い情報を取得
     *
     * @param integer $orderId 受注ID
     * @return bool|\Plugin\YamatoPayment\Entity\OrderExtension
     */
    public function getOrderPayData($orderId)
    {
        // 受注データ取得
        /** @var Order $Order */
        $Order = $this->app['eccube.repository.order']
            ->find($orderId);

        if (is_null($Order)) {
            return false;
        }
        $YamatoOrderScheduledShippingDate = $this->app['yamato_payment.repository.yamato_order_scheduled_shipping_date']
            ->find($orderId);

        if (is_null($YamatoOrderScheduledShippingDate)) {
            $YamatoOrderScheduledShippingDate = new YamatoOrderScheduledShippingDate();
            $YamatoOrderScheduledShippingDate->setId($orderId);
        }

        // 受注決済データ取得
        $YamatoOrderPayment = $this->app['yamato_payment.repository.yamato_order_payment']
            ->find($orderId);

        // 支払方法ID取得
        /** @var YamatoPaymentMethod $YamatoPaymentMethod */
        $YamatoPaymentMethod = $this->app['yamato_payment.repository.yamato_payment_method']->find($Order->getPayment()->getId());

        if (is_null($YamatoOrderPayment)) {
            $YamatoOrderPayment = new YamatoOrderPayment();
            $YamatoOrderPayment->setId($orderId);

            if (!is_null($YamatoPaymentMethod)) {
                $YamatoOrderPayment->setMemo03($YamatoPaymentMethod->getMemo03());
            }
        } elseif (!is_null($YamatoPaymentMethod) && ($YamatoOrderPayment->getMemo03() != $YamatoPaymentMethod->getMemo03())) {
            $YamatoOrderPayment->setMemo03($YamatoPaymentMethod->getMemo03());
        }

        // 決済データ取得
        $paymentData = $YamatoOrderPayment->getMemo05();

        // 決済ログ取得
        $paymentData['payment_log'] = $YamatoOrderPayment->getMemo09();

        // 与信承認日
        $authDateTime = null;
        if($paymentData['payment_log']) {
            foreach($paymentData['payment_log'] as $logs) {
                foreach($logs as $datetime => $log) {
                    if(array_key_exists('returnCode', $log) && array_key_exists('function_div', $log)) {
                        if($log['returnCode'] == '0' && ($log['function_div'] == 'A08' || $log['function_div'] == 'A09' || $log['function_div'] == 'A11')) {
                            $authDateTime = $datetime;
                        }
                    }
                }
            }
        }
        $paymentData['auth_datetime'] = $authDateTime;

        $preOrderId = $Order->getPreOrderId();
        if (!empty($preOrderId)) {
            $paymentData['preOrderId'] = $preOrderId;
        }

        if (!isset($paymentData['register_card'])) {
            $paymentData['register_card'] = "";
        }

        // 受注データ拡張クラスを作成して返す
        $orderExtension = new OrderExtension();
        $orderExtension->setOrder($Order);
        $orderExtension->setPaymentData($paymentData);
        $orderExtension->setYamatoOrderScheduledShippingDate($YamatoOrderScheduledShippingDate);
        $orderExtension->setYamatoOrderPayment($YamatoOrderPayment);
        if (isset($paymentData['OrderID'])) {
            $orderExtension->setOrderID($paymentData['OrderID']);
        }
        return $orderExtension;
    }

    /**
     * 受注情報に決済情報をセット
     *
     * @param YamatoOrderPayment $YamatoOrderPayment 受注情報
     * @param array $payData 決済レスポンス array('key'=>'value')
     * @return YamatoOrderPayment
     */
    public function setOrderPayData(YamatoOrderPayment $YamatoOrderPayment, array $payData)
    {
        //決済情報チェック
        $char_code = $this->app['config']['char_code'];
        $payData = (array)CommonUtil::checkEncode($payData, $char_code);

        //受注情報から決済ログ取得
        $listLog = $YamatoOrderPayment->getMemo09();
        $listLog[] = array(date('Y-m-d H:i:s') => $payData);
        $YamatoOrderPayment->setMemo09($listLog);

        //受注情報から決済データ取得
        $paymentData = $YamatoOrderPayment->getMemo05();

        //決済データのマージ
        foreach ($payData as $key => $val) {
            if (empty($val) && !empty($paymentData[$key])) {
                unset($payData[$key]);
            }
        }
        $paymentData = array_merge($paymentData, (array)$payData);
        $YamatoOrderPayment->setMemo05($paymentData);

        //決済状況の記録
        if (isset($payData['action_status'])) {
            $YamatoOrderPayment->setMemo04($payData['action_status']);
        }
        //審査結果の記録
        if (isset($payData['result_code'])) {
            $YamatoOrderPayment->setMemo06($payData['result_code']);
        }

        $this->app['orm.em']->persist($YamatoOrderPayment);
        $this->app['orm.em']->flush();

        return $YamatoOrderPayment;
    }

    /**
     * 受注データに決済情報をセット
     *
     * @param YamatoOrderPayment $YamatoOrderPayment 受注決済情報
     * @param array $payData 決済レスポンス
     * @param PaymentExtension $PaymentExtension 支払方法情報
     * @return YamatoOrderPayment
     */
    public function setOrderPaymentViewData(
        YamatoOrderPayment $YamatoOrderPayment,
        $payData,
        PaymentExtension $PaymentExtension
    ) {
        $arrPaymentConfig = $PaymentExtension->getArrPaymentConfig();
        $order_id = $YamatoOrderPayment->getId();

        $memo02 = $YamatoOrderPayment->getMemo02();
        $memo05 = $YamatoOrderPayment->getMemo05();

        $listData = array();

        // 送信日時(yyyyMMddHHmmss)
        if (isset($payData['returnDate']) && !is_null($payData['returnDate'])) {
            $listData['returnDate']['name'] = '注文日時';
            if(isset($memo02['returnDate'])) {
                $listData['returnDate']['value'] = $memo02['returnDate']['value'];
            } else {
                $listData['returnDate']['value'] = CommonUtil::getDateFromNumber('Y年m月d日 H時i分s秒', $payData['returnDate']);
            }
        }
        // ご注文番号
        if (!is_null($order_id)) {
            $listData['OrderId']['name'] = 'ご注文番号';
            $listData['OrderId']['value'] = $order_id;
        }
        // 与信承認番号
        if (isset($memo05['crdCResCd']) && !is_null($memo05['crdCResCd'])) {
            $listData['crdCResCd']['name'] = '与信承認番号';
            $listData['crdCResCd']['value'] = $memo05['crdCResCd'];
        }

        /* セブン-イレブン決済 */
        // 払込票番号
        if (isset($payData['billingNo']) && !is_null($payData['billingNo'])) {
            $listData['billingNo']['name'] = '払込票番号';
            $listData['billingNo']['value'] = $payData['billingNo'];
        }
        // 払込票URL
        if (isset($payData['billingUrl']) && !is_null($payData['billingUrl'])) {
            $listData['billingUrl']['name'] = '払込票URL';
            $listData['billingUrl']['value'] = $payData['billingUrl'];
        }

        /* ファミリーマート決済 */
        // 企業コード
        if (isset($payData['companyCode']) && !is_null($payData['companyCode'])) {
            $listData['companyCode']['name'] = '企業コード';
            $listData['companyCode']['value'] = $payData['companyCode'];
        }
        // 注文番号(ファミリーマート)
        if (isset($payData['orderNoF']) && !is_null($payData['orderNoF'])) {
            $listData['orderNoF']['name'] = '注文番号(ファミリーマート)';
            $listData['orderNoF']['value'] = $payData['orderNoF'];
        }

        /* ローソン、サークルKサンクス、ミニストップ、セイコーマート決済 */
        // 受付番号
        if (isset($payData['econNo']) && !is_null($payData['econNo'])) {
            $listData['econNo']['name'] = '受付番号';
            $listData['econNo']['value'] = $payData['econNo'];
        }

        /* コンビニ決済共通 */
        // 支払期限日
        if (isset($payData['expiredDate']) && !is_null($payData['expiredDate'])) {
            $listData['expiredDate']['name'] = '支払期限日';
            $listData['expiredDate']['value'] = CommonUtil::getDateFromNumber('Y年m月d日', $payData['expiredDate']);
        }

        // 決済完了案内タイトル（クレジット）
        if (isset($arrPaymentConfig['order_mail_title']) && !is_null($arrPaymentConfig['order_mail_title'])
            && isset($arrPaymentConfig['order_mail_body']) && !is_null($arrPaymentConfig['order_mail_body'])
        ) {
            $listData['order_mail_title']['name'] = $arrPaymentConfig['order_mail_title'];
            $listData['order_mail_title']['value'] = $arrPaymentConfig['order_mail_body'];
        }

        // 決済完了案内タイトル（コンビニ）
        if (isset($payData['cvs']) && !is_null($payData['cvs'])) {
            $title_key = 'order_mail_title_' . $payData['cvs'];
            $body_key = 'order_mail_body_' . $payData['cvs'];
            if (isset($arrPaymentConfig[$title_key]) && !is_null($arrPaymentConfig[$title_key])
                && isset($arrPaymentConfig[$body_key]) && !is_null($arrPaymentConfig[$body_key])
            ) {
                $listData[$title_key]['name'] = $arrPaymentConfig[$title_key];
                $listData[$title_key]['value'] = $arrPaymentConfig[$body_key];
            }
        }

        if (!empty($listData)) {
            // 受注データ更新
            $listData['title']['value'] = '1';
            $listData['title']['name'] = $PaymentExtension->getYamatoPaymentMethod()->getMethod();
            $YamatoOrderPayment->setMemo02($listData);

            $this->app['orm.em']->persist($YamatoOrderPayment);
            $this->app['orm.em']->flush();
        }

        return $YamatoOrderPayment;
    }

    /**
     * 支払方法の設定情報を取得する
     *
     * @param integer $payment_id 支払方法ID
     * @return bool|PaymentExtension 支払方法拡張データ
     */
    public function getPaymentTypeConfig($payment_id)
    {
        $paymentExtension = new PaymentExtension();

        // 支払方法情報取得
        /** @var YamatoPaymentMethod $YamatoPaymentMethod */
        $YamatoPaymentMethod = $this->app['yamato_payment.repository.yamato_payment_method']
            ->find($payment_id);

        if (empty($YamatoPaymentMethod)) {
            return $paymentExtension;
        }

        // 取得した情報をヤマト支払方法情報に設定する
        $paymentExtension->setYamatoPaymentMethod($YamatoPaymentMethod);

        // 決済モジュールの対象決済であるかの判断と内部識別コード(config.yml # 支払方法種別ID)の設定を同時に行う。
        $listPaymentCode = $this->getPaymentTypeCodes();
        $paymentExtension->setPaymentCode($listPaymentCode[$YamatoPaymentMethod->getMemo03()]);

        // 支払方法情報をヤマト支払方法設定データに設定する
        $memo05 = $YamatoPaymentMethod->getMemo05();
        if (!empty($memo05)) {
            $paymentExtension->setArrPaymentConfig($memo05);
        }
        return $paymentExtension;
    }

    /**
     * 決済状況更新
     *
     * 各種リクエスト時の成功可否によって決決済ステータスID更新する際に利用する.
     * ログには記録しない
     *
     * @param OrderExtension $OrderExtension
     * @param string $pay_status
     */
    public function updateOrderPayStatus(OrderExtension $OrderExtension, $pay_status)
    {
        $YamatoOrderPayment = $OrderExtension->getYamatoOrderPayment();
        $YamatoOrderPayment->setMemo04($pay_status);

        $this->app['orm.em']->persist($YamatoOrderPayment);
        $this->app['orm.em']->flush($YamatoOrderPayment);
    }

    /**
     * 決済金額更新
     *
     * クレジット金額変更時に保持している決済金額を更新する際に利用する.
     * ログには記録しない
     *
     * @param OrderExtension $OrderExtension
     * @return void
     */
    public function updateOrderSettlePrice(OrderExtension $OrderExtension)
    {
        $YamatoOrderPayment = $OrderExtension->getYamatoOrderPayment();

        //変更後の金額で決済金額を上書きする
        $memo05 = $YamatoOrderPayment->getMemo05();
        // クレジットカード決済の場合
        if ($YamatoOrderPayment->getMemo03() == $this->const['YAMATO_PAYID_CREDIT']) {
            $memo05['settle_price'] = $OrderExtension->getOrder()->getPaymentTotal();
        }
        // クロネコ代金後払い決済の場合
        if ($YamatoOrderPayment->getMemo03() == $this->const['YAMATO_PAYID_DEFERRED']) {
            $memo05['totalAmount'] = $OrderExtension->getOrder()->getPaymentTotal();
        }
        $YamatoOrderPayment->setMemo05($memo05);

        $this->app['orm.em']->persist($YamatoOrderPayment);
        $this->app['orm.em']->flush($YamatoOrderPayment);
    }

    /**
     * 購入データと商品テーブルから予約商品出荷予定日を取得する
     *
     * １注文に対し複数の予約商品が存在する場合は
     * 出荷予定日が一番未来の日付を返す
     *
     * @param integer $order_id
     * @return string $maxScheduledDate YYYYMMDD
     */
    public function getMaxScheduledShippingDate($order_id)
    {
        $OrderDetails = $this->app['eccube.repository.order']
            ->find($order_id)
            ->getOrderDetails();

        /*
         * 購入商品明細情報取得
         * (1)商品種別ID
         * (2)予約商品出荷予定日
         */
        $maxScheduledDate = null;
        foreach ($OrderDetails as $OrderDetail) {
            /* @var OrderDetail $OrderDetail */
            // 商品種別取得
            $productTypeId = $OrderDetail
                ->getProductClass()
                ->getProductType()
                ->getId();

            // 予約商品の場合
            if ($productTypeId == $this->const['PRODUCT_TYPE_ID_RESERVE']) {
                // 商品マスタ拡張データ取得
                /** @var YamatoProduct $YamatoProduct */
                $YamatoProduct = $this->app['yamato_payment.repository.yamato_product']
                    ->find($OrderDetail->getProduct()->getId());
                if (is_null($YamatoProduct)) {
                    continue;
                }
                // 予約商品出荷予定日取得
                $reserveDate = $YamatoProduct->getReserveDate();
                // 予約商品出荷予定日が一番未来の日付を取得する
                if (!empty($reserveDate)
                    && $maxScheduledDate < $reserveDate
                ) {
                    $maxScheduledDate = $reserveDate;
                }
            }
        }
        return $maxScheduledDate;
    }

    /**
     * 予約販売可否判別
     * 　予約商品購入
     * 　モジュール設定値　オプションあり
     * 　再与信日期限を超えていない場合
     *
     * @param bool $reserveFlg
     * @param Order $Order
     * @return bool
     */
    public function isReserve($reserveFlg, Order $Order)
    {
        if (!$reserveFlg) {
            return false;
        }

        $userSettings = $this->app['yamato_payment.util.plugin']->getUserSettings();
        //オプションサービスを契約していない場合（0:契約済 1:未契約）
        if ($userSettings['use_option'] != '0') {
            return false;
        }

        //出荷予定日取得
        $scheduled_shipping_date = $this->app['yamato_payment.util.payment']->getMaxScheduledShippingDate($Order->getId());
        if (is_null($scheduled_shipping_date)) {
            return false;
        }

        return $this->isWithinReCreditLimit($scheduled_shipping_date);
    }

    /**
     * 予約商品再与信期限内チェック
     *
     * @param string $scheduled_shipping_date YYYYMMDD
     * @return bool 予約商品の再与信期限日（出荷予定日含む10日前）前の場合、true
     */
    public function isWithinReCreditLimit($scheduled_shipping_date)
    {
        //出荷予定日の9日前
        //再与信は出荷予定日を含む10日前のため9日前として算出
        $timestamp = strtotime($scheduled_shipping_date . ' -' . $this->const['YAMATO_DEADLINE_RECREDIT'] . ' day');
        $reCreditDate = date('Ymd', $timestamp);

        return (date('Ymd') < $reCreditDate) ? true : false;
    }

    /**
     * 注文に予約商品が含まれているか判別
     *
     * @param Order $Order
     * @return boolean 予約商品を含む場合、true
     */
    public function isReservedOrder($Order)
    {
        // 受注明細取得
        $OrderDetails = $Order->getOrderDetails();
        /* @var OrderDetail $OrderDetail */
        foreach ($OrderDetails as $OrderDetail) {
            // 商品種別取得
            $productTypeId = $OrderDetail
                ->getProductClass()
                ->getProductType()
                ->getId();
            // 予約商品の場合
            if ($productTypeId == $this->const['PRODUCT_TYPE_ID_RESERVE']) {
                return true;
            }
        }
        return false;
    }

    /**
     * オプションサービス可否判別
     * 　モジュール設定値　オプションあり
     *
     * @return bool
     */
    public function isOption()
    {
        $objMdl = $this->app['yamato_payment.util.plugin'];
        $listMdlSetting = $objMdl->getUserSettings();
        //オプションサービスを契約していない場合（0:契約済 1:未契約）
        if ($listMdlSetting['use_option'] != '0') {
            return false;
        }
        return true;
    }

    /**
     * 支払い方法チェック
     *
     * 戻り値
     *  true  支払方法不整合あり
     *  false 支払方法不整合なし
     *
     * POST値とpay_idとの支払方法整合性チェック
     * POST値では支払方法ではなく決済手段として送信されるため、以下のような間接的なチェックとする.
     *
     * (1)クレジットカード決済
     *    POST    settle_method 1～13, 99
     *            pay_id        10
     * (2)コンビニ決済
     *    POST    settle_method 21～26
     *            pay_id        30
     * (3)電子マネー(楽天Edy)
     *    POST    settle_method 61
     *            pay_id        42
     * (4)電子マネー(楽天モバイルEdy)
     *    POST    settle_method 62
     *            pay_id        43
     * (5)電子マネー(Suica決済)
     *    POST    settle_method 63
     *            pay_id        44
     * (6)電子マネー(モバイルSuica決済)
     *    POST    settle_method 64
     *            pay_id        45
     * (7)電子マネー(WAON決済)
     *    POST    settle_method 65
     *            pay_id        46
     * (8)電子マネー(モバイルWAON決済)
     *    POST    settle_method 66
     *            pay_id        47
     * (9)ネットバンク決済
     *    POST    settle_method 41
     *            pay_id        52
     *
     * @param  string $settle_method 決済手段(POST)
     * @param  string $pay_id 決済タイプ（識別ID)
     * @return bool
     */
    public function isCheckPaymentMethod($settle_method, $pay_id)
    {
        $isError = false;

        //(1)クレジットカード決済
        if ($settle_method >= $this->const['CREDIT_METHOD_UC'] && $settle_method <= $this->const['CREDIT_METHOD_TOP']
            && $pay_id != $this->const['YAMATO_PAYID_CREDIT']
        ) {
            $isError = true;
        }
        //(2)コンビニ決済
        if (($settle_method >= $this->const['CONVENI_ID_SEVENELEVEN'] && $settle_method <= $this->const['CONVENI_ID_CIRCLEK'])
            && $pay_id != $this->const['YAMATO_PAYID_CVS']
        ) {
            $isError = true;
        }
        //(3)電子マネー(楽天Edy)
        if ($settle_method == $this->const['EMONEY_METHOD_RAKUTENEDY'] && $pay_id != $this->const['YAMATO_PAYID_EDY']) {
            $isError = true;
        }
        //(4)電子マネー(楽天モバイルEdy)
        if ($settle_method == $this->const['EMONEY_METHOD_M_RAKUTENEDY'] && $pay_id != $this->const['YAMATO_PAYID_MOBILEEDY']) {
            $isError = true;
        }
        //(5)電子マネー(Suica決済)
        if ($settle_method == $this->const['EMONEY_METHOD_SUICA'] && $pay_id != $this->const['YAMATO_PAYID_SUICA']) {
            $isError = true;
        }
        //(6)電子マネー(モバイルSuica決済)
        if ($settle_method == $this->const['EMONEY_METHOD_M_SUICA'] && $pay_id != $this->const['YAMATO_PAYID_MOBILESUICA']) {
            $isError = true;
        }
        //(7)電子マネー(WAON決済)
        if ($settle_method == $this->const['EMONEY_METHOD_WAON'] && $pay_id != $this->const['YAMATO_PAYID_WAON']) {
            $isError = true;
        }
        //(8)電子マネー(モバイルWAON決済)
        if ($settle_method == $this->const['EMONEY_METHOD_M_WAON'] && $pay_id != $this->const['YAMATO_PAYID_MOBILEWAON']) {
            $isError = true;
        }
        //(9)ネットバンク決済
        if ($settle_method == $this->const['NETBANK_METHOD_RAKUTENBANK'] && $pay_id != $this->const['YAMATO_PAYID_NETBANK']) {
            $isError = true;
        }
        return $isError;
    }

    /**
     * 出荷情報登録エラーチェック（クレジット決済）
     *
     * @param integer $orderId 受注ID
     * @return string エラーメッセージ
     */
    public function checkErrorShipmentEntryForCredit($orderId)
    {
        /** @var YamatoOrderPayment $YamatoOrderPayment */
        $YamatoOrderPayment = $this->app['yamato_payment.repository.yamato_order_payment']
            ->find($orderId);

        /** @var Order $Order */
        $Order = $this->app['eccube.repository.order']
            ->find($orderId);
        $Shippings = $Order->getShippings();

        // 送り状番号情報リポジトリ
        $shippingDelivSlipRepo = $this->app['yamato_payment.repository.yamato_shipping_deliv_slip'];

        // 他社配送
        $user_settings = $this->app['yamato_payment.util.plugin']->getSubData();
        $delivery_service_code = $user_settings["user_settings"]["delivery_service_code"];

        // 支払い方法チェック
        if (!$this->isCreditOrder($YamatoOrderPayment)) {
            return '操作に対応していない決済です。';
        }
        // 取引状況チェック（与信完了）
        if ($YamatoOrderPayment->getMemo04() != $this->const['YAMATO_ACTION_STATUS_COMP_AUTH']) {
            return '操作に対応していない取引状況です。';
        }
        // 送り状番号の登録状態を確認する
        if (!$shippingDelivSlipRepo->isSlippingOn($orderId) && $delivery_service_code == '00') {
            return '送り状番号が登録されていない配送先が存在します。';
        }
        // 複数配送送り先上限チェック（99件まで）
        if ($Shippings->count() > $this->const['YAMATO_DELIV_ADDR_MAX']) {
            return '1つの注文に対する出荷情報の上限（' . $this->const['YAMATO_DELIV_ADDR_MAX'] . '件）を超えております。';
        }
//        // 共通送り状番号での注文同梱上限チェック
//        if ($shippingDelivSlipRepo->isUpperLimitedShippedNum($orderId)) {
//            return '同一の送り状番号で同梱可能な注文数（' . $this->const['YAMATO_SHIPPED_MAX'] . '件）を超えております。';
//        }
//        // 共通送り状番号で注文同梱時の配送先同一チェック
//        if ($shippingDelivSlipRepo->isExistUnequalShipping($orderId)) {
//            return '同一の送り状番号で配送先が異なるものが存在しています。';
//        }

        return null;
    }

    /**
     * 出荷情報登録エラーチェック（後払い決済）
     *
     * @param integer $orderId 受注ID
     * @return string エラーメッセージ
     */
    public function checkErrorShipmentEntryForDeferred($orderId)
    {
        // 受注支払い情報を取得
        /** @var YamatoOrderPayment $YamatoOrderPayment */
        $YamatoOrderPayment = $this->app['yamato_payment.repository.yamato_order_payment']
            ->find($orderId);

        /** @var Order $Order */
        $Order = $this->app['eccube.repository.order']
            ->find($orderId);
        $Shippings = $Order->getShippings();

        // 送り状番号情報リポジトリ
        $shippingDelivSlipRepo = $this->app['yamato_payment.repository.yamato_shipping_deliv_slip'];

        // 他社配送
        $user_settings = $this->app['yamato_payment.util.plugin']->getSubData();
        $delivery_service_code = $user_settings["user_settings"]["delivery_service_code"];

        // 支払い方法チェック
        if (!$this->isDeferredOrder($YamatoOrderPayment)) {
            return '「出荷情報登録」に対応していない決済です。';
        }
        // 送り状番号必須チェック
        if (!$shippingDelivSlipRepo->isSlippingOn($orderId) && $delivery_service_code == '00') {
            return '送り状番号が登録されていない配送先が存在します。';
        }
        // 審査結果チェック(ご利用可)
        if ($YamatoOrderPayment->getMemo06() != $this->const['DEFERRED_AVAILABLE']) {
            return '「出荷情報登録」に対応していない審査結果です。';
        }
        // 配送先数チェック
        if ($Shippings->count() > $this->const['DEFERRED_DELIV_ADDR_MAX']) {
            return '1つの注文に対するお届け先の上限（' . $this->const['DEFERRED_DELIV_ADDR_MAX'] . '件）を超えております。';
        }

        return null;
    }

    /**
     * 再与信エラーチェック（クレジット決済）
     *
     * @param integer $orderId 受注ID
     * @return string エラーメッセージ
     */
    public function checkErrorReauthForCredit($orderId)
    {
        /** @var YamatoOrderPayment $YamatoOrderPayment */
        $YamatoOrderPayment = $this->app['yamato_payment.repository.yamato_order_payment']->find($orderId);

        // 支払い方法チェック
        if (!$this->isCreditOrder($YamatoOrderPayment)) {
            return '操作に対応していない決済です。';
        }
        // 取引状況チェック（与信完了 or 取消）
        $paymentStatus = $YamatoOrderPayment->getMemo04();
        if ($paymentStatus != $this->const['YAMATO_ACTION_STATUS_COMP_AUTH'] && $paymentStatus != $this->const['YAMATO_ACTION_STATUS_CANCEL']) {
            return '操作に対応していない取引状況です。';
        }

        return null;
    }

    /**
     * クレジット決済判定
     *
     * @param YamatoOrderPayment $YamatoOrderPayment
     * @return bool クレジット決済の場合、true
     */
    public function isCreditOrder($YamatoOrderPayment)
    {
        if (!is_null($YamatoOrderPayment)
            && $YamatoOrderPayment->getMemo03() == $this->const['YAMATO_PAYID_CREDIT']
        ) {
            return true;
        }
        return false;
    }

    /**
     * 後払い決済判定
     *
     * @param YamatoOrderPayment $YamatoOrderPayment
     * @return bool 後払い決済の場合、true
     */
    public function isDeferredOrder($YamatoOrderPayment)
    {
        if (!is_null($YamatoOrderPayment)
            && $YamatoOrderPayment->getMemo03() == $this->const['YAMATO_PAYID_DEFERRED']
        ) {
            return true;
        }
        return false;
    }

    /**
     * クレジットカードお預かり情報削除
     * @param integer $customer_id 顧客ID
     * @param array $listParam パラメタ
     * @param object $objPage 呼出元ページオブジェクト
     * @return bool
     */
    function doDeleteCard($customer_id, $listParam, $objPage)
    {
        //お預かり情報照会
        $objClient = $this->app['yamato_payment.service.client.member'];
        $result = $objClient->doGetCard($customer_id, $listParam);

        if (!$result) {
            $listErr = $objClient->getError();
            $objPage->error['payment'] = '※ お預かり照会でエラーが発生しました。<br />' . implode('<br />', $listErr);
            return false;
        }

        $listResults = $this->getArrCardInfo($objClient->getResults());

        //削除対象のカード情報セット
        $deleteCardData = array();
        foreach ($listResults['cardData'] as $cardData) {
            if ($cardData['cardKey'] == $listParam['card_key']) {
                $deleteCardData = $cardData;
                break;
            }
        }

        if (empty($deleteCardData)) {
            $objPage->error['payment'] = '※ 削除するカードを選択してください。';
            return false;
        }

        //削除対象が予約販売利用有りの場合はエラーで返す
        if (isset($deleteCardData['subscriptionFlg']) && $deleteCardData['subscriptionFlg'] == '1') {
            $objPage->error['payment'] = '※ 予約販売利用有りのカード情報は削除できません。';
            return false;
        }

        // お預かり情報削除
        $result = $objClient->doDeleteCard($customer_id, $deleteCardData);

        if (!$result) {
            $listErr = $objClient->getError();
            $objPage->error['payment'] = '※ お預かり情報削除でエラーが発生しました。<br />' . implode('<br />', $listErr);
            return false;
        }

        return true;
    }

    /**
     * カード預かり情報取得（整理済）
     *
     * 預かり情報１件の場合と２件以上の場合で配列の構造を合わせる
     * @param array $listCardInfos
     * @return array $listResults
     */
    function getArrCardInfo($listCardInfos = array())
    {
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
        } else {
            $listResults = $listCardInfos;
        }
        return $listResults;
    }

    /**
     * 送り先区分を取得する
     *
     * @param Order $Order 受注データ
     * @param \Doctrine\Common\Collections\Collection $Shippings 配送データ
     * @return int 0:自分送り、1:自分以外、2:同梱
     */
    public function getSendDiv($Order, $Shippings)
    {
        // 送り先が複数であれば 1 を返す
        if (count($Shippings) > 1) {
            return 1;
        }

        // 単一配送で購入者情報と送り先情報が異なる場合 1 を返す
        if ($Order->getName01() != $Shippings[0]['name01']
            || $Order->getName02() != $Shippings[0]['name02']
            || $Order->getKana01() != $Shippings[0]['kana01']
            || $Order->getKana02() != $Shippings[0]['kana02']
            || $Order->getTel01() != $Shippings[0]['tel01']
            || $Order->getTel02() != $Shippings[0]['tel02']
            || $Order->getTel03() != $Shippings[0]['tel03']
            || $Order->getZip01() != $Shippings[0]['zip01']
            || $Order->getZip02() != $Shippings[0]['zip02']
            || $Order->getPref() != $Shippings[0]['pref']
            || $Order->getAddr01() != $Shippings[0]['addr01']
            || $Order->getAddr02() != $Shippings[0]['addr02']
        ) {
            return 1;
        }

        // 単一配送でモジュール設定値「請求書の同梱」が 1:同梱する になっている場合 2 を返す
        $subData = $this->app['yamato_payment.util.plugin']->getUserSettings();
        if ($subData['ycf_send_div'] == '1') {
            return 2;
        }

        return 0;
    }

    /**
     * 後払い決済送信用の注文明細情報を取得する.
     *
     * @param Order $Order
     * @return array
     */
    public function getOrderDetailDeferred($Order)
    {
        $results = array();

        // 注文商品明細
        $OrderDetails = $Order->getOrderDetails();
        foreach ($OrderDetails as $OrderDetail) {
            /** @var OrderDetail $OrderDetail */
            $results[] = array(
                'itemName' => $OrderDetail->getProductName(),
                'itemCount' => $OrderDetail->getQuantity(),
                'unitPrice' => $OrderDetail->getPriceIncTax(),
                'subTotal' => $OrderDetail->getPriceIncTax() * $OrderDetail->getQuantity(),
            );
        }

        // 送料、手数料
        $OrderKey = array(
            'delivery_fee_total' => '送料',
            'charge' => '手数料'
        );

        $OrderData = array();
        foreach ($OrderKey as $key => $name) {
            $OrderData[] = array(
                'itemName' => $name,
                'itemCount' => '',
                'unitPrice' => '',
                'subTotal' => $Order[$key]
            );
        }

        // 値引き対応
        $OrderData[] = array(
                'itemName' => '値引き',
                'itemCount' => '',
                'unitPrice' => '',
                'subTotal' => (strlen($Order['discount']) ? $Order['discount'] * -1 : 0)
        );

        // 明細が10行より多くなる場合、商品明細を丸める
        $detail_cnt = $OrderDetails->count();
        $order_cnt = count($OrderData);
        $round_cnt = ($detail_cnt + $order_cnt) - 10;

        if ($round_cnt > 0) {
            $round_subtotal = 0;
            for ($i = $detail_cnt - $round_cnt - 1; $i < $detail_cnt; $i++) {
                $round_subtotal = $round_subtotal + $results[$i]['subTotal'];
                unset($results[$i]);
            }
            $results[] = array(
                'itemName' => 'その他商品',
                'itemCount' => '',
                'unitPrice' => '',
                'subTotal' => $round_subtotal
            );
        }

        // 送料、手数料の明細
        foreach ($OrderData as $data) {
            $results[] = $data;
        }

        // キーを0から振り直す
        return array_merge($results);
    }

    /**
     * カートの中身の商品種別をチェックする
     *
     * @param bool $remove_flg 追加商品を削除する場合は、true
     * @return bool エラーがなければ、true
     */
    public function checkCartProductType($remove_flg = false)
    {
        // カート情報を取得
        $Cart = $this->app['eccube.service.cart']->getCart();

        $temp_product_type_id = null;
        foreach ($Cart->getCartItems() as $CartItem) {
            $ProductClass = $CartItem->getObject();
            $product_type_id = $ProductClass->getProductType()->getId();

            // 商品種別が異なり、カートの中の商品に予約商品が存在する場合
            if (isset($temp_product_type_id) && $temp_product_type_id != $product_type_id &&
                ($temp_product_type_id == $this->const['PRODUCT_TYPE_ID_RESERVE'] || $product_type_id == $this->const['PRODUCT_TYPE_ID_RESERVE'])
            ) {
                // 商品種別が異るエラーメッセージを表示
                $this->app->addRequestError('予約商品と通常商品は同時に購入できません。');

                if ($remove_flg == true) {
                    // エラーになった商品をカートから削除する
                    $this->app['eccube.service.cart']->removeProduct($ProductClass->getId())->save();
                }
                return false;

            }
            $temp_product_type_id = $product_type_id;
        }

        return true;
    }

}
