/*browser:true*/
/*global define*/
define([
  'jquery',
  'uiComponent',
  'Magento_Ui/js/modal/alert',
  'Magento_Ui/js/lib/view/utils/dom-observer',
  'mage/translate'
], function ($, Class, alert, domObserver, $t) {
  'use strict';

  return Class.extend({

    defaults: {
      $selector: null,
      selector: 'edit_form',
      container: 'payment_form_pmclain_authorizenetcim',
      active: false,
      scriptLoaded: false,
      acceptjs: null,
      selectedCardType: null,
      imports: {
        onActiveChange: 'active'
      }
    },

    /**
     * Set list of observable attributes
     * @returns {exports.initObservable}
     */
    initObservable: function () {
      var self = this;

      self.$selector = $('#' + self.selector);
      this._super()
        .observe([
          'active',
          'scriptLoaded',
          'selectedCardType'
        ]);

      // re-init payment method events
      self.$selector.off('changePaymentMethod.' + this.code)
        .on('changePaymentMethod.' + this.code, this.changePaymentMethod.bind(this));

      // listen block changes
      domObserver.get('#' + self.container, function () {
        if (self.scriptLoaded()) {
          self.$selector.off('submit');
        }
      });

      return this;
    },

    /**
     * Enable/disable current payment method
     * @param {Object} event
     * @param {String} method
     * @returns {exports.changePaymentMethod}
     */
    changePaymentMethod: function (event, method) {
      this.active(method === this.code);

      return this;
    },

    /**
     * Triggered when payment changed
     * @param {Boolean} isActive
     */
    onActiveChange: function (isActive) {
      if (!isActive) {
        this.$selector.off('submitOrder.pmclain_authorizenetcim');

        return;
      }
      this.disableEventListeners();
      window.order.addExcludedPaymentMethod(this.code);

      if (!this.clientKey) {
        this.error($.mage.__('This payment is not available'));

        return;
      }

      this.enableEventListeners();

      if(!this.scriptLoaded()) {
        this.loadScript();
      }
    },

    loadScript: function() {
      var self = this;
      var state = self.scriptLoaded;
      var url = 'https://js.authorize.net/v1/Accept.js';

      $('body').trigger('processStart');
      if (self.test) {
        url = 'https://jstest.authorize.net/v1/Accept.js';
      }
      require([url], function () {
        state(true);
        self.acceptjs = window.Accept;
        $('body').trigger('processStop');
      });
    },

    /**
     * Show alert message
     * @param {String} message
     */
    error: function (message) {
      alert({
        content: message
      });
    },

    /**
     * Enable form event listeners
     */
    enableEventListeners: function () {
      this.$selector.on('submitOrder.pmclain_authorizenetcim', this.submitOrder.bind(this));
    },

    /**
     * Disable form event listeners
     */
    disableEventListeners: function () {
      this.$selector.off('submitOrder');
      this.$selector.off('submit');
    },

    /**
     * Trigger order submit
     */
    submitOrder: function () {
      var self = this;
      this.$selector.validate().form();
      this.$selector.trigger('afterValidate.beforeSubmit');

      // validate parent form
      if (this.$selector.validate().errorList.length) {
        $('body').trigger('processStop');
        return false;
      }

      $.when(this.createToken()).done(function(result) {
        $('body').trigger('processStop');
        var container = $('#' + self.container);

        container.find('#' + self.code + '_cc_last4').val(container.find('#' + self.code + '_cc_number').val().slice(-4));
        container.find('#' + self.code + '_cc_number').val('');
        container.find('[type="submit"]').trigger('click');
      }).fail(function(result) {
        $('body').trigger('processStop');
        self.error(result);

        return false;
      });
    },

    /**
     * Convert card information to acceptjs token
     */
    createToken: function() {
      var self = this;
      var container = $('#' + this.container);
      var secureData = {};
      var authData = {};

      var cardData = {
        cardNumber: container.find('#' + this.code + '_cc_number').val(),
        month: container.find('#' + this.code + '_expiration').val(),
        year: container.find('#' + this.code + '_expiration_yr').val(),
        cardCode: container.find('#' + this.code + '_cc_cid').val()
      };
      secureData.cardData = cardData;

      authData.clientKey = this.clientKey;
      authData.apiLoginID = this.apiLoginId;
      secureData.authData = authData;

      var defer = $.Deferred();

      this.acceptjs.dispatchData(secureData, function(response) {
        if (response.messages.resultCode === "Error") {
          defer.reject(response.messages.message);
        }else {
          $('#' + self.container).find('#' + self.code + '_cc_token').val(response.opaqueData.dataValue);
          defer.resolve(response);
        }
      });

      return defer.promise();
    },

    /**
     * Place order
     */
    placeOrder: function () {
      $('#' + this.selector).trigger('realOrder');
    },

    /**
     * Get list of currently available card types
     * @returns {Array}
     */
    getCcAvailableTypes: function () {
      var types = [],
        $options = $(this.getSelector('cc_type')).find('option');

      $.map($options, function (option) {
        types.push($(option).val());
      });

      return types;
    },

    /**
     * Get jQuery selector
     * @param {String} field
     * @returns {String}
     */
    getSelector: function (field) {
      return '#' + this.code + '_' + field;
    }
  });
});