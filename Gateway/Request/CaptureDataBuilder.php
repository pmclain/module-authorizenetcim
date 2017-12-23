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

use Magento\Framework\Exception\LocalizedException;
use Pmclain\AuthorizenetCim\Gateway\Helper\SubjectReader;
use Magento\Payment\Gateway\Request\BuilderInterface;
use Magento\Payment\Helper\Formatter;
use Pmclain\AuthorizenetCim\Model\Authorizenet\Contract\TransactionRequestTypeFactory;

class CaptureDataBuilder implements BuilderInterface
{
    use Formatter;

    /** @var SubjectReader */
    protected $_subjectReader;

    /** @var TransactionRequestTypeFactory */
    protected $_transactionRequestFactory;

    public function __construct(
        SubjectReader $subjectReader,
        TransactionRequestTypeFactory $transactionRequestTypeFactory
    ) {
        $this->_subjectReader = $subjectReader;
        $this->_transactionRequestFactory = $transactionRequestTypeFactory;
    }

    public function build(array $subject)
    {
        $paymentDataObject = $this->_subjectReader->readPayment($subject);
        $payment = $paymentDataObject->getPayment();
        $transactionId = $payment->getCcTransId();

        if (!$transactionId) {
            throw new LocalizedException(__('No Authorization Transaction to capture'));
        }

        $tranactionRequest = $this->_transactionRequestFactory->create();
        $tranactionRequest->setRefTransId($transactionId);
        $tranactionRequest->setAmount($this->_subjectReader->readAmount($subject));
        $tranactionRequest->setTransactionType('priorAuthCaptureTransaction');

        return ['transaction_request' => $tranactionRequest];
    }
}
