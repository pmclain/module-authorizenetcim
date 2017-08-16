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

use net\authorize\api\contract\v1\CreditCardType;
use Pmclain\AuthorizenetCim\Gateway\Helper\SubjectReader;
use Magento\Payment\Gateway\Request\BuilderInterface;
use Magento\Payment\Helper\Formatter;
use net\authorize\api\contract\v1\TransactionRequestType;
use net\authorize\api\contract\v1\PaymentType;
use Magento\Framework\Exception\LocalizedException;

class RefundDataBuilder implements BuilderInterface
{
  use Formatter;

  /** @var SubjectReader */
  protected $_subjectReader;

  /** @var TransactionRequestType */
  protected $_transactionRequest;

  /** @var CreditCardType */
  protected $_creditCard;

  /** @var PaymentType */
  protected $_payment;

  public function __construct(
    SubjectReader $subjectReader,
    TransactionRequestType $transactionRequestType,
    CreditCardType $creditCardType,
    PaymentType $paymentType
  ) {
    $this->_subjectReader = $subjectReader;
    $this->_transactionRequest = $transactionRequestType;
    $this->_creditCard = $creditCardType;
    $this->_payment = $paymentType;
  }

  public function build(array $subject)
  {
    $paymentDataObject = $this->_subjectReader->readPayment($subject);
    $payment = $paymentDataObject->getPayment();
    $amount = null;

    try {
      $amount = $this->formatPrice($this->_subjectReader->readAmount($subject));
    }catch (\InvalidArgumentException $e) {
      throw new LocalizedException(__($e->getMessage());
    }

    $this->_creditCard->setCardNumber($payment->getCcLast4());
    $this->_creditCard->setExpirationDate('XXXX');
    $this->_payment->setCreditCard($this->_creditCard);

    $this->_transactionRequest->setRefTransId($payment->getParentTransactionId());
    $this->_transactionRequest->setAmount($amount);
    $this->_transactionRequest->setPayment($this->_payment);
    $this->_transactionRequest->setTransactionType('refundTransaction');

    return ['transaction_request' => $this->_transactionRequest];
  }
}