<?php
/*
 * Copyright(c)2016, Yamato Financial Co.,Ltd. All rights reserved.
 * Copyright(c)2016, Yamato Credit finance Co.,Ltd. All rights reserved.
 */

namespace Plugin\YamatoPayment\Util;

use Eccube\Util\Str;

class CommonUtilTest extends \PHPUnit_Framework_TestCase
{
    const MOBILE_USER_AGENT = 'DoCoMo/2.0 SH06A3(c500;TC;W30H18)';
    const SPHONE_USER_AGENT = 'Opera/9.80 (J2ME/MIDP; Opera Mini/9.80 (S60; SymbOS; Opera Mobi/23.348; U; en) Presto/2.5.25 Version/10.54';
    const PC_USER_AGENT = 'Mozilla/5.0 (Windows NT 5.1; rv:38.0) Gecko/20100101 Firefox/38.0';
    const TABLET_USER_AGENT = 'Mozilla/5.0 (Linux; Android 4.3; Nexus 7 Build/JWR66Y) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/29.0.1547.72 Safari/537.36';

    function test_unSerializeData()
    {
        $data = array(
            'value1' => 'aaa',
            'value2' => 'bbb',
        );
        $serializeData = serialize($data);

        /*
         * Serializeしたデータを渡すと、unSerializeされて返る
         */
        $expected = $data;
        $actual = CommonUtil::unSerializeData($serializeData);
        $this->assertEquals($expected, $actual);
    }

    function test_getDeviceDivision()
    {
        /*
         * スマホの場合、1が返る
         */
        $_SERVER['HTTP_USER_AGENT'] = self::SPHONE_USER_AGENT;
        $expected = 1;
        $actual = CommonUtil::getDeviceDivision();
        $this->assertEquals($expected, $actual);

        /*
         * PCの場合、2が返る
         */
        $_SERVER['HTTP_USER_AGENT'] = self::PC_USER_AGENT;
        $expected = 2;
        $actual = CommonUtil::getDeviceDivision();
        $this->assertEquals($expected, $actual);

        /*
         * タブレットの場合、2が返る
         */
        $_SERVER['HTTP_USER_AGENT'] = self::TABLET_USER_AGENT;
        $expected = 2;
        $actual = CommonUtil::getDeviceDivision();
        $this->assertEquals($expected, $actual);
    }

    function test_isSmartPhone()
    {
        /*
         * スマホの場合、trueが返る
         */
        $_SERVER['HTTP_USER_AGENT'] = self::SPHONE_USER_AGENT;
        $expected = true;
        $actual = CommonUtil::isSmartPhone();
        $this->assertEquals($expected, $actual);

        /*
         * PCの場合、falseが返る
         */
        $_SERVER['HTTP_USER_AGENT'] = self::PC_USER_AGENT;
        $expected = false;
        $actual = CommonUtil::isSmartPhone();
        $this->assertEquals($expected, $actual);

        /*
         * タブレットの場合、falseが返る
         */
        $_SERVER['HTTP_USER_AGENT'] = self::TABLET_USER_AGENT;
        $expected = false;
        $actual = CommonUtil::isSmartPhone();
        $this->assertEquals($expected, $actual);

    }

    function test_isMobile()
    {
        /*
         * スマホの場合、falseが返る
         */
        $_SERVER['HTTP_USER_AGENT'] = self::SPHONE_USER_AGENT;
        $expected = false;
        $actual = CommonUtil::isMobile();
        $this->assertEquals($expected, $actual);

        /*
         * PCの場合、falseが返る
         */
        $_SERVER['HTTP_USER_AGENT'] = self::PC_USER_AGENT;
        $expected = false;
        $actual = CommonUtil::isMobile();
        $this->assertEquals($expected, $actual);

        /*
         * タブレットの場合、falseが返る
         */
        $_SERVER['HTTP_USER_AGENT'] = self::TABLET_USER_AGENT;
        $expected = false;
        $actual = CommonUtil::isMobile();
        $this->assertEquals($expected, $actual);
    }

