<?php
/*
 * Copyright(c)2016, Yamato Financial Co.,Ltd. All rights reserved.
 * Copyright(c)2016, Yamato Credit finance Co.,Ltd. All rights reserved.
 */


namespace Plugin\YamatoPayment\Form\Extension\Admin;

use Eccube\Application;
use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * 商品タイプエクステンション
 */
class ProductTypeExtension extends AbstractTypeExtension
{
    private $app;

    /**
     * コンストラクタ
     *
     * @param Application $app
     */
    public function __construct($app)
    {
        $this->app = $app;
    }

    /**
     * 設定画面の構築
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $app = $this->app;

        $builder
            ->add('reserve_date', 'text', array(
                'label' => '予約商品出荷予定日',
                'attr' => array(
                    'maxlength' => '8',
                ),
                'constraints' => array(
                    new Assert\Length(array(
                        'max' => 8,
                        'maxMessage' => "※ 予約商品出荷予定日は8文字以下で入力してください。")),
                    new Assert\Regex(array(
                        'pattern' => "/^[0-9]+$/",
                        'match' => true,
                        'message' => '※ 予約商品出荷予定日は数字で入力してください。')),
                ),
                'required' => false,
                'mapped' => false,
            ))
            ->add('not_deferred_flg', 'checkbox', array(
                'label' => '後払い不可',
                'required' => false,
                'mapped' => false,
            ))
            ->addEventListener(FormEvents::POST_SET_DATA, function (FormEvent $event) use ($app) {
                $form = $event->getForm();
                $Product = $event->getData();

                if (!is_null($Product)) {
                    $YamatoProduct = (is_null($Product->getId()))? null: $app['yamato_payment.repository.yamato_product']->find($Product->getId());
                    if ($YamatoProduct) {
                        // 追加項目の初期値設定
                        $form->get('reserve_date')->setData($YamatoProduct->getReserveDate());
                        $form->get('not_deferred_flg')->setData($YamatoProduct->getNotDeferredFlg());
                    }
                }
            })
            ->addEventListener(FormEvents::POST_SUBMIT, function (FormEvent $event) use ($app) {
                $form = $event->getForm();
                $const = $app['config']['YamatoPayment']['const'];

                // 規格無し商品の場合
                if ($form->has('class')) {
                    $ProductClass = $form['class']->getData();
                    $reserveDate = $form['reserve_date']->getData();

                    $UserSetteings = $app['yamato_payment.util.plugin']->getUserSettings();

                    //【契約】
                    //(1)オプションサービス契約なし
                    //(2)予約販売利用なし
                    if ($ProductClass['ProductType']['id'] == $const['PRODUCT_TYPE_ID_RESERVE']
                        &&( $UserSetteings['use_option'] == 1
                        || $UserSetteings['advance_sale'] == 1 )
                    ) {
                        $app->addError('予約商品は登録できません。商品種別を別の設定にするか、プラグイン設定を見直してください。', 'admin');
                        $form['class']->addError(new FormError('※ 予約商品は登録できません。商品種別を別の設定にするか、プラグイン設定を見直してください。'));
                    }

                    // 予約商品で予約商品出荷予定日が未入力の場合、エラー
                    if ($ProductClass['ProductType']['id'] == $const['PRODUCT_TYPE_ID_RESERVE']
                        && empty($reserveDate)
                    ) {
                        $form['reserve_date']->addError(new FormError('※ 予約商品出荷予定日を入力してください。'));
                    }
                }
            });
    }

    /**
     * {@inheritdoc}
     */
    public function buildView(FormView $view, FormInterface $form, array $options)
    {
    }

    public function getExtendedType()
    {
        return 'admin_product';
    }

}
