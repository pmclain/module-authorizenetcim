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

use Pmclain\AuthorizenetCim\Gateway\Request\CustomerDataBuilder;
use \PHPUnit_Framework_MockObject_MockObject as MockObject;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Customer\Api\Data\CustomerInterface;
use Magento\Customer\Model\Session;
use Magento\Framework\Api\AttributeInterface;

class CustomerDataBuilderTest extends \PHPUnit_Framework_TestCase
{
  /** @var CustomerDataBuilder */
  private $customerDataBuilder;

  /** @var CustomerRepositoryInterface|MockObject */
  private $customerRespositoryMock;

  /** @var CustomerInterface|MockObject */
  private $customerMock;

  /** @var Session|MockObject */
  private $sessionMock;

  /** @var AttributeInterface|MockObject */
  private $attributeMock;

  protected function setUp()
  {
    $objectManager = new ObjectManager($this);

    $this->customerRespositoryMock = $this->getMockBuilder(CustomerRepositoryInterface::class)
      ->setMethods(['getById'])
      ->getMockForAbstractClass();

    $this->customerMock = $this->getMockBuilder(CustomerInterface::class)
      ->setMethods(['getCustomAttribute'])
      ->getMockForAbstractClass();

    $this->sessionMock = $this->getMockBuilder(Session::class)
      ->disableOriginalConstructor()
      ->setMethods(['isLoggedIn','getCustomerId'])
      ->getMock();

    $this->attributeMock = $this->getMockBuilder(AttributeInterface::class)
      ->setMethods(['getValue'])
      ->getMockForAbstractClass();

    $this->customerDataBuilder = $objectManager->getObject(CustomerDataBuilder::class,
      [
        '_session' => $this->sessionMock,
        '_customerRepository' => $this->customerRespositoryMock,
      ]
    );
  }

  /** @covers CustomerDataBuilder::build */
  public function testBuildNotLoggedIn()
  {
    $this->sessionMock->expects($this->once())
      ->method('isLoggedIn')
      ->willReturn(false);

    $result = $this->customerDataBuilder->build([]);

    $this->assertEquals(
      [
        'customer_id' => null,
        'profile_id' => null,
      ],
      $result
    );
  }

  /** @covers CustomerDataBuilder::build */
  public function testBuildCustomerHasCimProfile()
  {
    $cimProfileId = '123456789';
    $customerId = '1';

    $this->sessionMock->expects($this->once())
      ->method('isLoggedIn')
      ->willReturn(true);

    $this->sessionMock->expects($this->exactly(2))
      ->method('getCustomerId')
      ->willReturn($customerId);

    $this->customerRespositoryMock->expects($this->once())
      ->method('getById')
      ->with($customerId)
      ->willReturn($this->customerMock);

    $this->customerMock->expects($this->once())
      ->method('getCustomAttribute')
      ->with('authorizenet_cim_profile_id')
      ->willReturn($this->attributeMock);

    $this->attributeMock->expects($this->once())
      ->method('getValue')
      ->willReturn($cimProfileId);

    $result = $this->customerDataBuilder->build([]);

    $this->assertEquals(
      [
        'customer_id' => $customerId,
        'profile_id' => $cimProfileId,
      ],
      $result
    );
  }

  /** @covers CustomerDataBuilder::build */
  public function testBuildCustomerWithoutCimProfile()
  {
    $customerId = '1';

    $this->sessionMock->expects($this->once())
      ->method('isLoggedIn')
      ->willReturn(true);

    $this->sessionMock->expects($this->exactly(2))
      ->method('getCustomerId')
      ->willReturn($customerId);

    $this->customerRespositoryMock->expects($this->once())
      ->method('getById')
      ->with($customerId)
      ->willReturn($this->customerMock);

    $this->customerMock->expects($this->once())
      ->method('getCustomAttribute')
      ->with('authorizenet_cim_profile_id')
      ->willReturn(null);

    $result = $this->customerDataBuilder->build([]);

    $this->assertEquals(
      [
        'customer_id' => $customerId,
        'profile_id' => null,
      ],
      $result
    );
  }
}