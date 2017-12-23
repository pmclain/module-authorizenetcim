<?php
/**
 * Pmclain_AuthorizenetCim extension
 * NOTICE OF LICENSE
 *
 * This source file is subject to the OSL 3.0 License
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * https://opensource.org/licenses/osl-3.0.php
 *
 * @category  Pmclain
 * @package   Pmclain_AuthorizenetCim
 * @copyright Copyright (c) 2017-2018
 * @license   Open Software License (OSL 3.0)
 */

namespace Pmclain\AuthorizenetCim\Gateway\Request;

use Magento\Payment\Gateway\Request\BuilderInterface;
use Magento\Payment\Helper\Formatter;
use Pmclain\AuthorizenetCim\Gateway\Config\Config;
use Pmclain\AuthorizenetCim\Gateway\Helper\SubjectReader;
use Pmclain\AuthorizenetCim\Model\Authorizenet\Contract\OpaqueDataTypeFactory;
use Pmclain\AuthorizenetCim\Model\Authorizenet\Contract\PaymentTypeFactory;
use Pmclain\AuthorizenetCim\Model\Authorizenet\Contract\TransactionRequestTypeFactory;
use Pmclain\AuthorizenetCim\Model\Authorizenet\Contract\OrderTypeFactory;
use Pmclain\AuthorizenetCim\Model\Authorizenet\Contract\CustomerPaymentProfileTypeFactory;
use Pmclain\AuthorizenetCim\Model\Authorizenet\Payment;

class PaymentDataBuilder implements BuilderInterface
{
    use Formatter;

    /** @var Config */
    protected $_config;

    /** @var SubjectReader */
    protected $_subjectReader;

    /** @var OpaqueDataTypeFactory */
    protected $_opaqueDataFactory;

    /** @var PaymentTypeFactory */
    protected $_paymentFactory;

    /** @var TransactionRequestTypeFactory */
    protected $_transactionRequestFactory;

    /** @var OrderTypeFactory */
    protected $_orderFactory;

    /** @var CustomerPaymentProfileTypeFactory */
    protected $_paymentProfileFactory;

    /** @var Payment */
    protected $payment;

    public function __construct(
        Config $config,
        SubjectReader $subjectReader,
        OpaqueDataTypeFactory $opaqueDataTypeFactory,
        PaymentTypeFactory $paymentTypeFactory,
        TransactionRequestTypeFactory $transactionRequestTypeFactory,
        OrderTypeFactory $orderTypeFactory,
        CustomerPaymentProfileTypeFactory $customerPaymentProfileTypeFactory,
        Payment $payment
    ) {
        $this->_config = $config;
        $this->_subjectReader = $subjectReader;
        $this->_opaqueDataFactory = $opaqueDataTypeFactory;
        $this->_paymentFactory = $paymentTypeFactory;
        $this->_transactionRequestFactory = $transactionRequestTypeFactory;
        $this->_orderFactory = $orderTypeFactory;
        $this->_paymentProfileFactory = $customerPaymentProfileTypeFactory;
        $this->payment = $payment;
    }

    public function build(array $subject)
    {
        $paymentDataObject = $this->_subjectReader->readPayment($subject);
        $payment = $paymentDataObject->getPayment();
        $order = $paymentDataObject->getOrder();

        $opaqueData = $this->_opaqueDataFactory->create();
        $opaqueData->setDataDescriptor('COMMON.ACCEPT.INAPP.PAYMENT'); //TODO: this should probably pass from the acceptjs response
        $opaqueData->setDataValue($payment->getAdditionalInformation('cc_token'));

        $paymentType = $this->_paymentFactory->create();
        $paymentType->setOpaqueData($opaqueData);

        $paymentProfile = $this->_paymentProfileFactory->create();
        $paymentProfile->setDefaultPaymentProfile(true);
        $paymentProfile->setPayment($paymentType);
        //TODO: should this check the address for company name for determining type?
        $paymentProfile->setCustomerType('individual');

        $orderType = $this->_orderFactory->create();
        $orderType->setInvoiceNumber($order->getOrderIncrementId());

        //TODO: transaction types should be constants somewhere.
        $transactionRequest = $this->_transactionRequestFactory->create();
        $transactionRequest->setTransactionType('authOnlyTransaction');
        $transactionRequest->setAmount($this->formatPrice($this->_subjectReader->readAmount($subject)));
        $transactionRequest->setCurrencyCode($this->_config->getCurrency());
        $transactionRequest->setOrder($orderType);

        $result = [
            'capture' => false,
            'transaction_request' => $transactionRequest,
            'payment' => $paymentProfile,
            'save_in_vault' => (bool)$payment->getAdditionalInformation('is_active_payment_token_enabler'),
            'guest_description' => $order->getOrderIncrementId() . '_' . mt_rand() . '-' . time(),
            'payment_info' => [
                'cc_type' => $payment->getAdditionalInformation('cc_type'),
                'cc_last4' => $payment->getAdditionalInformation('cc_last4'),
                'cc_exp_month' => $payment->getAdditionalInformation('cc_exp_month'),
                'cc_exp_year' => $payment->getAdditionalInformation('cc_exp_year'),
            ],
        ];

        /**
         * after opaque payment data has been converted to payment profile id it is stored
         * in a singleton object. if that object has a profile id set it should be used.
         * this creates support for multiple shipping addresses, since the purchase is split
         * into a separate order for each address resulting in multiple authorization requests
         * being sent to the merchant processor.
         */
        if ($this->payment->getProfileId()) {
            $result['payment_profile'] = $this->payment->getProfileId();
        }

        return $result;
    }
}
