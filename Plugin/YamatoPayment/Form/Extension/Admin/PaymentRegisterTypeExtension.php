<?php
/*
 * Copyright(c)2016, Yamato Financial Co.,Ltd. All rights reserved.
 * Copyright(c)2016, Yamato Credit finance Co.,Ltd. All rights reserved.
 */


namespace Plugin\YamatoPayment\Form\Extension\Admin;

use Eccube\Application;
use Eccube\Common\Constant;
use Eccube\Entity\Payment;
use Plugin\YamatoPayment\Entity\YamatoPaymentMethod;
use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * 支払方法タイプエクステンション
 */
class PaymentRegisterTypeExtension extends AbstractTypeExtension
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
        $const = $app['config']['YamatoPayment']['const'];

        // 支払種別一覧を取得する
        $listCreditPayMethod = $this->app['yamato_payment.util.payment']->getCreditPayMethod();

        // コンビニ名称一覧を取得する
        $listConveni = $this->app['yamato_payment.util.payment']->getConveni();


        $builder
            /*
             * クレジットカード決済決済
             */
            ->add('pay_way', 'choice', array(
                'choices' => $listCreditPayMethod,
                'required' => true,
                'expanded' => true,
                'multiple' => true,
                'mapped' => false,
            ))
            ->add('TdFlag', 'choice', array(
                'expanded' => true,
                'required' => false,
                'empty_value' => false,
                'choices' => array(
                    1 => '利用する',
                    0 => '利用しない',
                ),
                'mapped' => false,
            ))
            ->add('order_mail_title', 'text', array(
                'data' => 'お支払いについて',
                'required' => false,
                'attr' => array(
                    'class' => 'form-control',
                    'maxlength' => '50',
                ),
                'constraints' => array(
                    new Assert\Length(
                        array(
                            'max' => 50,
                            'maxMessage' => '※ 決済完了案内タイトルは50字以下で入力してください。'
                        )
                    ),
                ),
                'mapped' => false,
            ))
            ->add('order_mail_body', 'textarea', array(
                'required' => false,
                'attr' => array(
                    'class' => 'form-control',
                    'maxlength' => '1000',
                ),
                'constraints' => array(
                    new Assert\Length(
                        array(
                            'max' => 1000,
                            'maxMessage' => '※ 決済完了案内本文は1000字以下で入力してください。'
                        )
                    ),
                ),
                'mapped' => false,
            ))
            ->add('autoRegist', 'choice', array(
                    'expanded' => true,
                    'required' => false,
                    'empty_value' => false,
                    'choices' => array(
                            1 => '利用する',
                            0 => '利用しない',
                    ),
                    'mapped' => false,
            ))

            /*
             * コンビニ決済
             */
            ->add('conveni', 'choice', array(
                'label' => 'コンビニ選択',
                'attr' => array(
                    'class' => 'yamato_card_row',
                ),
                'expanded' => true,
                'multiple' => true,
                'choices' => $listConveni,
                'required' => true,
                'mapped' => false,
            ))
            ->add('order_mail_title_21', 'text', array(
                'data' => 'セブンイレブンでのお支払い',
                'required' => false,
                'attr' => array(
                    'maxlength' => '50',
                ),
                'constraints' => array(
                    new Assert\Length(
                        array(
                            'max' => 50,
                            'maxMessage' => '※ セブンイレブン決済完了案内タイトルは50字以下で入力してください。'
                        )
                    ),
                ),
                'mapped' => false,
            ))
            ->add('order_mail_body_21', 'textarea', array(
                'required' => false,
                'attr' => array(
                    'maxlength' => '1000',
                ),
                'constraints' => array(
                    new Assert\Length(
                        array(
                            'max' => 1000,
                            'maxMessage' => '※ セブンイレブン決済完了案内本文は1000字以下で入力してください。'
                        )
                    ),
                ),
                'mapped' => false,
            ))
            ->add('order_mail_title_22', 'text', array(
                'data' => 'ローソンでのお支払い',
                'required' => false,
                'attr' => array(
                    'maxlength' => '50',
                ),
                'constraints' => array(
                    new Assert\Length(
                        array(
                            'max' => 50,
                            'maxMessage' => '※ ローソン決済完了案内タイトルは50字以下で入力してください。'
                        )
                    ),
                ),
                'mapped' => false,
            ))
            ->add('order_mail_body_22', 'textarea', array(
                'required' => false,
                'attr' => array(
                    'maxlength' => '1000',
                ),
                'constraints' => array(
                    new Assert\Length(
                        array(
                            'max' => 1000,
                            'maxMessage' => '※ ローソン決済完了案内本文は1000字以下で入力してください。'
                        )
                    ),
                ),
                'mapped' => false,
            ))
            ->add('order_mail_title_23', 'text', array(
                'data' => 'ファミリーマートでのお支払い',
                'required' => false,
                'attr' => array(
                    'maxlength' => '50',
                ),
                'constraints' => array(
                    new Assert\Length(
                        array(
                            'max' => 50,
                            'maxMessage' => '※ ファミリーマート決済完了案内タイトルは50字以下で入力してください。'
                        )
                    ),
                ),
                'mapped' => false,
            ))
            ->add('order_mail_body_23', 'textarea', array(
                'required' => false,
                'attr' => array(
                    'maxlength' => '1000',
                ),
                'constraints' => array(
                    new Assert\Length(
                        array(
                            'max' => 1000,
                            'maxMessage' => '※ ファミリーマート決済完了案内本文は1000字以下で入力してください。'
                        )
                    ),
                ),
                'mapped' => false,
            ))
            ->add('order_mail_title_24', 'text', array(
                'data' => 'セイコーマートでのお支払い',
                'required' => false,
                'attr' => array(
                    'maxlength' => '50',
                ),
                'constraints' => array(
                    new Assert\Length(
                        array(
                            'max' => 50,
                            'maxMessage' => '※ セイコーマート決済完了案内タイトルは50字以下で入力してください。'
                        )
                    ),
                ),
                'mapped' => false,
            ))
            ->add('order_mail_body_24', 'textarea', array(
                'required' => false,
                'attr' => array(
                    'maxlength' => '1000',
                ),
                'constraints' => array(
                    new Assert\Length(
                        array(
                            'max' => 1000,
                            'maxMessage' => '※ セイコーマート決済完了案内本文は1000字以下で入力してください。'
                        )
                    ),
                ),
                'mapped' => false,
            ))
            ->add('order_mail_title_25', 'text', array(
                'data' => 'ミニストップでのお支払い',
                'required' => false,
                'attr' => array(
                    'maxlength' => '50',
                ),
                'constraints' => array(
                    new Assert\Length(
                        array(
                            'max' => 50,
                            'maxMessage' => '※ ミニストップ決済完了案内タイトルは50字以下で入力してください。'
                        )
                    ),
                ),
                'mapped' => false,
            ))
            ->add('order_mail_body_25', 'textarea', array(
                'required' => false,
                'attr' => array(
                    'maxlength' => '1000',
                ),
                'constraints' => array(
                    new Assert\Length(
                        array(
                            'max' => 1000,
                            'maxMessage' => '※ ミニストップ決済完了案内本文は1000字以下で入力してください。'
                        )
                    ),
                ),
                'mapped' => false,
            ))
            ->add('order_mail_title_26', 'text', array(
                'data' => 'サークルKサンクスでのお支払い',
                'required' => false,
                'attr' => array(
                    'maxlength' => '50',
                ),
                'constraints' => array(
                    new Assert\Length(
                        array(
                            'max' => 50,
                            'maxMessage' => '※ サークルKサンクス決済完了案内タイトルは50字以下で入力してください。'
                        )
                    ),
                ),
                'mapped' => false,
            ))
            ->add('order_mail_body_26', 'textarea', array(
                'required' => false,
                'attr' => array(
                    'maxlength' => '1000',
                ),
                'constraints' => array(
                    new Assert\Length(
                        array(
                            'max' => 1000,
                            'maxMessage' => '※ サークルKサンクス決済完了案内本文は1000字以下で入力してください。'
                        )
                    ),
                ),
                'mapped' => false,
            ))

            /*
             * クロネコ代金後払い決済
             */
            ->add('order_mail_title', 'text', array(
                'data' => 'お支払いについて',
                'required' => false,
                'attr' => array(
                    'maxlength' => '50',
                ),
                'constraints' => array(
                    new Assert\Length(
                        array(
                            'max' => 50,
                            'maxMessage' => '※ 決済完了案内タイトルは50字以下で入力してください。'
                        )
                    ),
                ),
                'mapped' => false,
            ))
            ->add('order_mail_body', 'textarea', array(
                'required' => false,
                'attr' => array(
                    'maxlength' => '1000',
                ),
                'constraints' => array(
                    new Assert\Length(
                        array(
                            'max' => 1000,
                            'maxMessage' => '※ 決済完了案内本文は1000字以下で入力してください。'
                        )
                    ),
                ),
                'mapped' => false,
            ))
            ->addEventListener(FormEvents::PRE_SUBMIT, function (FormEvent $event) {
                $form = $event->getForm();
                $data = $event->getData();

                /*
                 * EC-CUBEバグ対応
                 * 手数料フラグを無効にしている場合、
                 * フォームに手数料が無いため必須チェックエラーとなり更新できない。
                 * 対応として、手数料フラグが無効の場合は、手数料の入力チェックを外す。
                 */
                if (isset($data['charge_flg'])
                    && $data['charge_flg'] == Constant::DISABLED
                    && !isset($data['charge'])
                ) {
                    $name = $form->get('charge')->getName();
                    $type = $form->get('charge')->getConfig()->getType()->getName();
                    $options = $form->get('charge')->getConfig()->getOptions();
                    $options['constraints'] = array();
                    $form->add($name, $type, $options);
                }
            })
            ->addEventListener(FormEvents::POST_SUBMIT, function (FormEvent $event) use ($app, $const) {

                $form = $event->getForm();
                /** @var Payment $Payment */
                $Payment = $event->getData();

                $paymentId = $Payment->getId();
                // 新規登録時は抜ける
                if (is_null($paymentId)) {
                    return;
                }

                /** @var YamatoPaymentMethod $YamatoPaymentMethod */
                $YamatoPaymentMethod = $app['yamato_payment.repository.yamato_payment_method']->find($paymentId);
                if (is_null($YamatoPaymentMethod)) {
                    return;
                }

                switch ($YamatoPaymentMethod->getMemo03()) {
                    // クレジットカード決済
                    case $const['YAMATO_PAYID_CREDIT']:
                        if (count($form['pay_way']->getData()) == 0) {
                            $form['pay_way']->addError(
                                new FormError('※ 支払種別が選択されていません。')
                            );
                        }

                        if (strlen($form['TdFlag']->getData()) == 0) {
                            $form['TdFlag']->addError(
                                new FormError('※ 本人認証サービス(3Dセキュア)が選択されていません。')
                            );
                        }
                        break;

                    // コンビニ決済
                    case $const['YAMATO_PAYID_CVS']:
                        if (count($form['conveni']->getData()) == 0) {
                            $form['conveni']->addError(
                                new FormError('※ コンビニが選択されていません。')
                            );
                        }
                        break;

                    default:
                        break;
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
        return 'payment_register';
    }

}
