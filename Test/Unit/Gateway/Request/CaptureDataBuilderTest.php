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

namespace Pmclain\AuthorizenetCim\Test\Unit\Gateway\Request;

use Pmclain\Authnet\TransactionRequest;
use Pmclain\Authnet\TransactionRequestFactory;
use Pmclain\AuthorizenetCim\Gateway\Request\CaptureDataBuilder;
use \PHPUnit_Framework_MockObject_MockObject as MockObject;
use Pmclain\AuthorizenetCim\Gateway\Helper\SubjectReader;
use Magento\Payment\Gateway\Data\PaymentDataObjectInterface;
use Magento\Payment\Model\InfoInterface;

class CaptureDataBuilderTest extends \PHPUnit\Framework\TestCase
{
    /** @var CaptureDataBuilder */
    private $captureDataBuilder;

    /** @var SubjectReader */
    private $subjectReader;

    /** @var TransactionRequestFactory|MockObject */
    private $transactionRequestFactoryMock;

    /** @var TransactionRequest */
    private $transactionRequest;

    /** @var PaymentDataObjectInterface|MockObject */
    private $paymentDataObjectMock;

    /** @var InfoInterface|MockObject */
    private $paymentMock;

    protected function setUp()
    {
        $this->subjectReader = new SubjectReader();

        $this->transactionRequestFactoryMock = $this->createMock(TransactionRequestFactory::class);
        $this->paymentDataObjectMock = $this->createMock(PaymentDataObjectInterface::class);
        $this->paymentMock = $this->getMockBuilder(InfoInterface::class)
            ->setMethods(['getCcTransId'])
            ->getMockForAbstractClass();

        $this->transactionRequestFactoryMock->method('create')
            ->willReturn(new TransactionRequest());

        $this->paymentDataObjectMock->method('getPayment')
            ->willReturn($this->paymentMock);

        $this->captureDataBuilder = new CaptureDataBuilder(
            $this->subjectReader,
            $this->transactionRequestFactoryMock
        );
    }

    /** @cover CaptureDataBuilder::build */
    public function testBuild()
    {
        $transId = '123456789';

        $this->paymentMock->method('getCcTransId')
            ->willReturn($transId);

        $subject = [
            'payment' => $this->paymentDataObjectMock,
            'amount' => '1.00',
        ];

        $result = $this->captureDataBuilder->build($subject);
        $resultRequest = $result[CaptureDataBuilder::TRANSACTION_REQUEST]->toArray();

        $this->assertEquals(
            $transId,
            $resultRequest[TransactionRequest::FIELD_REF_TRANS_ID]
        );
    }

    /**
     * @cover CaptureDataBuilder::build
     * @expectedException \Magento\Framework\Exception\LocalizedException
     */
    public function testBuildWithoutTransactionId()
    {
        $this->paymentMock->method('getCcTransId')
            ->willReturn('');

        $subject = [
            'payment' => $this->paymentDataObjectMock,
            'amount' => '1.00',
        ];

        $this->captureDataBuilder->build($subject);
    }
}
