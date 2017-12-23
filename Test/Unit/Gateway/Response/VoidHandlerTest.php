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

use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use \PHPUnit_Framework_MockObject_MockObject as MockObject;
use Magento\Payment\Gateway\Data\PaymentDataObjectInterface;
use Pmclain\AuthorizenetCim\Gateway\Helper\SubjectReader;
use Magento\Sales\Model\Order\Payment;
use net\authorize\api\contract\v1\CreateTransactionResponse;
use net\authorize\api\contract\v1\TransactionResponseType;
use Pmclain\AuthorizenetCim\Gateway\Response\VoidHandler;

class VoidHandlerTest extends \PHPUnit\Framework\TestCase
{
    /** @var VoidHandler */
    private $voidHandler;

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
                'setIsTransactionClosed'
            ])
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

        $this->paymentMock->expects($this->once())
            ->method('setShouldCloseParentTransaction')
            ->with(true);

        $this->createTransactionResponseMock->expects($this->once())
            ->method('getTransactionResponse')
            ->willReturn($this->transactionResponseMock);

        $this->voidHandler = $objectManager->getObject(
            VoidHandler::class,
            [
                '_subjectReader' => $this->subjectReader
            ]
        );
    }

    public function testHandle()
    {
        $subject = ['payment' => $this->paymentDataObjectMock];
        $response = ['object' => $this->createTransactionResponseMock];

        $this->voidHandler->handle($subject, $response);
    }
}
