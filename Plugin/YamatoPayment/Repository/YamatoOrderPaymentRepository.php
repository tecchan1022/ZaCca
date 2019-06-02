<?php
/*
 * Copyright(c)2016, Yamato Financial Co.,Ltd. All rights reserved.
 * Copyright(c)2016, Yamato Credit finance Co.,Ltd. All rights reserved.
 */


namespace Plugin\YamatoPayment\Repository;

use Eccube\Application;
use Doctrine\ORM\EntityRepository;

class YamatoOrderPaymentRepository extends EntityRepository
{
    /**
     * @var Application
     */
    private $app;

    /**
     * @var array ヤマト決済プラグイン用定数定義
     */
    private $const;

    /**
     * 設定ファイルの情報を格納
     *
     * @param Application $app 設定する設定情報
     */
    public function setApplication($app)
    {
        $this->app = $app;
        $this->const = $app['config']['YamatoPayment']['const'];
    }

    /**
     * 検索条件を元に削除済みでない受注情報を取得
     *     引数により、フィルターをかける
     * 
     * @param array $searchData 検索条件
     * @return \Doctrine\ORM\QueryBuilder
     */
    public function getOrderBySearchDataForAdmin($searchData)
    {
        $sql =
            '(SELECT COUNT(m.last_deliv_slip_number) + COUNT(m.deliv_slip_url) '
            . 'FROM \Plugin\YamatoPayment\Entity\YamatoShippingDelivSlip AS m '
            . 'WHERE m.order_id = o.id '
            . 'AND ((m.last_deliv_slip_number IS NOT NULL AND m.last_deliv_slip_number <> \'\') '
            . 'OR (m.deliv_slip_url IS NOT NULL AND m.deliv_slip_url <> \'\')) '
            . ') AS deliv_slip_regist'
        ;
        
        $qb = $this->getEntityManager()->createQueryBuilder();
        $qb
            ->select('o', 'c', 'g.memo03', 'g.memo04', $sql)
            ->from('\Eccube\Entity\Order', 'o')
            ->leftJoin('o.OrderStatusColor', 'c')
            ->leftJoin('\Plugin\YamatoPayment\Entity\YamatoOrderPayment', 'g', 'WITH', 'o.id = g.id')
            ->where($qb->expr()->andx(
                $qb->expr()->neq('o.OrderStatus', $this->app['config']['order_pending']),
                $qb->expr()->neq('o.OrderStatus', $this->app['config']['order_processing']),
                $qb->expr()->eq('o.del_flg', 0)
            ));

        if (!empty($searchData['current_payment_status']) && $searchData['current_payment_status']) {
            $qb
                ->andWhere('o.OrderStatus = :status')
                ->setParameter('status', $searchData['current_payment_status']);
        }

        if (!empty($searchData['current_payment_type'])) {
            $qb
                ->leftJoin('o.Payment', 'p')
                ->andWhere($qb->expr()->in('p.id', ':payments'))
                ->setParameter('payments', $searchData['current_payment_type']);
        }

        if (!empty($searchData['order_date_start']) && $searchData['order_date_start']) {
            $date = $searchData['order_date_start']
                ->format('Y-m-d H:i:s');
            $qb
                ->andWhere('o.order_date >= :order_date_start')
                ->setParameter('order_date_start', $date);
        }
        if (!empty($searchData['order_date_end']) && $searchData['order_date_end']) {
            $date = clone $searchData['order_date_end'];
            $date = $date
                ->modify('+1 days')
                ->format('Y-m-d H:i:s');
            $qb
                ->andWhere('o.order_date < :order_date_end')
                ->setParameter('order_date_end', $date);
        }
        $qb->orderBy('o.update_date', 'DESC');
        
        return $qb
            ->getQuery()
            ->getResult();
    }

}
