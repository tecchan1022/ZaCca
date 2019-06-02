<?php
/*
 * Copyright(c)2016, Yamato Financial Co.,Ltd. All rights reserved.
 * Copyright(c)2016, Yamato Credit finance Co.,Ltd. All rights reserved.
 */


namespace Plugin\YamatoPayment\Repository;

use Eccube\Application;
use Eccube\Entity\Order;
use Eccube\Entity\OrderDetail;
use Plugin\YamatoPayment\Entity\YamatoProduct;
use Doctrine\ORM\EntityRepository;

class YamatoProductRepository extends EntityRepository
{
    /**
     * 受注商品に後払い不可商品を含むかの判定
     *
     * @param Order $Order
     * @return bool 後払い商品を含む場合、true
     */
    public function isNotDeferredFlg($Order)
    {
        foreach ($Order->getOrderDetails() as $OrderDetail) {
            /* @var OrderDetail $OrderDetail */
            $productId = $OrderDetail->getProduct()->getId();

            /** @var YamatoProduct $YamatoProduct */
            $YamatoProduct = $this->find($productId);

            if (!is_null($YamatoProduct)) {
                // 後払い不可商品の場合
                if ($YamatoProduct->getNotDeferredFlg()) {
                    return true;
                }
            }
        }
        return false;
    }
}