    function test_isProhibitedChar()
    {
        /*
         * nullを渡すと、falseが返る
         */
        $this->assertFalse(CommonUtil::isProhibitedChar(null));

        /*
         * 禁止文字（機種依存文字）を渡すと、trueが返る
         */
        $this->assertTrue(CommonUtil::isProhibitedChar('―'));
        $this->assertTrue(CommonUtil::isProhibitedChar('①'));
        $this->assertTrue(CommonUtil::isProhibitedChar('纊'));
        $this->assertTrue(CommonUtil::isProhibitedChar('ⅰ'));

        /*
         * 禁止文字以外（ひらがな）を渡すと、falseが返る
         */
        $this->assertFalse(CommonUtil::isProhibitedChar('あ'));

        /*
         * 禁止文字以外（半角カナ）を渡すと、falseが返る
         */
        $this->assertFalse(CommonUtil::isProhibitedChar('ｱ'));
    }

    function test_convertProhibitedChar()
    {
        /*
         * nullを渡すと、空文字が返る
         */
        $expected = '';
        $actual = CommonUtil::convertProhibitedChar(null);
        $this->assertEquals($expected, $actual);

        /*
         * 空文字を渡すと、空文字が返る
         */
        $expected = '';
        $actual = CommonUtil::convertProhibitedChar('');
        $this->assertEquals($expected, $actual);

        /*
         * 禁止文字を渡すと、全角スペースに変換されるる
         */
        $expected = '　';
        $actual = CommonUtil::convertProhibitedChar('①');
        $this->assertEquals($expected, $actual);

        /*
         * 禁止文字を渡すと、全角スペースに変換されるる
         */
        $expected = 'あアｱa　';
        $actual = CommonUtil::convertProhibitedChar('あアｱa①');
        $this->assertEquals($expected, $actual);
    }

    function test_convertProhibitedKigo()
    {
        /*
         * nullを渡すと、空文字が返る
         */
        $expected = '';
        $actual = CommonUtil::convertProhibitedKigo(null);
        $this->assertEquals($expected, $actual);

        /*
         * 空文字を渡すと、空文字が返る
         */
        $expected = '';
        $actual = CommonUtil::convertProhibitedKigo('');
        $this->assertEquals($expected, $actual);

        /*
         * 禁止文字を渡すと、半角スペースに変換されるる
         */
        $expected = ' ';
        $actual = CommonUtil::convertProhibitedKigo('$');
        $this->assertEquals($expected, $actual);

        /*
         * 禁止文字を渡すと、半角スペースに変換されるる
         */
        $expected = 'あアｱa ';
        $actual = CommonUtil::convertProhibitedKigo('あアｱa$');
        $this->assertEquals($expected, $actual);
    }

    function test_subString()
    {
        /*
         * 空文字を渡すと、空文字が返る
         */
        $expected = '';
        $actual = CommonUtil::subString('', 2);
        $this->assertEquals($expected, $actual);

        /*
         * nullを渡すと、空文字が返る
         */
        $expected = '';
        $actual = CommonUtil::subString(null, 2);
        $this->assertEquals($expected, $actual);

        /*
         * 文字列を渡すと、指定したバイト数カットされて返る
         */
        $expected = 'ab';
        $actual = CommonUtil::subString('abcde', 2);
        $this->assertEquals($expected, $actual);

        /*
         * 文字列(日本語)を渡すと、指定したバイト数カットされて返る
         */
        $expected = 'あ';
        $actual = CommonUtil::subString('あいうえお', 2);
        $this->assertEquals($expected, $actual);

        /*
         * 文字列(日本語)とバイト数が割り切れない場合も、割り切れるバイト数カットされて返る
         */
        $expected = 'あ';
        $actual = CommonUtil::subString('あいうえお', 3);
        $this->assertEquals($expected, $actual);
    }

