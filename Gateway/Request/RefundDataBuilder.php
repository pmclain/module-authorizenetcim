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

use Magento\Payment\Gateway\Request\BuilderInterface;
use Magento\Payment\Helper\Formatter;
use Magento\Framework\Exception\LocalizedException;
use Pmclain\AuthorizenetCim\Gateway\Helper\SubjectReader;
use Pmclain\Authnet\TransactionRequestFactory;
use Pmclain\Authnet\TransactionRequest;
use Pmclain\Authnet\PaymentProfile\Payment\CreditCardFactory;
use Pmclain\Authnet\PaymentProfile\Payment\CreditCard;

class RefundDataBuilder implements BuilderInterface
{
    use Formatter;

    const TRANSACTION_REQUEST = 'transaction_request';
    const REFUND_EXPIRATION = 'XXXX';

    /**
     * @var SubjectReader
     */
    protected $subjectReader;

    /**
     * @var TransactionRequestFactory
     */
    protected $transactionRequestFactory;

    /**
     * @var CreditCardFactory
     */
    protected $creditCardFactory;

    public function __construct(
        SubjectReader $subjectReader,
        TransactionRequestFactory $transactionRequestFactory,
        CreditCardFactory $creditCardFactory
    ) {
        $this->subjectReader = $subjectReader;
        $this->transactionRequestFactory = $transactionRequestFactory;
        $this->creditCardFactory = $creditCardFactory;
    }

    public function build(array $subject)
    {
        $paymentDataObject = $this->subjectReader->readPayment($subject);
        $payment = $paymentDataObject->getPayment();
        $amount = null;

        try {
            $amount = $this->formatPrice($this->subjectReader->readAmount($subject));
        } catch (\InvalidArgumentException $e) {
            throw new LocalizedException(__($e->getMessage()));
        }

        /**
         * @var CreditCard $creditCard
         */
        $creditCard = $this->creditCardFactory->create();
        $creditCard->setCardNumber($payment->getCcLast4());
        $creditCard->setExpirationDate(self::REFUND_EXPIRATION);

        /**
         * @var TransactionRequest $transactionRequest
         */
        $transactionRequest = $this->transactionRequestFactory->create();
        $transactionRequest->setRefTransId($payment->getParentTransactionId());
        $transactionRequest->setAmount($amount);
        $transactionRequest->setPayment($creditCard);
        $transactionRequest->setTransactionType(TransactionRequest\TransactionType::TYPE_REFUND);

        return [self::TRANSACTION_REQUEST => $transactionRequest];
    }
}
