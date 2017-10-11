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

use Pmclain\AuthorizenetCim\Gateway\Response\CardDetailsHandler;
use \PHPUnit_Framework_MockObject_MockObject as MockObject;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Pmclain\AuthorizenetCim\Gateway\Helper\SubjectReader;
use Magento\Payment\Gateway\Data\PaymentDataObjectInterface;
use Magento\Sales\Model\Order\Payment;
use net\authorize\api\contract\v1\CreateTransactionResponse;
use net\authorize\api\contract\v1\TransactionResponseType;

class CardDetailsHandlerTest extends \PHPUnit\Framework\TestCase
{
  /** @var CardDetailsHandler */
  private $cardDetailsHandler;

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
      ->setMethods(['getAccountNumber','getAccountType'])
      ->getMock();

    $this->paymentMock = $this->getMockBuilder(Payment::class)
      ->disableOriginalConstructor()
      ->setMethods(['setCcLast4','setCcType'])
      ->getMock();

    $this->cardDetailsHandler = $objectManager->getObject(CardDetailsHandler::class,
      [
        '_subjectReader' => $this->subjectReader
      ]
    );
  }

  public function testHandle()
  {
    $accountNumber = 'XXXXXX1111';
    $accountType = 'VI';

    $this->createTransactionResponseMock->expects($this->once())
      ->method('getTransactionResponse')
      ->willReturn($this->transactionResponseMock);

    $this->paymentDataObjectMock->expects($this->once())
      ->method('getPayment')
      ->willReturn($this->paymentMock);

    $this->transactionResponseMock->expects($this->once())
      ->method('getAccountNumber')
      ->willReturn($accountNumber);

    $this->transactionResponseMock->expects($this->once())
      ->method('getAccountType')
      ->willReturn($accountType);

    $this->paymentMock->expects($this->once())
      ->method('setCcLast4')
      ->with('1111');

    $this->paymentMock->expects($this->once())
      ->method('setCcType')
      ->with($accountType);

    $subject = ['payment' => $this->paymentDataObjectMock];
    $response = ['object' => $this->createTransactionResponseMock];

    $this->cardDetailsHandler->handle($subject, $response);
  }

  /**
   * @cover CardDetailsHandler::handle
   * @expectedException \InvalidArgumentException
   */
  public function testHandleWithoutTransactionObject()
  {
    $subject = ['payment' => $this->paymentDataObjectMock];
    $response = ['object' => 'string'];

    $this->cardDetailsHandler->handle($subject, $response);
  }
}