    function test_setMaskData()
    {
        $data = array(
            'card_no' => '12345678901111',
            'CARD_NO' => '12345678902222',
            'security_code' => '1234',
            'card_exp' => '12345',
            'cardExp' => '123456',
            'authentication_key' => '1234567',
            'check_sum' => '1234567890',
        );

        $maskedData = array(
            'card_no' => '**********1111',
            'CARD_NO' => '**********2222',
            'security_code' => '****',
            'card_exp' => '*****',
            'cardExp' => '******',
            'authentication_key' => '*******',
            'check_sum' => '12345678...',
        );

        /*
         * マスク対象のデータを渡すと、マスクされたデータが返る
         */
        $expected = $maskedData;
        $actual = CommonUtil::setMaskData($data);
        $this->assertEquals($expected, $actual);

        /*
         * マスク対象データが多重配列でも、マスクされたデータが返る
         */
        $expected = array('0' => $maskedData);
        $actual = CommonUtil::setMaskData(array('0' => $data));
        $this->assertEquals($expected, $actual);

        /*
         * マスク対象外のデータは、そのまま返る
         */
        $nonmask = array('nonmask' => '1234567890');
        $expected = $nonmask;
        $actual = CommonUtil::setMaskData($nonmask);
        $this->assertEquals($expected, $actual);

        /*
         * 空の配列を渡すと、空の配列が返る
         */
        $expected = array();
        $actual = CommonUtil::setMaskData(array());
        $this->assertEquals($expected, $actual);
    }

    function test_checkEncode()
    {
        $listData = array(
            'trader_code' => '12345678',
            'order_no' => '67',
            'settle_price' => '2296',
            'settle_date' => '11111111111111',
            'settle_result' => '1',
            'settle_detail' => '11',
            'settle_method' => '10',
        );
        $char_code = 'UTF-8';

        /*
         * 未設定、配列、単語空白の値を渡すと、そのままの値が返る
         */
        $expected = $listData;
        $actual = CommonUtil::checkEncode($listData, $char_code);
        $this->assertEquals($expected, $actual);

        /*
         * 未設定、配列、単語空白以外の値を渡すと、そのままの値が返る
         */
        $listData['trader_code'] = 'あいうえお12345';
        $expected = $listData;
        $actual = CommonUtil::checkEncode($listData, $char_code);
        $this->assertEquals($expected, $actual);

        /*
         * 特殊文字の値を渡すと、エラーメッセージが返る
         */
        $listData['trader_code'] = '';
        $expected = $listData;
        $expected['trader_code'] = 'unknown encoding strings';
        $actual = CommonUtil::checkEncode($listData, $char_code);
        $this->assertEquals($expected, $actual);
    }

    function test_getDateFromNumber()
    {
        $format = 'Y年m月d日 H時i分s秒';
        $number = 20160831101010;

        /*
         * 日付(YYYYMMDD)を渡すと、指定されたフォーマットで返る
         */
        $expected = '2016年08月31日 10時10分10秒';
        $actual = CommonUtil::getDateFromNumber($format, $number);
        $this->assertEquals($expected, $actual);

        /*
         * 存在しない日付(YYYYMMDD)をを渡すと、今日の日付で返る
         */
        $number = '20161332101010';
        $time = time();
        $expected = date($format, $time);
        $actual = CommonUtil::getDateFromNumber($format, $number);
        $this->assertEquals($expected, $actual);
    }

    function test_getMemberId()
    {
        $memder_id = 1;

        /*
         * 会員IDを渡すと、会員IDが返る
         */
        $expected = $memder_id;
        $actual = CommonUtil::getMemberId($memder_id);
        $this->assertEquals($expected, $actual);

        /*
         * 非会員ID(0)を渡すと、今日の日付が返る
         */
        $memder_id = 0;
        $time = time();
        $expected = date('YmdHis', $time);
        $actual = CommonUtil::getMemberId($memder_id);
        $this->assertEquals($expected, $actual);
    }

    function test_getAuthenticationKey()
    {
        $memder_id = 1;

        /*
         * 会員IDを渡すと、認証キー（会員ID）が返る
         */
        $expected = $memder_id;
        $actual = CommonUtil::getAuthenticationKey($memder_id);
        $this->assertEquals($expected, $actual);

        /*
         * 非会員ID(0)を渡すと、8桁の認証キー（ランダム値）が返る
         */
        $memder_id = 0;
        $expected = Str::quickRandom(8);
        $actual = CommonUtil::getAuthenticationKey($memder_id);
        $this->assertNotEquals($expected, $actual);
    }

