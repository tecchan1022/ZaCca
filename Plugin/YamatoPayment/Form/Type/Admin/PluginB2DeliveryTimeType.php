<?php
/*
 * Copyright(c)2016, Yamato Financial Co.,Ltd. All rights reserved.
 * Copyright(c)2016, Yamato Credit finance Co.,Ltd. All rights reserved.
 */


namespace Plugin\YamatoPayment\Form\Type\Admin;

use Eccube\Application;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints as Assert;

class PluginB2DeliveryTimeType extends AbstractType
{
    /**
     * @var Application
     */
    private $app;

    /**
     * コンストラクタ
     *
     * @param Application $app
     */
    public function __construct(Application $app)
    {
        $this->app = $app;
    }

    /**
     * 設定画面の構築
     *
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        // タイムコードの一覧を取得する
        $listDeliveryTimeCode = $this->app['yamato_payment.util.payment']->getDelivTimeCode();
        // 廃止となるタイムコードの末尾に（廃止）を追記
        foreach ($listDeliveryTimeCode as &$value){
            if (in_array($value, $this->app['config']['YamatoPayment']['const']['DELETE_DELIV_TIMECODE'])) {
                $value .= '（廃止）';
            }
        }

        $builder
            ->add('delivery_time_id', 'hidden')
            ->add('delivery_time', 'hidden')
            ->add('b2_delivtime_code', 'choice', array(
                'choices' => $listDeliveryTimeCode,
                'expanded' => false,
            ));
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'yamato_b2_delivery_time';
    }
}
