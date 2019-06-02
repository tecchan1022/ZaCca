<?php
/*
 * Copyright(c)2016, Yamato Financial Co.,Ltd. All rights reserved.
 * Copyright(c)2016, Yamato Credit finance Co.,Ltd. All rights reserved.
 */


namespace Plugin\YamatoPayment\Form\Type\Admin;

use Eccube\Application;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Validator\Constraints as Assert;

class PluginB2ConfigType extends AbstractType
{
    /** @var Application */
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
        $self = $this;

        $paymentUtil = $this->app['yamato_payment.util.payment'];

        $builder
            ->add('claim_customer_code', 'text', array(
                'label' => 'ご請求先顧客コード',
                'attr' => array(
                    'maxlength' => '12',
                ),
                'constraints' => array(
                    new Assert\NotBlank(array('message' => '※ ご請求先顧客コードが入力されていません。')),
                    new Assert\Regex(array(
                        'pattern' => "/^[0-9]+$/",
                        'match' => true,
                        'message' => '※ ご請求先顧客コードは数字で入力してください。'
                    )),
                    new Assert\Length(array(
                        'max' => 12,
                        'maxMessage' => "※ ご請求先顧客コードは12字以下で入力してください。"
                    )),
                ),
            ))
            ->add('claim_type_code', 'text', array(
                'label' => 'ご請求先分類コード',
                'attr' => array(
                    'maxlength' => '3',
                ),
                'constraints' => array(
                    new Assert\Regex(array(
                        'pattern' => "/^[0-9]+$/",
                        'match' => true,
                        'message' => '※ ご請求先分類コードは数字で入力してください。'
                    )),
                    new Assert\Length(array(
                        'max' => 3,
                        'maxMessage' => "※ ご請求先分類コードは3字以下で入力してください。"
                    )),
                ),
            ))
            ->add('transportation_no', 'text', array(
                'label' => '運賃管理番号',
                'attr' => array(
                    'maxlength' => '2',
                ),
                'constraints' => array(
                    new Assert\NotBlank(array('message' => '※ 運賃管理番号が入力されていません。')),
                    new Assert\Regex(array(
                        'pattern' => "/^[0-9]+$/",
                        'match' => true,
                        'message' => '※ 運賃管理番号は数字で入力してください。'
                    )),
                    new Assert\Length(array(
                        'max' => 2,
                        'maxMessage' => "※ 運賃管理番号は2字以下で入力してください。"
                    )),
                ),
            ))
            ->add('header_output', 'choice', array(
                'label' => '一行目タイトル行',
                'choices' => $paymentUtil->getOutput(),
                'expanded' => true,
                'constraints' => array(
                    new Assert\NotBlank(array('message' => '※ 一行目タイトル行が選択されていません。')),
                ),
            ));

        // B2送り状種別設定
        $builder
            ->add('b2_payment_type', 'collection', array(
                'type' => new PluginB2PaymentType($this->app),
                'allow_add' => true,
                'allow_delete' => true,
                'prototype' => true,
            ));

        // B2クール便区分設定
        // B2配送時間コード設定
        // B2配送サービスコード設定
        $builder
            ->add('b2_delivery_type', 'collection', array(
                'type' => new PluginB2DeliveryType($this->app),
                'allow_add' => true,
                'allow_delete' => true,
                'prototype' => true,
            ));

