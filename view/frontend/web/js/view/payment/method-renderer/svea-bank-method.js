/*browser:true*/
/*global define*/
define(
    [
        'jquery',
        'ko',
        'mage/translate',
        'Magento_Checkout/js/view/payment/default',
        'Magento_Checkout/js/model/error-processor',
        'Magento_Checkout/js/model/full-screen-loader',
    ],
    function (
        $,
        ko,
        $t,
        Component,
        errorProcessor,
        fullscreenLoader
    ) {
        'use strict';

        return Component.extend({
            defaults: {
                template: 'Webbhuset_SveaWebpay/payment/svea-bank',
            },
            /**
             * Use our own redirect instead of magento standard
             */
            redirectAfterPlaceOrder: false,
            initialize: function () {
                return this._super().initObservable();
            },
            /** Initialize all knockout observables */
            initObservable: function () {
                this.sveaForm       = ko.observable([]);
                this.selectedBank   = ko.observable('');

                return this._super();
            },
            getData: function() {
                var result  = {
                    "method": this.getCode(),
                    "po_number": null,
                    "additional_data": {}
                }

                var selectedBankKey = window.checkoutConfig.payment.svea_direct_bank.selected_hosted_method_key;
                result.additional_data[selectedBankKey] = this.selectedBank();

                return result;
            },
            /**
             * Get available banks
             *
             * @returns {*}
             */
            getBanks: function() {
                return window.checkoutConfig.payment.svea_direct_bank.banks;
            },
            /**
             * After successful order, redirect to Svea with
             */
            afterPlaceOrder: function() {
                var self = this;

                var data = {
                    method: self.getCode(),
                    form_key: $.mage.cookies.get('form_key')
                }

                $.ajax({
                    url: window.checkoutConfig.payment.svea_card.redirectOnSuccessUrl,
                    context: this,
                    method: 'POST',
                    data: data,
                    success: function(response) {
                        self.sveaForm(response.form);
                        $('#svea-payment-form').submit();
                    },
                    error: function(response) {
                        fullscreenLoader.stopLoader();
                        errorProcessor.process(response, this.messageContainer);
                    }
                });

            },
            validate: function() {
                if (!this.selectedBank()) {
                    this.messageContainer.addErrorMessage({
                        message: $t('Please select a bank')
                    });
                    return false;
                }

                return true;
            }
        });
    }
);
