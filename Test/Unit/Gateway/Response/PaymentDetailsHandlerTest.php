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

namespace Pmclain\AuthorizenetCim\Test\Unit\Gateway\Response;

use Pmclain\AuthorizenetCim\Gateway\Response\PaymentDetailsHandler;
use \PHPUnit_Framework_MockObject_MockObject as MockObject;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Pmclain\AuthorizenetCim\Gateway\Helper\SubjectReader;
use Magento\Payment\Gateway\Data\PaymentDataObjectInterface;
use Magento\Sales\Model\Order\Payment;
use net\authorize\api\contract\v1\CreateTransactionResponse;
use net\authorize\api\contract\v1\TransactionResponseType;

class PaymentDetailsHandlerTest extends \PHPUnit\Framework\TestCase
{
    /** @var PaymentDetailsHandler */
    private $paymentDetailsHandler;

    /** @var SubjectReader */
    private $subjectReader;

    /** @var PaymentDataObjectInterface|MockObject */
    private $paymentDataObjectMock;

    /** @var CreateTransactionResponse|MockObject */
    private $createTransactionResponseMock;

    /** @var TransactionResponseType|MockObject */
    private $transactionResponseMock;

    /** @var Payment|MockObject */
    private $paymentMock;

    protected function setUp()
    {
        $objectManager = new ObjectManager($this);

        $this->subjectReader = $objectManager->getObject(SubjectReader::class);

        $this->paymentDataObjectMock = $this->getMockBuilder(PaymentDataObjectInterface::class)
            ->setMethods(['getPayment'])
            ->getMockForAbstractClass();

        $this->createTransactionResponseMock = $this->getMockBuilder(CreateTransactionResponse::class)
            ->disableOriginalConstructor()
            ->setMethods(['getTransactionResponse'])
            ->getMock();

        $this->transactionResponseMock = $this->getMockBuilder(TransactionResponseType::class)
            ->disableOriginalConstructor()
            ->setMethods(
                [
                    'getTransId',
                    'getAuthCode',
                    'getAvsResultCode',
                    'getCavvResultCode',
                    'getCvvResultCode'
                ]
            )->getMock();

        $this->paymentMock = $this->getMockBuilder(Payment::class)
            ->disableOriginalConstructor()
            ->setMethods([
                'setCcTransId',
                'setLastTransId',
                'setAdditionalInformation'
            ])
            ->getMock();

        $this->paymentDetailsHandler = $objectManager->getObject(
            PaymentDetailsHandler::class,
            [
                '_subjectReader' => $this->subjectReader
            ]
        );
    }

    public function testHandle()
    {
        $additionalInformation = [
            'auth_code' => 'abc123',
            'avs_code' => '2',
            'cavv_code' => 'Y',
            'cvv_code' => 'T'
        ];
        $transactionId = '123456789';

        $this->createTransactionResponseMock->expects($this->once())
            ->method('getTransactionResponse')
            ->willReturn($this->transactionResponseMock);

        $this->paymentDataObjectMock->expects($this->once())
            ->method('getPayment')
            ->willReturn($this->paymentMock);

        $this->transactionResponseMock->expects($this->exactly(2))
            ->method('getTransId')
            ->willReturn($transactionId);

        $this->transactionResponseMock->expects($this->once())
            ->method('getAuthCode')
            ->willReturn($additionalInformation['auth_code']);

        $this->transactionResponseMock->expects($this->once())
            ->method('getAvsResultCode')
            ->willReturn($additionalInformation['avs_code']);

        $this->transactionResponseMock->expects($this->once())
            ->method('getCavvResultCode')
            ->willReturn($additionalInformation['cavv_code']);

        $this->transactionResponseMock->expects($this->once())
            ->method('getCvvResultCode')
            ->willReturn($additionalInformation['cvv_code']);

        $this->paymentMock->expects($this->once())
            ->method('setCcTransId')
            ->with($transactionId);

        $this->paymentMock->expects($this->once())
            ->method('setLastTransId')
            ->with($transactionId);

        $subject = ['payment' => $this->paymentDataObjectMock];
        $response = ['object' => $this->createTransactionResponseMock];

        $this->paymentDetailsHandler->handle($subject, $response);
    }

    /**
     * @cover CardDetailsHandler::handle
     * @expectedException \InvalidArgumentException
     */
    public function testHandleWithoutTransactionObject()
    {
        $subject = ['payment' => $this->paymentDataObjectMock];
        $response = ['object' => 'string'];

        $this->paymentDetailsHandler->handle($subject, $response);
    }
}