        $builder
            ->add('tel_hyphenation', 'choice', array(
                'label' => '電話番号',
                'choices' => $paymentUtil->getHyphen(),
                'expanded' => true,
            ))
            ->add('zip_hyphenation', 'choice', array(
                'label' => '郵便番号',
                'choices' => $paymentUtil->getHyphen(),
                'expanded' => true,
            ))
            ->add('service_deliv_mail_enable', 'choice', array(
                'label' => 'お届け予定eメール ',
                'choices' => $paymentUtil->getUtilizationFlg(),
                'expanded' => true,
            ))
            ->add('service_deliv_mail_message', 'textarea', array(
                'label' => 'お届け予定eメールメッセージ',
                'attr' => array(
                    'maxlength' => '74',
                ),
                'constraints' => array(
                    new Assert\Length(array(
                        'max' => 74,
                        'maxMessage' => "※ お届け予定eメールメッセージは74字以下で入力してください。"
                    )),
                ),
            ))
            ->add('service_complete_mail_enable', 'choice', array(
                'label' => 'お届け完了eメール',
                'choices' => $paymentUtil->getUtilizationFlg(),
                'expanded' => true,
            ))
            ->add('service_complete_mail_message', 'textarea', array(
                'label' => 'お届け予定eメールメッセージ',
                'attr' => array(
                    'maxlength' => '159',
                ),
                'constraints' => array(
                    new Assert\Length(array(
                        'max' => 159,
                        'maxMessage' => "※ お届け予定eメールメッセージは159字以下で入力してください。"
                    )),
                ),
            ))
            ->add('output_order_type', 'choice', array(
                'label' => 'ご依頼主出力',
                'choices' => $paymentUtil->getRequestOutput(),
                'expanded' => true,
            ))
            ->add('posting_plan_mail_enable', 'choice', array(
                'label' => '投函予定メール',
                'choices' => $paymentUtil->getUtilizationFlg(),
                'expanded' => true,
            ))
            ->add('posting_plan_mail_message', 'textarea', array(
                'label' => '投函予定メールメッセージ',
                'attr' => array(
                    'maxlength' => '74',
                ),
                'constraints' => array(
                    new Assert\Length(array(
                        'max' => 74,
                        'maxMessage' => "※ 投函予定メールメッセージは74字以下で入力してください。"
                    )),
                ),
            ))
            ->add('posting_complete_deliv_mail_enable', 'choice', array(
                'label' => '投函完了メール(注文者宛) ',
                'choices' => $paymentUtil->getUtilizationFlg(),
                'expanded' => true,
            ))
            ->add('posting_complete_deliv_mail_message', 'textarea', array(
                'label' => '投函完了メール(注文者宛)メッセージ',
                'attr' => array(
                    'maxlength' => '159',
                ),
                'constraints' => array(
                    new Assert\Length(array(
                        'max' => 159,
                        'maxMessage' => "※ 投函完了メール(注文者宛)メッセージは159字以下で入力してください。"
                    )),
                ),
            ))
            ->add('use_b2_format', 'choice', array(
                'label' => '取込フォーマット',
                'choices' => $paymentUtil->getB2ImportFormat(),
                'expanded' => true,
            ))
            ->add('shpping_info_regist', 'choice', array(
                'label' => '取込時出荷情報登録',
                'choices' => $paymentUtil->getUtilizationFlg(),
                'expanded' => true,
            ))
            ->add('mode', 'hidden')
            ->addEventListener(FormEvents::PRE_SET_DATA, function (FormEvent $event) use ($self) {
                $data = $event->getData();

                // 初期値設定
                $self->setDefaultData($data);
                $event->setData($data);
            })
            ->addEventListener(FormEvents::POST_SUBMIT, function (FormEvent $event) {
                $form = $event->getForm();

                // お届け予定eメールを利用する場合、お届け予定eメールメッセージは必須とする
                if ($form['service_deliv_mail_enable']->getData() == '1'
                    && is_null($form['service_deliv_mail_message']->getData())
                ) {
                    $form['service_deliv_mail_message']->addError(
                        new FormError('※ 「お届け予定eメール」を利用する場合は必須です。')
                    );
                }

                // お届け完了eメールを利用する場合、お届け完了eメールメッセージは必須とする
                if ($form['service_complete_mail_enable']->getData() == '1'
                    && is_null($form['service_complete_mail_message']->getData())
                ) {
                    $form['service_complete_mail_message']->addError(
                        new FormError('※ 「お届け完了eメール」を利用する場合は必須です。')
                    );
                }

                // 投函予定メールを利用する場合、投函予定メールメッセージは必須とする
                if ($form['posting_plan_mail_enable']->getData() == '1'
                    && is_null($form['posting_plan_mail_message']->getData())
                ) {
                    $form['posting_plan_mail_message']->addError(
                        new FormError('※ 「投函予定メール」を利用する場合は必須です。')
                    );
                }

                // 投函完了メール(注文者宛)を利用する場合、投函完了メール(注文者宛)メッセージは必須とする
                if ($form['posting_complete_deliv_mail_enable']->getData() == '1'
                    && is_null($form['posting_complete_deliv_mail_message']->getData())
                ) {
                    $form['posting_complete_deliv_mail_message']->addError(
                        new FormError('※ 「投函完了メール」を利用する場合は必須です。')
                    );
                }
            });
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'yamato_b2_config';
    }

    /**
     * 初期値設定
     *
     * @param array $data
     */
    public function setDefaultData(&$data)
    {
        // 運賃管理番号
        if (!isset($data['transportation_no'])) {
            $data['transportation_no'] = '01';
        }

        // 一行目タイトル
        if (!isset($data['header_output'])) {
            $data['header_output'] = '1';
        }

        // 電話番号
        if (!isset($data['tel_hyphenation'])) {
            $data['tel_hyphenation'] = '1';
        }

        // 郵便番号
        if (!isset($data['zip_hyphenation'])) {
            $data['zip_hyphenation'] = '1';
        }

        // お届け予定eメール
        if (!isset($data['service_deliv_mail_enable'])) {
            $data['service_deliv_mail_enable'] = '0';
        }

        // お届け完了eメール
        if (!isset($data['service_complete_mail_enable'])) {
            $data['service_complete_mail_enable'] = '0';
        }

        // ご依頼主出力
        if (!isset($data['output_order_type'])) {
            $data['output_order_type'] = '0';
        }

        // 投函予定eメール
        if (!isset($data['posting_plan_mail_enable'])) {
            $data['posting_plan_mail_enable'] = '0';
        }

        // 投函完了eメール
        if (!isset($data['posting_complete_deliv_mail_enable'])) {
            $data['posting_complete_deliv_mail_enable'] = '0';
        }

        // 取込フォーマット
        if (!isset($data['use_b2_format'])) {
            $data['use_b2_format'] = '0';
        }

        // 取込時出荷情報登録API
        if (!isset($data['shpping_info_regist'])) {
            $data['shpping_info_regist'] = '0';
        }
    }
}
