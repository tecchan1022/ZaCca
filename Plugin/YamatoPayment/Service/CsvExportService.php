<?php
/*
 * Copyright(c)2016, Yamato Financial Co.,Ltd. All rights reserved.
 * Copyright(c)2016, Yamato Credit finance Co.,Ltd. All rights reserved.
 */


namespace Plugin\YamatoPayment\Service;

use Eccube\Application;
use Eccube\Doctrine\Filter\SoftDeleteFilter;
use Eccube\Entity\BaseInfo;
use Eccube\Entity\Help;
use Eccube\Entity\Order;
use Eccube\Entity\ShipmentItem;
use Eccube\Entity\Shipping;
use Plugin\YamatoPayment\Entity\YamatoPaymentMethod;
use Plugin\YamatoPayment\Entity\YamatoShippingDelivSlip;

class CsvExportService
{
    /**
     * @var Application
     */
    protected $app;

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
     * B2 CSVヘッダ行を出力する.
     */
    public function exportB2CsvHeader()
    {
        $row = $this->getB2CsvHeader();

        $this->app['eccube.service.csv.export']->fopen();
        $this->app['eccube.service.csv.export']->fputcsv($row);
        $this->app['eccube.service.csv.export']->fclose();
    }

    /**
     * B2 CSVヘッダー定義
     *
     * @return array
     */
    private function getB2CsvHeader()
    {
        return array(
            'お客様管理番号',
            '送り状種別',
            'クール区分',
            '伝票番号',
            '出荷予定日',
            'お届け予定（指定）日',
            '配達時間帯',
            'お届け先コード',
            'お届け先電話番号',
            'お届け先電話番号枝番',
            'お届け先郵便番号',
            'お届け先住所',
            'お届け先住所（アパートマンション名）',
            'お届け先会社・部門名１',
            'お届け先会社・部門名２',
            'お届け先名',
            'お届け先名称略カナ',
            '敬称',
            'ご依頼主コード',
            'ご依頼主電話番号',
            'ご依頼主電話番号枝番',
            'ご依頼主郵便番号',
            'ご依頼主住所',
            'ご依頼主住所（ｱﾊﾟｰﾄﾏﾝｼｮﾝ名）',
            'ご依頼主名',
            'ご依頼主略称カナ',
            '品名コード１',
            '品名１',
            '品名コード２',
            '品名２',
            '荷扱い１',
            '荷扱い２',
            '記事',
            'コレクト代金引換額（税込）',
            'コレクト内消費税額等',
            '営業所止置き',
            '営業所コード',
            '発行枚数',
            '個数口枠の印字',
            'ご請求先顧客コード',
            'ご請求先分類コード',
            '運賃管理番号',
            '注文時カード払いデータ登録',
            '注文時カード払い加盟店番号',
            '注文時カード払い申し込み受付番号１',
            '注文時カード払い申し込み受付番号２',
            '注文時カード払い申し込み受付番号３',
            'お届け予定eメール利用区分',
            'お届け予定eメールe-mailアドレス',
            '入力機種',
            'お届け予定eメールメッセージ',
            'お届け完了eメール利用区分',
            'お届け完了eメールe-mailアドレス',
            'お届け完了eメールメッセージ',
            'クロネコ収納代行利用区分',
            '予備',
            '収納代行請求金額（税込）',
            '収納代行内消費税額等',
            '収納代行請求先郵便番号',
            '収納代行請求先住所',
            '収納代行請求先住所（ｱﾊﾟｰﾄﾏﾝｼｮﾝ名)',
            '収納代行請求先会社･部門名１',
            '収納代行請求先会社･部門名２',
            '収納代行請求先名（漢字）',
            '収納代行請求先名（カナ）',
            '収納代行問合せ先名（カナ）',
            '収納代行問合せ先郵便番号',
            '収納代行問合せ先住所',
            '収納代行問合せ先住所（ｱﾊﾟｰﾄﾏﾝｼｮﾝ名）',
            '収納代行問合せ先電話番号',
            '収納代行管理番号',
            '収納代行品名',
            '収納代行備考',
            '予備０１',
            '予備０２',
            '予備０３',
            '予備０４',
            '予備０５',
            '予備０６',
            '予備０７',
            '予備０８',
            '予備０９',
            '予備１０',
            '予備１１',
            '予備１２',
            '予備１３',
            '投函予定メール利用区分',
            '投函予定メールe-mailアドレス',
            '投函予定メールメッセージ',
            '投函完了メール(受人宛て)利用区分',
            '投函完了メール(受人宛て)e-mailアドレス',
            '投函完了メール(受人宛て)メッセージ',
            '投函完了メール(出人宛て)利用区分',
            '投函完了メール(出人宛て)e-mailアドレス',
            '投函完了メール(出人宛て)メッセージ',
        );
    }

