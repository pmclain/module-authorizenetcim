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
use Magento\Payment\Gateway\Request\BuilderInterface;
use Magento\Payment\Helper\Formatter;
use Pmclain\AuthorizenetCim\Gateway\Helper\SubjectReader;
use Pmclain\Authnet\TransactionRequestFactory;
use Pmclain\Authnet\TransactionRequest;

class VoidDataBuilder implements BuilderInterface
{
    use Formatter;

    /**
     * @var SubjectReader
     */
    protected $subjectReader;

    /**
     * @var TransactionRequestFactory
     */
    protected $transactionRequestFactory;

    public function __construct(
        SubjectReader $subjectReader,
        TransactionRequestFactory $transactionRequestFactory
    ) {
        $this->subjectReader = $subjectReader;
        $this->transactionRequestFactory = $transactionRequestFactory;
    }

    public function build(array $subject)
    {
        $paymentDataObject = $this->subjectReader->readPayment($subject);
        $payment = $paymentDataObject->getPayment();

        $transactionId = $payment->getParentTransactionId() ?: $payment->getLastTransId();

        if (!$transactionId) {
            throw new LocalizedException(__('No Transaction to void'));
        }

        /**
         * @var TransactionRequest $transactionRequest
         */
        $transactionRequest = $this->transactionRequestFactory->create();
        $transactionRequest->setRefTransId($transactionId);
        $transactionRequest->setTransactionType(TransactionRequest\TransactionType::TYPE_VOID);

        return [PaymentDataBuilder::TRANSACTION_REQUEST => $transactionRequest];
    }
}
