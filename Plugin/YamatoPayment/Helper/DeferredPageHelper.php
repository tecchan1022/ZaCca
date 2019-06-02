<?php
/*
 * Copyright(c)2016, Yamato Financial Co.,Ltd. All rights reserved.
 * Copyright(c)2016, Yamato Credit finance Co.,Ltd. All rights reserved.
 */


namespace Plugin\YamatoPayment\Helper;

use Eccube\Application;
use Eccube\Entity\Order;
use Eccube\Entity\Payment;
use Eccube\Entity\Shipping;
use Eccube\Util\Str;
use Plugin\YamatoPayment\Entity\PaymentExtension;
use Plugin\YamatoPayment\Util\CommonUtil;

/**
 * 決済モジュール 決済画面ヘルパー：クロネコ代金後払い決済
 */
class DeferredPageHelper
{
    /** @var Application */
    protected $app;
    public $isComplete = false;
    public $error;

    /**
     * コンストラクタ
     *
     * @param Application $app
     */
    public function __construct(Application $app)
    {
        $this->app = $app;
    }

    /**
     * 画面モード毎のアクションを行う
     *
     * @param array $listParam フォームデータ
     * @param Order $Order 受注情報
     * @param PaymentExtension $paymentInfo 支払方法の設定情報
     * @param $objPage
     */
    public function modeAction($listParam, $Order, $paymentInfo, $objPage)
    {
        $this->isComplete = false;

        $order_id = $Order->getId();
        //受注情報取得
        $orderExtension = $this->app['yamato_payment.util.payment']->getOrderPayData($order_id);
        // 決済実行
        $objClient = $this->app['yamato_payment.service.client.deferred'];
        $result = $objClient->doPaymentRequest($orderExtension, $listParam, $paymentInfo);

        if ($result) {
            //注文状況を「新規受付」へ
            $order_status = $this->app['config']['order_new'];
            $Order->setOrderStatus($this->app['eccube.repository.order_status']->find($order_status));
            $this->app['orm.em']->persist($Order);
            $this->app['orm.em']->flush();
            $this->isComplete = true;
        } else {
            $error = $objClient->getError();
            $objPage->error['payment'] = '※ 決済でエラーが発生しました。<br />' . implode('<br />', $error);
        }
    }

    /**
     * クロネコ代金後払い用のフォームの生成
     *
     * @param Order $Order 受注情報
     * @return array フォーム情報
     */
    public function createFormData($Order)
    {
        // ユーザー設定の取得
        $userSettings = $this->app['yamato_payment.util.plugin']->getUserSettings();

        // 出荷情報を取得
        $Shippings = $Order->getShippings();

        // 出荷区分を取得
        $sendDiv = $this->app['yamato_payment.util.payment']->getSendDiv($Order, $Shippings);

        $formData = array(
            // 基本情報エリア
            'ycfStrCode' => $userSettings['ycf_str_code'],
            'orderNo' => $Order->getId(),
            'orderYmd' => date_format($Order->getCreateDate(), 'Ymd'),
            'shipYmd' => date('Ymd',
                strtotime('+' . $userSettings['ycf_ship_ymd'] . 'day', $Order->getCreateDate()->getTimestamp())),
            'name' => $Order->getName01() . '　' . $Order->getName02(),
            'nameKana' => $Order->getKana01() . ' ' . $Order->getKana02(),
            'postCode' => $Order->getZip01() . $Order->getZip02(),
            'address1' => $Order->getPref() . $Order->getAddr01() . '　'. $Order->getAddr02(),
            'address2' => null,
            'telNum' => $Order->getTel01() . $Order->getTel02() . $Order->getTel03(),
            'email' => $Order->getEmail(),
            'totalAmount' => $Order->getPaymentTotal(),
            'sendDiv' => $sendDiv,
            // カート社識別コード
            'cartCode' => 'eccube3',
            // 共通情報エリア
            'requestDate' => date('YmdHis'),
            'password' => $userSettings['ycf_str_password'],
        );

        // 明細情報を取得
        $formData['details'] = $this->app['yamato_payment.util.payment']->getOrderDetailDeferred($Order);

        // 送り先情報エリア
        $formData['shippings'] = array();
        foreach ($Shippings as $Shipping) {
            /** @var Shipping $Shipping */
            $formData['shippings'][] = array(
                'sendName' => $Shipping->getName01() . '　' . $Shipping->getName02(),
                'sendPostCode' => $Shipping->getZip01() . $Shipping->getZip02(),
                'sendAddress1' => $Shipping->getPref()->getName() . $Shipping->getAddr01(). '　'. $Shipping->getAddr02(),
                'sendAddress2' => null,
                'sendTelNum' => $Shipping->getTel01() . $Shipping->getTel02() . $Shipping->getTel03(),
            );
        }
        // 文字種変換
        $this->convFormData($formData);

        // 文字列を切り取る
        $this->subStrFormData($formData);

        return $formData;
    }

