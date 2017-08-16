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

namespace Pmclain\AuthorizenetCim\Test\Unit\Gateway\Request;

use Pmclain\AuthorizenetCim\Gateway\Request\CaptureDataBuilder;
use \PHPUnit_Framework_MockObject_MockObject as MockObject;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Pmclain\AuthorizenetCim\Gateway\Helper\SubjectReader;
use Pmclain\AuthorizenetCim\Model\Authorizenet\Contract\TransactionRequestTypeFactory;
use net\authorize\api\contract\v1\TransactionRequestType;
use Magento\Payment\Gateway\Data\PaymentDataObjectInterface;
use Magento\Payment\Model\InfoInterface;

class CaptureDataBuilderTest extends \PHPUnit_Framework_TestCase
{
  /** @var CaptureDataBuilder */
  private $captureDataBuilder;

  /** @var SubjectReader */
  private $subjectReader;

  /** @var TransactionRequestTypeFactory|MockObject */
  private $transactionRequestFactoryMock;

  /** @var TransactionRequestType */
  private $transactionRequest;

  /** @var PaymentDataObjectInterface|MockObject */
  private $paymentDataObjectMock;

  /** @var InfoInterface|MockObject */
  private $paymentMock;

  protected function setUp()
  {
    $objectManager = new ObjectManager($this);

    $this->subjectReader = $objectManager->getObject(SubjectReader::class);

    $this->transactionRequestFactoryMock = $this->getMockBuilder(TransactionRequestTypeFactory::class)
      ->disableOriginalConstructor()
      ->setMethods(['create'])
      ->getMock();

    $this->transactionRequest = $objectManager->getObject(TransactionRequestType::class);

    $this->paymentDataObjectMock = $this->getMockBuilder(PaymentDataObjectInterface::class)
      ->setMethods(['getPayment'])
      ->getMockForAbstractClass();

    $this->paymentMock = $this->getMockBuilder(InfoInterface::class)
      ->disableOriginalConstructor()
      ->setMethods(['getCcTransId'])
      ->getMockForAbstractClass();

    $this->paymentDataObjectMock->expects($this->once())
      ->method('getPayment')
      ->willReturn($this->paymentMock);

    $this->captureDataBuilder = $objectManager->getObject(CaptureDataBuilder::class,
      [
        '_subjectReader' => $this->subjectReader,
        '_transactionRequestFactory' => $this->transactionRequestFactoryMock
      ]
    );
  }

  /** @covers CaptureDataBuilder::build */
  public function testBuild()
  {
    $this->paymentMock->expects($this->once())
      ->method('getCcTransId')
      ->willReturn('123456789');

    $this->transactionRequestFactoryMock->expects($this->once())
      ->method('create')
      ->willReturn($this->transactionRequest);

    $subject = [
      'payment' => $this->paymentDataObjectMock,
      'amount' => '1.00',
    ];

    $result = $this->captureDataBuilder->build($subject);

    $this->assertInstanceOf(
      TransactionRequestType::class,
      $result['transaction_request']
    );
  }

  /**
   * @covers CaptureDataBuilder::build
   * @expectedException \Magento\Framework\Exception\LocalizedException
   */
  public function testBuildWithoutTransactionId()
  {
    $this->paymentMock->expects($this->once())
      ->method('getCcTransId')
      ->willReturn('');

    $subject = [
      'payment' => $this->paymentDataObjectMock,
      'amount' => '1.00',
    ];

    $this->captureDataBuilder->build($subject);
  }
}