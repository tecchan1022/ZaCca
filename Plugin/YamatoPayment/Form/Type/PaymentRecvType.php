<?php
/*
 * Copyright(c)2016, Yamato Financial Co.,Ltd. All rights reserved.
 * Copyright(c)2016, Yamato Credit finance Co.,Ltd. All rights reserved.
 */


namespace Plugin\YamatoPayment\Form\Type;

use Eccube\Application;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormEvents;

class PaymentRecvType extends AbstractType
{
    /**
     * @var Application
     */
    protected $app;
    /**
     * @var array
     */
    protected $const;
    /**
     * @var array
     */
    protected $userSettings;

    /**
     * コンストラクタ
     *
     * @param Application $app
     */
    public function __construct(Application $app)
    {
        $this->app = $app;
        $this->const = $app['config']['YamatoPayment']['const'];
        $pluginUtil = $app['yamato_payment.util.plugin'];
        $this->userSettings = $pluginUtil->getUserSettings();
    }

    /**
     * 決済結果受信パラメータ構築
     *
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $app = $this->app;
        $userSettings = $this->userSettings;

        $builder
            ->add('trader_code', 'text', array(
                'constraints' => array(
                    new Assert\NotBlank(array('message' => '※ trader_codeが入力されていません。')),
                    new Assert\Length(array(
                        'max' => 20,
                        'maxMessage' => "※ trader_codeは20字以下で入力してください。"
                    )),
                    new Assert\Regex(array(
                        'pattern' => "/^[[:graph:][:space:]]+$/i",
                        'match' => true,
                        'message' => '※ trader_codeは英数記号で入力してください。'
                    )),
                ),
            ))
            ->add('order_no', 'text', array(
                'constraints' => array(
                    new Assert\NotBlank(array('message' => '※ order_noが入力されていません。')),
                    new Assert\Length(array(
                        'max' => $this->app['config']['int_len'],
                        'maxMessage' => "※ order_noは23字以下で入力してください。"
                    )),
                    new Assert\Regex(array(
                        'pattern' => "/^[0-9]+$/",
                        'match' => true,
                        'message' => '※ order_noは数字で入力してください。'
                    )),
                ),
            ))
            ->add('function_div', 'text')
            ->add('settle_price', 'text', array(
                'constraints' => array(
                    new Assert\Length(array(
                        'max' => 7,
                        'maxMessage' => "※ settle_priceは7字以下で入力してください。"
                    )),
                    new Assert\Regex(array(
                        'pattern' => "/^[0-9]+$/",
                        'match' => true,
                        'message' => '※ settle_priceは数字で入力してください。'
                    )),
                ),
            ))
            ->add('settle_date', 'text', array(
                'constraints' => array(
                    new Assert\Length(array(
                        'max' => 14,
                        'maxMessage' => "※ settle_dateは14字以下で入力してください。"
                    )),
                    new Assert\Regex(array(
                        'pattern' => "/^[0-9]+$/",
                        'match' => true,
                        'message' => '※ settle_dateは数字で入力してください。'
                    )),
                ),
            ))
            ->add('settle_result', 'text', array(
                'constraints' => array(
                    new Assert\Length(array(
                        'max' => 1,
                        'maxMessage' => "※ settle_resultは1字以下で入力してください。"
                    )),
                    new Assert\Regex(array(
                        'pattern' => "/^[0-9]+$/",
                        'match' => true,
                        'message' => '※ settle_resultは数字で入力してください。'
                    )),
                ),
            ))
            ->add('settle_detail', 'text', array(
                'constraints' => array(
                    new Assert\Length(array(
                        'max' => 2,
                        'maxMessage' => "※ settle_detailは2字以下で入力してください。"
                    )),
                    new Assert\Regex(array(
                        'pattern' => "/^[0-9]+$/",
                        'match' => true,
                        'message' => '※ settle_detailは数字で入力してください。'
                    )),
                ),
            ))
            ->add('settle_method', 'text', array(
                'constraints' => array(
                    new Assert\Length(array(
                        'max' => 2,
                        'maxMessage' => "※ settle_methodは2字以下で入力してください。"
                    )),
                    new Assert\Regex(array(
                        'pattern' => "/^[0-9]+$/",
                        'match' => true,
                        'message' => '※ settle_methodは数字で入力してください。'
                    )),
                ),
            ))
            ->addEventListener(FormEvents::POST_SUBMIT, function (FormEvent $event) use ($app, $userSettings) {
                $form = $event->getForm();

                //ショップIDチェック
                if ($form['trader_code']->getData() != $userSettings['shop_id']) {
                    $form['trader_code']->addError(
                        new FormError('※ shop_idが一致しません。')
                    );
                }
                if (!$form->isValid()) {
                    return;
                }

                //注文データ取得
                $order_id = $form['order_no']->getData();
                $OrderExtention = $app['yamato_payment.util.payment']->getOrderPayData($order_id);
                //該当注文存在チェック
                if ($OrderExtention === false) {
                    $form['order_no']->addError(
                        new FormError('※ order_no ' . $order_id . ' が存在しません。')
                    );
                    return;
                }

                //決済データ取得
                $Order = $OrderExtention->getOrder();
                $YamatoOrderPayment = $OrderExtention->getYamatoOrderPayment();
                $memo05 = $YamatoOrderPayment->getMemo05();

                //チェック処理
                if (!isset($memo05['function_div'])) {
                    $form['function_div']->addError(
                        new FormError('※ ECサイトの注文は決済をご利用になっておりません。')
                    );
                    return;
                }

                //支払方法チェック
                if ($app['yamato_payment.util.payment']->isCheckPaymentMethod(
                    $form['settle_method']->getData(),
                    $YamatoOrderPayment->getMemo03()
                )
                ) {
                    $form['settle_method']->addError(
                        new FormError('※ 支払方法が一致していません。(' . $YamatoOrderPayment->getMemo03() . ')')
                    );
                    return;
                }

                //コンビニ種類チェック
                if (isset($memo05['cvs'])
                    && $memo05['cvs'] != $form['settle_method']->getData()
                ) {
                    $form['settle_method']->addError(
                        new FormError('※ コンビニエンスストアの種類が異なります。(' . $memo05['cvs'] . ')')
                    );
                    return;
                }

                //決済金額チェック
                if ($form['settle_price']->getData() != $Order->getPaymentTotal()) {
                    $form['settle_price']->addError(
                        new FormError('※ 決済金額がECサイトのお支払い合計金額と異なります。(' . $Order->getPaymentTotal() . ')')
                    );
                    return;
                }
            });
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(array(
            'csrf_protection' => false,
            'allow_extra_fields' => true
        ));
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return '';
    }

}
