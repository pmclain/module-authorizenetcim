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
use Pmclain\AuthorizenetCim\Gateway\Request\TokenDataBuilder;
use \PHPUnit_Framework_MockObject_MockObject as MockObject;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Pmclain\AuthorizenetCim\Gateway\Helper\SubjectReader;
use Magento\Sales\Api\Data\OrderPaymentExtension;
use Magento\Vault\Api\Data\PaymentTokenInterface;

class TokenDataBuilderTest extends \PHPUnit_Framework_TestCase
{
  /** @var TokenDataBuilder */
  private $tokenDataBuilder;

  /** @var SubjectReader */
  private $subjectReader;

  /** @var PaymentDataObjectInterface|MockObject */
  private $paymentDataObjectMock;

  /** @var InfoInterface|MockObject */
  private $paymentMock;

  /** @var OrderPaymentExtension|MockObject */
  private $extensionAttributesMock;

  /** @var PaymentTokenInterface|MockObject */
  private $paymentTokenMock;

  public function testBuild()
  {
    $gatewayToken = '123456';

    $objectManager = new ObjectManager($this);

    $this->subjectReader = $objectManager->getObject(SubjectReader::class);

    $this->paymentDataObjectMock = $this->getMockBuilder(PaymentDataObjectInterface::class)
      ->setMethods(['getPayment'])
      ->getMockForAbstractClass();

    $this->paymentMock = $this->getMockBuilder(InfoInterface::class)
      ->setMethods(['getExtensionAttributes'])
      ->getMockForAbstractClass();

    $this->extensionAttributesMock = $this->getMockBuilder(OrderPaymentExtension::class)
      ->setMethods(['getVaultPaymentToken'])
      ->getMockForAbstractClass();

    $this->paymentTokenMock = $this->getMockBuilder(PaymentTokenInterface::class)
      ->setMethods(['getGatewayToken'])
      ->getMockForAbstractClass();

    $this->paymentDataObjectMock->expects($this->once())
      ->method('getPayment')
      ->willReturn($this->paymentMock);

    $this->paymentMock->expects($this->once())
      ->method('getExtensionAttributes')
      ->willReturn($this->extensionAttributesMock);

    $this->extensionAttributesMock->expects($this->once())
      ->method('getVaultPaymentToken')
      ->willReturn($this->paymentTokenMock);

    $this->paymentTokenMock->expects($this->once())
      ->method('getGatewayToken')
      ->willReturn($gatewayToken);

    $this->tokenDataBuilder = $objectManager->getObject(TokenDataBuilder::class,
      [
        '_subjectReader' => $this->subjectReader
      ]
    );

    $this->assertEquals(
      ['payment_profile' => $gatewayToken],
      $this->tokenDataBuilder->build(['payment' => $this->paymentDataObjectMock])
    );
  }
}