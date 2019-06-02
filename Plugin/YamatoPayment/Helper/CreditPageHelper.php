<?php
/*
 * Copyright(c)2016, Yamato Financial Co.,Ltd. All rights reserved.
 * Copyright(c)2016, Yamato Credit finance Co.,Ltd. All rights reserved.
 */


namespace Plugin\YamatoPayment\Helper;

use Eccube\Application;
use Eccube\Entity\Order;
use Plugin\YamatoPayment\Entity\YamatoOrderScheduledShippingDate;

/**
 * 決済モジュール 決済画面ヘルパー：クレジット決済
 */
class CreditPageHelper
{
    /** @var Application */
    protected $app;
    public $error;
    public $isComplete = false;

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
     * @param string $request
     * @param string $mode Mode値
     * @param array $listParam フォームデータ
     * @param Order $Order 受注情報
     * @param $objPage
     */
    public function modeAction($request, $mode, $listParam, $Order, $objPage)
    {
        $this->isComplete = false;

        //予約商品存在確認
        $objPage->tpl_is_reserve = $this->app['yamato_payment.util.payment']->isReservedOrder($Order);

        // modeでの分岐
        switch ($mode) {
            case 'next':
                // 入力チェック
                $this->checkError($objPage, $listParam);
                if (!empty($objPage->error)) {
                    break;
                }

                // 決済実行
                if ($this->doNext($Order, $listParam, $objPage)) {
                    //予約商品購入の場合は出荷予定日をセット
                    if ($objPage->tpl_is_reserve) {
                        $this->registScheduledShippingDate($Order->getId());
                    }
                    $this->isComplete = true;
                }
                break;

            case '3dTran':
                //ACSからの戻りは加工せずに戻すこと
                //決済実行
                if ($this->do3dTran($request, $Order, $objPage)) {
                    //予約商品購入の場合は出荷予定日をセット
                    if ($objPage->tpl_is_reserve) {
                        // 出荷予定日を取得
                        $this->registScheduledShippingDate($Order->getId());
                    }
                    $this->isComplete = true;
                }
                break;

            case 'deleteCard':

                // ログインしている場合のみ
                if ($this->app->isGranted('ROLE_USER')) {
                    //預かりカードを削除する
                    $this->app['yamato_payment.util.payment']
                        ->doDeleteCard($this->app->user()->getId(), $listParam, $objPage);
                }
                break;

            default:
                break;
        }
    }

    /**
     * 呼出元ページオブジェクト
     *
     * @param Order $order 受注情報
     * @param array $listParam パラメ-タ
     * @param $objPage
     * @return bool
     */
    public function doNext(Order $order, array $listParam, $objPage)
    {
        $app = $this->app;

        $order_id = $order->getId();
        $paymentUtil = $app['yamato_payment.util.payment'];

        // 支払方法の設定情報を取得する
        $paymentExtension = $paymentUtil->getPaymentTypeConfig($order->getPayment()->getId());
        // 受注情報取得
        $orderExtension = $paymentUtil->getOrderPayData($order_id);
        $orderExtension->setOrderId($order_id);
        // 予約商品有無
        $listParam['tpl_is_reserve'] = $objPage->tpl_is_reserve;
        $listParam['info_use_threeD'] = null;

        if ($listParam['tpl_is_reserve'] == true && !empty($listParam['card_no'])) {
            // 予約商品有の場合、カードお預かりは必須
            $listParam['register_card'] = '1';
        }

        // 決済実行
        $objClient = $app['yamato_payment.service.client.credit'];
        if(empty($listParam['webcollectToken'])) {
            $result = $objClient->doPaymentRequest($orderExtension, $listParam, $paymentExtension);
        } else {
            $result = $objClient->doPaymentTokenRequest($orderExtension, $listParam, $paymentExtension);
        }

        //決済結果取得
        $listResults = $objClient->getResults();

        //3Dセキュア未加入迂回処理
        if ($listResults['errorCode'] == $app['config']['YamatoPayment']['const']['YAMATO_3D_EXCLUDED']) {
            //3Dセキュア利用判定のため次の処理(request)にerrorCodeを渡す
            $listParam['info_use_threeD'] = $listResults['errorCode'];
            //決済実行(objClientを再度インスタンス化.エラーログ等を引き継がないため.)
            $objClient = $app['yamato_payment.service.client.credit'];
            $result = $objClient->doPaymentRequest($orderExtension, $listParam, $paymentExtension);

            //決済結果取得
            $listResults = $objClient->getResults();
        }

        //リクエスト結果確認
        if (!$result) {
            $listErr = $objClient->getError();
            $objPage->error['payment'] = '※ 決済でエラーが発生しました。<br />' . implode('<br />', $listErr);

            //決済ステータスを「決済中断」に変更する
            $payment_status = $app['config']['YamatoPayment']['const']['YAMATO_ACTION_STATUS_NG_TRANSACTION'];
            $YamatoOrderPayment = $app['yamato_payment.repository.yamato_order_payment']->find($order_id);
            $YamatoOrderPayment->setMemo04($payment_status);
            $app['orm.em']->flush();

            $objClient->checkMultiAtack();

            return false;
        }

        //3Dセキュア無しの場合
        if ($listResults['threeDAuthHtml'] == '' && $listResults['threeDToken'] == '') {
            //注文状況を「新規受付」へ
            $order_status = $app['config']['order_new'];
            $order->setOrderStatus($app['eccube.repository.order_status']->find($order_status));
            $YamatoOrderPayment = $app['yamato_payment.repository.yamato_order_payment']->find($order->getId());

            //「予約販売」の場合「予約受付完了」に変更する
            if ($app['yamato_payment.util.payment']->isReserve($objPage->tpl_is_reserve, $order)) {
                $payment_status = $app['config']['YamatoPayment']['const']['YAMATO_ACTION_STATUS_COMP_RESERVE'];
            } else {
                //「与信完了」に変更する
                $payment_status = $app['config']['YamatoPayment']['const']['YAMATO_ACTION_STATUS_COMP_AUTH'];
            }
            $YamatoOrderPayment->setMemo04($payment_status);

            $app['orm.em']->flush();
            return true;
        }

        // CDATAで画面表示できなくなるバグが存在するためCDATAを除去する
        $threeDAuthHtml = preg_replace('/\]\]>$/', '', preg_replace('/^<!\[CDATA\[/', '', $listResults['threeDAuthHtml']));

//        $threeDAuthHtml = preg_replace('/onLoad="document.creditSendForm.submit\(\)"/', '', preg_replace('/<\/form>/', '<input type="submit" /></form>', $threeDAuthHtml));


        $objPage->threeDsecure = $threeDAuthHtml;
    }

