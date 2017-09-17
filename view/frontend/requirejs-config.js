var config = {
  map: {
    '*': {
      acceptjs: 'https://js.authorize.net/v1/Accept.js',
      acceptjstest: 'https://jstest.authorize.net/v1/Accept.js'
    }
  },
  config: {
    mixins: {
      'Magento_Multishipping/js/payment': {
        'Pmclain_AuthorizenetCim/js/payment-mixin': true
      }
    }
  }
};