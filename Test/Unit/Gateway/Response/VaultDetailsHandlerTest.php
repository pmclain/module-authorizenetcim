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

use Pmclain\AuthorizenetCim\Gateway\Response\VaultDetailsHandler;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use \PHPUnit_Framework_MockObject_MockObject as MockObject;
use Magento\Payment\Gateway\Data\PaymentDataObjectInterface;
use Pmclain\AuthorizenetCim\Gateway\Helper\SubjectReader;
use Magento\Sales\Api\Data\OrderPaymentExtensionInterface;
use Magento\Sales\Api\Data\OrderPaymentExtensionInterfaceFactory;
use Pmclain\AuthorizenetCim\Gateway\Config\Config;
use Magento\Vault\Api\Data\PaymentTokenInterface;
use Magento\Vault\Model\CreditCardTokenFactory;
use net\authorize\api\contract\v1\CreateTransactionResponse;
use net\authorize\api\contract\v1\TransactionResponseType;
use net\authorize\api\contract\v1\CustomerProfileIdType;
use Magento\Payment\Model\InfoInterface;

class VaultDetailsHandlerTest extends \PHPUnit_Framework_TestCase
{
  /** @var VaultDetailsHandler */
  private $vaultDetailsHandler;

  /** @var SubjectReader */
  private $subjectReader;

  /** @var PaymentDataObjectInterface|MockObject */
  private $paymentDataObjectMock;

  /** @var OrderPaymentExtensionInterface|MockObject */
  private $paymentExtensionMock;

  /** @var OrderPaymentExtensionInterfaceFactory|MockObject */
  private $paymentExtensionFactoryMock;

  /** @var Config|MockObject */
  private $configMock;

  /** @var CreditCardTokenFactory|MockObject */
  private $creditCardTokenFactoryMock;

  /** @var PaymentTokenInterface|MockObject */
  private $paymentTokenMock;

  /** @var CreateTransactionResponse|MockObject */
  private $createTransactionResponseMock;

  /** @var TransactionResponseType|MockObject */
  private $transactionResponseMock;

  /** @var InfoInterface|MockObject */
  private $paymentMock;

  /** @var CustomerProfileIdType|MockObject */
  private $customerProfileIdMock;

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
      ->setMethods(['getProfile'])
      ->getMock();

    $this->customerProfileIdMock = $this->getMockBuilder(CustomerProfileIdType::class)
      ->disableOriginalConstructor()
      ->setMethods(['getCustomerPaymentProfileId'])
      ->getMock();

    $this->creditCardTokenFactoryMock = $this->getMockBuilder(CreditCardTokenFactory::class)
      ->disableOriginalConstructor()
      ->setMethods(['create'])
      ->getMock();

    $this->paymentTokenMock = $this->getMockBuilder(PaymentTokenInterface::class)
      ->setMethods(['setTokenDetails'])
      ->getMockForAbstractClass();

    $this->paymentMock = $this->getMockBuilder(InfoInterface::class)
      ->setMethods(['getAdditionalInformation','getExtensionAttributes','setExtensionAttributes'])
      ->getMockForAbstractClass();

    $this->paymentExtensionFactoryMock = $this->getMockBuilder(OrderPaymentExtensionInterfaceFactory::class)
      ->disableOriginalConstructor()
      ->setMethods(['create'])
      ->getMock();

    $this->paymentExtensionMock = $this->getMockBuilder(OrderPaymentExtensionInterface::class)
      ->getMockForAbstractClass();

    $this->paymentExtensionFactoryMock->expects($this->any())
      ->method('create')
      ->willReturn($this->paymentExtensionMock);

    $this->paymentDataObjectMock->expects($this->once())
      ->method('getPayment')
      ->willReturn($this->paymentMock);

    $this->createTransactionResponseMock->expects($this->once())
      ->method('getTransactionResponse')
      ->willReturn($this->transactionResponseMock);

    $this->vaultDetailsHandler = $objectManager->getObject(VaultDetailsHandler::class,
      [
        '_paymentTokenFactory' => $this->creditCardTokenFactoryMock,
        '_paymentExtensionFactory' => $this->paymentExtensionFactoryMock,
        '_subjectReader' => $this->subjectReader,
        '_config' => $this->configMock,
      ]
    );
  }

  /** @cover VaultDetailsHandler::handle */
  public function testHandleNoVaultSave()
  {
    $this->paymentMock->expects($this->once())
      ->method('getAdditionalInformation')
      ->with('is_active_payment_token_enabler')
      ->willReturn(false);

    $subject = ['payment' => $this->paymentDataObjectMock];
    $response = ['object' => $this->createTransactionResponseMock];

    $this->vaultDetailsHandler->handle($subject, $response);
  }

  /** @cover VaultDetailsHandler::handle */
  public function testHandle()
  {
    $paymentProfileId = '123456789';
    $ccExpMonth = '09';
    $ccExpYear = '2022';

    $this->paymentMock->expects($this->at(0))
      ->method('getAdditionalInformation')
      ->with('is_active_payment_token_enabler')
      ->willReturn(true);

    $this->createTransactionResponseMock->expects($this->once())
      ->method('getTransactionResponse')
      ->willReturn($this->transactionResponseMock);

    $this->transactionResponseMock->expects($this->once())
      ->method('getProfile')
      ->willReturn($this->customerProfileIdMock);

    $this->customerProfileIdMock->expects($this->once())
      ->method('getCustomerPaymentProfileId')
      ->willReturn($paymentProfileId);

    $this->creditCardTokenFactoryMock->expects($this->once())
      ->method('create')
      ->willReturn($this->paymentTokenMock);

    $this->paymentMock->expects($this->at(1))
      ->method('getAdditionalInformation')
      ->with('cc_exp_year')
      ->willReturn($ccExpYear);

    $this->paymentMock->expects($this->at(2))
      ->method('getAdditionalInformation')
      ->with('cc_exp_month')
      ->willReturn($ccExpMonth);

    $this->paymentTokenMock->expects($this->once())
      ->method('setGatewayToken')
      ->with($paymentProfileId);

    $this->paymentTokenMock->expects($this->once())
      ->method('setExpiresAt')
      ->with('2022-10-01 00:00:00');

    $subject = ['payment' => $this->paymentDataObjectMock];
    $response = ['object' => $this->createTransactionResponseMock];

    $this->vaultDetailsHandler->handle($subject, $response);
  }
}