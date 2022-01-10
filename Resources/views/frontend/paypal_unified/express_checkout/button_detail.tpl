{block name='paypal_unified_ec_button_detail_container'}
    <div class="paypal-unified-ec--outer-button-container">
        {block name='paypal_unified_ec_button_container_detail_inner'}
            <div class="paypal-unified-ec--button-container right"
                 data-paypalUnifiedEcButton="true"
                 data-clientId="{$paypalUnifiedClientId}"
                 data-currency="{$paypalUnifiedCurrency}"
                 data-paypalIntent="{$paypalUnifiedIntent}"
                 data-paypalMode="{if $paypalUnifiedModeSandbox}sandbox{else}production{/if}"
                 data-createOrderUrl="{url module=widgets controller=PaypalUnifiedV2ExpressCheckout action=createOrder forceSecure}"
                 data-onApproveUrl="{url module=widgets controller=PaypalUnifiedV2ExpressCheckout action=onApprove forceSecure}"
                 data-confirmUrl="{url module=frontend controller=Checkout action=confirm forceSecure}"
                 data-logUrl="{url module=widgets controller=PaypalUnifiedV2ExpressCheckout action=logErrorMessage forceSecure}"
                 data-color="{$paypalUnifiedEcButtonStyleColor}"
                 data-shape="{$paypalUnifiedEcButtonStyleShape}"
                 data-size="{$paypalUnifiedEcButtonStyleSize}"
                 data-locale="{$paypalUnifiedLanguageIso}"
                 data-productNumber="{$sArticle.ordernumber}"
                 data-detailPage="true"
                 data-riskManagementMatchedProducts='{$riskManagementMatchedProducts}'
                 data-esdProducts='{$paypalUnifiedEsdProducts}'
                 data-communicationErrorMessage="{s name='error/communication' namespace='frontend/paypal_unified/checkout/messages'}{/s}"
                 data-communicationErrorTitle="{s name='error/communication/title' namespace='frontend/paypal_unified/checkout/messages'}{/s}"
                {block name='paypal_unified_ec_button_container_detail_data'}{/block}>
            </div>
        {/block}
    </div>
{/block}
