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

use Pmclain\AuthorizenetCim\Gateway\Request\RefundDataBuilder;
use \PHPUnit_Framework_MockObject_MockObject as MockObject;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Pmclain\AuthorizenetCim\Gateway\Helper\SubjectReader;
use Magento\Payment\Gateway\Data\PaymentDataObjectInterface;
use Magento\Payment\Model\InfoInterface;
use Pmclain\AuthorizenetCim\Model\Authorizenet\Contract\TransactionRequestTypeFactory;
use Pmclain\AuthorizenetCim\Model\Authorizenet\Contract\PaymentTypeFactory;
use Pmclain\AuthorizenetCim\Model\Authorizenet\Contract\CreditCardTypeFactory;
use net\authorize\api\contract\v1\TransactionRequestType;
use net\authorize\api\contract\v1\PaymentType;
use net\authorize\api\contract\v1\CreditCardType;

class RefundDataBuilderTest extends \PHPUnit_Framework_TestCase
{
  /** @var RefundDataBuilder */
  private $refundDataBuilder;

  /** @var SubjectReader */
  private $sujectReader;

  /** @var PaymentDataObjectInterface|MockObject */
  private $paymentDataObjectMock;

  /** @var InfoInterface|MockObject */
  private $paymentMock;

  /** @var TransactionRequestTypeFactory|MockObject */
  private $transactionRequestFactoryMock;

  /** @var PaymentTypeFactory|MockObject */
  private $paymentTypeFactoryMock;

  /** @var CreditCardTypeFactory|MockObject */
  private $creditCardFactoryMock;

  /** @var TransactionRequestType */
  private $transactionRequest;

  /** @var PaymentType */
  private $paymentType;

  /** @var CreditCardType */
  private $creditCard;

  protected function setUp()
  {
    $objectManager = new ObjectManager($this);

    $this->sujectReader = $objectManager->getObject(SubjectReader::class);

    $this->paymentDataObjectMock = $this->getMockBuilder(PaymentDataObjectInterface::class)
      ->setMethods(['getPayment'])
      ->getMockForAbstractClass();

    $this->paymentMock = $this->getMockBuilder(InfoInterface::class)
      ->setMethods(['getCcLast4','getParentTransactionId'])
      ->getMockForAbstractClass();

    $this->creditCardFactoryMock = $this->getMockBuilder(CreditCardTypeFactory::class)
      ->disableOriginalConstructor()
      ->setMethods(['create'])
      ->getMock();

    $this->paymentTypeFactoryMock = $this->getMockBuilder(CreditCardTypeFactory::class)
      ->disableOriginalConstructor()
      ->setMethods(['create'])
      ->getMock();

    $this->transactionRequestFactoryMock = $this->getMockBuilder(TransactionRequestTypeFactory::class)
      ->disableOriginalConstructor()
      ->setMethods(['create'])
      ->getMock();

    $this->creditCard = $objectManager->getObject(CreditCardType::class);

    $this->paymentType = $objectManager->getObject(PaymentType::class);

    $this->transactionRequest = $objectManager->getObject(TransactionRequestType::class);

    $this->paymentDataObjectMock->expects($this->once())
      ->method('getPayment')
      ->willReturn($this->paymentMock);

    $this->refundDataBuilder = $objectManager->getObject(RefundDataBuilder::class,
      [
        '_subjectReader' => $this->sujectReader,
        '_transactionRequestFactory' => $this->transactionRequestFactoryMock,
        '_creditCardFactory' => $this->creditCardFactoryMock,
        '_paymentFactory' => $this->paymentTypeFactoryMock,
      ]
    );
  }

  /**
   * @covers RefundDataBuilder::build
   * @expectedException \Magento\Framework\Exception\LocalizedException
   */
  public function testBuildWithoutAmout()
  {
    $subject = [
      'payment' => $this->paymentDataObjectMock
    ];

    $this->refundDataBuilder->build($subject);
  }

  public function testBuild()
  {
    $amount = '10.01';
    $ccLast4 = '1111';
    $transactionId = '123456789';

    $subject = [
      'payment' => $this->paymentDataObjectMock,
      'amount' => $amount
    ];

    $this->creditCardFactoryMock->expects($this->once())
      ->method('create')
      ->willReturn($this->creditCard);

    $this->paymentMock->expects($this->once())
      ->method('getCcLast4')
      ->willReturn($ccLast4);

    $this->paymentTypeFactoryMock->expects($this->once())
      ->method('create')
      ->willReturn($this->paymentType);

    $this->transactionRequestFactoryMock->expects($this->once())
      ->method('create')
      ->willReturn($this->transactionRequest);

    $this->paymentMock->expects($this->once())
      ->method('getParentTransactionId')
      ->willReturn($transactionId);

    $result = $this->refundDataBuilder->build($subject);
    /** @var TransactionRequestType $transactionRequest */
    $transactionRequest = $result['transaction_request'];

    $this->assertInstanceOf(TransactionRequestType::class, $transactionRequest);

    $this->assertEquals($transactionId, $transactionRequest->getRefTransId());

    $this->assertEquals($amount, $transactionRequest->getAmount());

    $this->assertEquals(
      $ccLast4,
      $transactionRequest->getPayment()->getCreditCard()->getCardNumber()
    );
  }
}