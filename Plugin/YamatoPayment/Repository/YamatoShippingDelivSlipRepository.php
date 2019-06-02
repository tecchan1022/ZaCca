<?php
/*
 * Copyright(c)2016, Yamato Financial Co.,Ltd. All rights reserved.
 * Copyright(c)2016, Yamato Credit finance Co.,Ltd. All rights reserved.
 */


namespace Plugin\YamatoPayment\Repository;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Eccube\Application;
use Doctrine\ORM\EntityRepository;
use Eccube\Entity\Shipping;
use Plugin\YamatoPayment\Entity\YamatoShippingDelivSlip;

class YamatoShippingDelivSlipRepository extends EntityRepository
{
    /**
     * @var Application
     */
    private $app;

    /**
     * @var array ヤマト決済プラグイン定数定義
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
     * 受注データに紐づく送り状番号がすべて登録されているか判定
     *
     * @param integer $orderId 受注番号
     * @return bool true:すべて登録済み、false:未登録あり
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function isSlippingOn($orderId)
    {
        // 他社配送設定
        $deliveryServiceCode = $this->app['yamato_payment.util.plugin']->getDeliveryServiceCode($orderId);
        if($deliveryServiceCode == '99') return true; // 他社配送の場合はチェックしない

        // order_id が一致する 送り状番号：指定なし のデータを取得する
        $qb = $this->getEntityManager()->createQueryBuilder();
        $qb
            ->select('o.id')
            ->from('\Eccube\Entity\Order', 'o')
            ->leftJoin('o.Shippings', 's')
            ->leftJoin('\Plugin\YamatoPayment\Entity\YamatoShippingDelivSlip', 'm', 'WITH', 's.id = m.id')
            ->where('o.id = :order_id AND m.deliv_slip_number IS NULL')
            ->setParameter('order_id', $orderId);

        $result = $qb->getQuery()->getResult();
        return (empty($result)) ? true : false;
    }

    /**
     * 受注データに紐づく全配送先の送信成功した送り状番号が登録されているかどうか判定
     *
     * @param integer $orderId 受注番号
     * @return bool true:すべて登録済み、false:未登録あり
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function isAllExistLastDelivSlip($orderId)
    {
        $qb = $this->getEntityManager()->createQueryBuilder();
        $qb
            ->select('o', 's', 'm.last_deliv_slip_number')
            ->from('\Eccube\Entity\Order', 'o')
            ->leftJoin('o.Shippings', 's')
            ->leftJoin('\Plugin\YamatoPayment\Entity\YamatoShippingDelivSlip', 'm', 'WITH', 's.id = m.id')
            ->where('o.id = :order_id AND m.last_deliv_slip_number IS NULL')
            ->setParameter('order_id', $orderId);

        $result = $qb->getQuery()->getResult();
        return (empty($result)) ? true : false;
    }

    /**
     * 共通送り状番号での注文同梱上限チェック
     *
     * true : 該当注文の中に注文同梱上限をオーバーしている注文が存在する
     * false: 注文同梱上限に達していない
     *
     * @param integer $orderId 受注番号
     * @return boolean
     */
    public function isUpperLimitedShippedNum($orderId)
    {
        $qb = $this->getEntityManager()->createQueryBuilder();

        $qb
            ->select('m.deliv_slip_number')
            ->from('\Eccube\Entity\Order', 'o')
            ->leftJoin('\Plugin\YamatoPayment\Entity\YamatoShippingDelivSlip', 'm', 'WITH', 'o.id = m.order_id')
            ->where('o.id = :order_id')
            ->setParameter('order_id', $orderId);
        $results = $qb->getQuery()->getResult();

        $result = '';
        foreach( $results as $result) {
            $deliv_slip_number = current($result);

            $qb2 = $this->getEntityManager()->createQueryBuilder();
            $qb2
                ->select('COUNT(o)')
                ->from('\Eccube\Entity\Order', 'o')
                ->leftJoin('o.Shippings', 's')
                ->leftJoin('\Plugin\YamatoPayment\Entity\YamatoShippingDelivSlip', 'm', 'WITH', 's.id = m.id')
                ->where('m.deliv_slip_number = :deliv_slip_number')
                ->setParameter('deliv_slip_number', $deliv_slip_number)
                ->having('COUNT(o) > :shipped_max')
                ->setParameter('shipped_max', $this->const['YAMATO_SHIPPED_MAX']);

            $result = $qb2->getQuery()->getOneOrNullResult();
            if(!is_null($result)){
                break;
            }
        }
        return ($result) ? true : false;
    }

    /**
     * 共通送り状番号で注文同梱時の発送同一チェック
     *
     * (1)対象注文番号の送り状番号取得（複数可）
     * (2)送り状番号を対象とし配送先情報を取得（各送り状番号の配送先情報を配列で保持）
     * (3)各送り状番号で配送先情報が異なるかチェック
     *    比較カラムは以下
     *    1.shipping_name01
     *    2.shipping_name02
     *    3.shipping_tel01
     *    4.shipping_tel02
     *    5.shipping_tel03
     *    6.shipping_addr01
     *    7.shipping_addr02
     *
     * @param integer $orderId 受注番号
     * @return boolean
     */
    public function isExistUnequalShipping($orderId)
    {
        $qb = $this->getEntityManager()->createQueryBuilder();

        $qb
            ->select('m.deliv_slip_number')
            ->from('\Eccube\Entity\Order', 'o')
            ->leftJoin('\Plugin\YamatoPayment\Entity\YamatoShippingDelivSlip', 'm', 'WITH', 'o.id = m.order_id')
            ->where('o.id = :order_id')
            ->setParameter('order_id', $orderId);
        $results = $qb->getQuery()->getResult();

        $result = '';
        foreach( $results as $result) {
            $deliv_slip_number = current($result);

            $qb2 = $this->getEntityManager()->createQueryBuilder();
            $qb2
                ->select('COUNT(o)')
                ->from('\Eccube\Entity\Order', 'o')
                ->leftJoin('o.Shippings', 's')
                ->leftJoin('\Plugin\YamatoPayment\Entity\YamatoShippingDelivSlip', 'm', 'WITH', 's.id = m.id')
                ->where('m.deliv_slip_number = :deliv_slip_number')
                ->setParameter('deliv_slip_number', $deliv_slip_number)
                ->groupBy(
                    's.name01'
                    , 's.name02'
                    , 's.tel01'
                    , 's.tel02'
                    , 's.tel03'
                    , 's.addr01'
                    , 's.addr02'
                    , 'm.deliv_slip_number');

            $result = $qb2->getQuery()->getResult();
        }
        return (count($result) > 1) ? true : false;
    }

    /**
     * 配送情報に紐づく配送伝票情報を取得
     *
     * @param Collection $Shippings 配送情報
     * @return Collection
     */
    public function getDelivSlipByShippings($Shippings)
    {
        $ret = new ArrayCollection();

        foreach ($Shippings as $Shipping) {
            /** @var Shipping $Shipping */
            $DelivSlip = $this->find($Shipping->getId());
            if (is_null($DelivSlip)) {
                $DelivSlip = new YamatoShippingDelivSlip();
                $DelivSlip->setId($Shipping->getId());
                $DelivSlip->setOrderId($Shipping->getOrder()->getId());
            }
            $ret->add($DelivSlip);
        }
        return $ret;
    }

}