    function test_getCheckSum()
    {
        $listParam = array(
            'authentication_key' => '1',
            'member_id' => '1',
        );
        $listMdlSetting = array(
            'access_key' => '1111111',
        );

        /*
         * 会員時のパラメータを渡すと、チェックサムが返る
         */
        $expected = '1a5376ad727d65213a79f3108541cf95012969a0d3064f108b5dd6e7f8c19b89';
        $actual = CommonUtil::getCheckSum($listParam, $listMdlSetting);
        $this->assertEquals($expected, $actual);

        /*
         * 非会員時のパラメータを渡すと、チェックサム（ランダム値）が返る
         */
        $listParam = array(
            'authentication_key' => Str::quickRandom(8),
            'member_id' => '0',
        );
        $expected = '1a5376ad727d65213a79f3108541cf95012969a0d3064f108b5dd6e7f8c19b89';
        $actual = CommonUtil::getCheckSum($listParam, $listMdlSetting);
        $this->assertNotEquals($expected, $actual);
    }

    function test_getFormatedDate()
    {
        $date = '2014-02-11';
        $format = 'Ymd';

        /*
         * 日付（DB値）を渡すと、DATE型が返る
         */
        $expected = '20140211';
        $actual = CommonUtil::getFormatedDate($date, $format);
        $this->assertEquals($expected, $actual);

        /*
         * 空の日付（DB値）を渡すと、空の値が返る
         */
        $date = '';
        $expected = '';
        $actual = CommonUtil::getFormatedDate($date, $format);
        $this->assertEquals($expected, $actual);

        /*
         * 空のフォーマットを渡すと、日付（DB値）が返る
         */
        $date = '2014-02-11';
        $format = '';
        $expected = '2014-02-11';
        $actual = CommonUtil::getFormatedDate($date, $format);
        $this->assertEquals($expected, $actual);
    }

    function test_isInt()
    {
        /*
         * １文字以上の数値の場合、trueが返る
         */
        $value = 1;
        $expected = true;
        $actual = CommonUtil::isInt($value);
        $this->assertEquals($expected, $actual);

        /*
         * nullの場合、falseが返る
         */
        $value = null;
        $expected = false;
        $actual = CommonUtil::isInt($value);
        $this->assertEquals($expected, $actual);

        /*
         * 9文字以下の数値の場合、trueが返る
         */
        $value = 111111111;
        $expected = true;
        $actual = CommonUtil::isInt($value);
        $this->assertEquals($expected, $actual);

        /*
         * 9文字以上の数値の場合、falseが返る
         */
        $value = 1111111111;
        $expected = false;
        $actual = CommonUtil::isInt($value);
        $this->assertEquals($expected, $actual);

        /*
         * 数値以外の場合、falseが返る
         */
        $value = 'abcdefghi';
        $expected = false;
        $actual = CommonUtil::isInt($value);
        $this->assertEquals($expected, $actual);
    }

    function test_checkDelivSlip()
    {
        /*
         * 先頭11桁÷7の余りが末尾1桁の12桁の場合、trueが返る
         */
        $delivSlip = 123456789013;
        $expected = true;
        $actual = CommonUtil::checkDelivSlip($delivSlip);
        $this->assertEquals($expected, $actual);

        /*
         * 12桁でない場合、falseが返る
         */
        $delivSlip = 1;
        $expected = false;
        $actual = CommonUtil::checkDelivSlip($delivSlip);
        $this->assertEquals($expected, $actual);

        /*
         * 先頭11桁÷7の余りが末尾1桁でない12桁の場合、falseが返る
         */
        $delivSlip = 123456789012;
        $expected = false;
        $actual = CommonUtil::checkDelivSlip($delivSlip);
        $this->assertEquals($expected, $actual);
    }

    function test_convHalfToFull()
    {
        $str = 'あa1"\'\~';
        $expected = 'あａ１”’￥～';
        $actual = CommonUtil::convHalfToFull($str);
        $this->assertEquals($expected, $actual);
    }
}