    /**
     * 文字種変換.
     *
     * @param array $data
     * @param string $encoding
     */
    private function convFormData(&$data, $encoding = 'utf-8')
    {
        foreach ($data as $key => &$value) {
            if (!is_array($value) && strlen($value) == 0) {
                continue;
            }
            switch ($key) {
                case 'name':
                case 'address1':
                case 'address2':
                case 'sendName':
                case 'sendAddress1':
                case 'sendAddress2':
                case 'itemName':
                    // 半角→全角変換
                    $value = CommonUtil::convHalfToFull($value, $encoding);
                    break;
                case 'nameKana':
                    // 全角かな→全角カナ変換
                    $value = mb_convert_kana($value, 'k', $encoding);
                    break;
                case 'details':
                case 'shippings':
                    // 再帰呼び出し
                    foreach ($value as &$arrVal) {
                        $this->convFormData($arrVal);
                    }
                    break;
                default:
                    break;
            }
        }
    }

    /**
     * 文字列を切り取る.
     *
     * @param array $formData
     * @param string $encoding
     */
    private function subStrFormData(&$formData, $encoding = 'utf-8')
    {
        $formData['name'] = mb_substr($formData['name'], 0, 30, $encoding);
        $formData['nameKana'] = mb_substr($formData['nameKana'], 0, 80, $encoding);
        $address = $formData['address1'];
        $formData['address1'] = mb_substr($address, 0, 25, $encoding);
        if (mb_substr($address, 25, 25, $encoding) != '') {
            $formData['address2'] = mb_substr($address, 25, 25, $encoding);
        }
        $formData['email'] = mb_substr($formData['email'], 0, 64, $encoding);
        foreach($formData['details'] as &$detail){
            $detail['itemName'] = mb_substr($detail['itemName'], 0, 30, $encoding);
        }
        foreach($formData['shippings'] as &$shipping){
            $shipping['sendName'] = mb_substr($shipping['sendName'], 0, 30, $encoding);
            $sendAddress = $shipping['sendAddress1'];
            $shipping['sendAddress1'] = mb_substr($sendAddress, 0, 25, $encoding);
            if (mb_substr($sendAddress, 25, 25, $encoding) != '') {
                $shipping['sendAddress2'] = mb_substr($sendAddress, 25, 25, $encoding);
            }
        }
    }

