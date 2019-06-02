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

class PluginConfigType extends AbstractType
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

        $app = $this->app;

        $paymentUtil = $this->app['yamato_payment.util.payment'];

        $builder
            ->add('exec_mode', 'choice', array(
                'label' => '動作モード',
                'choices' => $paymentUtil->getExecMode(),
                'expanded' => true,
                'constraints' => array(
                    new Assert\NotBlank(array('message' => '※ 動作モードが入力されていません。')),
                ),
            ))
            ->add('delivery_service_code', 'choice', array(
                    'label' => '他社配送設定',
                    'choices' => $paymentUtil->getDeliveryServiceCode(),
                    'expanded' => true,
                    'constraints' => array(
                            new Assert\NotBlank(array('message' => '※ 他社配送設定が入力されていません。')),
                    ),
            ))
            ->add('shop_id', 'text', array(
                'label' => 'クロネコｗｅｂコレクト加盟店コード',
                'attr' => array(
                    'maxlength' => '9',
                ),
                'constraints' => array(
                    new Assert\Regex(array(
                        'pattern' => "/^[0-9]+$/",
                        'match' => true,
                        'message' => '※ クロネコｗｅｂコレクト加盟店コードは数字で入力してください。'
                    )),
                    new Assert\Length(array(
                        'max' => 9,
                        'maxMessage' => "※ クロネコｗｅｂコレクト加盟店コードは9字以下で入力してください。"
                    )),
                ),
            ))
            ->add('ycf_str_code', 'text', array(
                'label' => 'クロネコ代金後払い加盟店コード',
                'attr' => array(
                    'maxlength' => '11',
                ),
                'constraints' => array(
                    new Assert\Regex(array(
                        'pattern' => "/^[0-9]+$/",
                        'match' => true,
                        'message' => '※ クロネコ代金後払い加盟店コードは数字で入力してください。'
                    )),
                    new Assert\Length(array(
                        'max' => 11,
                        'maxMessage' => "※ クロネコ代金後払い加盟店コードは11字以下で入力してください。"
                    )),
                ),
            ))
            ->add('enable_payment_type', 'choice', array(
                'label' => '有効にする決済方法',
                'choices' => $paymentUtil->getPaymentTypeNames(),
                'expanded' => true,
                'multiple' => true,
                'constraints' => array(
                    new Assert\NotBlank(array('message' => '※ 決済方法が選択されていません。')),
                ),
            ))
            ->add('use_option', 'choice', array(
                'label' => 'オプションサービス',
                'choices' => $paymentUtil->getUseOption(),
                'expanded' => true,
            ))
            ->add('access_key', 'text', array(
                'label' => 'アクセスキー',
                'attr' => array(
                    'maxlength' => '7',
                ),
                'constraints' => array(
                    new Assert\Regex(array(
                        'pattern' => "/^[0-9]+$/",
                        'match' => true,
                        'message' => '※ アクセスキーは数字で入力してください。'
                    )),
                    new Assert\Length(array(
                        'max' => 7,
                        'maxMessage' => "※ アクセスキーは7字以下で入力してください。"
                    )),
                ),
            ))
            ->add('advance_sale', 'choice', array(
                'label' => '予約販売機能',
                'choices' => $paymentUtil->getUtilization(),
                'expanded' => true,
            ))
            ->add('ycf_str_password', 'text', array(
                'label' => 'パスワード',
                'attr' => array(
                    'maxlength' => '8',
                ),
                'constraints' => array(
                    new Assert\Regex(array(
                        'pattern' => '/^[a-zA-Z0-9]*$/',
                        'match' => true,
                        'message' => '※ パスワードは英数字で入力してください。'
                    )),
                    new Assert\Length(array(
                        'max' => 8,
                        'maxMessage' => "※ パスワードは8字以下で入力してください。"
                    )),
                ),
            ))
            ->add('ycf_send_div', 'choice', array(
                'label' => '請求書の同梱',
                'choices' => $paymentUtil->getSendDivision(),
                'expanded' => true,
            ))
            ->add('ycf_ship_ymd', 'text', array(
                'label' => '出荷予定日',
                'attr' => array(
                    'maxlength' => '2',
                ),
                'constraints' => array(
                    new Assert\Regex(array(
                        'pattern' => "/^[0-9]+$/",
                        'match' => true,
                        'message' => '※ 出荷予定日は数字で入力してください。'
                    )),
                    new Assert\Length(array(
                        'max' => 2,
                        'maxMessage' => "※ 出荷予定日は2字以下で入力してください。"
                    )),
                ),
            ))
            ->add('ycf_deliv_disp', 'choice', array(
                'label' => 'メールの追跡情報表示機能',
                'choices' => $paymentUtil->getUtilization(),
                'expanded' => true,
            ))
            ->add('ycf_invoice_reissue_mail_address', 'email', array(
                'label' => '請求書再発行通知メール：受取メールアドレス',
                'required' => true,
                'constraints' => array(
                    new Assert\Email(array(
                        'strict' => true,
                    )),
                    new Assert\Regex(array(
                        'pattern' => '/^[[:graph:][:space:]]+$/i',
                        'message' => 'form.type.graph.invalid',
                    )),
                ),
            ))
            ->add('ycf_invoice_reissue_mail_header', 'textarea', array(
                'label' => '請求書再発行通知メール：メールヘッダー',
                'constraints' => array(
                    new Assert\Length(array(
                        'max' => 99999,
                        'maxMessage' => "※ 請求書再発行通知メール：メールヘッダーは99999字以下で入力してください。"
                    )),
                ),
            ))
            ->add('ycf_invoice_reissue_mail_footer', 'textarea', array(
                'label' => '請求書再発行通知メール：メールフッター',
                'constraints' => array(
                    new Assert\Length(array(
                        'max' => 99999,
                        'maxMessage' => "※ 請求書再発行通知メール：メールフッターは99999字以下で入力してください。"
                    )),
                ),
            ))
            ->addEventListener(FormEvents::PRE_SET_DATA, function (FormEvent $event) use ($self) {
                $data = $event->getData();

                // 初期値設定
                $self->setDefaultData($data);
                $event->setData($data);
            })
            ->addEventListener(FormEvents::POST_SUBMIT, function (FormEvent $event) use ($app) {
                $const = $app['config']['YamatoPayment']['const'];
                $form = $event->getForm();

                $paymentTypeIdList = $form['enable_payment_type']->getData();

                // クレジットカード決済が有効の場合
                if(in_array($const['YAMATO_PAYID_CREDIT'], (array)$paymentTypeIdList)) {
                    if (is_null($form['access_key']->getData())) {
                        $form['access_key']->addError(
                                new FormError('※ アクセスキーが入力されていません。')
                        );
                    }
                }

                // 後払い決済が有効の場合
                if (in_array($const['YAMATO_PAYID_DEFERRED'], (array)$paymentTypeIdList)) {

                    if (is_null($form['ycf_str_code']->getData())) {
                        $form['ycf_str_code']->addError(
                            new FormError('※ クロネコ代金後払い加盟店コードが入力されていません。')
                        );
                    }
                    if (is_null($form['ycf_str_password']->getData())) {
                        $form['ycf_str_password']->addError(
                            new FormError('※ クロネコ代金後払いパスワードが入力されていません。')
                        );
                    }
                    if (is_null($form['ycf_send_div']->getData())) {
                        $form['ycf_send_div']->addError(
                            new FormError('※ 請求書の同梱が選択されていません。')
                        );
                    }
                    if (is_null($form['ycf_ship_ymd']->getData())) {
                        $form['ycf_ship_ymd']->addError(
                            new FormError('※ 出荷予定日が入力されていません。')
                        );
                    }
                    if (is_null($form['ycf_deliv_disp']->getData())) {
                        $form['ycf_deliv_disp']->addError(
                            new FormError('※ メールの追跡情報表示機能が選択されていません。')
                        );
                    }
                    if (is_null($form['ycf_invoice_reissue_mail_address']->getData())) {
                        $form['ycf_invoice_reissue_mail_address']->addError(
                            new FormError('※ 請求書再発行通知メール：受取メールアドレスが入力されていません。')
                        );
                    }
                    if (is_null($form['ycf_invoice_reissue_mail_header']->getData())) {
                        $form['ycf_invoice_reissue_mail_header']->addError(
                            new FormError('※ 請求書再発行通知メール：メールヘッダーが入力されていません。')
                        );
                    }
                }

                // クレジットまたはコンビニ決済が有効の場合
                if (in_array($const['YAMATO_PAYID_CREDIT'], (array)$paymentTypeIdList)
                    || in_array($const['YAMATO_PAYID_CVS'], (array)$paymentTypeIdList)
                ) {
                    if (is_null($form['shop_id']->getData())) {
                        $form['shop_id']->addError(
                            new FormError('※ クロネコｗｅｂコレクト加盟店コードが入力されていません。')
                        );
                    }
//                     if (is_null($form['access_key']->getData())) {
//                         $form['access_key']->addError(
//                                 new FormError('※ アクセスキーが入力されていません。')
//                         );
//                     }

                    // オプションサービス契約済みの場合
                    if ($form['use_option']->getData() == 0) {
                        if (is_null($form['advance_sale']->getData())) {
                            $form['advance_sale']->addError(
                                new FormError('※ 予約販売機能が選択されていません。')
                            );
                        }
                    }
                }

                if (!is_null($form['ycf_ship_ymd']->getData()) && $form['ycf_ship_ymd']->getData() > 90) {
                    $form['ycf_ship_ymd']->addError(new FormError('※ 出荷予定日は90日以内で入力して下さい。'));
                }
            });
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'yamato_plugin_config';
    }

    /**
     * 初期値設定
     *
     * @param array $data
     */
    public function setDefaultData(&$data)
    {
        // 動作モード
        if (!isset($data['exec_mode'])) {
            $data['exec_mode'] = '0';
        }

        // オプションサービス
        if (!isset($data['use_option'])) {
            $data['use_option'] = '1';
        }

        // 予約販売機能
        if (!isset($data['advance_sale'])) {
            $data['advance_sale'] = '1';
        }

        // 請求書の同梱
        if (!isset($data['ycf_send_div'])) {
            $data['ycf_send_div'] = '0';
        }

        // 出荷予定日
        if (!isset($data['ycf_ship_ymd'])) {
            $data['ycf_ship_ymd'] = '3';
        }

        // メールの追跡情報表示機能
        if (!isset($data['ycf_deliv_disp'])) {
            $data['ycf_deliv_disp'] = '0';
        }
    }
}
