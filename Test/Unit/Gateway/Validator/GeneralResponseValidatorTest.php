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

namespace Pmclain\AuthorizenetCim\Test\Unit\Gateway\Validator;

use Pmclain\AuthorizenetCim\Gateway\Validator\GeneralResponseValidator;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use \PHPUnit_Framework_MockObject_MockObject as MockObject;
use Pmclain\AuthorizenetCim\Gateway\Helper\SubjectReader;
use net\authorize\api\contract\v1\AnetApiResponseType;
use net\authorize\api\contract\v1\MessagesType;
use net\authorize\api\contract\v1\TransactionResponseType\MessagesAType\MessageAType;
use Magento\Payment\Gateway\Validator\ResultInterfaceFactory;

class GeneralResponseValidatorTest extends \PHPUnit_Framework_TestCase
{
  /** @var GeneralResponseValidator */
  private $generalResponseValidator;

  /** @var SubjectReader */
  private $subjectReader;

  /** @var AnetApiResponseType|MockObject */
  private $responseMock;

  /** @var MessagesType|MockObject */
  private $messagesMock;

  /** @var MessageAType|MockObject */
  private $messageMock;

  /** @var ResultInterfaceFactory|MockObject */
  private $resultInterfaceFactoryMock;

  public function setUp()
  {
    $objectManager = new ObjectManager($this);

    $this->subjectReader = $objectManager->getObject(SubjectReader::class);

    $this->responseMock = $this->getMockBuilder(AnetApiResponseType::class)
      ->disableOriginalConstructor()
      ->setMethods(['getMessages','getTransactionResponse'])
      ->getMock();

    $this->messagesMock = $this->getMockBuilder(MessagesType::class)
      ->disableOriginalConstructor()
      ->setMethods(['getResultCode','getMessage'])
      ->getMock();

    $this->messageMock = $this->getMockBuilder(MessageAType::class)
      ->disableOriginalConstructor()
      ->setMethods(['getText'])
      ->getMock();

    $this->resultInterfaceFactoryMock = $this->getMockBuilder(ResultInterfaceFactory::class)
      ->disableOriginalConstructor()
      ->setMethods(['create'])
      ->getMock();

    $this->responseMock->expects($this->any())
      ->method('getMessages')
      ->willReturn($this->messagesMock);

    $this->generalResponseValidator = $objectManager->getObject(GeneralResponseValidator::class,
      [
        '_subjectReader' => $this->subjectReader,
        'resultInterfaceFactory' => $this->resultInterfaceFactoryMock
      ]
    );
  }

  public function testValidate()
  {
    $this->messagesMock->expects($this->once())
      ->method('getResultCode')
      ->willReturn('Ok');

    $this->messagesMock->expects($this->once())
      ->method('getMessage')
      ->willReturn([$this->messageMock]);

    $this->messageMock->expects($this->once())
      ->method('getText')
      ->willReturn('Transaction Approved');

    $this->resultInterfaceFactoryMock->expects($this->once())
      ->method('create')
      ->with([
        'isValid' => true,
        'failsDescription' => []
      ]);

    $subject = ['response' => ['object' => $this->responseMock]];

    $this->generalResponseValidator->validate($subject);
  }

  /** @cover GeneralResponseValidator::validate */
  public function testValidateWithError()
  {
    $this->messagesMock->expects($this->once())
      ->method('getResultCode')
      ->willReturn('Error');

    $this->messagesMock->expects($this->once())
      ->method('getMessage')
      ->willReturn([$this->messageMock]);

    $this->messageMock->expects($this->once())
      ->method('getText')
      ->willReturn('Transaction Declined');

    $this->resultInterfaceFactoryMock->expects($this->once())
      ->method('create')
      ->with([
        'isValid' => false,
        'failsDescription' => ['Transaction Declined']
      ]);

    $subject = ['response' => ['object' => $this->responseMock]];

    $this->generalResponseValidator->validate($subject);
  }
}