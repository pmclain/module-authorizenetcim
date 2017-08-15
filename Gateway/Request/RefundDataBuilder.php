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

use Pmclain\AuthorizenetCim\Gateway\Helper\SubjectReader;
use Magento\Payment\Gateway\Request\BuilderInterface;
use Magento\Payment\Helper\Formatter;
use net\authorize\api\contract\v1\TransactionRequestType;
use Magento\Sales\Api\Data\TransactionInterface;

class RefundDataBuilder implements BuilderInterface
{
  use Formatter;

  /** @var SubjectReader */
  protected $_subjectReader;

  /** @var TransactionRequestType */
  protected $_transactionRequest;

  public function __construct(
    SubjectReader $subjectReader,
    TransactionRequestType $transactionRequestType
  ) {
    $this->_subjectReader = $subjectReader;
    $this->_transactionRequest = $transactionRequestType;
  }

  public function build(array $subject)
  {
    $paymentDataObject = $this->_subjectReader->readPayment($subject);
    $payment = $paymentDataObject->getPayment();
    $amount = null;

    try {
      $amount = $this->formatPrice($this->_subjectReader->readAmount($subject));
    }catch (\InvalidArgumentException $e) {
      //nothing
    }

    $txnId = str_replace(
      '-' . TransactionInterface::TYPE_CAPTURE,
      '',
      $payment->getParentTransactionId()
    );

    $this->_transactionRequest->setRefTransId($txnId);
    $this->_transactionRequest->setAmount($amount);
    $this->_transactionRequest->setTransactionType('refundTransaction');

    return ['transaction_request' => $this->_transactionRequest];
  }
}