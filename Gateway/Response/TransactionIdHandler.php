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

namespace Pmclain\AuthorizenetCim\Gateway\Response;

use Pmclain\AuthorizenetCim\Gateway\Helper\SubjectReader;
use Magento\Payment\Gateway\Response\HandlerInterface;
use Magento\Sales\Model\Order\Payment;

class TransactionIdHandler implements HandlerInterface
{
    /** @var SubjectReader */
    protected $_subjectReader;

    public function __construct(
        SubjectReader $subjectReader
    ) {
        $this->_subjectReader = $subjectReader;
    }

    public function handle(array $handlingSubject, array $response)
    {
        $paymentDataObject = $this->_subjectReader->readPayment($handlingSubject);

        if ($paymentDataObject->getPayment() instanceof Payment) {
            $transaction = $this->_subjectReader->readTransaction($response);
            $transaction = $transaction->getTransactionResponse();
            $orderPayment = $paymentDataObject->getPayment();

            $this->_setTransactionId(
                $orderPayment,
                $transaction
            );

            $orderPayment->setIsTransactionClosed($this->_shouldCloseTransaction());
            $closed = $this->_shouldCloseParentTransaction($orderPayment);
            $orderPayment->setShouldCloseParentTransaction($closed);
        }
    }

    protected function _setTransactionId(Payment $orderPayment, $transaction)
    {
        $orderPayment->setTransactionId($transaction->getTransId());
    }

    protected function _shouldCloseTransaction()
    {
        return false;
    }

    protected function _shouldCloseParentTransaction(Payment $orderPayment)
    {
        return false;
    }
}