    /**
     * B2 CSV出力データ作成
     *
     * @param Order $Order
     * @param Shipping $Shipping
     * @return array $row
     */
    public function createB2CsvData($Order, $Shipping)
    {
        $app = $this->app;
        $row = array();

        $subData = $app['yamato_payment.util.plugin']->getUserSettings();
        $b2SubData = $app['yamato_payment.util.plugin']->getB2UserSettings();

        /** @var BaseInfo $BaseInfo */
        $BaseInfo = $app['eccube.repository.base_info']->get();
        /** @var Help $Help */
        $Help = $app['eccube.repository.help']->get();

        // 支払方法IDを取得
        $payment_id = null;

        // 削除フラグを無効化する
        /** @var SoftDeleteFilter $softDeleteFilter */
        $softDeleteFilter = $this->app['orm.em']->getFilters()->getFilter('soft_delete');
        $originExcludes = $softDeleteFilter->getExcludes();

        $softDeleteFilter->setExcludes(array(
            'Eccube\Entity\Payment',
        ));

        if (!is_null($Order->getPayment())) {
            $payment_id = $Order->getPayment()->getId();
        }
        // 削除フラグを元に戻す
        $softDeleteFilter->setExcludes($originExcludes);

        // 配送業者IDを取得
        $delivery_id = null;
        if (!is_null($Shipping->getDelivery())) {
            $delivery_id = $Shipping->getDelivery()->getId();
        }

        // 配送時間を取得
        $delivery_time_id = null;
        if (!is_null($Shipping->getDeliveryTime())) {
            $delivery_time_id = $Shipping->getDeliveryTime()->getId();
        }

        // 配送伝票番号情報を取得
        /** @var YamatoShippingDelivSlip $YamatoShippingDelivSlip */
        $YamatoShippingDelivSlip = $app['yamato_payment.repository.yamato_shipping_deliv_slip']
            ->find($Shipping->getId());
        if (is_null($YamatoShippingDelivSlip)) {
            $YamatoShippingDelivSlip = new YamatoShippingDelivSlip();
        }

        // 決済方法情報を取得
        /** @var YamatoPaymentMethod $YamatoPaymentMethod */
        $YamatoPaymentMethod = $app['yamato_payment.repository.yamato_payment_method']
            ->find($payment_id);
        if (is_null($YamatoPaymentMethod)) {
            $YamatoPaymentMethod = new YamatoPaymentMethod();
        }

        // 1. お客様管理番号
        $row[] = $Order->getId() . '_' . $Shipping->getId();
        // 2. 送り状種別
        if (isset($b2SubData['deliv_slip_type'][$payment_id])) {
            $row[] = $b2SubData['deliv_slip_type'][$payment_id];
        } else {
            $row[] = '';
        }
        // 3. クール区分
        if (!is_null($b2SubData) && isset($b2SubData['cool_kb'][$delivery_id])) {
            $row[] = $b2SubData['cool_kb'][$delivery_id];
        } else {
            $row[] = '';
        }
        // 4. 伝票番号
        $row[] = $YamatoShippingDelivSlip->getDelivSlipNumber();
        // 5. 出荷予定日(ダウンロードした日)
        $row[] = date('Y/m/d');
        // 6. お届け予定（指定）日
        if (!is_null($Shipping->getShippingDeliveryDate())) {
            $row[] = date_format($Shipping->getShippingDeliveryDate(), 'Y/m/d');
        } else {
            $row[] = '';
        }
        // 7. 配達時間帯
        if (isset($b2SubData['b2_delivtime_code'][$delivery_id][$delivery_time_id])) {
            $delivTimeCode = $app['yamato_payment.util.payment']->getDelivTimeCode();
            $row[] = $delivTimeCode[$b2SubData['b2_delivtime_code'][$delivery_id][$delivery_time_id]];
        } else {
            $row[] = '';
        }
        // 8.お届け先コード (空で出力)
        $row[] = '';

        $tel_hyphenation = ($b2SubData['tel_hyphenation'] == '1') ? '-' : '';
        $zip_hyphenation = ($b2SubData['zip_hyphenation'] == '1') ? '-' : '';

        // 9. お届け先電話番号
        $row[] = $Shipping->getTel01() . $tel_hyphenation . $Shipping->getTel02() . $tel_hyphenation . $Shipping->getTel03();
        // 10. お届け先電話番号枝番 (空で出力)
        $row[] = '';
        // 11. お届け先郵便番号
        $row[] = $Shipping->getZip01() . $zip_hyphenation . $Shipping->getZip02();
        // 12. お届け先住所
        $row[] = $Shipping->getPref()->getName() . $Shipping->getAddr01();
        // 13. お届け先住所（アパートマンション名）
        $row[] = $Shipping->getAddr02();
        // 14. お届け先会社・部門名１
        $row[] = $Shipping->getCompanyName();
        // 15. お届け先会社・部門名２ (空で出力)
        $row[] = '';
        // 16. お届け先名
        $row[] = $Shipping->getName01() . '　' . $Shipping->getName02();
        // 17. お届け先名称略カナ
        $row[] = mb_convert_kana($Shipping->getKana01() . ' ' . $Shipping->getKana02(), 'k', 'UTF-8');
        // 18. 敬称 (空で出力)
        $row[] = '';

        // 19. ご依頼主コード
        // 20. ご依頼主電話番号
        // 21. ご依頼主電話番号枝番 (空で出力)
        // 22. ご依頼主郵便番号
        // 23. ご依頼主住所
        // 24. ご依頼主住所（ｱﾊﾟｰﾄﾏﾝｼｮﾝ名）
        // 25. ご依頼主名
        // 26. ご依頼主略称カナ

        if ($b2SubData['output_order_type'] == '1') {
            $row[] = '';
            $row[] = $BaseInfo->getTel01() . $tel_hyphenation . $BaseInfo->getTel02() . $tel_hyphenation . $BaseInfo->getTel03();
            $row[] = '';
            $row[] = $BaseInfo->getZip01() . $zip_hyphenation . $BaseInfo->getZip02();
            $prefName = (is_null($BaseInfo->getPref())) ? '' : $BaseInfo->getPref()->getName();
            $row[] = $prefName . $BaseInfo->getAddr01();
            $row[] = $BaseInfo->getAddr02();
            $row[] = $BaseInfo->getCompanyName();
            $row[] = mb_convert_kana($BaseInfo->getCompanyKana(), 'k', 'UTF-8');
        } elseif ($b2SubData['output_order_type'] == '2') {
            $row[] = '';
            $row[] = $Help->getLawTel01() . $tel_hyphenation . $Help->getLawTel02() . $tel_hyphenation . $Help->getLawTel03();
            $row[] = '';
            $row[] = $Help->getLawZip01() . $zip_hyphenation . $Help->getLawZip02();
            $prefName = (is_null($Help->getLawPref())) ? '' : $Help->getLawPref()->getName();
            $row[] = $prefName . $Help->getLawAddr01();
            $row[] = $Help->getLawAddr02();
            $row[] = $Help->getLawCompany();
            $row[] = '';
        } else {
            $row[] = '';
            $row[] = $Order->getTel01() . $tel_hyphenation . $Order->getTel02() . $tel_hyphenation . $Order->getTel03();
            $row[] = '';
            $row[] = $Order->getZip01() . $zip_hyphenation . $Order->getZip02();
            $prefName = (is_null($Order->getPref())) ? '' : $Order->getPref()->getName();
            $row[] = $prefName . $Order->getAddr01();
            $row[] = $Order->getAddr02();
            $row[] = $Order->getName01() . '　' . $Order->getName02();
            $row[] = mb_convert_kana($Order->getKana01() . ' ' . $Order->getKana02(), 'k', 'UTF-8');
        }

        // 27. 品名コード１
        // 28. 品名１
        // 29. 品名コード２
        // 30. 品名２

        // 受注商品情報を取得
        $ShipmentItems = $Shipping->getShipmentItems();

        // 受注商品情報を配列に格納し、ソートする
        $listItem = array();
        foreach ($ShipmentItems as $item) {
            /** @var ShipmentItem $item */
            $listItem[$item->getId()] = array('code' => $item->getProductCode(), 'name' => $item->getProductName());
        }
        ksort($listItem);

        $index = 0;
        foreach ($listItem as $item) {
            if ($index == 2) {
                break;
            }

            $row[] = $item['code'];
            $row[] = $item['name'];

            if (count($listItem) == 1) {
                $row[] = '';
                $row[] = '';
                break;
            }
            $index++;
        }

        // 31. 荷扱い１ (空で出力)
        $row[] = '';
        // 32. 荷扱い２ (空で出力)
        $row[] = '';
        // 33. 記事
        $row[] = '';
        // 34. コレクト代金引換額（税込）(空で取得)
        // 35. コレクト内消費税額等(空で取得)
        if (isset($b2SubData['deliv_slip_type'][$payment_id]) &&
            $b2SubData['deliv_slip_type'][$payment_id] == $app['config']['YamatoPayment']['const']['DELIV_SLIP_TYPE_CORECT']
        ) {
            $row[] = $Order->getPaymentTotal();
            $row[] = $Order->getTax();
        } else {
            $row[] = '';
            $row[] = '';
        }
        // 36. 営業所止置き(0 : 利用しない 1 : 利用する)
        $row[] = '0';
        // 37. 営業所コード(空で出力)
        $row[] = '';
        // 38. 発行枚数(空で出力)
        $row[] = '';
        // 39. 個数口枠の印字(1 : 印字する 2 : 印字しない)
        $row[] = '';
        // 40. ご請求先顧客コード
        $row[] = is_null($b2SubData['claim_customer_code']) ? '' : $b2SubData['claim_customer_code'];
        // 41. ご請求先分類コード
        $row[] = is_null($b2SubData['claim_type_code']) ? '' : $b2SubData['claim_type_code'];
        // 42. 運賃管理番号
        $row[] = is_null($b2SubData['transportation_no']) ? '' : $b2SubData['transportation_no'];
        // 43. 注文時カード払いデータ登録(0 : 無し 1 : 有り)
        if ($YamatoPaymentMethod->getMemo03() == $app['config']['YamatoPayment']['const']['YAMATO_PAYID_CREDIT']
            || $YamatoPaymentMethod->getMemo03() == $app['config']['YamatoPayment']['const']['YAMATO_PAYID_DEFERRED']
        ) {
            $row[] = '1';
        } else {
            $row[] = '0';
        }
        // 44. 注文時カード払い加盟店番号
        $row[] = $subData['shop_id'];
        // 45. 注文時カード払い申し込み受付番号１
        $row[] = $Order->getId();
        // 46. 注文時カード払い申し込み受付番号２ (空で出力)
        $row[] = '';
        // 47. 注文時カード払い申し込み受付番号３ (空で出力)
        $row[] = '';
        // 48. お届け予定eメール利用区分(0 : 利用しない 1 : 利用する)
        $row[] = $b2SubData['service_deliv_mail_enable'];
        // 49. お届け予定eメールe-mailアドレス
        // 50. 入力機種
        // 51. お届け予定eメールメッセージ
        if ($b2SubData['service_deliv_mail_enable'] == '1') {
            $row[] = $Order->getEmail();
            $row[] = 1;
            $row[] = $b2SubData['service_deliv_mail_message'];
        } else {
            $row[] = '';
            $row[] = '';
            $row[] = '';
        }

        // 52. お届け完了eメール利用区分(0 : 利用しない 1 : 利用する)
        $row[] = $b2SubData['service_complete_mail_enable'];
        // 53. お届け完了eメールe-mailアドレス
        // 54. お届け完了eメールメッセージ
        if ($b2SubData['service_complete_mail_enable'] == '1') {
            $row[] = $Order->getEmail();
            $row[] = $b2SubData['service_complete_mail_message'];
        } else {
            $row[] = '';
            $row[] = '';
        }

        // 55. クロネコ収納代行利用区分(0 : 無し 1 : 有り)
        $row[] = '0';
        // 56. 予備
        $row[] = '';
        // 57. 収納代行請求金額（税込）
        $row[] = '';
        // 58. 収納代行内消費税額等
        $row[] = '';
        // 59. 収納代行請求先郵便番号
        $row[] = '';
        // 60. 収納代行請求先住所
        $row[] = '';
        // 61. 収納代行請求先住所（ｱﾊﾟｰﾄﾏﾝｼｮﾝ名)
        $row[] = '';
        // 62. 収納代行請求先会社･部門名１
        $row[] = '';
        // 63. 収納代行請求先会社･部門名２
        $row[] = '';
        // 64. 収納代行請求先名（漢字）
        $row[] = '';
        // 65. 収納代行請求先名（カナ）
        $row[] = '';
        // 66. 収納代行問合せ先名（カナ）
        $row[] = '';
        // 67. 収納代行問合せ先郵便番号
        $row[] = '';
        // 68. 収納代行問合せ先住所
        $row[] = '';
        // 69. 収納代行問合せ先住所（ｱﾊﾟｰﾄﾏﾝｼｮﾝ名）
        $row[] = '';
        // 70. 収納代行問合せ先電話番号
        $row[] = '';
        // 71. 収納代行管理番号
        $row[] = '';
        // 72. 収納代行品名
        $row[] = '';
        // 73. 収納代行備考
        $row[] = '';
        // 74. 予備０１
        $row[] = '';
        // 75. 予備０２
        $row[] = '';
        // 76. 予備０３
        $row[] = '';
        // 77. 予備０４
        $row[] = '';
        // 78. 予備０５
        $row[] = '';
        // 79. 予備０６
        $row[] = '';
        // 80. 予備０７
        $row[] = '';
        // 81. 予備０８
        $row[] = '';
        // 82. 予備０９
        $row[] = '';
        // 83. 予備１０
        $row[] = '';
        // 84. 予備１１
        $row[] = '';
        // 85. 予備１２
        $row[] = '';
        // 86. 予備１３
        $row[] = '';

        // 87 投函予定メール利用区分(0 : 利用しない 1 : 利用する PC宛て 2 : 利用する モバイル宛て)
        // 88 投函予定メールe-mailアドレス
        // 89 投函予定メールメッセージ
        if (isset($b2SubData['deliv_slip_type'][$payment_id])
            && $b2SubData['deliv_slip_type'][$payment_id] == $app['config']['YamatoPayment']['const']['DELIV_SLIP_TYPE_NEKOPOS']
            && isset($b2SubData['posting_plan_mail_enable'])
            && $b2SubData['posting_plan_mail_enable'] == '1'
        ) {
            $row[] = 1;
            $row[] = $Order->getEmail();
            $row[] = $b2SubData['posting_plan_mail_message'];
        } else {
            $row[] = 0;
            $row[] = '';
            $row[] = '';
        }

        // 90.投函完了メール(受人宛て)利用区分(0 : 利用しない 1 : 利用する PC宛て 2 : 利用する モバイル宛て)
        $row[] = '0';
        // 91.投函完了メール(受人宛て)e-mailアドレス
        $row[] = '';
        // 92.投函完了メール(受人宛て)メッセージ
        $row[] = '';

        // 93.投函完了メール(出人宛て)利用区分
        // 94.投函完了メール(出人宛て)e-mailアドレス
        // 95.投函完了メール(出人宛て)メッセージ
        if (isset($b2SubData['deliv_slip_type'][$payment_id])
            && $b2SubData['deliv_slip_type'][$payment_id] == $app['config']['YamatoPayment']['const']['DELIV_SLIP_TYPE_NEKOPOS']
            && isset($b2SubData['posting_complete_deliv_mail_enable'])
            && $b2SubData['posting_complete_deliv_mail_enable'] == '1'
        ) {
            $row[] = 1;
            $row[] = $Order->getEmail();
            $row[] = $b2SubData['posting_complete_deliv_mail_message'];
        } else {
            $row[] = 0;
            $row[] = '';
            $row[] = '';
        }

        return $row;
    }

