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

use Magento\Framework\Exception\LocalizedException;
use Magento\Payment\Gateway\Request\BuilderInterface;
use Magento\Payment\Helper\Formatter;
use Pmclain\AuthorizenetCim\Gateway\Helper\SubjectReader;
use Pmclain\AuthorizenetCim\Model\Authorizenet\Contract\TransactionRequestTypeFactory;

class VoidDataBuilder implements BuilderInterface
{
  use Formatter;

  /** @var SubjectReader */
  protected $_subjectReader;

  /** @var TransactionRequestTypeFactory */
  protected $_transactionRequestFactory;

  public function __construct(
    SubjectReader $subjectReader,
    TransactionRequestTypeFactory $transactionRequestTypeFactory
  )
  {
    $this->_subjectReader = $subjectReader;
    $this->_transactionRequestFactory = $transactionRequestTypeFactory;
  }

  public function build(array $subject)
  {
    $paymentDataObject = $this->_subjectReader->readPayment($subject);
    $payment = $paymentDataObject->getPayment();

    $transactionId = $payment->getParentTransactionId() ?: $payment->getLastTransId();

    if(!$transactionId) {
      throw new LocalizedException(__('No Transaction to void'));
    }

    $transactionRequest = $this->_transactionRequestFactory->create();
    $transactionRequest->setRefTransId($transactionId);
    $transactionRequest->setTransactionType('voidTransaction');

    return ['transaction_request' => $transactionRequest];
  }
}