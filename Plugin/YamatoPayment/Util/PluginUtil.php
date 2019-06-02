<?php
/*
 * Copyright(c)2016, Yamato Financial Co.,Ltd. All rights reserved.
 * Copyright(c)2016, Yamato Credit finance Co.,Ltd. All rights reserved.
 */


namespace Plugin\YamatoPayment\Util;

use Eccube\Application;
use Eccube\Entity\Customer;
use Monolog\Logger;
use Plugin\YamatoPayment\Entity\YamatoPlugin;
use Symfony\Component\Yaml\Yaml;

/**
 * 決済モジュール基本クラス
 */
class PluginUtil
{
    /**
     * @var Application
     */
    private $app;

    /**
     * @var array
     */
    private $const;

    /**
     * @var array プラグイン設定データ
     */
    var $subData = null;

    /**
     * @var array B2用設定データ
     */
    var $b2Data = null;

    /**
     * @var array プラグイン情報
     */
    var $pluginInfo = array();

    /**
     * コンストラクタ
     *
     * @param Application $app
     */
    public function __construct(Application $app)
    {
        $this->app = $app;
        $this->const = $app['config']['YamatoPayment']['const'];
        $this->pluginInfo = Yaml::parse(__DIR__ . '/../config.yml');
    }

    /**
     * プラグイン名の取得
     *
     * @return string プラグイン名
     */
    public function getPluginName()
    {
        return (isset($this->pluginInfo['name'])) ? $this->pluginInfo['name'] : '';
    }

    /**
     * プラグインコードの取得
     *
     * @return string プラグインコード
     */
    public function getPluginCode()
    {
        return (isset($this->pluginInfo['code'])) ? $this->pluginInfo['code'] : '';
    }

    /**
     * プラグインバージョンの取得
     *
     * @return string プラグインバージョン
     */
    public function getPluginVersion()
    {
        return (isset($this->pluginInfo['version'])) ? $this->pluginInfo['version'] : '';
    }

    /**
     * サブデータの UserSetting の情報を取得する
     *
     * @param string $key UserSetting の添字
     * @return array|string プラグインの設定情報
     */
    public function getUserSettings($key = null)
    {
        // サブデータを取得する
        $subData = $this->getSubData();

        if (is_null($key)) {
            $returnData = isset($subData['user_settings'])
                ? $subData['user_settings']
                : null;
        } else {
            $returnData = isset($subData['user_settings'][$key])
                ? $subData['user_settings'][$key]
                : null;
        }

        return $returnData;
    }

    /**
     * サブデータを取得する.
     *
     * @param string $key subDataの添字
     * @return array|string プラグインの設定情報
     */
    public function getSubData($key = null)
    {
        if (is_null($this->subData)) {
            // プラグインコードに一致する dtb_yamato_plugin の SubData の情報を取得
            /** @var YamatoPlugin $YamatoPlugin */
            $YamatoPlugin = $this->app['yamato_payment.repository.yamato_plugin']
                ->findOneBy(array(
                    'code' => $this->getPluginCode()
                ));
            $this->subData = (!is_null($YamatoPlugin)) ? $YamatoPlugin->getSubData() : array();
        }

        if (is_null($key)) {
            return $this->subData;
        } else {
            return (isset($this->subData[$key])) ? $this->subData[$key] : null;
        }
    }

    /**
     * サブデータをDBへ登録する
     * $keyがnullの時は全データを上書きする
     *
     * @param array|string $data 登録するデータ
     * @param string $key subDataの添字
     */
    public function registerSubData($data, $key = null)
    {
        $subData = $this->getSubData();

        if (is_null($key)) {
            $subData = $data;
        } else {
            $subData[$key] = $data;
        }

        // プラグインコードに一致するデータを plg_yamato_plugin から取得
        /** @var YamatoPlugin $YamatoPlugin */
        $YamatoPlugin = $this->app['yamato_payment.repository.yamato_plugin']
            ->findOneBy(array(
                'code' => $this->getPluginCode())
            );

        // データの有無を判定
        if (!is_null($YamatoPlugin)) {
            // サブデータを設定する
            $YamatoPlugin->setSubData($subData);
            $this->app['orm.em']->persist($YamatoPlugin);
            $this->app['orm.em']->flush();
        }

        $this->subData = $subData;
    }

    /**
     * UserSetting 情報の登録
     *
     * @param array $data 登録するデータ
     */
    public function registerUserSettings($data)
    {
        $this->registerSubData($data, 'user_settings');
    }

    /**
     * B2用データの UserSetting の情報を取得する
     *
     * @param string $key UserSetting の添字
     * @return array|string|null プラグインB2設定情報
     */
    public function getB2UserSettings($key = null)
    {
        // B2用データを取得する
        $b2Data = $this->getB2Data();
        $returnData = null;

        if (is_null($key)) {
            $returnData = isset($b2Data['user_settings'])
                ? $b2Data['user_settings']
                : null;
        } else {
            $returnData = isset($b2Data['user_settings'][$key])
                ? $b2Data['user_settings'][$key]
                : null;
        }

        return $returnData;
    }

    /**
     * B2用データを取得する.
     *
     * @param string $key subData の添字
     * @return array|string|null プラグインB2設定情報
     */
    public function getB2Data($key = null)
    {
        if (is_null($this->b2Data)) {
            // プラグインコードに一致する dtb_yamato_plugin の B2Data の情報を取得
            /** @var YamatoPlugin $YamatoPlugin */
            $YamatoPlugin = $this->app['yamato_payment.repository.yamato_plugin']
                ->findOneBy(array(
                    'code' => $this->getPluginCode()
                ));
            $this->b2Data = (!is_null($YamatoPlugin)) ? $YamatoPlugin->getB2Data() : array();
        }

        if (is_null($key)) {
            return $this->b2Data;
        } else {
            return (isset($this->b2Data[$key])) ? $this->b2Data[$key] : null;
        }
    }

