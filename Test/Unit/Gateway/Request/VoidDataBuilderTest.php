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

use Magento\Payment\Gateway\Data\PaymentDataObjectInterface;
use Magento\Payment\Model\InfoInterface;
use net\authorize\api\contract\v1\TransactionRequestType;
use Pmclain\AuthorizenetCim\Gateway\Helper\SubjectReader;
use Pmclain\AuthorizenetCim\Gateway\Request\VoidDataBuilder;
use \PHPUnit_Framework_MockObject_MockObject as MockObject;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Pmclain\AuthorizenetCim\Model\Authorizenet\Contract\TransactionRequestTypeFactory;

class VoidDataBuilderTest extends \PHPUnit_Framework_TestCase
{
  /** @var VoidDataBuilder */
  private $voidDataBuilder;

  /** @var SubjectReader */
  private $subjectReader;

  /** @var PaymentDataObjectInterface|MockObject */
  private $paymentDataObjectMock;

  /** @var InfoInterface|MockObject */
  private $paymentMock;

  /** @var TransactionRequestTypeFactory|MockObject */
  private $transactionRequestFactoryMock;

  /** @var TransactionRequestType */
  private $transactionRequest;

  protected function setUp()
  {
    $objectManager = new ObjectManager($this);

    $this->subjectReader = $objectManager->getObject(SubjectReader::class);

    $this->paymentDataObjectMock = $this->getMockBuilder(PaymentDataObjectInterface::class)
      ->setMethods(['getPayment'])
      ->getMockForAbstractClass();

    $this->paymentMock = $this->getMockBuilder(InfoInterface::class)
      ->disableOriginalConstructor()
      ->setMethods(['getParentTransactionId','getLastTransId'])
      ->getMockForAbstractClass();

    $this->transactionRequestFactoryMock = $this->getMockBuilder(TransactionRequestTypeFactory::class)
      ->disableOriginalConstructor()
      ->setMethods(['create'])
      ->getMock();

    $this->transactionRequest = $objectManager->getObject(TransactionRequestType::class);

    $this->paymentDataObjectMock->expects($this->once())
      ->method('getPayment')
      ->willReturn($this->paymentMock);

    $this->voidDataBuilder = $objectManager->getObject(VoidDataBuilder::class,
      [
        '_subjectReader' => $this->subjectReader,
        '_transactionRequestFactory' => $this->transactionRequestFactoryMock
      ]
    );
  }

  /** @covers VoidDataBuilder::build */
  public function testBuild()
  {
    $this->paymentMock->expects($this->once())
      ->method('getParentTransactionId')
      ->willReturn('');

    $this->paymentMock->expects($this->once())
      ->method('getLastTransId')
      ->willReturn('123456789');

    $this->transactionRequestFactoryMock->expects($this->once())
      ->method('create')
      ->willReturn($this->transactionRequest);

    $result = $this->voidDataBuilder->build([
      'payment' => $this->paymentDataObjectMock
    ]);

    $this->assertInstanceOf(
      TransactionRequestType::class,
      $result['transaction_request']
    );
  }

  /** @covers VoidDataBuilder::build */
  public function testBuildWithParentTransaction()
  {
    $this->paymentMock->expects($this->once())
      ->method('getParentTransactionId')
      ->willReturn('123456789');

    $this->transactionRequestFactoryMock->expects($this->once())
      ->method('create')
      ->willReturn($this->transactionRequest);

    $result = $this->voidDataBuilder->build([
      'payment' => $this->paymentDataObjectMock
    ]);

    $this->assertInstanceOf(
      TransactionRequestType::class,
      $result['transaction_request']
    );
  }

  /**
   * @covers VoidDataBuilder::build
   * @expectedException \Magento\Framework\Exception\LocalizedException
   */
  public function testBuildWithException()
  {
    $this->paymentMock->expects($this->once())
      ->method('getParentTransactionId')
      ->willReturn('');

    $this->paymentMock->expects($this->once())
      ->method('getLastTransId')
      ->willReturn('');

    $result = $this->voidDataBuilder->build([
      'payment' => $this->paymentDataObjectMock
    ]);
  }
}