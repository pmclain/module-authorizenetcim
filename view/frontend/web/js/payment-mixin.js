define([
    'jquery'
], function ($) {
    'use strict';

    return function (widget) {

        $.widget('mage.payment', widget, {
            /**
             * Display payment details when payment method radio button is checked
             * @private
             * @param {EventObject} e
             */
            _paymentMethodHandler: function (e) {
                this._super(e);

                var method = $(e.target).val();
                $('#multishipping-billing-form').trigger('changePaymentMethod', [method]);
            }
        });

        return $.mage.payment;
    }
});