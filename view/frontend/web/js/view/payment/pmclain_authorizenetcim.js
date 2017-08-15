define(
  [
    'uiComponent',
    'Magento_Checkout/js/model/payment/renderer-list'
  ],
  function (
    Component,
    rendererList
  ) {
    'use strict';
    rendererList.push(
      {
        type: 'pmclain_authorizenetcim',
        component: 'Pmclain_AuthorizenetCim/js/view/payment/method-renderer/pmclain_authorizenetcim'
      }
    );
    /** Add view logic here if needed */
    return Component.extend({});
  }
);