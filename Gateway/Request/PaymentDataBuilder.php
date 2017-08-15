<?php
/**
 * Pmclain_AuthorizenetCim extension
 * NOTICE OF LICENSE
 *
 * This source file is subject to the GPL v3 License
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * https://www.gnu.org/licenses/gpl.txt
 *
 * @category  Pmclain
 * @package   Pmclain_AuthorizenetCim
 * @copyright Copyright (c) 2017
 * @license   https://www.gnu.org/licenses/gpl.txt GPL v3 License
 */

namespace Pmclain\AuthorizenetCim\Gateway\Request;

use Magento\Payment\Gateway\Request\BuilderInterface;
use Magento\Payment\Helper\Formatter;
use Pmclain\AuthorizenetCim\Gateway\Config\Config;
use Pmclain\AuthorizenetCim\Gateway\Helper\SubjectReader;
use net\authorize\api\contract\v1\OpaqueDataType;
use net\authorize\api\contract\v1\PaymentType;
use net\authorize\api\contract\v1\TransactionRequestType;
use net\authorize\api\contract\v1\OrderType;
use net\authorize\api\contract\v1\CustomerPaymentProfileType;

class PaymentDataBuilder implements BuilderInterface
{
  use Formatter;

  /** @var Config */
  protected $_config;

  /** @var SubjectReader */
  protected $_subjectReader;

  /** @var OpaqueDataType */
  protected $_opaqueData;

  /** @var PaymentType */
  protected $_payment;

  /** @var TransactionRequestType */
  protected $_transactionRequest;

  /** @var OrderType */
  protected $_order;

  /** @var CustomerPaymentProfileType */
  protected $_paymentProfile;

  public function __construct(
    Config $config,
    SubjectReader $subjectReader,
    OpaqueDataType $opaqueDataType,
    PaymentType $paymentType,
    TransactionRequestType $transactionRequestType,
    OrderType $orderType,
    CustomerPaymentProfileType $customerPaymentProfileType
  ) {
    $this->_config = $config;
    $this->_subjectReader = $subjectReader;
    $this->_opaqueData = $opaqueDataType;
    $this->_payment = $paymentType;
    $this->_transactionRequest = $transactionRequestType;
    $this->_order = $orderType;
    $this->_paymentProfile = $customerPaymentProfileType;
  }

  public function build(array $subject)
  {
    $paymentDataObject = $this->_subjectReader->readPayment($subject);
    $payment = $paymentDataObject->getPayment();
    $order = $paymentDataObject->getOrder();

    $this->_opaqueData->setDataDescriptor('COMMON.ACCEPT.INAPP.PAYMENT'); //TODO: this should probably pass from the acceptjs response
    $this->_opaqueData->setDataValue($payment->getAdditionalInformation('cc_token'));

    $this->_payment->setOpaqueData($this->_opaqueData);

    $this->_paymentProfile->setDefaultPaymentProfile(true);
    $this->_paymentProfile->setPayment($this->_payment);
    //TODO: should this check the address for company name for determining type?
    $this->_paymentProfile->setCustomerType('individual');

    $this->_order->setInvoiceNumber($order->getOrderIncrementId());

    //TODO: transaction types should be constants somewhere.
    $this->_transactionRequest->setTransactionType('authOnlyTransaction');
    $this->_transactionRequest->setAmount($this->formatPrice($this->_subjectReader->readAmount($subject)));
    $this->_transactionRequest->setCurrencyCode($this->_config->getCurrency());
    $this->_transactionRequest->setOrder($this->_order);

    return [
      'capture' => false,
      'transaction_request' => $this->_transactionRequest,
      'payment' => $this->_paymentProfile,
      'save_in_vault' => (bool)$payment->getAdditionalInformation('is_active_payment_token_enabler'),
      'guest_description' => $order->getOrderIncrementId() . '_' . mt_rand() . '-' . time(),
      'payment_info' => [
        'cc_type' => $payment->getAdditionalInformation('cc_type'),
        'cc_last4' => $payment->getAdditionalInformation('cc_last4'),
        'cc_exp_month' => $payment->getAdditionalInformation('cc_exp_month'),
        'cc_exp_year' => $payment->getAdditionalInformation('cc_exp_year')
      ],
    ];
  }
}