    /**
     * 買手情報一括登録 CSVヘッダ行を出力する.
     */
    public function exportBuyerCsvHeader()
    {
        $row = $this->getBuyerCsvHeader();

        $this->app['eccube.service.csv.export']->fopen();
        $this->app['eccube.service.csv.export']->fputcsv($row);
        $this->app['eccube.service.csv.export']->fclose();
    }

    /**
     * 買手情報一括登録 CSVヘッダー定義
     *
     * @return array
     */
    private function getBuyerCsvHeader()
    {
        return array(
            '受注日',
            '出荷予定日',
            '受注番号',
            '氏名',
            '氏名（カナ）',
            '郵便番号',
            '住所①',
            '住所②',
            '電話番号',
            '予備１',
            '予備２',
            '予備３',
            '予備４',
            'メールアドレス',
            '請求金額合計',
            '明細０１商品名',
            '明細０１数量',
            '明細０１単価',
            '明細０１小計',
            '明細０２商品名',
            '明細０２数量',
            '明細０２単価',
            '明細０２小計',
            '明細０３商品名',
            '明細０３数量',
            '明細０３単価',
            '明細０３小計',
            '明細０４商品名',
            '明細０４数量',
            '明細０４単価',
            '明細０４小計',
            '明細０５商品名',
            '明細０５数量',
            '明細０５単価',
            '明細０５小計',
            '明細０６商品名',
            '明細０６数量',
            '明細０６単価',
            '明細０６小計',
            '明細０７商品名',
            '明細０７数量',
            '明細０７単価',
            '明細０７小計',
            '明細０８商品名',
            '明細０８数量',
            '明細０８単価',
            '明細０８小計',
            '明細０９商品名',
            '明細０９数量',
            '明細０９単価',
            '明細０９小計',
            '明細１０商品名',
            '明細１０数量',
            '明細１０単価',
            '明細１０小計',
            '送付先選択',
            '送付先０１郵便番号',
            '送付先０１住所①',
            '送付先０１住所②',
            '送付先０１名称',
            '送付先０１電話番号',
            '送付先０２郵便番号',
            '送付先０２住所①',
            '送付先０２住所②',
            '送付先０２名称',
            '送付先０２電話番号',
            '送付先０３郵便番号',
            '送付先０３住所①',
            '送付先０３住所②',
            '送付先０３名称',
            '送付先０３電話番号',
            '送付先０４郵便番号',
            '送付先０４住所①',
            '送付先０４住所②',
            '送付先０４名称',
            '送付先０４電話番号',
            '送付先０５郵便番号',
            '送付先０５住所①',
            '送付先０５住所②',
            '送付先０５名称',
            '送付先０５電話番号',
            '送付先０６郵便番号',
            '送付先０６住所①',
            '送付先０６住所②',
            '送付先０６名称',
            '送付先０６電話番号',
            '送付先０７郵便番号',
            '送付先０７住所①',
            '送付先０７住所②',
            '送付先０７名称',
            '送付先０７電話番号',
            '送付先０８郵便番号',
            '送付先０８住所①',
            '送付先０８住所②',
            '送付先０８名称',
            '送付先０８電話番号',
            '送付先０９郵便番号',
            '送付先０９住所①',
            '送付先０９住所②',
            '送付先０９名称',
            '送付先０９電話番号',
            '送付先１０郵便番号',
            '送付先１０住所①',
            '送付先１０住所②',
            '送付先１０名称',
            '送付先１０電話番号',
        );
    }