    /**
     * 3Dセキュア与信実行.
     *
     * @param string $request
     * @param Order $order 受注情報
     * @param $objPage
     * @return bool リダイレクト
     */
    public function do3dTran($request, Order $order, &$objPage)
    {
        $app = $this->app;

        $order_id = $order->getId();
        $paymentUtil = $app['yamato_payment.util.payment'];
        // 支払方法の設定情報を取得する
        $paymentExtension = $paymentUtil->getPaymentTypeConfig($order->getPayment()->getId());
        // 決済実行
        $objClient = $app['yamato_payment.service.client.credit'];
        //ACSからの戻りは加工せずに戻す
        if(empty($request['TOKEN'])){
            $result = $objClient->doSecureTran($order_id, $request, $paymentExtension);
        } else {
            $result = $objClient->doSecureTranToken($order_id, $request, $paymentExtension);
        }

        if (!$result) {
            $listErr = $objClient->getError();
            $objPage->error['payment'] = '※ 決済でエラーが発生しました。<br />' . implode('<br />', $listErr);

            //決済ステータスを「決済中断」に変更する
            $payment_status = $app['config']['YamatoPayment']['const']['YAMATO_ACTION_STATUS_NG_TRANSACTION'];
            $YamatoOrderPayment = $app['yamato_payment.repository.yamato_order_payment']->find($order_id);
            $YamatoOrderPayment->setMemo04($payment_status);
            $app['orm.em']->persist($YamatoOrderPayment);
            $app['orm.em']->flush();

            $objClient->checkMultiAtack();

            return false;
        }

        //注文状況を「新規受付」へ
        $order_status = $app['config']['order_new'];
        $order->setOrderStatus($app['eccube.repository.order_status']->find($order_status));

        //「予約販売」の場合「予約受付完了」に変更する
        if ($app['yamato_payment.util.payment']->isReserve($objPage->tpl_is_reserve, $order)) {
            $payment_status = $app['config']['YamatoPayment']['const']['YAMATO_ACTION_STATUS_COMP_RESERVE'];
            $YamatoOrderPayment = $app['yamato_payment.repository.yamato_order_payment']->find($order->getId());
            $YamatoOrderPayment->setMemo04($payment_status);

        } else {
            //「与信完了」に変更する
            $payment_status = $app['config']['YamatoPayment']['const']['YAMATO_ACTION_STATUS_COMP_AUTH'];
            $YamatoOrderPayment = $app['yamato_payment.repository.yamato_order_payment']->find($order->getId());
            $YamatoOrderPayment->setMemo04($payment_status);
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
     * 出荷予定日データ登録
     *
     * @param integer $orderId
     */
    private function registScheduledShippingDate($orderId)
    {
        //出荷予定日取得
        $scheduled_shipping_date = $this->app['yamato_payment.util.payment']
            ->getMaxScheduledShippingDate($orderId);

        //出荷予定日データ取得
        $YamatoOrderScheduledShippingDate = $this->app['yamato_payment.repository.yamato_order_scheduled_shipping_date']
            ->find($orderId);

        if (is_null($YamatoOrderScheduledShippingDate)) {
            $YamatoOrderScheduledShippingDate = new YamatoOrderScheduledShippingDate();
            $YamatoOrderScheduledShippingDate->setId($orderId);
        }
        //出荷予定日を設定
        $YamatoOrderScheduledShippingDate
            ->setScheduledshippingDate($scheduled_shipping_date);

        $this->app['orm.em']->persist($YamatoOrderScheduledShippingDate);
        $this->app['orm.em']->flush();
    }

    function checkError(&$objPage, &$listParam)
    {
        // 予約商品購入の場合
        if($objPage->tpl_is_reserve){

            $userSettings =$this->app['yamato_payment.util.plugin']->getUserSettings();
            // 予約販売利用が必須
            if ($userSettings['use_option'] != '0' || $userSettings['advance_sale'] != '0') {
                $objPage->error['advance_sale'] = '※ 現在のご契約内容では予約商品販売は行えません。大変お手数をおかけいたしますが店舗運営者までお問い合わせくださいませ。<br />';
            }

            if ($objPage->tpl_is_reserve && !empty($listParam['card_no'])) {
                // 予約商品有の場合、カードお預かりは必須
                $listParam['register_card'] = '1';
            }

            // 登録済みカード利用または、カード情報登録が必須
            if ($listParam['register_card'] != '1' && $listParam['use_registed_card'] != '1') {
                $objPage->error['register_card'] = '※ 予約商品購入はカード情報お預かり、もしくは登録済カード情報でのご購入が必要です。<br />';
            }

        }
    }

}
