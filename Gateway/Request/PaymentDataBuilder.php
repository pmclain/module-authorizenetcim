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
use Pmclain\Authnet\PaymentProfile\CustomerType;
use Pmclain\AuthorizenetCim\Gateway\Config\Config;
use Pmclain\AuthorizenetCim\Gateway\Helper\SubjectReader;
use Pmclain\Authnet\PaymentProfile\Payment\OpaqueDataFactory;
use Pmclain\Authnet\PaymentProfile\Payment\OpaqueData;
use Pmclain\Authnet\TransactionRequestFactory;
use Pmclain\Authnet\TransactionRequest;
use Pmclain\Authnet\PaymentProfileFactory;
use Pmclain\Authnet\PaymentProfile;
use Pmclain\Authnet\TransactionRequest\OrderFactory;
use Pmclain\Authnet\TransactionRequest\Order;
use Pmclain\AuthorizenetCim\Model\Authorizenet\Payment;

class PaymentDataBuilder implements BuilderInterface
{
    use Formatter;

    const CAPTURE = 'capture';
    const TRANSACTION_REQUEST = 'transaction_request';
    const PAYMENT = 'payment';
    const SAVE_IN_VAULT = 'save_in_vault';
    const PAYMENT_PROFILE = 'payment_profile';
    const PAYMENT_INFO = 'payment_info';
    const PAYMENT_INFO_TYPE = 'cc_type';
    const PAYMENT_INFO_LAST4 = 'cc_last4';
    const PAYMENT_INFO_EXP_MONTH = 'cc_exp_month';
    const PAYMENT_INFO_EXP_YEAR = 'cc_exp_year';

    /**
     * @var Config
     */
    protected $config;

    /**
     * @var SubjectReader
     */
    protected $subjectReader;

    /**
     * @var OpaqueDataFactory
     */
    protected $opaqueDataFactory;

    /**
     * @var TransactionRequestFactory
     */
    protected $transactionRequestFactory;

    /**
     * @var OrderFactory
     */
    protected $orderFactory;

    /**
     * @var PaymentProfileTypeFactory
     */
    protected $paymentProfileFactory;

    /**
     * @var Payment
     */
    protected $payment;

    public function __construct(
        Config $config,
        SubjectReader $subjectReader,
        OpaqueDataFactory $opaqueDataFactory,
        TransactionRequestFactory $transactionRequestFactory,
        OrderFactory $orderFactory,
        PaymentProfileFactory $paymentProfileFactory,
        Payment $payment
    ) {
        $this->config = $config;
        $this->subjectReader = $subjectReader;
        $this->opaqueDataFactory = $opaqueDataFactory;
        $this->transactionRequestFactory = $transactionRequestFactory;
        $this->orderFactory = $orderFactory;
        $this->paymentProfileFactory = $paymentProfileFactory;
        $this->payment = $payment;
    }

    public function build(array $subject)
    {
        $paymentDataObject = $this->subjectReader->readPayment($subject);
        $payment = $paymentDataObject->getPayment();
        $order = $paymentDataObject->getOrder();

        /**
         * @var OpaqueData $opaqueData
         */
        $opaqueData = $this->opaqueDataFactory->create();
        $opaqueData->setDataDescriptor('COMMON.ACCEPT.INAPP.PAYMENT');
        $opaqueData->setDataValue($payment->getAdditionalInformation('cc_token'));

        /**
         * @var PaymentProfile $paymentProfile
         */
        $paymentProfile = $this->paymentProfileFactory->create();
        $paymentProfile->setDefaultPaymentProfile(true);
        $paymentProfile->setPayment($opaqueData);
        $paymentProfile->setCustomerType(CustomerType::INDIVIDUAL);

        /**
         * @var Order $orderType
         */
        $orderType = $this->orderFactory->create();
        $orderType->setInvoiceNumber($order->getOrderIncrementId());

        /**
         * @var TransactionRequest $transactionRequest
         */
        $transactionRequest = $this->transactionRequestFactory->create();
        $transactionRequest->setTransactionType(TransactionRequest\TransactionType::TYPE_AUTH_ONLY);
        $transactionRequest->setAmount($this->formatPrice($this->subjectReader->readAmount($subject)));
        $transactionRequest->setOrder($orderType);

        $result = [
            self::CAPTURE => false,
            self::TRANSACTION_REQUEST => $transactionRequest,
            self::PAYMENT => $paymentProfile,
            self::SAVE_IN_VAULT => (bool)$payment->getAdditionalInformation('is_active_payment_token_enabler'),
            self::PAYMENT_INFO => [
                self::PAYMENT_INFO_TYPE => $payment->getAdditionalInformation('cc_type'),
                self::PAYMENT_INFO_LAST4 => $payment->getAdditionalInformation('cc_last4'),
                self::PAYMENT_INFO_EXP_MONTH => $payment->getAdditionalInformation('cc_exp_month'),
                self::PAYMENT_INFO_EXP_YEAR => $payment->getAdditionalInformation('cc_exp_year'),
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
            $result[self::PAYMENT_PROFILE] = $this->payment->getProfileId();
        }

        return $result;
    }
}