    /**
     * 買手情報一括登録 CSV出力データ作成
     *
     * @param Order $Order
     * @return array $row
     */
    public function createBuyerCsvData($Order)
    {
        $app = $this->app;
        $row = array();
        $encoding = 'UTF-8';

        $subData = $app['yamato_payment.util.plugin']->getUserSettings();

        // 1.受注日
        $row[] = $Order->getCreateDate()->format('Ymd');
        // 2.出荷予定日
        if (isset($subData['ycf_ship_ymd']) && !is_null($subData['ycf_ship_ymd'])) {
            $row[] = date('Ymd', strtotime('+' . $subData['ycf_ship_ymd'] . ' day'));
        } else {
            $row[] = date('Ymd');
        }
        // 3.受注番号
        $row[] = $Order->getId();
        // 4.氏名
        $row[] = mb_substr($Order->getName01() . '　' . $Order->getName02(), 0, 30, $encoding);
        // 5.氏名（カナ）
        $kana = mb_convert_kana($Order->getKana01() . ' ' . $Order->getKana02(), 'k', 'UTF-8');
        $row[] = mb_substr($kana, 0, 80, $encoding);
        // 6.郵便番号
        $row[] = $Order->getZip01() . $Order->getZip02();
        // 7.住所①
        // 8.住所②
        $address = $Order->getPref()->getName() . $Order->getAddr01() . '　' . $Order->getAddr02();
        $row[] = mb_substr($address, 0, 25, $encoding);
        $row[] = mb_substr($address, 25, 25, $encoding);
        // 9.電話番号
        $row[] = $Order->getTel01() . $Order->getTel02() . $Order->getTel03();
        // 10.予備1
        $row[] = '';
        // 11.予備2
        $row[] = '';
        // 12.予備3
        $row[] = '';
        // 13.予備4
        $row[] = '';
        // 14.メールアドレス
        $row[] = mb_substr($Order->getEmail(), 0, 64, $encoding);
        // 15.請求金額合計
        $row[] = $Order->getPaymentTotal();

        // 明細情報取得(後払い決済用)
        $details = $app['yamato_payment.util.payment']->getOrderDetailDeferred($Order);
        for ($i = 0; $i < 10; $i++) {
            $detail = (array_key_exists($i, $details)) ? $details[$i] : null;
            // 16～52.明細商品名
            $row[] = (is_null($detail)) ? '' : mb_substr($detail['itemName'], 0, 30, $encoding);
            // 53.明細数量
            $row[] = (is_null($detail)) ? '' : $detail['itemCount'];;
            // 54.明細単価
            $row[] = (is_null($detail)) ? '' : $detail['unitPrice'];
            // 55.明細小計
            $row[] = (is_null($detail)) ? '' : $detail['subTotal'];
        }

        // 56.送付先選択
        $Shippings = $Order->getShippings();

        $row[] = $app['yamato_payment.util.payment']->getSendDiv($Order, $Shippings);

        for ($i = 0; $i < 10; $i++) {
            /** @var Shipping $Shipping */
            $Shipping = ($Shippings->containsKey($i)) ? $Shippings->get($i) : null;
            // 57.送付先郵便番号
            $row[] = (is_null($Shipping)) ? '' : $Shipping->getZip01() . $Shipping->getZip02();
            // 58.送付先住所①
            // 59.送付先住所②
            $send_address = (is_null($Shipping)) ? ''
                : $Shipping->getPref()->getName() . $Shipping->getAddr01() . '　' . $Shipping->getAddr02();
            $row[] = mb_substr($send_address, 0, 25, $encoding);
            $row[] = mb_substr($send_address, 25, 25, $encoding);
            // 60.送付先名称
            $row[] = (is_null($Shipping)) ? ''
                : mb_substr($Shipping->getName01() . '　' . $Shipping->getName02(), 0, 30, $encoding);
            // 61.送付先電話番号
            $row[] = (is_null($Shipping)) ? ''
                : $Shipping->getTel01() . $Shipping->getTel02() . $Shipping->getTel03();
            // 62～106行目までくりかえし
        }

        return $row;
    }

}
