<?php return array (
  'AdminLoginAlert' => 
  array (
    'config' => 
    array (
      'name' => '管理者ログイン通知',
      'code' => 'AdminLoginAlert',
      'version' => '1.0.0',
      'event' => 'AdminLoginAlertEvent',
      'service' => 
      array (
        0 => 'AdminLoginAlertServiceProvider',
      ),
      'orm.path' => 
      array (
        0 => '/Resource/doctrine',
      ),
    ),
    'event' => 
    array (
      'eccube.event.admin.dummy' => 
      array (
        0 => 
        array (
          0 => 'onDummy',
          1 => 'NORMAL',
        ),
      ),
    ),
  ),
  'GoogleAnalyticsSimpleSetup' => 
  array (
    'config' => 
    array (
      'name' => 'GoogleAnalytics簡単設置プラグイン',
      'code' => 'GoogleAnalyticsSimpleSetup',
      'version' => '1.0.2',
      'service' => 
      array (
        0 => 'GoogleAnalyticsSimpleSetupServiceProvider',
      ),
      'orm.path' => 
      array (
        0 => '/Resource/doctrine',
      ),
      'migration.path' => 
      array (
        0 => '/Migration',
      ),
      'event' => 'Event',
    ),
    'event' => 
    array (
      'eccube.event.controller.shopping_complete.before' => 
      array (
        0 => 
        array (
          0 => 'onControllerShippingCompleteBefore',
          1 => 'NORMAL',
        ),
      ),
      'eccube.event.render.shopping_complete.before' => 
      array (
        0 => 
        array (
          0 => 'onRenderShippingCompleteBefore',
          1 => 'NORMAL',
        ),
      ),
    ),
  ),
  'MailMagazine' => 
  array (
    'config' => 
    array (
      'name' => 'MailMagazine',
      'event' => 'MailMagazine',
      'code' => 'MailMagazine',
      'version' => '1.0.1',
      'service' => 
      array (
        0 => 'MailMagazineServiceProvider',
      ),
      'orm.path' => 
      array (
        0 => '/Resource/doctrine',
      ),
      'const' => 
      array (
        'mail_magazine_dir' => '${ROOT_DIR}/app/mail_magazine',
      ),
    ),
    'event' => 
    array (
      'Entry/index.twig' => 
      array (
        0 => 
        array (
          0 => 'onRenderEntryIndex',
          1 => 'NORMAL',
        ),
      ),
      'Entry/confirm.twig' => 
      array (
        0 => 
        array (
          0 => 'onRenderEntryConfirm',
          1 => 'NORMAL',
        ),
      ),
      'front.entry.index.complete' => 
      array (
        0 => 
        array (
          0 => 'onFrontEntryIndexComplete',
          1 => 'NORMAL',
        ),
      ),
      'Mypage/change.twig' => 
      array (
        0 => 
        array (
          0 => 'onRenderMypageChange',
          1 => 'NORMAL',
        ),
      ),
      'front.mypage.change.index.complete' => 
      array (
        0 => 
        array (
          0 => 'onFrontMypageChangeIndexComplete',
          1 => 'NORMAL',
        ),
      ),
      'Admin/Customer/edit.twig' => 
      array (
        0 => 
        array (
          0 => 'onRenderAdminCustomerEdit',
          1 => 'NORMAL',
        ),
      ),
      'admin.customer.edit.index.complete' => 
      array (
        0 => 
        array (
          0 => 'onAdminCustomerEditIndexComplete',
          1 => 'NORMAL',
        ),
      ),
      'eccube.event.render.entry.before' => 
      array (
        0 => 
        array (
          0 => 'onRenderEntryBefore',
          1 => 'NORMAL',
        ),
      ),
      'eccube.event.controller.entry.after' => 
      array (
        0 => 
        array (
          0 => 'onControllerEntryAfter',
          1 => 'NORMAL',
        ),
      ),
      'eccube.event.render.mypage_change.before' => 
      array (
        0 => 
        array (
          0 => 'onRenderMypageChangeBefore',
          1 => 'NORMAL',
        ),
      ),
      'eccube.event.controller.mypage_change.after' => 
      array (
        0 => 
        array (
          0 => 'onControllMypageChangeAfter',
          1 => 'NORMAL',
        ),
      ),
      'eccube.event.render.admin_customer_new.before' => 
      array (
        0 => 
        array (
          0 => 'onRenderAdminCustomerBefore',
          1 => 'NORMAL',
        ),
      ),
      'eccube.event.render.admin_customer_edit.before' => 
      array (
        0 => 
        array (
          0 => 'onRenderAdminCustomerBefore',
          1 => 'NORMAL',
        ),
      ),
    ),
  ),
  'MailTemplateEditor' => 
  array (
    'config' => 
    array (
      'name' => 'メール設定プラグイン',
      'code' => 'MailTemplateEditor',
      'version' => '1.0.0',
      'service' => 
      array (
        0 => 'MailTemplateEditorServiceProvider',
      ),
    ),
    'event' => NULL,
  ),
  'OrderPdf' => 
  array (
    'config' => 
    array (
      'name' => 'OrderPdf',
      'event' => 'OrderPdfEvent',
      'code' => 'OrderPdf',
      'version' => '1.0.0',
      'service' => 
      array (
        0 => 'OrderPdfServiceProvider',
      ),
      'orm.path' => 
      array (
        0 => '/Resource/doctrine',
      ),
      'const' => 
      array (
        'logo_file' => 'logo.png',
        'pdf_file' => 'nouhinsyo1.pdf',
        'order_pdf_message_len' => 30,
      ),
    ),
    'event' => 
    array (
      'Admin/Order/index.twig' => 
      array (
        0 => 
        array (
          0 => 'onAdminOrderIndexRender',
          1 => 'NORMAL',
        ),
      ),
      'eccube.event.render.admin_order.before' => 
      array (
        0 => 
        array (
          0 => 'onRenderAdminOrderPdfBefore',
          1 => 'NORMAL',
        ),
      ),
      'eccube.event.render.admin_order_page.before' => 
      array (
        0 => 
        array (
          0 => 'onRenderAdminOrderPdfBefore',
          1 => 'NORMAL',
        ),
      ),
    ),
  ),
  'SimpleSiteMaintenance' => 
  array (
    'config' => 
    array (
      'name' => 'メンテナンスプラグイン',
      'code' => 'SimpleSiteMaintenance',
      'event' => 'SimpleSiteMaintenance',
      'version' => '1.0.1',
      'service' => 
      array (
        0 => 'SimpleSiteMaintenanceServiceProvider',
      ),
      'orm.path' => 
      array (
        0 => '/Resource/doctrine',
      ),
    ),
    'event' => 
    array (
      'eccube.event.front.response' => 
      array (
        0 => 
        array (
          0 => 'onEccubeEventFrontResponse',
          1 => 'LAST',
        ),
      ),
    ),
  ),
  'YamatoPayment' => 
  array (
    'config' => 
    array (
      'name' => 'クロネコヤマト カード・後払い一体型決済モジュール',
      'event' => 'YamatoPaymentEvent',
      'code' => 'YamatoPayment',
      'version' => '1.1.3',
      'service' => 
      array (
        0 => 'PaymentServiceProvider',
      ),
      'const' => 
      array (
        'PaymentUtil' => true,
        'YAMATO_SERVICE_NAME' => 'ネット総合決済サービス',
        'YAMATO_API_HTTP_TIMEOUT' => 20,
        'RECV_ALLOW_HOST' => 
        array (
          0 => '127.0.0.1',
          1 => '192.168.56.1',
          2 => '218.40.0.72',
        ),
        'YAMATO_PAYID_CREDIT' => 10,
        'YAMATO_PAYID_CVS' => 30,
        'YAMATO_PAYID_EDY' => 42,
        'YAMATO_PAYID_MOBILEEDY' => 43,
        'YAMATO_PAYID_SUICA' => 44,
        'YAMATO_PAYID_MOBILESUICA' => 45,
        'YAMATO_PAYID_WAON' => 46,
        'YAMATO_PAYID_MOBILEWAON' => 47,
        'YAMATO_PAYID_NETBANK' => 52,
        'YAMATO_PAYID_DEFERRED' => 60,
        'YAMATO_ACTION_STATUS_SEND_REQUEST' => 0,
        'YAMATO_ACTION_STATUS_COMP_REQUEST' => 1,
        'YAMATO_ACTION_STATUS_PROMPT_REPORT' => 2,
        'YAMATO_ACTION_STATUS_DIFINIT_REPORT' => 3,
        'YAMATO_ACTION_STATUS_COMP_AUTH' => 4,
        'YAMATO_ACTION_STATUS_COMP_RESERVE' => 5,
        'YAMATO_ACTION_STATUS_NG_CUSTOMER' => 11,
        'YAMATO_ACTION_STATUS_NG_SHOP' => 12,
        'YAMATO_ACTION_STATUS_NG_PAYMENT' => 13,
        'YAMATO_ACTION_STATUS_NG_SYSTEM' => 14,
        'YAMATO_ACTION_STATUS_NG_RESERVE' => 15,
        'YAMATO_ACTION_STATUS_NG_REQUEST_CANCEL' => 16,
        'YAMATO_ACTION_STATUS_NG_CHANGE_PAYMENT' => 17,
        'YAMATO_ACTION_STATUS_NG_TRANSACTION' => 20,
        'YAMATO_ACTION_STATUS_WAIT' => 21,
        'YAMATO_ACTION_STATUS_WAIT_SETTLEMENT' => 30,
        'YAMATO_ACTION_STATUS_COMMIT_SETTLEMENT' => 31,
        'YAMATO_ACTION_STATUS_CANCEL' => 40,
        'YAMATO_ACTION_STATUS_3D_WAIT' => 50,
        'DEFERRED_STATUS_AUTH_OK' => 1,
        'DEFERRED_STATUS_AUTH_CANCEL' => 2,
        'DEFERRED_STATUS_REGIST_DELIV_SLIP' => 3,
        'DEFERRED_STATUS_RESEARCH_DELIV' => 5,
        'DEFERRED_STATUS_SEND_WARNING' => 6,
        'DEFERRED_STATUS_SALES_OK' => 10,
        'DEFERRED_STATUS_SEND_BILL' => 11,
        'DEFERRED_STATUS_PAID' => 12,
        'DEFERRED_AVAILABLE' => 0,
        'DEFERRED_NOT_AVAILABLE' => 1,
        'DEFERRED_OVER_LIMIT' => 2,
        'DEFERRED_UNDER_EXAM' => 3,
        'DEFERRED_INVOICE_REISSUE' => 1,
        'DEFERRED_INVOICE_REISSUE_WITHDRAWN' => 3,
        'ORDER_SHIPPING_REGISTERED' => 9625,
        'DELIV_COMPLETE_MAIL_ID' => 9625,
        'DEFERRED_DELIV_SLIP_URL' => 'http://toi.kuronekoyamato.co.jp/cgi-bin/tneko',
        'YAMATO_PAYNAME_CREDIT' => 'クレジットカード決済',
        'YAMATO_PAYNAME_CVS' => 'コンビニ決済',
        'YAMATO_PAYNAME_DEFERRED' => 'クロネコ代金後払い決済',
        'YAMATO_PAYCODE_CREDIT' => 'Credit',
        'YAMATO_PAYCODE_CVS' => 'CVS',
        'YAMATO_PAYCODE_DEFERRED' => 'Deferred',
        'DEFERRED_SEND_DELIV_CHARGE' => 100,
        'DEFERRED_CHARGE' => 190,
        'CREDIT_METHOD_UC' => 1,
        'CREDIT_METHOD_DINERS' => 2,
        'CREDIT_METHOD_JCB' => 3,
        'CREDIT_METHOD_DC' => 4,
        'CREDIT_METHOD_MITSUISUMITOMO' => 5,
        'CREDIT_METHOD_UFJ' => 6,
        'CREDIT_METHOD_SAISON' => 7,
        'CREDIT_METHOD_NICOS' => 8,
        'CREDIT_METHOD_VISA' => 9,
        'CREDIT_METHOD_MASTER' => 10,
        'CREDIT_METHOD_AEON' => 11,
        'CREDIT_METHOD_AMEX' => 12,
        'CREDIT_METHOD_TOP' => 13,
        'CREDIT_METHOD_OTHER' => 99,
        'CONVENI_ID_SEVENELEVEN' => 21,
        'CONVENI_ID_LAWSON' => 22,
        'CONVENI_ID_FAMILYMART' => 23,
        'CONVENI_ID_SEICOMART' => 24,
        'CONVENI_ID_MINISTOP' => 25,
        'CONVENI_ID_CIRCLEK' => 26,
        'CONVENI_NAME_SEVENELEVEN' => 'セブンイレブン',
        'CONVENI_NAME_LAWSON' => 'ローソン',
        'CONVENI_NAME_FAMILYMART' => 'ファミリーマート',
        'CONVENI_NAME_SEICOMART' => 'セイコーマート',
        'CONVENI_NAME_MINISTOP' => 'ミニストップ',
        'CONVENI_NAME_CIRCLEK' => 'サークルKサンクス',
        'CONVENI_FUNCTION_DIV_21' => 'B01',
        'CONVENI_FUNCTION_DIV_22' => 'B03',
        'CONVENI_FUNCTION_DIV_23' => 'B02',
        'CONVENI_FUNCTION_DIV_24' => 'B06',
        'CONVENI_FUNCTION_DIV_25' => 'B05',
        'CONVENI_FUNCTION_DIV_26' => 'B04',
        'EMONEY_METHOD_RAKUTENEDY' => 61,
        'EMONEY_METHOD_M_RAKUTENEDY' => 62,
        'EMONEY_METHOD_SUICA' => 63,
        'EMONEY_METHOD_M_SUICA' => 64,
        'EMONEY_METHOD_WAON' => 65,
        'EMONEY_METHOD_M_WAON' => 66,
        'EMONEY_NAME_RAKUTENEDY' => '楽天Edy決済',
        'EMONEY_NAME_M_RAKUTENEDY' => '楽天モバイルEdy決済',
        'EMONEY_NAME_SUICA' => 'Suica決済',
        'EMONEY_NAME_M_SUICA' => 'モバイルSuica決済',
        'EMONEY_NAME_WAON' => 'WAON決済',
        'EMONEY_NAME_M_WAON' => 'モバイルWAON決済',
        'NETBANK_METHOD_RAKUTENBANK' => 41,
        'DELIV_TIMECODE' => 
        array (
          0 => '',
          1 => '0812',
          2 => '1214',
          3 => '1416',
          4 => '1618',
          5 => '1820',
          9 => '1921',
          6 => '2021',
          7 => '0010',
          8 => '0017',
        ),
        'DELETE_DELIV_TIMECODE' => 
        array (
          2 => '1214',
          6 => '2021',
        ),
        'DELIV_SLIP_TYPE_CORECT' => 2,
        'DELIV_SLIP_TYPE_NEKOPOS' => 7,
        'CREDIT_SAVE_LIMIT' => 3,
        'YAMATO_ENABLE_PAYMENT_VALUE' => 0,
        'YAMATO_DISABLE_PAYMENT_VALUE' => 1,
        'YAMATO_DELIV_ADDR_MAX' => 99,
        'YAMATO_SHIPPED_MAX' => 3,
        'DEFERRED_DELIV_ADDR_MAX' => 10,
        'PRODUCT_TYPE_ID_RESERVE' => 9625,
        'YAMATO_DEADLINE_RECREDIT' => 9,
        'YAMATO_TRADER_URL' => 'shopping/load_payment_module.php?mode=3dTran',
        'YAMATO_3D_EXCLUDED' => 'A012050002',
        'CREDIT_NO_LEN' => 16,
        'SECURITY_CODE_MIN_LEN' => 3,
        'SECURITY_CODE_MAX_LEN' => 4,
        'CARD_ORNER_LEN' => 20,
        'ITEM_NAME_LEN' => 200,
        'api.name' => 
        array (
          'A01' => 'クレジット決済登録通常用',
          'A02' => 'クレジット決済登録３Ｄセキュア結果用',
          'A03' => 'クレジットカードのお預かり情報照会',
          'A04' => 'クレジットカードのお預かり情報変更',
          'A05' => 'クレジットカードのお預かり情報削除',
          'A06' => 'クレジット決済取消',
          'A07' => 'クレジット金額変更',
          'A08' => 'クレジット決済登録通常用(トークン)',
          'A09' => 'クレジット決済登録３Ｄセキュア結果用(トークン)',
          'A11' => 'クレジット再与信',
          'B01' => 'コンビニ（オンライン払い）セブン-イレブン',
          'B02' => 'コンビニ（オンライン払い）ファミリーマート',
          'B03' => 'コンビニ（オンライン払い）ローソン',
          'B04' => 'コンビニ（オンライン払い）サークルＫサンクス',
          'B05' => 'コンビニ（オンライン払い）ミニストップ',
          'B06' => 'コンビニ（オンライン払い）セイコーマート',
          'C01' => '電子マネー決済登録(楽天Edy(Cyber))',
          'C02' => '電子マネー決済登録(楽天Edy(Mobile))',
          'C03' => '電子マネー決済登録(Suica(インターネットサービス))',
          'C04' => '電子マネー決済登録(Suica(Mobile))',
          'C05' => '電子マネー決済登録(Waon(PC))',
          'C06' => '電子マネー決済登録(Waon(MB))',
          'D01' => 'ネットバンク決済登録（楽天銀行）',
          'E01' => '出荷情報登録',
          'E02' => '出荷情報取消',
          'E03' => '出荷予定日変更',
          'E04' => '取引情報照会',
          'F01' => '接続元加盟店ECサーバ認証',
          'F02' => '出荷情報登録（切売）',
          'F03' => '出荷情報取消（切売）',
          'F04' => '取引情報照会（切売）',
          'H01' => 'グローバルＩＰアドレス照会',
          'KAAAU0010APIAction' => '後払い与信依頼',
          'KAARS0010APIAction' => '後払い与信結果取得',
          'KAASL0010APIAction' => '後払い出荷情報登録',
          'KAAST0010APIAction' => '後払い取引状況取得',
          'KAACL0010APIAction' => '後払い与信取消依頼',
          'KAASD0010APIAction' => '後払い請求書印字データ取得',
          'KAARA0010APIAction' => '後払いリアルタイムオーソリ',
          'KAAKK0010APIAction' => '後払い金額変更(減額)',
          'KAARR0010APIAction' => '後払い請求書再発行',
        ),
        'api.url' => 
        array (
          'A01' => 'https://api.kuronekoyamato.co.jp/api/credit',
          'A02' => 'https://api.kuronekoyamato.co.jp/api/credit3D',
          'A03' => 'https://api.kuronekoyamato.co.jp/api/creditInfoGet',
          'A04' => 'https://api.kuronekoyamato.co.jp/api/creditInfoUpdate',
          'A05' => 'https://api.kuronekoyamato.co.jp/api/creditInfoDelete',
          'A06' => 'https://api.kuronekoyamato.co.jp/api/creditCancel',
          'A07' => 'https://api.kuronekoyamato.co.jp/api/creditChangePrice',
          'A08' => 'https://api.kuronekoyamato.co.jp/api/creditToken',
          'A09' => 'https://api.kuronekoyamato.co.jp/api/creditToken3D',
          'A11' => 'https://api.kuronekoyamato.co.jp/api/reAuth',
          'B01' => 'https://api.kuronekoyamato.co.jp/api/cvs1',
          'B02' => 'https://api.kuronekoyamato.co.jp/api/cvs2',
          'B03' => 'https://api.kuronekoyamato.co.jp/api/cvs3',
          'B04' => 'https://api.kuronekoyamato.co.jp/api/cvs3',
          'B05' => 'https://api.kuronekoyamato.co.jp/api/cvs3',
          'B06' => 'https://api.kuronekoyamato.co.jp/api/cvs3',
          'C01' => 'https://api.kuronekoyamato.co.jp/api/e_money1',
          'C02' => 'https://api.kuronekoyamato.co.jp/api/e_money2',
          'C03' => 'https://api.kuronekoyamato.co.jp/api/e_money3',
          'C04' => 'https://api.kuronekoyamato.co.jp/api/e_money4',
          'C05' => 'https://api.kuronekoyamato.co.jp/api/e_money5',
          'C06' => 'https://api.kuronekoyamato.co.jp/api/e_money6',
          'D01' => 'https://api.kuronekoyamato.co.jp/api/bank1',
          'E01' => 'https://api.kuronekoyamato.co.jp/api/shipmentEntry',
          'E02' => 'https://api.kuronekoyamato.co.jp/api/shipmentCancel',
          'E03' => 'https://api.kuronekoyamato.co.jp/api/changeDate',
          'E04' => 'https://api.kuronekoyamato.co.jp/api/tradeInfo',
          'F01' => 'https://apixp.kuronekoyamato.co.jp/api/xp/authXp',
          'F02' => 'https://apixp.kuronekoyamato.co.jp/api/xp/shipmentEntryXp',
          'F03' => 'https://apixp.kuronekoyamato.co.jp/api/xp/shipmentCancelXp',
          'F04' => 'https://apixp.kuronekoyamato.co.jp/api/xp/tradeInfoXp',
          'H01' => 'https://ptwebcollect.jp/test_gateway/traderIp.api',
          'KAAAU0010APIAction' => 'https://yamato-credit-finance.jp/kuroneko-atobarai-api/KAAAU0010APIAction_execute.action',
          'KAARS0010APIAction' => 'https://yamato-credit-finance.jp/kuroneko-atobarai-api/KAARS0010APIAction_execute.action',
          'KAASL0010APIAction' => 'https://yamato-credit-finance.jp/kuroneko-atobarai-api/KAASL0010APIAction_execute.action',
          'KAAST0010APIAction' => 'https://yamato-credit-finance.jp/kuroneko-atobarai-api/KAAST0010APIAction_execute.action',
          'KAACL0010APIAction' => 'https://yamato-credit-finance.jp/kuroneko-atobarai-api/KAACL0010APIAction_execute.action',
          'KAASD0010APIAction' => 'https://yamato-credit-finance.jp/kuroneko-atobarai-api/KAASD0010APIAction_execute.action',
          'KAARA0010APIAction' => 'https://yamato-credit-finance.jp/kuroneko-atobarai-api/KAARA0010APIAction_execute.action',
          'KAAKK0010APIAction' => 'https://yamato-credit-finance.jp/kuroneko-atobarai-api/KAAKK0010APIAction_execute.action',
          'KAARR0010APIAction' => 'https://yamato-credit-finance.jp/kuroneko-atobarai-api/KAARR0010APIAction_execute.action',
        ),
        'api.test.gateway' => 
        array (
          'A01' => 'https://ptwebcollect.jp/test_gateway/credit.api',
          'A02' => 'https://ptwebcollect.jp/test_gateway/credit3D.api',
          'A03' => 'https://ptwebcollect.jp/test_gateway/creditInfoGet.api',
          'A04' => 'https://ptwebcollect.jp/test_gateway/creditInfoUpdate.api',
          'A05' => 'https://ptwebcollect.jp/test_gateway/creditInfoDelete.api',
          'A06' => 'https://ptwebcollect.jp/test_gateway/creditCancel.api',
          'A07' => 'https://ptwebcollect.jp/test_gateway/creditChangePrice.api',
          'A08' => 'https://ptwebcollect.jp/test_gateway/creditToken.api',
          'A09' => 'https://ptwebcollect.jp/test_gateway/creditToken3D.api',
          'A11' => 'https://ptwebcollect.jp/test_gateway/reAuth.api',
          'B01' => 'https://ptwebcollect.jp/test_gateway/cvs1.api',
          'B02' => 'https://ptwebcollect.jp/test_gateway/cvs2.api',
          'B03' => 'https://ptwebcollect.jp/test_gateway/cvs3.api',
          'B04' => 'https://ptwebcollect.jp/test_gateway/cvs3.api',
          'B05' => 'https://ptwebcollect.jp/test_gateway/cvs3.api',
          'B06' => 'https://ptwebcollect.jp/test_gateway/cvs3.api',
          'C01' => 'https://ptwebcollect.jp/test_gateway/e_money1.api',
          'C02' => 'https://ptwebcollect.jp/test_gateway/e_money2.api',
          'C03' => 'https://ptwebcollect.jp/test_gateway/e_money3.api',
          'C04' => 'https://ptwebcollect.jp/test_gateway/e_money4.api',
          'C05' => 'https://ptwebcollect.jp/test_gateway/e_money5.api',
          'C06' => 'https://ptwebcollect.jp/test_gateway/e_money6.api',
          'D01' => 'https://ptwebcollect.jp/test_gateway/bank1.api',
          'E01' => 'https://ptwebcollect.jp/test_gateway/shipmentEntry.api',
          'E02' => 'https://ptwebcollect.jp/test_gateway/shipmentCancel.api',
          'E03' => 'https://ptwebcollect.jp/test_gateway/changeDate.api',
          'E04' => 'https://ptwebcollect.jp/test_gateway/tradeInfo.api',
          'F01' => 'https://ptwebcollect.jp/test_gateway/xp/authXp.api',
          'F02' => 'https://ptwebcollect.jp/test_gateway/xp/shipmentEntryXp.api',
          'F03' => 'https://ptwebcollect.jp/test_gateway/xp/shipmentCancelXp.api',
          'F04' => 'https://ptwebcollect.jp/test_gateway/xp/tradeInfoXp.api',
          'H01' => 'https://ptwebcollect.jp/test_gateway/traderIp.api',
          'KAAAU0010APIAction' => 'http://demo.yamato-credit-finance.jp/kuroneko-atobarai-api/KAAAU0010APIAction_execute.action',
          'KAARS0010APIAction' => 'http://demo.yamato-credit-finance.jp/kuroneko-atobarai-api/KAARS0010APIAction_execute.action',
          'KAASL0010APIAction' => 'http://demo.yamato-credit-finance.jp/kuroneko-atobarai-api/KAASL0010APIAction_execute.action',
          'KAAST0010APIAction' => 'http://demo.yamato-credit-finance.jp/kuroneko-atobarai-api/KAAST0010APIAction_execute.action',
          'KAACL0010APIAction' => 'http://demo.yamato-credit-finance.jp/kuroneko-atobarai-api/KAACL0010APIAction_execute.action',
          'KAASD0010APIAction' => 'http://demo.yamato-credit-finance.jp/kuroneko-atobarai-api/KAASD0010APIAction_execute.action',
          'KAARA0010APIAction' => 'http://demo.yamato-credit-finance.jp/kuroneko-atobarai-api/KAARA0010APIAction_execute.action',
          'KAAKK0010APIAction' => 'http://demo.yamato-credit-finance.jp/kuroneko-atobarai-api/KAAKK0010APIAction_execute.action',
          'KAARR0010APIAction' => 'http://demo.yamato-credit-finance.jp/kuroneko-atobarai-api/KAARR0010APIAction_execute.action',
        ),
        'TOKEN_URL_1' => 'https://api.kuronekoyamato.co.jp/api/token/js/embeddedTokenLib.js',
        'TOKEN_URL_0' => 'https://ptwebcollect.jp/test_gateway/token/js/embeddedTokenLib.js',
        'USE_SECURITY_CODE' => 1,
        'YAMATO_MULTI_ATACK_PERMIT_COUNT' => 5,
        'YAMATO_MULTI_ATACK_WAIT' => 5,
      ),
      'orm.path' => 
      array (
        0 => '/Resource/doctrine',
      ),
    ),
    'event' => 
    array (
      'admin.order.index.search' => 
      array (
        0 => 
        array (
          0 => 'onAdminOrderIndexSearch',
          1 => 'NORMAL',
        ),
      ),
      'Admin/Order/index.twig' => 
      array (
        0 => 
        array (
          0 => 'onAdminOrderIndexRender',
          1 => 'NORMAL',
        ),
      ),
      'admin.order.edit.index.initialize' => 
      array (
        0 => 
        array (
          0 => 'onAdminOrderEditIndexInitialize',
          1 => 'NORMAL',
        ),
      ),
      'admin.order.edit.index.complete' => 
      array (
        0 => 
        array (
          0 => 'onAdminOrderEditIndexComplete',
          1 => 'NORMAL',
        ),
      ),
      'Admin/Order/edit.twig' => 
      array (
        0 => 
        array (
          0 => 'onAdminOrderEditRender',
          1 => 'NORMAL',
        ),
      ),
      'eccube.event.route.admin_order_edit.request' => 
      array (
        0 => 
        array (
          0 => 'onRouteAdminOrderEditRequest',
          1 => 'NORMAL',
        ),
      ),
      'admin.order.mail.index.complete' => 
      array (
        0 => 
        array (
          0 => 'onAdminOrderMailIndexComplete',
          1 => 'NORMAL',
        ),
      ),
      'Admin/Order/mail_confirm.twig' => 
      array (
        0 => 
        array (
          0 => 'onAdminOrderMailConfirmRender',
          1 => 'NORMAL',
        ),
      ),
      'admin.order.mail.mail.all.complete' => 
      array (
        0 => 
        array (
          0 => 'onAdminOrderMailMailAllComplete',
          1 => 'NORMAL',
        ),
      ),
      'Admin/Order/mail_all_confirm.twig' => 
      array (
        0 => 
        array (
          0 => 'onAdminOrderMailAllConfirmRender',
          1 => 'NORMAL',
        ),
      ),
      'admin.product.edit.complete' => 
      array (
        0 => 
        array (
          0 => 'onAdminProductEditComplete',
          1 => 'NORMAL',
        ),
      ),
      'admin.product.copy.complete' => 
      array (
        0 => 
        array (
          0 => 'onAdminProductCopyComplete',
          1 => 'NORMAL',
        ),
      ),
      'Admin/Product/product.twig' => 
      array (
        0 => 
        array (
          0 => 'onAdminProductProductRender',
          1 => 'NORMAL',
        ),
      ),
      'admin.setting.shop.payment.edit.initialize' => 
      array (
        0 => 
        array (
          0 => 'onAdminSettingShopPaymentEditInitialize',
          1 => 'NORMAL',
        ),
      ),
      'admin.setting.shop.payment.edit.complete' => 
      array (
        0 => 
        array (
          0 => 'onAdminSettingShopPaymentEditComplete',
          1 => 'NORMAL',
        ),
      ),
      'Admin/Setting/Shop/payment_edit.twig' => 
      array (
        0 => 
        array (
          0 => 'onAdminSettingShopPaymentEditRender',
          1 => 'NORMAL',
        ),
      ),
      'mail.order' => 
      array (
        0 => 
        array (
          0 => 'onMailOrderRender',
          1 => 'NORMAL',
        ),
      ),
      'mail.admin.order' => 
      array (
        0 => 
        array (
          0 => 'onMailAdminOrderRender',
          1 => 'NORMAL',
        ),
      ),
      'eccube.event.front.request' => 
      array (
        0 => 
        array (
          0 => 'onFrontRequest',
          1 => 'NORMAL',
        ),
      ),
      'eccube.event.render.mypage.before' => 
      array (
        0 => 
        array (
          0 => 'onRenderMypageBefore',
          1 => 'NORMAL',
        ),
      ),
      'eccube.event.render.mypage_change.before' => 
      array (
        0 => 
        array (
          0 => 'onRenderMypageBefore',
          1 => 'NORMAL',
        ),
      ),
      'eccube.event.render.mypage_change_complete.before' => 
      array (
        0 => 
        array (
          0 => 'onRenderMypageBefore',
          1 => 'NORMAL',
        ),
      ),
      'eccube.event.render.mypage_delivery.before' => 
      array (
        0 => 
        array (
          0 => 'onRenderMypageBefore',
          1 => 'NORMAL',
        ),
      ),
      'eccube.event.render.mypage_delivery_new.before' => 
      array (
        0 => 
        array (
          0 => 'onRenderMypageBefore',
          1 => 'NORMAL',
        ),
      ),
      'eccube.event.render.mypage_delivery_edit.before' => 
      array (
        0 => 
        array (
          0 => 'onRenderMypageBefore',
          1 => 'NORMAL',
        ),
      ),
      'eccube.event.render.mypage_favorite.before' => 
      array (
        0 => 
        array (
          0 => 'onRenderMypageBefore',
          1 => 'NORMAL',
        ),
      ),
      'eccube.event.render.mypage_history.before' => 
      array (
        0 => 
        array (
          0 => 'onRenderMypageBefore',
          1 => 'NORMAL',
        ),
      ),
      'eccube.event.render.mypage_mail_view.before' => 
      array (
        0 => 
        array (
          0 => 'onRenderMypageBefore',
          1 => 'NORMAL',
        ),
      ),
      'eccube.event.render.mypage_order.before' => 
      array (
        0 => 
        array (
          0 => 'onRenderMypageBefore',
          1 => 'NORMAL',
        ),
      ),
      'eccube.event.render.mypage_withdraw.before' => 
      array (
        0 => 
        array (
          0 => 'onRenderMypageBefore',
          1 => 'NORMAL',
        ),
      ),
      'eccube.event.render.yamato_mypage_change_card.before' => 
      array (
        0 => 
        array (
          0 => 'onRenderMypageBefore',
          1 => 'NORMAL',
        ),
      ),
      'eccube.event.route.cart.request' => 
      array (
        0 => 
        array (
          0 => 'onRouteCartRequest',
          1 => 'NORMAL',
        ),
      ),
      'eccube.event.route.shopping.request' => 
      array (
        0 => 
        array (
          0 => 'onRouteShoppingRequest',
          1 => 'NORMAL',
        ),
      ),
      'front.shopping.index.initialize' => 
      array (
        0 => 
        array (
          0 => 'onFrontShoppingIndexInitialize',
          1 => 'NORMAL',
        ),
      ),
      'Shopping/index.twig' => 
      array (
        0 => 
        array (
          0 => 'onShoppingIndexRender',
          1 => 'NORMAL',
        ),
      ),
      'eccube.event.route.shopping_confirm.request' => 
      array (
        0 => 
        array (
          0 => 'onRouteFrontShoppingConfirmRequest',
          1 => 'NORMAL',
        ),
      ),
      'Shopping/complete.twig' => 
      array (
        0 => 
        array (
          0 => 'onShoppingCompleteRender',
          1 => 'NORMAL',
        ),
      ),
    ),
  ),
);