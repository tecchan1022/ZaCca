<?php
/*
 * Copyright(c)2016, Yamato Financial Co.,Ltd. All rights reserved.
 * Copyright(c)2016, Yamato Credit finance Co.,Ltd. All rights reserved.
 */


namespace Plugin\YamatoPayment\Form\Type;

use Eccube\Application;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Validator\Constraints as Assert;

class RegistCreditType extends AbstractType
{
    /**
     * @var Application
     */
    private $app;

    /**
     * @var array
     */
    private $paymentInfo;

    /**
     * コンストラクタ
     *
     * @param Application $app
     * @param array $paymentInfo
     */
    public function __construct(Application $app, $paymentInfo = null)
    {
        $this->app = $app;
        $this->paymentInfo = $paymentInfo;
    }

    /**
     * カード情報画面 共通箇所の構築
     *
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $app = $this->app;
        $paymentInfo = $this->paymentInfo;

        // 支払方法で設定されている「支払回数」の情報を取得
        $sendPayMethod = $this->app['yamato_payment.util.payment']->getCreditPayMethod();
        $listPayMethod = array();
        if (isset($this->paymentInfo['pay_way'])) {
            foreach ((array)$this->paymentInfo['pay_way'] as $pay_way) {
                if (!is_null($sendPayMethod[$pay_way])) {
                    $listPayMethod[$pay_way] = $sendPayMethod[$pay_way];
                }
            }
        }

        // 2ケタ表示(0付加)の日付情報を取得
        $year = $this->getZeroYear(date('Y'), date('Y') + 15);
        $month = $this->getZeroMonth();

        $builder
            ->add('card_no', 'text', array(
                'label' => 'カード番号',
                'attr' => array(
                    'class' => 'form-control',
                    'minlength' => '12',
                    'maxlength' => '16',
                    'autocomplete' => 'off',
                ),
                'required' => false,
            ))
            ->add('card_exp_month', 'choice', array(
                'label' => '有効期限(月)',
                'empty_value' => '--',
                'required' => false,
                'choices' => $month,
            ))
            ->add('card_exp_year', 'choice', array(
                'label' => '有効期限(年)',
                'empty_value' => '--',
                'required' => false,
                'choices' => $year,
            ))
            ->add('card_owner', 'text', array(
                'label' => 'カード名義',
                'attr' => array(
                    'maxlength' => '25',
                ),
                'required' => false,
            ))
            ->add('security_code', 'text', array(
                'label' => 'セキュリティコード',
                'attr' => array(
                    'maxlength' => '4',
                    'autocomplete' => 'off',
                        'style' => 'ime-mode: disabled;',
                ),
                'required' => false,
            ))
            ->add('CardSeq', 'text', array(
                'read_only' => true,
                'attr' => array(
                    'class' => 'form-control',
                ),
            ))
            ->add('mode', 'hidden', array(
                    'data' => 'add'
            ))
            ->add('pay_way', 'choice', array(
                'choices' => $listPayMethod,
            ))
            ->add('register_card', 'checkbox', array(
                'label' => ' このカードを登録する',
                'required' => false,
            ))
            ->add('use_registed_card', 'checkbox', array(
                'required' => false,
            ))
            ->add('card_key', 'hidden')
            ->add('webcollectToken', 'hidden')
            ->addEventListener(FormEvents::POST_SET_DATA, function(FormEvent $event) use ($builder, $app) {
                $registcard_list = $event->getData();
                $form = $event->getForm();
                // 預かりカード情報が3件の場合、カード登録のチェックボックスは非活性となる
                if (count($registcard_list) == $app['config']['YamatoPayment']['const']['CREDIT_SAVE_LIMIT']) {
                    $form->add('register_card', 'checkbox', array(
                        'label' => ' このカードを登録する',
                        'required' => false,
                        'disabled' => true,
                    ));
                }
            })
            ->addEventListener(FormEvents::POST_SUBMIT, function (FormEvent $event) use ($paymentInfo) {
                $form = $event->getForm();
                $token = $form['webcollectToken']->getData();
                if(empty($token)) {
                    $card_exp_month = $form['card_exp_month']->getData();
                    $card_exp_year = $form['card_exp_year']->getData();
                    if (!empty($card_exp_month) && !empty($card_exp_year)) {
                        if (strtotime('-1 month') > strtotime('20' . $card_exp_year . '/' . $card_exp_month . '/1')) {
                            $form['card_exp_year']->addError(
                                new FormError("※ 有効期限が過ぎたカードは利用出来ません。")
                            );
                        }
                    }

                    $card_no = $form['card_no']->getData();
                    $card_exp_month = $form['card_exp_month']->getData();
                    $card_exp_year = $form['card_exp_year']->getData();
                    $card_owner = $form['card_owner']->getData();
                    $security_code = $form['security_code']->getData();

                    // クレジットカード決済のみ
                    if (!is_null($paymentInfo)) {
                        $pay_way = $form['pay_way']->getData();
                        if (is_null($pay_way)) {
                            $form['pay_way']->addError(
                                new FormError("※ 支払い方法が入力されていません。")
                            );
                        }
                    }

                    $mode = $form->get('mode')->getData();
                    $use_registed_card = $form->get('use_registed_card')->getData();

                    // modeがdeleteCardでない場合
                    if ($mode != 'deleteCard') {
                        // クレジットカード決済（預かりカード未使用）または MyPage カード情報編集画面の場合
                        if (!$use_registed_card || is_null($paymentInfo)) {

                            if (empty($card_no)) {
                                $form['card_no']->addError(
                                    new FormError("※ カード番号が入力されていません。")
                                );
                            }
                            if (empty($card_exp_month)) {
                                $form['card_exp_month']->addError(
                                    new FormError("※ カード有効期限年が入力されていません。")
                                );
                            }
                            if (empty($card_exp_year)) {
                                $form['card_exp_year']->addError(
                                    new FormError("※ カード有効期限月は数字で入力してください。")
                                );
                            }
                            if (empty($card_owner)) {
                                $form['card_owner']->addError(
                                    new FormError("※ カード名義が入力されていません。")
                                );
                            }
                        }

                        if (empty($security_code)) {
                            $form['security_code']->addError(
                                new FormError("※ セキュリティコードが入力されていません。")
                            );
                        }
                    }
                }
            });
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'regist_credit';
    }

    /**
     * 2ケタ表示(0付加)の月情報を取得
     *
     * @return array 月データ
     */
    public function getZeroMonth()
    {
        $month_array = array();
        for ($i = 1; $i <= 12; $i++) {
            $val = sprintf('%02d', $i);
            $month_array[$val] = $val;
        }

        return $month_array;
    }

    /**
     * 2ケタ表示(0付加)の年情報を取得
     *
     * @param string $star_year 開始年
     * @param string $end_year 終了年
     * @return array 年データ
     */
    public function getZeroYear($star_year = null, $end_year = null)
    {
        if (!$star_year) {
            $star_year = DATE('Y');
        }
        if (!$end_year) {
            $end_year = (DATE('Y') + 3);
        }
        $years = array();
        for ($i = $star_year; $i <= $end_year; $i++) {
            $key = substr($i, -2);
            $years[$key] = $key;
        }
        return $years;
    }

}
