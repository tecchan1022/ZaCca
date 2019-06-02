<?php
/*
 * Copyright(c)2016, Yamato Financial Co.,Ltd. All rights reserved.
 * Copyright(c)2016, Yamato Credit finance Co.,Ltd. All rights reserved.
 */

namespace Plugin\YamatoPayment\Service\Client;

use Eccube\Entity\Order;
use Eccube\Entity\OrderDetail;
use Plugin\YamatoPayment\Service\AbstractServiceTestCase;

class BaseClientServiceTest extends AbstractServiceTestCase
{
    /**
     * @var BaseClientService
     */
    protected $object;

    function setUp()
    {
        parent::setUp();
        $this->object = new BaseClientService($this->app);

        // 動作モードをテストモードに固定する
        $property = $this->getPrivateProperty('userSettings');
        $userSettings = $property->getValue($this->object);
        $userSettings['exec_mode'] = '0';
        $property->setValue($this->object, $userSettings);
    }

    function test_getSendData()
    {
        $method = new \ReflectionMethod(get_class($this->object), 'getSendData');
        $method->setAccessible(true);
        $sendKey = $this->getAllSendKey();

        /*
         * 空の配列を渡すと空の配列が返ってくる
         */
        $this->expected = array();
        $this->actual = $method->invoke($this->object, array(), array());
        $this->verify();

        /*
         * 全てのキーを指定し空データを渡すと、全てのキー配列が返ってくる
         */
        $this->expected = count($sendKey);
        $this->actual = count($method->invoke($this->object, $sendKey, array()));
        $this->verify();
    }

    public function test_getItemName()
    {
        $method = new \ReflectionMethod(get_class($this->object), 'getItemName');
        $method->setAccessible(true);

        /*
         * 空データを渡す
         */
        $Order = new Order();
        $res = $method->invoke($this->object, $Order);

        // 空文字が返る
        $this->expected = '';
        $this->actual = $res;
        $this->verify();

        /*
         * 受注明細を複数レコードセットして渡す
         */
        $Order = new Order();
        $OrderDetail = new OrderDetail();
        $OrderDetail->setProductName('aaa');
        $Order->addOrderDetail($OrderDetail);
        $OrderDetail = new OrderDetail();
        $OrderDetail->setProductName('bbb');
        $Order->addOrderDetail($OrderDetail);
        $res = $method->invoke($this->object, $Order);

        // 受注明細の1件目の商品名が返る
        $this->expected = 'aaa';
        $this->actual = $res;
        $this->verify();

        foreach ($this->getProhibitedChars() as $key => $value) {
            /*
             * 禁止文字をセットして渡す
             */
            $Order = new Order();
            $OrderDetail = new OrderDetail();
            $OrderDetail->setProductName("{$key}-{$value}");
            $Order->addOrderDetail($OrderDetail);
            $res = $method->invoke($this->object, $Order);

            // 禁止文字は全角スペースに変換される
            $this->expected = "{$key}-　";
            $this->actual = $res;
            $this->verify("商品名＞禁止文字確認 :{$value}");
        }

        /*
         * 禁止半角記号をセットして渡す
         */
        $Order = new Order();
        $OrderDetail = new OrderDetail();
        $OrderDetail->setProductName('aa!');
        $Order->addOrderDetail($OrderDetail);
        $res = $method->invoke($this->object, $Order);

        // 禁止半角記号は半角スペースに変換される
        $this->expected = 'aa ';
        $this->actual = $res;
        $this->verify();

        /*
         * 規定の桁数を超える商品名を渡す
         */
        $Order = new Order();
        $OrderDetail = new OrderDetail();
        $OrderDetail->setProductName(str_repeat('a', (int)$this->const['ITEM_NAME_LEN'] + 1));
        $Order->addOrderDetail($OrderDetail);
        $res = $method->invoke($this->object, $Order);

        // 規定の桁数までカットされる
        $this->expected = $this->const['ITEM_NAME_LEN'];
        $this->actual = strlen($res);
        $this->verify();
    }

