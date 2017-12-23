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

namespace Pmclain\AuthorizenetCim\Test\Unit\Gateway\Response;

use Pmclain\AuthorizenetCim\Gateway\Response\RefundHandler;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use \PHPUnit_Framework_MockObject_MockObject as MockObject;
use Magento\Payment\Gateway\Data\PaymentDataObjectInterface;
use Pmclain\AuthorizenetCim\Gateway\Helper\SubjectReader;
use Magento\Sales\Model\Order\Creditmemo;
use Magento\Sales\Model\Order\Invoice;
use Magento\Sales\Model\Order\Payment;
use net\authorize\api\contract\v1\CreateTransactionResponse;
use net\authorize\api\contract\v1\TransactionResponseType;

class RefundHandlerTest extends \PHPUnit\Framework\TestCase
{
    /** @var RefundHandler */
    private $refundHandler;

    /** @var SubjectReader */
    private $subjectReader;

    /** @var Creditmemo|MockObject */
    private $creditmemoMock;

    /** @var Invoice|MockObject */
    private $invoiceMock;

    /** @var PaymentDataObjectInterface|MockObject */
    private $paymentDataObjectMock;

    /** @var Payment|MockObject */
    private $paymentMock;

    /** @var CreateTransactionResponse|MockObject */
    private $createTransactionResponseMock;

    /** @var TransactionResponseType|MockObject */
    private $transactionResponseMock;

    protected function setUp()
    {
        $objectManager = new ObjectManager($this);

        $this->subjectReader = $objectManager->getObject(SubjectReader::class);

        $this->creditmemoMock = $this->getMockBuilder(Creditmemo::class)
            ->disableOriginalConstructor()
            ->setMethods(['getInvoice'])
            ->getMock();

        $this->paymentMock = $this->getMockBuilder(Payment::class)
            ->disableOriginalConstructor()
            ->setMethods([
                'getCreditmemo',
                'setShouldCloseParentTransaction',
                'setIsTransactionClosed'
            ])
            ->getMock();

        $this->invoiceMock = $this->getMockBuilder(Invoice::class)
            ->disableOriginalConstructor()
            ->setMethods(['canRefund'])
            ->getMock();

        $this->paymentDataObjectMock = $this->getMockBuilder(PaymentDataObjectInterface::class)
            ->setMethods(['getPayment'])
            ->getMockForAbstractClass();

        $this->createTransactionResponseMock = $this->getMockBuilder(CreateTransactionResponse::class)
            ->disableOriginalConstructor()
            ->setMethods(['getTransactionResponse'])
            ->getMock();

        $this->transactionResponseMock = $this->getMockBuilder(TransactionResponseType::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->paymentDataObjectMock->expects($this->any())
            ->method('getPayment')
            ->willReturn($this->paymentMock);

        $this->paymentMock->expects($this->once())
            ->method('setIsTransactionClosed')
            ->with(true);

        $this->createTransactionResponseMock->expects($this->once())
            ->method('getTransactionResponse')
            ->willReturn($this->transactionResponseMock);

        $this->paymentMock->expects($this->once())
            ->method('getCreditmemo')
            ->willReturn($this->creditmemoMock);

        $this->creditmemoMock->expects($this->once())
            ->method('getInvoice')
            ->willReturn($this->invoiceMock);

        $this->refundHandler = $objectManager->getObject(
            RefundHandler::class,
            [
                '_subjectReader' => $this->subjectReader
            ]
        );
    }

    /** @cover RefundHandler::handle */
    public function testHandleShouldCloseParentTransactionTrue()
    {
        $this->invoiceMock->expects($this->once())
            ->method('canRefund')
            ->willReturn(false);

        $this->paymentMock->expects($this->once())
            ->method('setShouldCloseParentTransaction')
            ->with(true);

        $subject = ['payment' => $this->paymentDataObjectMock];
        $response = ['object' => $this->createTransactionResponseMock];

        $this->refundHandler->handle($subject, $response);
    }

    /** @cover RefundHandler::handle */
    public function testHandleShouldCloseParentTransactionFalse()
    {
        $this->invoiceMock->expects($this->once())
            ->method('canRefund')
            ->willReturn(true);

        $this->paymentMock->expects($this->once())
            ->method('setShouldCloseParentTransaction')
            ->with(false);

        $subject = ['payment' => $this->paymentDataObjectMock];
        $response = ['object' => $this->createTransactionResponseMock];

        $this->refundHandler->handle($subject, $response);
    }
}
