<?php
/*
 * Copyright(c)2016, Yamato Financial Co.,Ltd. All rights reserved.
 * Copyright(c)2016, Yamato Credit finance Co.,Ltd. All rights reserved.
 */

namespace Plugin\YamatoPayment\Util;

use Plugin\YamatoPayment\AbstractYamatoPaymentTestCase;

class MobileDetectUtilTest extends AbstractYamatoPaymentTestCase
{
    const MOBILE_USER_AGENT = 'DoCoMo/2.0 SH06A3(c500;TC;W30H18)';
    const SPHONE_USER_AGENT = 'Opera/9.80 (J2ME/MIDP; Opera Mini/9.80 (S60; SymbOS; Opera Mobi/23.348; U; en) Presto/2.5.25 Version/10.54';
    const PC_USER_AGENT = 'Mozilla/5.0 (Windows NT 5.1; rv:38.0) Gecko/20100101 Firefox/38.0';
    const TABLET_USER_AGENT = 'Mozilla/5.0 (Linux; Android 4.3; Nexus 7 Build/JWR66Y) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/29.0.1547.72 Safari/537.36';


    function test_isMobile()
    {
        /*
         * スマホの場合、trueが返る
         */
        $_SERVER['HTTP_USER_AGENT'] = self::SPHONE_USER_AGENT;
        $detect = new MobileDetectUtil();
        $expected = true;
        $actual = $detect->isMobile();
        $this->assertEquals($expected, $actual);

        /*
         * PCの場合、falseが返る
         */
        $_SERVER['HTTP_USER_AGENT'] = self::PC_USER_AGENT;
        $detect = new MobileDetectUtil();
        $expected = false;
        $actual = $detect->isMobile();
        $this->assertEquals($expected, $actual);

        /*
         * タブレットの場合、trueが返る
         */
        $_SERVER['HTTP_USER_AGENT'] = self::TABLET_USER_AGENT;
        $detect = new MobileDetectUtil();
        $expected = true;
        $actual = $detect->isMobile();
        $this->assertEquals($expected, $actual);
    }

    function test_isTablet()
    {
        /*
         * スマホの場合、falseが返る
         */
        $_SERVER['HTTP_USER_AGENT'] = self::SPHONE_USER_AGENT;
        $detect = new MobileDetectUtil();
        $expected = false;
        $actual = $detect->isTablet();
        $this->assertEquals($expected, $actual);

        /*
         * PCの場合、falseが返る
         */
        $_SERVER['HTTP_USER_AGENT'] = self::PC_USER_AGENT;
        $detect = new MobileDetectUtil();
        $expected = false;
        $actual = $detect->isTablet();
        $this->assertEquals($expected, $actual);

        /*
         * タブレットの場合、trueが返る
         */
        $_SERVER['HTTP_USER_AGENT'] = self::TABLET_USER_AGENT;
        $detect = new MobileDetectUtil();
        $expected = true;
        $actual = $detect->isTablet();
        $this->assertEquals($expected, $actual);
    }

}