    public function test_getCardCode()
    {
        $method = new \ReflectionMethod(get_class($this->object), 'getCardCode');
        $method->setAccessible(true);

        /*
         * 空文字を渡すと99が返る
         */
        $this->expected = 99;
        $this->actual = $method->invoke($this->object, '');
        $this->verify();

        /*
         * ダイナースの判定：2が返る
         */
        $this->expected = 2;
        $this->actual = $method->invoke($this->object, '300000');
        $this->verify();
        $this->actual = $method->invoke($this->object, '309500');
        $this->verify();
        $this->actual = $method->invoke($this->object, '360000');
        $this->verify();
        $this->actual = $method->invoke($this->object, '380000');
        $this->verify();

        /*
         * ＪＣＢの判定：3が返る
         */
        $this->expected = 3;
        $this->actual = $method->invoke($this->object, '352800');
        $this->verify();
        $this->actual = $method->invoke($this->object, '353000');
        $this->verify();

        /*
         * ＶＩＳＡの判定：9が返る
         */
        $this->expected = 9;
        $this->actual = $method->invoke($this->object, '400000');
        $this->verify();

        /*
         * ＭＡＳＴＥＲの判定：10が返る
         */
        $this->expected = 10;
        $this->actual = $method->invoke($this->object, '500000');
        $this->verify();

        /*
         * アメックスの判定：12が返る
         */
        $this->expected = 12;
        $this->actual = $method->invoke($this->object, '340000');
        $this->verify();
        $this->actual = $method->invoke($this->object, '370000');
        $this->verify();

        /*
         * ダミーカードの判定：9が返る
         */
        $this->expected = 9;
        $this->actual = $method->invoke($this->object, '000000');
        $this->verify();
        // 本番モードの場合：99が返る
        $this->expected = 99;
        $this->actual = $method->invoke($this->object, '000000', '1');
        $this->verify();
    }

    function test_sendRequest_パラメータが空の場合_falseが返る()
    {
        $method = new \ReflectionMethod(get_class($this->object), 'sendRequest');
        $method->setAccessible(true);

        /*
         * クレジット決済URLを指定する
         */
        $getApiUrlMethod = new \ReflectionMethod(get_class($this->object), 'getApiUrl');
        $getApiUrlMethod->setAccessible(true);
        $creditUrl = $getApiUrlMethod->invoke($this->object, 'A01');

        // パラメータなしでリクエスト送信
        $res = $method->invoke($this->object, $creditUrl, array());

        // falseが返ってくる
        $this->assertNotTrue($res);

        // エラーメッセージがセットされる
        $this->expected = array(0 => '機能区分なし');
        $this->actual = $this->object->getError();
        $this->verify();
    }

    function test_sendRequest_存在しないURLを指定した場合_falseが返る()
    {
        $method = new \ReflectionMethod(get_class($this->object), 'sendRequest');
        $method->setAccessible(true);

        /*
         * 存在しないURLを指定する
         */
        $getApiUrlMethod = new \ReflectionMethod(get_class($this->object), 'getApiUrl');
        $getApiUrlMethod->setAccessible(true);
        $creditUrl = $getApiUrlMethod->invoke($this->object, 'A01');
        $creditUrl .= 'xxxxxxxxxx';

        // パラメータなしでリクエスト送信
        $res = $method->invoke($this->object, $creditUrl, array());

        // falseが返ってくる
        $this->assertNotTrue($res);

        // エラーメッセージがセットされる
        $this->assertNotEmpty($this->object->getError());
    }

    function test_parseResponse_XML形式の文字列を渡すと_配列に変換されて返る()
    {
        $method = new \ReflectionMethod(get_class($this->object), 'parseResponse');
        $method->setAccessible(true);

        $xml = <<< EOM
<return>
  <returnCode>1</returnCode>
  <returnDate>20160426102543</returnDate>
</return>
EOM;

        $this->expected = array(
            'returnCode' => '1',
            'returnDate' => '20160426102543',
        );
        $this->actual = $method->invoke($this->object, $xml);
        $this->verify();

        $this->expected = array();
        $this->actual = $this->object->getError();
        $this->verify();
    }

    function test_parseResponse_errorCodeを含むXML形式の文字列を渡すと_エラーメッセージが設定される()
    {
        $method = new \ReflectionMethod(get_class($this->object), 'parseResponse');
        $method->setAccessible(true);

        $xml = <<< EOM
<return>
  <returnCode>1</returnCode>
  <errorCode>Z012000001</errorCode>
  <returnDate>20160426102543</returnDate>
</return>
EOM;

        $this->expected = array(
            'returnCode' => '1',
            'errorCode' => 'Z012000001',
            'returnDate' => '20160426102543',
        );
        $this->actual = $method->invoke($this->object, $xml);
        $this->verify();

        $this->expected = array(0 => 'POST以外のリクエスト方式');
        $this->actual = $this->object->getError();
        $this->verify();
    }

