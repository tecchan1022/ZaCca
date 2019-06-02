<?php
/*
* This file is part of EC-CUBE
*
* Copyright(c) 2000-2015 LOCKON CO.,LTD. All Rights Reserved.
* http://www.lockon.co.jp/
*
* For the full copyright and license information, please view the LICENSE
* file that was distributed with this source code.
*/

namespace Plugin\GoogleAnalyticsSimpleSetup;

use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Yaml\Yaml;

class Event
{
    private $app;
    private $global_name = 'complete_order_id';

    public function __construct($app)
    {
        $this->app = $app;
    }

    public function onControllerShippingCompleteBefore()
    {
        // 受注IDを取得
        $orderId = $this->app['session']->get('eccube.front.shopping.order.id');
        /* var $Twig \Twig_Environment */
        $Twig = $this->app['twig'];
        $Twig->addGlobal($this->global_name, $orderId);
    }

    public function onRenderShippingCompleteBefore(FilterResponseEvent $event)
    {
        /* @var $Twig \Twig_Environment */
        $Twig = $this->app['twig'];
        $arrGlobal = $Twig->getGlobals();
        $order_id = $arrGlobal[$this->global_name];

        $app = $this->app;

        $pConfig = Yaml::parse(__DIR__ . '/config.yml');

        /* @var $Plugin \Eccube\Entity\Plugin */
        $Plugin = $this->app['eccube.repository.plugin']->findOneBy(array('code' => $pConfig['code']));

        $enable_flg = true;
        if (is_null($Plugin)) {
            $enable_flg = false;
        }else{
            $enable_flg = $Plugin->getEnable() == 1;
        }

        // google analytics用コード
        //受注
        $Order = null;
        $arrOrder = array();
        $arrOrderDetail = array();

        if ($enable_flg){
            /* @var $Order \Eccube\Entity\Order */
            if ($order_id > 0){
                $Order = $app['eccube.repository.order']->find($order_id);
            }

            if (!empty($Order)){
                $arrOrder['total']        = $Order->getPaymentTotal();
                $arrOrder['tax']          = $Order->getTax();
                $arrOrder['deliv_fee']    = $Order->getDeliveryFeeTotal();
                $arrOrder['order_pref']   = $Order->getPref()->getName();
                $arrOrder['order_addr01'] = $Order->getAddr01();

                /* @var $OrderDetail \Eccube\Entity\OrderDetail */
                $OrderDetails = $Order->getOrderDetails();
                foreach ($OrderDetails as $OrderDetail) {
                    $arrOrderDetail[$OrderDetail->getId()]['product_code']       = $OrderDetail->getProductCode();
                    $arrOrderDetail[$OrderDetail->getId()]['product_name']       = $OrderDetail->getProductName();
                    $arrOrderDetail[$OrderDetail->getId()]['price']              = $OrderDetail->getPrice();
                    $arrOrderDetail[$OrderDetail->getId()]['quantity']           = $OrderDetail->getQuantity();
                    $classcategory_name = $OrderDetail->getClassCategoryName1();
                    if ($OrderDetail->getClassCategoryName2() != ''){
                        $classcategory_name .= ' ' . $OrderDetail->getClassCategoryName2();
                    }
                    $arrOrderDetail[$OrderDetail->getId()]['classcategory_name'] = $classcategory_name;
                }
            }
        }

        if (empty($arrOrder) || empty($arrOrderDetail)){
            $order_id = '';
        }

        $twig = $app->renderView(
            'GoogleAnalyticsSimpleSetup/Resource/template/shopping_complete.twig',
            array(
                'order_id' => $order_id,
                'arrOrder' => $arrOrder,
                'arrOrderDetail' => $arrOrderDetail,
            )
        );

        $response = $event->getResponse();

        $html = $response->getContent();
        $crawler = new Crawler($html);

        $oldElement = $crawler->filter('body')->first();

        if ($oldElement->count() > 0) {
            $oldHtml = $oldElement->html();
            $newHtml = $oldHtml.$twig;

            $change_html = str_replace($oldHtml, $newHtml, $crawler->html());

            $response->setContent($change_html);
        }

        $event->setResponse($response);
    }

}
