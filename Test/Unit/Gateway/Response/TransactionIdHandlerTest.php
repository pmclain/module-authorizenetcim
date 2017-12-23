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

use Pmclain\AuthorizenetCim\Gateway\Response\TransactionIdHandler;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use \PHPUnit_Framework_MockObject_MockObject as MockObject;
use Magento\Payment\Gateway\Data\PaymentDataObjectInterface;
use Pmclain\AuthorizenetCim\Gateway\Helper\SubjectReader;
use Magento\Sales\Model\Order\Payment;
use net\authorize\api\contract\v1\CreateTransactionResponse;
use net\authorize\api\contract\v1\TransactionResponseType;

class TransactionIdHandlerTest extends \PHPUnit\Framework\TestCase
{
    /** @var TransactionIdHandler */
    private $transactionIdHandler;

    /** @var SubjectReader */
    private $subjectReader;

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

        $this->paymentMock = $this->getMockBuilder(Payment::class)
            ->disableOriginalConstructor()
            ->setMethods([
                'getCreditmemo',
                'setShouldCloseParentTransaction',
                'setIsTransactionClosed',
                'setTransactionId'
            ])->getMock();

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
            ->with(false);

        $this->paymentMock->expects($this->once())
            ->method('setShouldCloseParentTransaction')
            ->with(false);

        $transId = '123456789';

        $this->paymentMock->expects($this->once())
            ->method('setTransactionId')
            ->with($transId);

        $this->transactionResponseMock->expects($this->once())
            ->method('getTransId')
            ->willReturn($transId);

        $this->createTransactionResponseMock->expects($this->once())
            ->method('getTransactionResponse')
            ->willReturn($this->transactionResponseMock);

        $this->transactionIdHandler = $objectManager->getObject(
            TransactionIdHandler::class,
            [
                '_subjectReader' => $this->subjectReader
            ]
        );
    }

    public function testHandle()
    {
        $subject = ['payment' => $this->paymentDataObjectMock];
        $response = ['object' => $this->createTransactionResponseMock];

        $this->transactionIdHandler->handle($subject, $response);
    }
}
