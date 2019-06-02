<?php
/*
 * Copyright(c)2016, Yamato Financial Co.,Ltd. All rights reserved.
 * Copyright(c)2016, Yamato Credit finance Co.,Ltd. All rights reserved.
 */


namespace Plugin\YamatoPayment\Event;

use Doctrine\Common\Collections\ArrayCollection;
use Eccube\Application;
use Eccube\Entity\Master\ProductType;
use Eccube\Event\EventArgs;
use Eccube\Event\TemplateEvent;

class AdminOrderIndexEvent extends AbstractEvent
{
    /**
     * 受注管理画面：Renderイベント
     *
     * @param TemplateEvent $event
     */
    public function onAdminOrderIndexRender(TemplateEvent $event)
    {
        // イベントパラメータ取得
        $source = $event->getSource();

        /*
         * CSVダウンロード種別の追加
         */
        // Twig取得
        $snipet = file_get_contents(__DIR__ . '/../Resource/template/admin/Order/order_index_csv.twig');

        // HTML書き換え
        $search = '配送CSVダウンロード</a></li>';
        $replace = $search . $snipet;
        $source = str_replace($search, $replace, $source);

        /*
         * 検索条件の追加
         */
        // 差し込む内容を取得
        $twig = file_get_contents(__DIR__ . '/../Resource/template/admin/Order/order_index_search.twig');

        // 差し込む位置を取得
        $search = '<div id="search_box_main__clear" class="row">';
        // 差し込み
        $replace = $twig . $search;
        $source = str_replace($search, $replace, $source);

        $event->setSource($source);
    }

    /**
     * 受注管理画面：Searchイベント
     *
     * @param EventArgs $event
     */
    public function onAdminOrderIndexSearch(EventArgs $event)
    {
        // パラメータ取得
        $searchForm = $event->getArgument('form');
        $qb = $event->getArgument('qb');

        // 検索条件を取得
        /** @var ArrayCollection $ProductTypes */
        $ProductTypes = $searchForm->get('product_type')->getData();

        if (count($ProductTypes) > 0) {
            $product_types = array();
            foreach ($ProductTypes as $ProductType) {
                /** @var ProductType $ProductType */
                $product_types[] = $ProductType->getId();
            }
            $qb
                ->innerJoin('o.OrderDetails', 'add_od')
                ->innerJoin('add_od.ProductClass', 'add_pc')
                ->innerJoin('add_pc.ProductType', 'add_pt')
                ->andWhere($qb->expr()->in('add_pt.id', ':product_types'))
                ->setParameter('product_types', $product_types);
        }
    }

}
