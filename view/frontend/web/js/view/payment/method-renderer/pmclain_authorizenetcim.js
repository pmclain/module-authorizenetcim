define(
  [
    'jquery',
    'Magento_Payment/js/view/payment/cc-form',
    'Magento_Checkout/js/action/place-order',
    'Magento_Checkout/js/model/full-screen-loader',
    'Magento_Checkout/js/model/payment/additional-validators',
    'Magento_Payment/js/model/credit-card-validation/validator',
    'Magento_Checkout/js/action/redirect-on-success',
    'Magento_Vault/js/view/payment/vault-enabler'
  ],
  function ($,
            Component,
            placeOrderAction,
            fullScreenLoader,
            additionalValidators,
            validator,
            redirectOnSuccessAction,
            VaultEnabler
  ) {
    'use strict';

    return Component.extend({
      defaults: {
        template: 'Pmclain_AuthorizenetCim/payment/form'
      },

      initialize: function() {
        this._super();
        this.initAcceptJs();
        this.vaultEnabler = new VaultEnabler();
        this.vaultEnabler.setPaymentCode(this.getVaultCode());
      },

      placeOrder: function(data, event) {
        var self = this,
          placeOrder;

        if (event) {
          event.preventDefault();
        }

        if (this.validate()) {
          this.isPlaceOrderActionAllowed(false);
          fullScreenLoader.startLoader();

          $.when(this.createToken()).done(function() {
            placeOrder = placeOrderAction(self.getData(), self.messageContainer);
            $.when(placeOrder).done(function() {
              if (self.redirectAfterPlaceOrder) {
                redirectOnSuccessAction.execute();
              }
            }).fail(function() {
              fullScreenLoader.stopLoader();
              self.isPlaceOrderActionAllowed(true);
            });
          }).fail(function(result) {
            fullScreenLoader.stopLoader();
            self.isPlaceOrderActionAllowed(true);

            for (var i = 0; i < response.messages.message.length; i++) {
              self.messageContainer.addErrorMessage({
                'message': response.messages.message[i].code + ": " + response.messages.message[i].text
              });
            }
          });

          return true;
        }
        return false;
      },

      createToken: function() {
        var self = this;

        var secureData = {};
        var authData = {};
        var cardData = {};

        cardData.cardNumber = this.creditCardNumber();
        cardData.month = this.creditCardExpMonth();
        cardData.year = this.creditCardExpYear();
        cardData.cardCode = this.creditCardVerificationNumber();
        secureData.cardData = cardData;

        authData.clientKey = this.getClientKey();
        authData.apiLoginID = this.getApiLoginId();
        secureData.authData = authData;

        var defer = $.Deferred();

        this.acceptjs.dispatchData(secureData, function(response) {
          if (response.messages.resultCode === "Error") {
            defer.reject(response.messages.message);
          } else {
            self.token = response.opaqueData.dataValue;
            defer.resolve();
          }
        });

        return defer.promise();
      },

      getCode: function() {
        return 'pmclain_authorizenetcim';
      },

      isActive: function() {
        return true;
      },

      getData: function() {
        var data = this._super();

        data.additional_data.cc_last4 = this.creditCardNumber().slice(-4);
        delete data.additional_data.cc_number;
        data.additional_data.cc_token = this.token;

        this.vaultEnabler.visitAdditionalData(data);

        return data;
      },

      getClientKey: function () {
        return window.checkoutConfig.payment[this.getCode()].clientKey;
      },

      getApiLoginId: function () {
        return window.checkoutConfig.payment[this.getCode()].apiLoginId;
      },

      getIsTest: function () {
        return window.checkoutConfig.payment[this.getCode()].test;
      },

      validate: function() {
        var $form = $('#' + this.getCode() + '-form');
        return $form.validation() && $form.validation('isValid');
      },

      isVaultEnabled: function () {
        return this.vaultEnabler.isVaultEnabled();
      },

      getVaultCode: function () {
        return window.checkoutConfig.payment[this.getCode()].vaultCode;
      },

      initAcceptJs: function() {
        var self = this;
        var acceptjsDep = 'acceptjs';

        if (self.getIsTest() == "1") {
          acceptjsDep = 'acceptjstest';
        }
        requirejs([acceptjsDep], function (acceptjs) {
          self.acceptjs = Accept;
        });
      }
    });
  }
);
