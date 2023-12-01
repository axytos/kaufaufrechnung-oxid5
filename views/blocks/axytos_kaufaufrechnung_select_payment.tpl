[{oxstyle include=$oViewConf->getModuleUrl('axytos_kaufaufrechnung', 'out/src/css/axytos_kaufaufrechnung.css')}]
[{if $sPaymentID == "axytos_kaufaufrechnung"}]
    [{if $smarty.session.axytos_kaufaufrechnung_error_id === 'CHANGE_PAYMENT_METHOD' && $smarty.get.axytos_kaufaufrechnung_error_id === 'CHANGE_PAYMENT_METHOD'}]
        <div class="well well-sm" style="background: #fcf8e3;">
            [{if is_string($smarty.session.axytos_kaufaufrechnung_error_message)}]
                [{$smarty.session.axytos_kaufaufrechnung_error_message}]
            [{else}]
                [{oxmultilang ident="axytos_kaufaufrechnung_payment_rejected_message"}]
            [{/if}]
        </div>
    [{/if}]
    [{if $smarty.session.axytos_kaufaufrechnung_error_id !== 'CHANGE_PAYMENT_METHOD'}]
        <dl>
            <dt>
                <input id="payment_[{$sPaymentID}]" type="radio" name="paymentid" value="[{$sPaymentID}]"
                    [{if $oView->getCheckedPaymentId() == $paymentmethod->oxpayments__oxid->value}]checked[{/if}]>
                <label for="payment_[{$sPaymentID}]">
                    <b>[{$paymentmethod->oxpayments__oxdesc->value}]</b>
                </label>
            </dt>
            <dd class="[{if $oView->getCheckedPaymentId() == $paymentmethod->oxpayments__oxid->value}]activePayment[{/if}]">
                [{if $paymentmethod->getPrice()}]
                    [{assign var="oPaymentPrice" value=$paymentmethod->getPrice()}]
                    [{if $oViewConf->isFunctionalityEnabled('blShowVATForPayCharge')}]
                        ( [{oxprice price=$oPaymentPrice->getNettoPrice() currency=$currency}]
                        [{if $oPaymentPrice->getVatValue() > 0}]
                            [{oxmultilang ident="PLUS_VAT"}] [{oxprice price=$oPaymentPrice->getVatValue() currency=$currency}]
                        [{/if}])
                    [{else}]
                        ([{oxprice price=$oPaymentPrice->getBruttoPrice() currency=$currency}])
                    [{/if}]
                [{/if}]

                [{block name="checkout_payment_longdesc"}]
                    [{if $paymentmethod->oxpayments__oxlongdesc->value|trim}]
                        <div class="desc">
                            [{$paymentmethod->oxpayments__oxlongdesc->getRawValue()}]
                        </div>
                    [{/if}]
                [{/block}]

                <div class="axytos_kaufaufrechnung_credit_check_agreement_text">
                    [{oxmultilang ident="axytos_kaufaufrechnung_credit_check_agreement_text"}]
                </div>
                <a class="axytos_kaufaufrechnung_credit_check_agreement_info_link_text" rel="nofollow"
                    href="index.php?cl=axytos_kaufaufrechnung_credit_check_agreement"
                    onclick="window.open('index.php?cl=axytos_kaufaufrechnung_credit_check_agreement', 'axytos_kaufaufrechnung_credit_check_agreement_popup', 'resizable=yes,status=no,scrollbars=yes,menubar=no,width=620,height=400');return false;"
                    href="javascript:void(0);">[{oxmultilang ident="axytos_kaufaufrechnung_credit_check_agreement_info_link_text"}]
                </a>
            </dd>
        </dl>
    [{/if}]
[{else}]
    [{$smarty.block.parent}]
[{/if}]
