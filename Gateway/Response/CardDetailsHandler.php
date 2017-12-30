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
        $transaction = $transaction->getData('transactionResponse');
        $payment = $paymentDataObject->getPayment();
        ContextHelper::assertOrderPayment($payment);

        $payment->setCcLast4($this->_getLast4($transaction->getData('accountNumber')));
        $payment->setCcType($transaction->getData('accountType'));
    }

    protected function _getLast4($string)
    {
        return substr($string, strlen($string) - 4, strlen($string));
    }
}
