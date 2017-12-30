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

use Magento\Payment\Gateway\Response\HandlerInterface;
use Pmclain\AuthorizenetCim\Gateway\Helper\SubjectReader;

class PaymentDetailsHandler implements HandlerInterface
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
        $transaction = $this->_subjectReader->readTransaction($response);
        $transaction = $transaction->getData('transactionResponse');
        $payment = $paymentDataObject->getPayment();

        $payment->setCcTransId($transaction->getData('transId'));
        $payment->setLastTransId($transaction->getData('transId'));

        $additionalInformation = [
            'auth_code' => $transaction->getData('authCode'),
            'avs_code' => $transaction->getData('avsResultCode'),
            'cavv_code' => $transaction->getData('cavvResultCode'),
            'cvv_code' => $transaction->getData('cvvResultCode')
        ];

        foreach ($additionalInformation as $key => $value) {
            $payment->setAdditionalInformation($key, $value);
        }
    }
}
