<?php
/*
 * Copyright(c)2016, Yamato Financial Co.,Ltd. All rights reserved.
 * Copyright(c)2016, Yamato Credit finance Co.,Ltd. All rights reserved.
 */


namespace Plugin\YamatoPayment\Util;

use Eccube\Util\Str;

class CommonUtil
{
    /**
     * unSerializeした文字列を返す
     *
     * @param string $data
     * @return mixed
     */
    public static function unSerializeData($data)
    {
        $returnData = preg_replace_callback('!s:(\d+):"(.*?)";!', function ($match) {
            return ($match[1] == strlen($match[2])) ? $match[0] : 's:' . strlen($match[2]) . ':"' . $match[2] . '";';
        }, $data);

        return unserialize($returnData);
    }

    /**
     * 端末区分を取得する
     *
     *  1:スマートフォン
     *  2:PC
     *  3:携帯電話
     *
     * @return integer 端末区分
     */
    public static function getDeviceDivision()
    {
        $result = 2;
        // 携帯は対応しない
        //if (self::isMobile()) {
        //    $result = 3;
        //}
        if (self::isSmartPhone()) {
            $result = 1;
        }
        return $result;
    }

    /**
     * スマートフォンかどうかを判別する。
     *
     * @return boolean
     */
    public static function isSmartPhone()
    {
        $detect = new MobileDetectUtil();

        // TabletはPC扱い
        return ($detect->isMobile() && !$detect->isTablet());
    }

    /**
     * 携帯電話端末かどうかを判別する。
     *
     * @return boolean
     */
    public static function isMobile()
    {
        // 携帯は対応しないので、固定でfalseを返す
        return false;
    }

    /**
     * 禁止文字か判定を行う。
     *
     * @param string $value 判定対象
     * @return boolean 禁止文字の場合、true
     */
    public static function isProhibitedChar($value)
    {
        $check_char = mb_convert_encoding($value, "SJIS-win", "UTF-8");

        $listProhibited = array('815C', '8160', '8161', '817C', '8191', '8192', '81CA');
        foreach ($listProhibited as $prohibited) {
            if (hexdec($prohibited) == hexdec(bin2hex($check_char))) {
                return true;
            }
        }

        if (hexdec('8740') <= hexdec(bin2hex($check_char)) && hexdec('879E') >= hexdec(bin2hex($check_char))) {
            return true;
        }
        if ((hexdec('ED40') <= hexdec(bin2hex($check_char)) && hexdec('ED9E') >= hexdec(bin2hex($check_char)))
            || (hexdec('ED9F') <= hexdec(bin2hex($check_char)) && hexdec('EDFC') >= hexdec(bin2hex($check_char)))
            || (hexdec('EE40') <= hexdec(bin2hex($check_char)) && hexdec('EE9E') >= hexdec(bin2hex($check_char)))
            || (hexdec('FA40') <= hexdec(bin2hex($check_char)) && hexdec('FA9E') >= hexdec(bin2hex($check_char)))
            || (hexdec('FA9F') <= hexdec(bin2hex($check_char)) && hexdec('FAFC') >= hexdec(bin2hex($check_char)))
            || (hexdec('FB40') <= hexdec(bin2hex($check_char)) && hexdec('FB9E') >= hexdec(bin2hex($check_char)))
            || (hexdec('FB9F') <= hexdec(bin2hex($check_char)) && hexdec('FBFC') >= hexdec(bin2hex($check_char)))
            || (hexdec('FC40') <= hexdec(bin2hex($check_char)) && hexdec('FC4B') >= hexdec(bin2hex($check_char)))
        ) {
            return true;
        }
        if ((hexdec('EE9F') <= hexdec(bin2hex($check_char)) && hexdec('EEFC') >= hexdec(bin2hex($check_char)))
            || (hexdec('F040') <= hexdec(bin2hex($check_char)) && hexdec('F9FC') >= hexdec(bin2hex($check_char)))
        ) {
            return true;
        }

        return false;
    }