    /**
     * 後払い決済のチェックを行なう.
     *
     * @param array $listParam
     * @param Order $Order
     * @return array
     */
    public function checkError(&$listParam, $Order)
    {
        $errors = array();
        $const = $this->app['config']['YamatoPayment']['const'];

        /** @var Payment $Payment */
        $Payment = $Order->getPayment();

        // 決済金額総計が支払方法利用条件を満たしていなければエラー
        if (($Payment->getRuleMax() != null && $listParam['totalAmount'] > $Payment->getRuleMax())
            || ($Payment->getRuleMax() != null&& $listParam['totalAmount'] < $Payment->getRuleMin())
        ) {
            $errors['totalAmount'] = '※ 決済金額総計が支払方法利用条件を満たしておりません。<br />';
        }

        // 配送先数が10より多ければエラー
        if (count($listParam['shippings']) > $const['DEFERRED_DELIV_ADDR_MAX']) {
            $errors['sendCount'] = '※ 送り先の上限数は' . $const['DEFERRED_DELIV_ADDR_MAX'] . '件です。<br />';
        }

        // 送り先区分が「1:自分以外」の場合必須
        if ($listParam['sendDiv'] == '1') {
            for ($i = 0; $i < count($listParam['shippings']); $i++) {
                $shipping = $listParam['shippings'][$i];
                $seq = $i + 1;
                if (Str::isBlank($shipping['sendName'])) {
                    $errors['sendName' . $seq] = '※ 送り先名称' . $seq . 'が入力されていません。<br />';
                }
                if (Str::isBlank($shipping['sendPostCode'])) {
                    $errors['sendPostCode' . $seq] = '※ 送り先郵便番号' . $seq . 'が入力されていません。<br />';
                }
                if (Str::isBlank($shipping['sendAddress1'])) {
                    $errors['sendAddress1' . $seq] = '※ 送り先住所' . $seq . 'が入力されていません。<br />';
                }
            }
        }

        // 注文商品明細
        for ($i = 0; $i < count($listParam['details']); $i++) {
            $detail = $listParam['details'][$i];
            $seq = $i + 1;
            // 購入商品数量最大値チェック
            if (Str::isNotBlank($detail['itemCount']) && $detail['itemCount'] > 9999) {
                $errors['itemCount' . $seq] = '※ 商品数量は9999までです。<br />';
            }
            // 購入商品単価エラーチェック
            if (Str::isNotBlank($detail['unitPrice'])) {
                $price_abs = abs($detail['unitPrice']);
                if ($price_abs > 999999) {
                    $errors['unitPrice' . $seq] = '※ 商品単価が不正です。<br />';
                }
            }
            // 購入商品小計エラーチェック
            if (Str::isNotBlank($detail['subTotal'])) {
                $subtotal_abs = abs($detail['subTotal']);
                if ($subtotal_abs > 999999) {
                    $errors['subTotal' . $seq] = '※ 商品小計が不正です。<br />';
                }
            }
        }

        // APIパスワードのエラー文言変更
        if (Str::isBlank($listParam['password'])) {
            $errors['password'] = 'パスワードが不正です。店舗までお問合わせ下さい。<br />';
        }

        // 注文者の電話番号桁数エラーチェック
        if (strlen($listParam['telNum']) > 11) {
            $errors['telNum'] = '注文者の電話番号は10桁または11桁で入力してください。<br />';
        }

        // 配送先の電話番号桁数エラーチェック
        for ($i = 0; $i < count($listParam['shippings']); $i++) {
            $shipping = $listParam['shippings'][$i];
            $seq = $i + 1;
            if (strlen($shipping['sendTelNum']) > 11) {
                $errors['sendTelNum'.$seq] = 'お届け先(' . $seq . ')の電話番号は10桁または11桁で入力してください。<br />';
            }
        }

        return $errors;
    }

    /**
     * API送信用パラメータに変換する
     *
     * @param array $listParam
     */
    public function convertFormDataToSendParam(&$listParam)
    {
        // 商品購入エリア
        foreach ($listParam['details'] as $key => $val) {
            $seq = $key + 1;
            $listParam['itemName' . $seq] =  $val['itemName'];
            $listParam['itemCount' . $seq] =  $val['itemCount'];
            $listParam['unitPrice' . $seq] =  $val['unitPrice'];
            $listParam['subTotal' . $seq] =  $val['subTotal'];
        }
        unset($listParam['details']);

        // 送り先情報エリア
        foreach ($listParam['shippings'] as $key => $val) {
            $seq = $key + 1;
            if ($seq == 1) {
                // 1件目の項目に数字を振らない
                $seq = '';
            }
            $listParam['sendName' . $seq] =  $val['sendName'];
            $listParam['sendPostCode' . $seq] =  $val['sendPostCode'];
            $listParam['sendAddress1' . $seq] =  $val['sendAddress1'];
            $listParam['sendAddress2' . $seq] =  $val['sendAddress2'];
            $listParam['sendTelNum' . $seq] =  $val['sendTelNum'];
        }
    }

}
