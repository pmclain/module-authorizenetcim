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
use Magento\Framework\Exception\LocalizedException;
use Pmclain\AuthorizenetCim\Gateway\Helper\SubjectReader;
use Pmclain\AuthorizenetCim\Model\Authorizenet\Contract\TransactionRequestTypeFactory;
use Pmclain\AuthorizenetCim\Model\Authorizenet\Contract\PaymentTypeFactory;
use Pmclain\AuthorizenetCim\Model\Authorizenet\Contract\CreditCardTypeFactory;

class RefundDataBuilder implements BuilderInterface
{
  use Formatter;

  /** @var SubjectReader */
  protected $_subjectReader;

  /** @var TransactionRequestTypeFactory */
  protected $_transactionRequestFactory;

  /** @var CreditCardTypeFactory */
  protected $_creditCardFactory;

  /** @var PaymentTypeFactory */
  protected $_paymentFactory;

  public function __construct(
    SubjectReader $subjectReader,
    TransactionRequestTypeFactory $transactionRequestTypeFactory,
    CreditCardTypeFactory $creditCardTypeFactory,
    PaymentTypeFactory $paymentTypeFactory
  ) {
    $this->_subjectReader = $subjectReader;
    $this->_transactionRequestFactory = $transactionRequestTypeFactory;
    $this->_creditCardFactory = $creditCardTypeFactory;
    $this->_paymentFactory = $paymentTypeFactory;
  }

  public function build(array $subject)
  {
    $paymentDataObject = $this->_subjectReader->readPayment($subject);
    $payment = $paymentDataObject->getPayment();
    $amount = null;

    try {
      $amount = $this->formatPrice($this->_subjectReader->readAmount($subject));
    }catch (\InvalidArgumentException $e) {
      throw new LocalizedException(__($e->getMessage()));
    }

    $creditCard = $this->_creditCardFactory->create();
    $creditCard->setCardNumber($payment->getCcLast4());
    $creditCard->setExpirationDate('XXXX');

    $paymentType = $this->_paymentFactory->create();
    $paymentType->setCreditCard($creditCard);

    $transactionRequest = $this->_transactionRequestFactory->create();
    $transactionRequest->setRefTransId($payment->getParentTransactionId());
    $transactionRequest->setAmount($amount);
    $transactionRequest->setPayment($paymentType);
    $transactionRequest->setTransactionType('refundTransaction');

    return ['transaction_request' => $transactionRequest];
  }
}