    function test_getErrorMessageByErrorCode_エラーコードに対応するが返る()
    {
        $this->expected = '加盟店コードなし';
        $this->actual = $this->object->getErrorMessageByErrorCode('A011010201');
        $this->verify();
    }

    function test_getErrorMessageByErrorCode_定義されていないエラーコードを渡すと空文字が返る()
    {
        $this->expected = '';
        $this->actual = $this->object->getErrorMessageByErrorCode('z');
        $this->verify();
    }

    function test_getApiUrl_動作モードが本番モードの場合_本番のURLが返る()
    {
        $method = new \ReflectionMethod(get_class($this->object), 'getApiUrl');
        $method->setAccessible(true);

        // 動作モードを本番モードに設定する
        $property = $this->getPrivateProperty('userSettings');
        $userSettings = $property->getValue($this->object);
        $userSettings['exec_mode'] = '1';
        $property->setValue($this->object, $userSettings);

        // API区分一覧取得
        $functionDivList = $this->getFunctionDivList();

        foreach ($functionDivList as $functionDiv) {
            $this->expected = $this->const['api.url'][$functionDiv];
            $this->actual = $method->invoke($this->object, $functionDiv);
            $this->verify("API＞URL確認 :{$functionDiv}");
        }
    }

    function test_getApiUrl_動作モードがテストモードの場合_テスト環境のURLが返る()
    {
        $method = new \ReflectionMethod(get_class($this->object), 'getApiUrl');
        $method->setAccessible(true);

        // API区分一覧取得
        $functionDivList = $this->getFunctionDivList();

        foreach ($functionDivList as $functionDiv) {
            $this->expected = $this->const['api.test.gateway'][$functionDiv];
            $this->actual = $method->invoke($this->object, $functionDiv);
            $this->verify("API＞URL確認 :{$functionDiv}");
        }
    }

    function test_getApiUrl_存在しないAPI区分の場合_空文字が返る()
    {
        $method = new \ReflectionMethod(get_class($this->object), 'getApiUrl');
        $method->setAccessible(true);

        // API区分一覧取得
        $functionDiv = 'zzz';

        $this->expected = '';
        $this->actual = $method->invoke($this->object, $functionDiv);
        $this->verify("API＞URL確認 :{$functionDiv}");
    }

