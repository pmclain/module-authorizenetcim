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

namespace Pmclain\AuthorizenetCim\Gateway\Response;

use Magento\Payment\Gateway\Helper\ContextHelper;
use Pmclain\AuthorizenetCim\Gateway\Helper\SubjectReader;
use Magento\Payment\Gateway\Response\HandlerInterface;

class CardDetailsHandler implements HandlerInterface
{
    /** @var SubjectReader */
    protected $_subjectReader;

    public function __construct(
        SubjectReader $subjectReader
    ) {
        $this->_subjectReader = $subjectReader;
    }

    public function handle(array $subject, array $response)
    {
        $paymentDataObject = $this->_subjectReader->readPayment($subject);
        $transaction = $this->_subjectReader->readTransaction($response);
        $transaction = $transaction->getTransactionResponse();
        $payment = $paymentDataObject->getPayment();
        ContextHelper::assertOrderPayment($payment);


        $payment->setCcLast4($this->_getLast4($transaction->getAccountNumber()));
        $payment->setCcType($transaction->getAccountType());
    }

    protected function _getLast4($string)
    {
        return substr($string, strlen($string) - 4, strlen($string));
    }
}
