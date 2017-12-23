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

namespace Pmclain\AuthorizenetCim\Test\Unit\Gateway\Config;

use Pmclain\AuthorizenetCim\Gateway\Config\CanVoidHandler;
use \PHPUnit_Framework_MockObject_MockObject as MockObject;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Pmclain\AuthorizenetCim\Gateway\Helper\SubjectReader;
use Magento\Payment\Gateway\Data\PaymentDataObjectInterface;
use Magento\Sales\Model\Order\Payment;

class CanVoidHandlerTest extends \PHPUnit\Framework\TestCase
{
    /** @var CanVoidHandler */
    private $canVoidHandler;

    /** @var SubjectReader|MockObject */
    private $subjectReaderMock;

    /** @var PaymentDataObjectInterface|MockObject */
    private $paymentDataObjectMock;

    /** @var Payment|MockObject */
    private $paymentMock;

    protected function setUp()
    {
        $objectManager = new ObjectManager($this);

        $this->subjectReaderMock = $this->getMockBuilder(SubjectReader::class)
            ->disableOriginalConstructor()
            ->setMethods(['readPayment'])
            ->getMock();

        $this->paymentDataObjectMock = $this->getMockBuilder(PaymentDataObjectInterface::class)
            ->setMethods(['getPayment'])
            ->getMockForAbstractClass();

        $this->paymentMock = $this->getMockBuilder(Payment::class)
            ->disableOriginalConstructor()
            ->setMethods(['getAmountPaid'])
            ->getMock();

        $this->subjectReaderMock->expects($this->once())
            ->method('readPayment')
            ->willReturn($this->paymentDataObjectMock);

        $this->paymentDataObjectMock->expects($this->once())
            ->method('getPayment')
            ->willReturn($this->paymentMock);

        $this->canVoidHandler = $objectManager->getObject(
            CanVoidHandler::class,
            [
                '_subjectReader' => $this->subjectReaderMock,
            ]
        );
    }

    /** @cover CanVoidHandler::handle */
    public function testHandleCanNotVoid()
    {
        $this->paymentMock->expects($this->once())
            ->method('getAmountPaid')
            ->willReturn('10.01');

        $this->assertEquals(
            false,
            $this->canVoidHandler->handle([])
        );
    }

    /** @cover CanVoidHandler::handle */
    public function testHandleCanVoid()
    {
        $this->paymentMock->expects($this->once())
            ->method('getAmountPaid')
            ->willReturn(0);

        $this->assertEquals(
            true,
            $this->canVoidHandler->handle([])
        );
    }
}
