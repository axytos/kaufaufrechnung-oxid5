<?php

/**
 * Metadata version.
 */

use Axytos\KaufAufRechnung_OXID5\Events\AxytosEvents;

$sMetadataVersion = '1.0';
$moduleDirectory = 'axytos/kaufaufrechnung/';
$extendModelDirectory = $moduleDirectory . 'Extend/Model/';
$controllerDirectory = $moduleDirectory . 'Controller/';

/**
 * Module information.
 */
$aModule = [
    'id' => 'axytos_kaufaufrechnung',
    'title' => [
        'de' => 'Kauf auf Rechnung',
        'en' => 'Buy Now Pay Later',
    ],
    'description' => [
        'de' => 'Sie zahlen bequem die Rechnung, sobald Sie die Ware erhalten haben, innerhalb der Zahlfrist',
        'en' => 'You conveniently pay the invoice as soon as you receive the goods, within the payment period',
    ],
    'thumbnail' => 'assets/img/logo.png',
    'version' => '1.7.0-rc',
    'author' => 'axytos GmbH',
    'url' => 'https://www.axytos.com',
    'email' => 'info@axytos.com',
    'extend' => [
        // models
        'oxpaymentlist' => $extendModelDirectory . 'AxytosPaymentList',
        'oxpaymentgateway' => $extendModelDirectory . 'AxytosPaymentGateway',
        'oxpayment' => $extendModelDirectory . 'AxytosPayment',
        'oxmaintenance' => $extendModelDirectory . 'AxytosMaintenance',
        'oxorder' => $extendModelDirectory . 'AxytosOrder',
    ],
    'events' => [
        'onActivate' => AxytosEvents::class . '::onActivate',
        'onDeactivate' => AxytosEvents::class . '::onDeactivate',
    ],
    'files' => [
        'axytos_kaufaufrechnung_credit_check_agreement' => $controllerDirectory . 'axytos_kaufaufrechnung_credit_check_agreement.php',
        'axytos_kaufaufrechnung_action_callback' => $controllerDirectory . 'axytos_kaufaufrechnung_action_callback.php',
    ],
    'settings' => [
        [
            'group' => 'axytos_kaufaufrechnung_settings',
            'name' => 'axytos_kaufaufrechnung_api_host',
            'type' => 'select',
            'value' => 'APIHOST_SANDBOX',
            'constraints' => 'APIHOST_LIVE|APIHOST_SANDBOX',
        ],
        [
            'group' => 'axytos_kaufaufrechnung_settings',
            'name' => 'axytos_kaufaufrechnung_api_key',
            'type' => 'password',
            'value' => '',
        ],
        [
            'group' => 'axytos_kaufaufrechnung_settings',
            'name' => 'axytos_kaufaufrechnung_client_secret',
            'type' => 'password',
            'value' => '',
        ],
        [
            'group' => 'axytos_kaufaufrechnung_settings',
            'name' => 'axytos_kaufaufrechnung_error_message',
            'type' => 'str',
            'value' => '',
        ],
    ],
    'templates' => [
        'credit_check_agreement.tpl' => 'axytos/kaufaufrechnung/views/tpl/credit_check_agreement.tpl',
    ],
    'blocks' => [
        [
            'template' => 'page/checkout/payment.tpl',
            'block' => 'change_payment',
            'file' => 'views/blocks/axytos_kaufaufrechnung_change_payment.tpl',
        ],
        [
            'template' => 'page/checkout/payment.tpl',
            'block' => 'select_payment',
            'file' => 'views/blocks/axytos_kaufaufrechnung_select_payment.tpl',
        ],
        [
            'template' => 'page/checkout/payment.tpl',
            'block' => 'checkout_payment_nextstep',
            'file' => 'views/blocks/axytos_kaufaufrechnung_checkout_payment_nextstep.tpl',
        ],
    ],
];
