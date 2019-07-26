;(function($, window) {
    'use strict';

    $.plugin('swagPayPalUnifiedSmartPaymentButtons', {
        defaults: {
            /**
             * The url used to create the payment
             */
            createPaymentUrl: '',

            /**
             * The url used to approve the payment
             */
            approvePaymentUrl: '',

            /**
             * The class name to identify whether or not the paypal sdk has been loaded
             *
             * @type string
             */
            scriptLoadedClass: 'paypal-unified--paypal-sdk-loaded',

            /**
             * Value to determine whether to use sandbox or clientId
             *
             * @type boolean
             */
            sandbox: false,

            /**
             * Holds the client id
             *
             * @type string
             */
            clientId: '',

            /**
             * The language ISO (ISO_639) for the Smart Payment Buttons.
             *
             * @type string
             */
            languageIso: 'en_GB',

            currency: 'EUR',

            basketId: ''
        },

        /**
         * PayPal Object
         */
        paypal: {},

        init: function() {
            var me = this;

            me.applyDataAttributes();

            $.publish('plugin/swagPayPalUnifiedSmartPaymentButtons/init', me);

            me.createButtons();

            $.publish('plugin/swagPayPalUnifiedSmartPaymentButtons/buttonsCreated', me);
        },

        createButtons: function() {
            var me = this,
                scriptUrl = me.getScriptUrl(),
                $head = $('head');
            if (!$head.hasClass(me.opts.scriptLoadedClass)) {
                $.ajax({
                    url: scriptUrl,
                    dataType: 'script',
                    cache: true,
                    success: function () {
                        $head.addClass(me.opts.scriptLoadedClass);
                        me.paypal = window.paypal;
                        me.renderButtons();
                    }
                });
            } else {
                me.paypal = window.paypal;
                me.renderButtons();
            }
        },

        getScriptUrl: function() {
            var me = this;
            var url = 'https://www.paypal.com/sdk/js';
            if (me.opts.sandbox) {
                url += '?client-id=sb';
            } else {
                url += '?client-id=' + me.opts.clientId;
            }

            url += '&currency=' + me.opts.currency;

            return url;
        },

        renderButtons: function() {
            var me = this,
                buttonConfig = me.getButtonConfig(),
                el = me.$el.get(0);

            me.paypal.Buttons(buttonConfig).render(el);
        },

        getButtonConfig: function() {
            var me = this;

            return {
                onClick: me.onClick,

                /**
                 * Will be called if on smarty payment button is clicked
                 */
                createOrder: me.createOrder.bind(this),

                /**
                 * Will be called if the payment process is approved by paypal
                 */
                onApprove: me.onApprove.bind(this),

                /**
                 * Will be called if the payment process is cancelled by the customer
                 */
                onCancel: me.onCancel
            };
        },

        onClick: function(data, actions) {
            var form = $('#confirm--form'),
                confirmForm = form.get(0);

            if (confirmForm.checkValidity()) {
                return actions.resolve();
            }

            return actions.reject();
        },

        createOrder: function() {
            var me = this;

            $.loadingIndicator.open({
                openOverlay: true,
                closeOnClick: false,
                theme: 'light'
            });

            return $.ajax({
                method: 'get',
                url: me.opts.createPaymentUrl
            }).then(function(response) {
                if (response.errorUrl) {
                    window.location.replace(response.errorUrl);
                    return;
                }
                me.opts.paymentId = response.paymentId;
                me.opts.basketId = response.basketId;
                return response.token;
            }).promise();
        },

        onApprove: function(data) {
            var me = this,
                paymentId = data.paymentID,
                payerId = data.payerID,
                form,
                token;

            if (CSRF.checkToken()) {
                token = CSRF.getToken();
            }

            form = me.createApproveForm(token, paymentId, payerId);

            form.submit();
        },

        onCancel: function() {
            $.loadingIndicator.close();
        },

        createApproveForm: function(token, paymentId, payerId) {
            var me = this,
                $form,
                createField = function(name, val) {
                    return $('<input>', {
                        type: 'hidden',
                        name: name,
                        value: val
                    });
                };

            $form = $('<form>', {
                action: me.opts.approvePaymentUrl,
                method: 'POST'
            });

            createField('__csrf_token', token).appendTo($form);
            createField('paymentId', paymentId).appendTo($form);
            createField('PayerID', payerId).appendTo($form);
            createField('smartPaymentButtons', true).appendTo($form);
            createField('basketId', me.opts.basketId).appendTo($form);

            $form.appendTo($('body'));

            return $form;
        },

        /**
         * Buffer helper function to set a timeout for a function
         *
         * @param {function} fn
         * @param {number} timeout
         * @return {number}
         */
        buffer: function(fn, timeout) {
            var me = this;

            timeout = timeout || 500;

            return window.setTimeout(fn.bind(me), timeout);
        },

        destroy: function() {
            var me = this;
            me._destroy();
        }
    });

    window.StateManager.addPlugin('*[data-paypalUnifiedSmartPaymentButtons="true"]', 'swagPayPalUnifiedSmartPaymentButtons');
})(jQuery, window);
