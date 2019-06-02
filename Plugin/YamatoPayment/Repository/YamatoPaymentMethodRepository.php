<?php
/*
 * Copyright(c)2016, Yamato Financial Co.,Ltd. All rights reserved.
 * Copyright(c)2016, Yamato Credit finance Co.,Ltd. All rights reserved.
 */


namespace Plugin\YamatoPayment\Repository;

use Eccube\Application;
use Eccube\Common\Constant;
use Eccube\Entity\Payment;
use Eccube\Doctrine\Filter\SoftDeleteFilter;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Query;
use Plugin\YamatoPayment\Entity\YamatoPaymentMethod;

class YamatoPaymentMethodRepository extends EntityRepository
{
    /**
     * @var Application
     */
    private $app;

    /**
     * @var array 設定情報
     */
    private $const;

    /**
     * 設定情報の設定
     *
     * @param Application $app 設定する設定情報
     */
    public function setApplication($app)
    {
        $this->app = $app;
        $this->const = $app['config']['YamatoPayment']['const'];
    }

    /**
     * プラグイン設定に基づいて決済方法を有効にする
     *
     * @return void
     */
    public function enableYamatoPaymentByConfig()
    {
        // 一旦、全ての決済方法を無効にする
        $this->disableYamatoPaymentAll();

        // プラグイン設定から有効な決済種別IDを取得
        $enablePaymentTypes = $this->app['yamato_payment.util.plugin']->getUserSettings('enable_payment_type');
        foreach ((array)$enablePaymentTypes as $payment_type) {
            // 決済種別IDに紐づく支払方法を有効にする
            $this->enablePaymentByPaymentType($payment_type);
        }
    }

    /**
     * 全ての決済方法を無効にする
     *
     * @return void
     */
    public function disableYamatoPaymentAll()
    {
        $YamatoPaymentMethods = $this->findAll();
        foreach ($YamatoPaymentMethods as $YamatoPaymentMethod) {
            /** @var YamatoPaymentMethod $YamatoPaymentMethod */
            /** @var Payment $Payment */
            $Payment = $this->app['eccube.repository.payment']->find($YamatoPaymentMethod->getId());
            if (!is_null($Payment)) {
                // 削除フラグを立てる
                $Payment
                    ->setDelFlg(Constant::ENABLED)
                    ->setRank(0);
            }
        }
        $this->app['orm.em']->flush();

        // 並び順更新
        $this->updateRankOfPayments();
    }

    /**
     * 指定した決済種別idに紐づく支払方法を有効にする
     *
     * @param integer $payment_type 決済種別ID
     * @return void
     */
    public function enablePaymentByPaymentType($payment_type)
    {
        /** @var YamatoPaymentMethod $YamatoPaymentMethod */
        $YamatoPaymentMethod = $this->findOneBy(array('memo03' => $payment_type));
        if (is_null($YamatoPaymentMethod)) {
            return;
        }

        /** @var SoftDeleteFilter $softDeleteFilter */
        $softDeleteFilter = $this->app['orm.em']->getFilters()->getFilter('soft_delete');
        $originExcludes = $softDeleteFilter->getExcludes();

        $softDeleteFilter->setExcludes(array(
            'Eccube\Entity\Payment',
        ));

        /** @var Payment $Payment */
        $Payment = $this->app['eccube.repository.payment']->find($YamatoPaymentMethod->getId());
        if (!is_null($Payment) && $Payment->getDelFlg() == Constant::ENABLED) {

            // 後払い決済の場合
            if ($payment_type == $this->const['YAMATO_PAYID_DEFERRED']) {
                if (is_null($Payment->getCharge())) {
                    // 手数料設定
                    $charge = $this->app['yamato_payment.util.plugin']->getDeferredCharge();
                    $Payment->setCharge($charge);
                }
            }
            // Rankの最大値を取得する
            $PaymentRankMax = $this->app['eccube.repository.payment']->findOneBy(
                array('del_flg' => Constant::DISABLED),
                array('rank' => 'DESC')
            );

            $rank = 1;
            if ($PaymentRankMax) {
                $rank = $PaymentRankMax->getRank() + 1;
            }

            // 削除フラグを折る
            $Payment
                ->setRank($rank)
                ->setDelFlg(Constant::DISABLED);
            $this->app['orm.em']->flush($Payment);
        }

        $softDeleteFilter->setExcludes($originExcludes);
    }

    /**
     * 支払い方法マスタの並び順を一括更新
     *
     * @return void
     */
    public function updateRankOfPayments()
    {
        $rank = 1;
        $Payments = $this->app['eccube.repository.payment']->findBy(array(), array('rank' => 'ASC'));
        foreach ($Payments as $Payment) {
            $Payment->setRank($rank);
            $rank ++;
        }
        $this->app['orm.em']->flush();
    }

    /**
     * ヤマト決済用の支払方法の取得
     * 
     * @return array
     */
    public function findMulPayPayments()
    {
        $qb = $this->createQueryBuilder('g');
        $qb
            ->select('g')
            ->innerJoin('\Eccube\Entity\Payment', 'p', 'WITH', 'g.id = p.id');

        $ret = $qb
            ->getQuery()
            ->getResult();

        return $ret;
    }

}