    /**
     * 禁止文字を全角スペースに置換する。
     *
     * @param string $value 対象文字列
     * @param string $encoding
     * @return string 結果
     */
    public static function convertProhibitedChar($value, $encoding = 'utf-8')
    {
        $ret = $value;
        for ($i = 0; $i < mb_strlen($value); $i++) {
            $tmp = mb_substr($value, $i, 1, $encoding);
            if (self::isProhibitedChar($tmp)) {
                $ret = str_replace($tmp, "　", $ret);
            }
        }
        return $ret;
    }

    /**
     * 禁止半角記号を半角スペースに変換する。
     *
     * @param string $value
     * @return string 変換した値
     */
    public static function convertProhibitedKigo($value, $rep = " ")
    {
        $listProhibitedKigo = array(
            '!', '"', '$', '%', '&', '\'', '(', ')', '+', ',', '.', ';', '=', '?', '[', '\\', ']', '^', '_', '`', '{', '|', '}', '~',
        );
        foreach ($listProhibitedKigo as $prohibitedKigo) {
            if (strstr($value, $prohibitedKigo)) {
                $value = str_replace($prohibitedKigo, $rep, $value);
            }
        }
        return $value;
    }

    /**
     * 文字列から指定バイト数を切り出す。
     *
     * @param string $value
     * @param integer $len
     * @return string 結果
     */
    public static function subString($value, $len)
    {
        $ret = '';
        $value = mb_convert_encoding($value, "SJIS-win", "UTF-8");
        for ($i = 1; $i <= mb_strlen($value); $i++) {
            $tmp = mb_substr($value, 0, $i, "SJIS-win");
            if (strlen($tmp) <= $len) {
                $ret = mb_convert_encoding($tmp, "UTF-8", "SJIS-win");
            } else {
                break;
            }
        }
        return $ret;
    }

    /**
     * 配列データからログに記録しないデータをマスクする
     *
     * @param array $listData
     * @return array マスク後データ
     */
    public static function setMaskData($listData)
    {
        foreach ($listData as $key => &$val) {
            if (is_array($val) || is_object($val)) {
                $val = self::setMaskData($val);
                continue;
            }
            switch ($key) {
                case 'card_no':
                    $val = str_repeat('*', strlen($val) - 4) . substr($val, -4);
                    break;
                case 'CARD_NO':
                    $val = str_repeat('*', strlen($val) - 4) . substr($val, -4);
                    break;
                case 'security_code':
                    $val = str_repeat('*', strlen($val));
                    break;
                case 'card_exp':
                    $val = str_repeat('*', strlen($val));
                    break;
                case 'cardExp':
                    $val = str_repeat('*', strlen($val));
                    break;
                case 'authentication_key':
                    $val = str_repeat('*', strlen($val));
                    break;
                case 'check_sum':
                    //先頭8文字のみとする
                    $val = substr($val, 0, 8) . '...';
                    break;
                default:
                    break;
            }
        }
        return $listData;
    }

    /**
     * エンコードチェック
     * $char_code > SJIS-win > $char_code で欠落のないことを確認する
     *
     * @param array $listData
     * @param string $char_code 文字コード
     * @return array $listData
     */
    public static function checkEncode(array $listData, $char_code)
    {
        foreach ($listData as $key => $val) {
            //未設定、配列、単語空白以外の場合はスキップ
            if (!$val || is_array($val) || preg_match('/^[\w\s]+$/i', $val)) {
                continue;
            }
            //CHAR_CODE > SJIS-WIN > CHAR_CODEで欠落のないことを確認
            $temp = mb_convert_encoding($val, 'SJIS-win', $char_code);
            $temp = mb_convert_encoding($temp, $char_code, 'SJIS-win');
            if ($val !== $temp) {
                $temp = mb_convert_encoding($val, $char_code, 'SJIS-win');
                $temp = mb_convert_encoding($temp, 'SJIS-win', $char_code);
                if ($val === $temp) {
                    $listData[$key] = mb_convert_encoding($val, $char_code, 'SJIS-win');
                } else {
                    $listData[$key] = 'unknown encoding strings';
                }
            }
        }
        return $listData;
    }