    private function getAllSendKey()
    {
        return array(
            'trader_code',
            'device_div',
            'order_no',
            'settle_price',
            'new_price',
            'buyer_name_kanji',
            'buyer_name_kana',
            'buyer_tel',
            'buyer_email',
            'goods_name',
            'card_code_api',
            'reserve_1',
            'last_credit_date',
            'card_exp',
            'comp_cd',
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
    }

    private static function getProhibitedChars()
    {
        return array(
            '―',
            '～',
            '∥',
            '－',
            '￠',
            '￡',
            '￢',
            '①',
            '②',
            '③',
            '④',
            '⑤',
            '⑥',
            '⑦',
            '⑧',
            '⑨',
            '⑩',
            '⑪',
            '⑫',
            '⑬',
            '⑭',
            '⑮',
            '⑯',
            '⑰',
            '⑱',
            '⑲',
            '⑳',
            'Ⅰ',
            'Ⅱ',
            'Ⅲ',
            'Ⅳ',
            'Ⅴ',
            'Ⅵ',
            'Ⅶ',
            'Ⅷ',
            'Ⅸ',
            'Ⅹ',
            '㍉',
            '㌔',
            '㌢',
            '㍍',
            '㌘',
            '㌧',
            '㌃',
            '㌶',
            '㍑',
            '㍗',
            '㌍',
            '㌦',
            '㌣',
            '㌫',
            '㍊',
            '㌻',
            '㎜',
            '㎝',
            '㎞',
            '㎎',
            '㎏',
            '㏄',
            '㎡',
            '㍻',
            '〝',
            '〟',
            '№',
            '㏍',
            '℡',
            '㊤',
            '㊥',
            '㊦',
            '㊧',
            '㊨',
            '㈱',
            '㈲',
            '㈹',
            '㍾',
            '㍽',
            '㍼',
            '∮',
            '∑',
            '∟',
            '⊿',
            '纊',
            '褜',
            '鍈',
            '銈',
            '蓜',
            '俉',
            '炻',
            '昱',
            '棈',
            '鋹',
            '曻',
            '彅',
            '丨',
            '仡',
            '仼',
            '伀',
            '伃',
            '伹',
            '佖',
            '侒',
            '侊',
            '侚',
            '侔',
            '俍',
            '偀',
            '倢',
            '俿',
            '倞',
            '偆',
            '偰',
            '偂',
            '傔',
            '僴',
            '僘',
            '兊',
            '兤',
            '冝',
            '冾',
            '凬',
            '刕',
            '劜',
            '劦',
            '勀',
            '勛',
            '匀',
            '匇',
            '匤',
            '卲',
            '厓',
            '厲',
            '叝',
            '﨎',
            '咜',
            '咊',
            '咩',
            '哿',
            '喆',
            '坙',
            '坥',
            '垬',
            '埈',
            '埇',
            '﨏',
            '塚',
            '增',
            '墲',
            '夋',
            '奓',
            '奛',
            '奝',
            '奣',
            '妤',
            '妺',
            '孖',
            '寀',
            '甯',
            '寘',
            '寬',
            '尞',
            '岦',
            '岺',
            '峵',
            '崧',
            '嵓',
            '﨑',
            '嵂',
            '嵭',
            '嶸',
            '嶹',
            '巐',
            '弡',
            '弴',
            '彧',
            '德',
            '忞',
            '恝',
            '悅',
            '悊',
            '惞',
            '惕',
            '愠',
            '惲',
            '愑',
            '愷',
            '愰',
            '憘',
            '戓',
            '抦',
            '揵',
            '摠',
            '撝',
            '擎',
            '敎',
            '昀',
            '昕',
            '昻',
            '昉',
            '昮',
            '昞',
            '昤',
            '晥',
            '晗',
            '晙',
            '晴',
            '晳',
            '暙',
            '暠',
            '暲',
            '暿',
            '曺',
            '朎',
            '朗',
            '杦',
            '枻',
            '桒',
            '柀',
            '栁',
            '桄',
            '棏',
            '﨓',
            '楨',
            '﨔',
            '榘',
            '槢',
            '樰',
            '橫',
            '橆',
            '橳',
            '橾',
            '櫢',
            '櫤',
            '毖',
            '氿',
            '汜',
            '沆',
            '汯',
            '泚',
            '洄',
            '涇',
            '浯',
            '涖',
            '涬',
            '淏',
            '淸',
            '淲',
            '淼',
            '渹',
            '湜',
            '渧',
            '渼',
            '溿',
            '澈',
            '澵',
            '濵',
            '瀅',
            '瀇',
            '瀨',
            '炅',
            '炫',
            '焏',
            '焄',
            '煜',
            '煆',
            '煇',
            '凞',
            '燁',
            '燾',
            '犱',
            '犾',
            '猤',
            '猪',
            '獷',
            '玽',
            '珉',
            '珖',
            '珣',
            '珒',
            '琇',
            '珵',
            '琦',
            '琪',
            '琩',
            '琮',
            '瑢',
            '璉',
            '璟',
            '甁',
            '畯',
            '皂',
            '皜',
            '皞',
            '皛',
            '皦',
            '益',
            '睆',
            '劯',
            '砡',
            '硎',
            '硤',
            '硺',
            '礰',
            '礼',
            '神',
            '祥',
            '禔',
            '福',
            '禛',
            '竑',
            '竧',
            '靖',
            '竫',
            '箞',
            '精',
            '絈',
            '絜',
            '綷',
            '綠',
            '緖',
            '繒',
            '罇',
            '羡',
            '羽',
            '茁',
            '荢',
            '荿',
            '菇',
            '菶',
            '葈',
            '蒴',
            '蕓',
            '蕙',
            '蕫',
            '﨟',
            '薰',
            '蘒',
            '﨡',
            '蠇',
            '裵',
            '訒',
            '訷',
            '詹',
            '誧',
            '誾',
            '諟',
            '諸',
            '諶',
            '譓',
            '譿',
            '賰',
            '賴',
            '贒',
            '赶',
            '﨣',
            '軏',
            '﨤',
            '逸',
            '遧',
            '郞',
            '都',
            '鄕',
            '鄧',
            '釚',
            '釗',
            '釞',
            '釭',
            '釮',
            '釤',
            '釥',
            '鈆',
            '鈐',
            '鈊',
            '鈺',
            '鉀',
            '鈼',
            '鉎',
            '鉙',
            '鉑',
            '鈹',
            '鉧',
            '銧',
            '鉷',
            '鉸',
            '鋧',
            '鋗',
            '鋙',
            '鋐',
            '﨧',
            '鋕',
            '鋠',
            '鋓',
            '錥',
            '錡',
            '鋻',
            '﨨',
            '錞',
            '鋿',
            '錝',
            '錂',
            '鍰',
            '鍗',
            '鎤',
            '鏆',
            '鏞',
            '鏸',
            '鐱',
            '鑅',
            '鑈',
            '閒',
            '隆',
            '﨩',
            '隝',
            '隯',
            '霳',
            '霻',
            '靃',
            '靍',
            '靏',
            '靑',
            '靕',
            '顗',
            '顥',
            '飯',
            '飼',
            '餧',
            '館',
            '馞',
            '驎',
            '髙',
            '髜',
            '魵',
            '魲',
            '鮏',
            '鮱',
            '鮻',
            '鰀',
            '鵰',
            '鵫',
            '鶴',
            '鸙',
            '黑',
            'ⅰ',
            'ⅱ',
            'ⅲ',
            'ⅳ',
            'ⅴ',
            'ⅵ',
            'ⅶ',
            'ⅷ',
            'ⅸ',
            'ⅹ',
            '￢',
            '￤',
            '＇',
            '＂',
            'ⅰ',
            'ⅱ',
            'ⅲ',
            'ⅳ',
            'ⅴ',
            'ⅵ',
            'ⅶ',
            'ⅷ',
            'ⅸ',
            'ⅹ',
            'Ⅰ',
            'Ⅱ',
            'Ⅲ',
            'Ⅳ',
            'Ⅴ',
            'Ⅵ',
            'Ⅶ',
            'Ⅷ',
            'Ⅸ',
            'Ⅹ',
            '￢',
            '￤',
            '＇',
            '＂',
            '㈱',
            '№',
            '℡',
            '纊',
            '褜',
            '鍈',
            '銈',
            '蓜',
            '俉',
            '炻',
            '昱',
            '棈',
            '鋹',
            '曻',
            '彅',
            '丨',
            '仡',
            '仼',
            '伀',
            '伃',
            '伹',
            '佖',
            '侒',
            '侊',
            '侚',
            '侔',
            '俍',
            '偀',
            '倢',
            '俿',
            '倞',
            '偆',
            '偰',
            '偂',
            '傔',
            '僴',
            '僘',
            '兊',
            '兤',
            '冝',
            '冾',
            '凬',
            '刕',
            '劜',
            '劦',
            '勀',
            '勛',
            '匀',
            '匇',
            '匤',
            '卲',
            '厓',
            '厲',
            '叝',
            '﨎',
            '咜',
            '咊',
            '咩',
            '哿',
            '喆',
            '坙',
            '坥',
            '垬',
            '埈',
            '埇',
            '﨏',
            '塚',
            '增',
            '墲',
            '夋',
            '奓',
            '奛',
            '奝',
            '奣',
            '妤',
            '妺',
            '孖',
            '寀',
            '甯',
            '寘',
            '寬',
            '尞',
            '岦',
            '岺',
            '峵',
            '崧',
            '嵓',
            '﨑',
            '嵂',
            '嵭',
            '嶸',
            '嶹',
            '巐',
            '弡',
            '弴',
            '彧',
            '德',
            '忞',
            '恝',
            '悅',
            '悊',
            '惞',
            '惕',
            '愠',
            '惲',
            '愑',
            '愷',
            '愰',
            '憘',
            '戓',
            '抦',
            '揵',
            '摠',
            '撝',
            '擎',
            '敎',
            '昀',
            '昕',
            '昻',
            '昉',
            '昮',
            '昞',
            '昤',
            '晥',
            '晗',
            '晙',
            '晴',
            '晳',
            '暙',
            '暠',
            '暲',
            '暿',
            '曺',
            '朎',
            '朗',
            '杦',
            '枻',
            '桒',
            '柀',
            '栁',
            '桄',
            '棏',
            '﨓',
            '楨',
            '﨔',
            '榘',
            '槢',
            '樰',
            '橫',
            '橆',
            '橳',
            '橾',
            '櫢',
            '櫤',
            '毖',
            '氿',
            '汜',
            '沆',
            '汯',
            '泚',
            '洄',
            '涇',
            '浯',
            '涖',
            '涬',
            '淏',
            '淸',
            '淲',
            '淼',
            '渹',
            '湜',
            '渧',
            '渼',
            '溿',
            '澈',
            '澵',
            '濵',
            '瀅',
            '瀇',
            '瀨',
            '炅',
            '炫',
            '焏',
            '焄',
            '煜',
            '煆',
            '煇',
            '凞',
            '燁',
            '燾',
            '犱',
            '犾',
            '猤',
            '猪',
            '獷',
            '玽',
            '珉',
            '珖',
            '珣',
            '珒',
            '琇',
            '珵',
            '琦',
            '琪',
            '琩',
            '琮',
            '瑢',
            '璉',
            '璟',
            '甁',
            '畯',
            '皂',
            '皜',
            '皞',
            '皛',
            '皦',
            '益',
            '睆',
            '劯',
            '砡',
            '硎',
            '硤',
            '硺',
            '礰',
            '礼',
            '神',
            '祥',
            '禔',
            '福',
            '禛',
            '竑',
            '竧',
            '靖',
            '竫',
            '箞',
            '精',
            '絈',
            '絜',
            '綷',
            '綠',
            '緖',
            '繒',
            '罇',
            '羡',
            '羽',
            '茁',
            '荢',
            '荿',
            '菇',
            '菶',
            '葈',
            '蒴',
            '蕓',
            '蕙',
            '蕫',
            '﨟',
            '薰',
            '蘒',
            '﨡',
            '蠇',
            '裵',
            '訒',
            '訷',
            '詹',
            '誧',
            '誾',
            '諟',
            '諸',
            '諶',
            '譓',
            '譿',
            '賰',
            '賴',
            '贒',
            '赶',
            '﨣',
            '軏',
            '﨤',
            '逸',
            '遧',
            '郞',
            '都',
            '鄕',
            '鄧',
            '釚',
            '釗',
            '釞',
            '釭',
            '釮',
            '釤',
            '釥',
            '鈆',
            '鈐',
            '鈊',
            '鈺',
            '鉀',
            '鈼',
            '鉎',
            '鉙',
            '鉑',
            '鈹',
            '鉧',
            '銧',
            '鉷',
            '鉸',
            '鋧',
            '鋗',
            '鋙',
            '鋐',
            '﨧',
            '鋕',
            '鋠',
            '鋓',
            '錥',
            '錡',
            '鋻',
            '﨨',
            '錞',
            '鋿',
            '錝',
            '錂',
            '鍰',
            '鍗',
            '鎤',
            '鏆',
            '鏞',
            '鏸',
            '鐱',
            '鑅',
            '鑈',
            '閒',
            '隆',
            '﨩',
            '隝',
            '隯',
            '霳',
            '霻',
            '靃',
            '靍',
            '靏',
            '靑',
            '靕',
            '顗',
            '顥',
            '飯',
            '飼',
            '餧',
            '館',
            '馞',
            '驎',
            '髙',
            '髜',
            '魵',
            '魲',
            '鮏',
            '鮱',
            '鮻',
            '鰀',
            '鵰',
            '鵫',
            '鶴',
            '鸙',
            '黑',
        );
    }

    private function getFunctionDivList()
    {
        return array(
            'A01',
            'A02',
            'A03',
            'A04',
            'A05',
            'A06',
            'A07',
            'B01',
            'B02',
            'B03',
            'B04',
            'B05',
            'B06',
            'C01',
            'C02',
            'C03',
            'C04',
            'C05',
            'C06',
            'D01',
            'E01',
            'E02',
            'E03',
            'E04',
            'F01',
            'F02',
            'F03',
            'F04',
            'KAAAU0010APIAction',
            'KAARS0010APIAction',
            'KAASL0010APIAction',
            'KAAST0010APIAction',
            'KAACL0010APIAction',
            'KAASD0010APIAction',
            'KAARA0010APIAction',
        );
    }

}