    /**
     * B2用データをDBへ登録する
     * $keyがnullの時は全データを上書きする
     *
     * @param mixed $data 登録するデータ
     * @param string $key B2Data の添字
     * @return void
     */
    public function registerB2Data($data, $key = null)
    {
        $b2Data = $this->getB2Data();

        if (is_null($key)) {
            $b2Data = $data;
        } else {
            $b2Data[$key] = $data;
        }

        // プラグインコードに一致するデータを plg_yamato_plugin から取得
        /** @var YamatoPlugin $YamatoPlugin */
        $YamatoPlugin = $this->app['yamato_payment.repository.yamato_plugin']
            ->findOneBy(array(
                'code' => $this->getPluginCode())
            );

        // データの有無を判定
        if (!is_null($YamatoPlugin)) {
            // サブデータを設定する
            $YamatoPlugin->setB2Data($b2Data);
            $this->app['orm.em']->persist($YamatoPlugin);
            $this->app['orm.em']->flush();
        }

        $this->b2Data = $b2Data;
    }

    /**
     * B2用 UserSetting 情報の登録
     *
     * @param array $data 登録するデータ
     */
    public function registerB2UserSettings($data)
    {
        $this->registerB2Data($data, 'user_settings');
    }

    /**
     * 後払い決済手数料を取得する
     *
     * @return integer
     */
    public function getDeferredCharge()
    {
        $ret = 0;

        $div = $this->getUserSettings('ycf_send_div');
        if ($div == '0') {
            // 請求書同梱しない
            $ret = $this->const['DEFERRED_CHARGE'];
        } elseif ($div == '1') {
            // 請求書同梱する
            $ret = $this->const['DEFERRED_SEND_DELIV_CHARGE'];
        }

        return $ret;
    }

    /**
     * ログを出力.
     *
     * @param string $msg
     * @param string $level
     */
    public function printLog($msg, $level = 'DEBUG')
    {
        if (!empty($msg) && !is_array($msg)) {
            // 改行処理 '(' と '[' の前に改行
            if (strpos($msg, '(')) {
                $msg = str_replace('(', "\n" . '(', $msg);
            }
            if (strpos($msg, '[')) {
                $msg = str_replace('[', "\n" . '[', $msg);
            }
        }

        $user = $this->app['user'];
        $customer_id = (!is_null($user) && $user instanceof Customer) ? $user->getId() : '0';

        // パスワード等をマスクする
        if (is_array($msg) || is_object($msg)) {
            $msg = CommonUtil::setMaskData($msg);
            $msg = print_r($msg, true);
        }

        if (empty($_SERVER['REMOTE_ADDR'])) {
            $_SERVER['REMOTE_ADDR'] = '127.0.0.1';
        }

        $msg = "user=$customer_id: " . $msg;
        $msg = $level . ' ' . $msg;
        $msg .= ' from {' . $_SERVER['REMOTE_ADDR'] . '}' . "\n";

        /** @var Logger $log */
        $log = $this->app['yamato_payment.log'];

        switch ($level) {
            case 'ERROR':
                $log->addError($msg);
                break;
            case 'DEBUG':
                $log->addDebug($msg);
                break;
            default:
                $log->addDebug($msg);
                break;
        }
    }

    /**
     * エラーログを出力
     *
     * @param string $message
     */
    public function printErrorLog($message)
    {
        self::printLog($message, 'ERROR');
    }

    /**
     * デバッグログを出力
     *
     * @param string $message
     */
    public function printDebugLog($message)
    {
        self::printLog($message, 'DEBUG');
    }

    /**
     * 前ページの正当性を記録
     */
    public function setRegistSuccess()
    {
        $this->app['session']->set('yamato_payment.pre_regist_success', true);
    }


    /**
     * ページのパスをセッションに保持する
     *
     * @param string $pathInfo ページのパス
     */
    public function savePagePath($pathInfo)
    {
        $this->app['session']->set('yamato_payment.pre_page', '');

        if ($this->app['session']->has('yamato_payment.now_page')) {
            $this->app['session']->set('yamato_payment.pre_page', $this->app['session']->get('yamato_payment.now_page'));
        }

        $this->app['session']->set('yamato_payment.now_page', $pathInfo);
    }

    /**
     * 前ページが正当であるかの判定
     *
     * @return bool
     */
    public function isPrePage()
    {
        $now_page = $this->app['session']->get('yamato_payment.now_page');
        $pre_page = $this->app['session']->get('yamato_payment.pre_page');
        $pre_regist_success = $this->app['session']->get('yamato_payment.pre_regist_success');

        if ($pre_page != '' && $now_page != '') {
            if ($pre_regist_success === true || $pre_page == $now_page) {
                $this->app['session']->remove('yamato_payment.pre_regist_success');
                return true;
            }
        }
        return false;
    }

    public function getDeliveryServiceCode($orderId) {
        if(is_null($orderId)) {
            return null;
        }
        $Order = $this->app['eccube.repository.order']->find($orderId);
        if($Order == false) {
            return null;
        }

        $subData = $this->getB2UserSettings();

        $Shippings = $Order->getShippings();
        foreach ($Shippings as $Shipping) {
            $delivery_id = $Shipping->getDelivery()->getId();
            if(is_null($delivery_id) == false) {
                break;
            }
        }

        return $subData["delivery_service_code"][$delivery_id];

    }
}