    /**
     * 日付(YYYYMMDD)をフォーマットして返す
     *
     * @param string $format
     * @param integer $number 日付(YYYYMMDD)
     * @return string フォーマット後の日付
     */
    public static function getDateFromNumber($format = 'Ymd', $number)
    {
        $number = (string)$number;
        $shortFlag = (strlen($number) < 9) ? true : false;
        $year = substr($number, 0, 4);
        $month = substr($number, 4, 2);
        $day = substr($number, 6, 2);
        $hour = ($shortFlag) ? '0' : substr($number, 8, 2);
        $minute = ($shortFlag) ? '0' : substr($number, 10, 2);
        $second = ($shortFlag) ? '0' : substr($number, 12, 2);

        return (checkdate($month, $day, $year))? date($format, mktime($hour, $minute, $second, $month, $day, $year)) : date($format);
    }

    /**
     * メンバーID取得
     *
     * @param integer $customer_id
     * @return integer $customer_id
     */
    public static function getMemberId($customer_id)
    {
        return ($customer_id != '0') ? $customer_id : date('YmdHis');
    }

    /**
     * 認証キー取得
     *
     * @param integer $customer_id 会員ID
     * @return string 認証キー
     */
    public static function getAuthenticationKey($customer_id)
    {
        return ($customer_id != '0') ? $customer_id : Str::quickRandom(8);
    }

    /**
     * チェックサム取得
     *
     * @param array $listParam パラメタ
     * @param array $listMdlSetting モジュール設定
     * @return string
     */
    public static function getCheckSum($listParam, $listMdlSetting)
    {
        $authKey = $listParam['authentication_key'];
        $memberId = $listParam['member_id'];
        $accessKey = $listMdlSetting['access_key'];
        $checksum = hash('sha256', $memberId . $authKey . $accessKey);
        return $checksum;
    }

    /**
     * トークン発行時のチェックサム取得
     *
     * @param array $listParam パラメタ
     * @param array $listMdlSetting モジュール設定
     * @return string
     */
    public static function getCheckSumForToken($listParam, $listMdlSetting) {
        $memberId = $listParam['member_id'];
        $authKey = $listParam['authentication_key'];
        $accessKey = $listMdlSetting['access_key'];
        $authDiv = $listParam['auth_div'];
        $checksum = hash('sha256', $memberId . $authKey . $accessKey . $authDiv);
        return $checksum;
    }

    /**
     * 日付(DATE型)を整形する
     *
     * 例)
     * DB値  ： 2014-02-11
     * 戻り値： 20140211
     *
     * @param string $date
     * @param string $format
     * @return string
     */
    public static function getFormatedDate($date, $format = 'Ymd')
    {
        if (empty($date)) {
            return $date;
        }
        if (empty($format)) {
            return $date;
        }
        return date($format, strtotime($date));
    }

    /**
     *  INT型の数値チェック
     *  ・FIXME: マイナス値の扱いが不明確
     *  ・XXX: INT_LENには収まるが、INT型の範囲を超えるケースに対応できないのでは?
     *
     * @param mixed $value
     * @return bool
     */
    public static function isInt($value)
    {
        if (strlen($value) >= 1 && strlen($value) <= 9 && is_numeric($value)) {
            return true;
        }

        return false;
    }

    /**
     * 伝票番号 桁数チェック・セブンチェックを行う.
     *
     * @param integer $delivSlip
     * @return bool
     */
    public static function checkDelivSlip($delivSlip = 0)
    {
        $arrStr = str_split((string)$delivSlip);
        //桁数チェック
        if (count($arrStr) != 12) return false;
        //セブンチェック（先頭11桁÷7の余りが末尾1桁）
        $tempMod = 0;
        for ($i = 0; $i < 11; $i++) {
            $tempMod = $tempMod * 10 + (int)$arrStr[$i];
            $tempMod %= 7;
        }
        if ($tempMod !== (int)$arrStr[11]) return false;
        return true;
    }

    /**
     * 半角→全角変換
     *
     * @param string $data
     * @param string $encoding
     * @return string
     */
    public static function convHalfToFull(&$data, $encoding = 'utf-8')
    {
        $data = str_replace('"','”',$data);
        $data = str_replace("'","’",$data);
        $data = str_replace("\\","￥",$data);
        $data = str_replace("~","～",$data);
        $data = mb_convert_kana($data, 'KVAS', $encoding);

        return $data;
    }

}
