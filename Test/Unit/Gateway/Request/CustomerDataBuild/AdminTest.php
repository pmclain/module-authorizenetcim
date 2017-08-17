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

namespace Pmclain\AuthorizenetCim\Test\Unit\Gateway\Request\CustomerDataBuilder;

use Pmclain\AuthorizenetCim\Gateway\Request\CustomerDataBuilder\Admin;
use \PHPUnit_Framework_MockObject_MockObject as MockObject;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Customer\Api\Data\CustomerInterface;
use Magento\Backend\Model\Session\Quote;
use Magento\Framework\Api\AttributeInterface;

class AdminTest extends \PHPUnit_Framework_TestCase
{
  /** @var Admin */
  private $customerDataBuilder;

  /** @var CustomerRepositoryInterface|MockObject */
  private $customerRespositoryMock;

  /** @var CustomerInterface|MockObject */
  private $customerMock;

  /** @var Quote|MockObject */
  private $adminSessionMock;

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

    $this->adminSessionMock = $this->getMockBuilder(Quote::class)
      ->disableOriginalConstructor()
      ->setMethods(['getCustomerId'])
      ->getMock();

    $this->attributeMock = $this->getMockBuilder(AttributeInterface::class)
      ->setMethods(['getValue'])
      ->getMockForAbstractClass();

    $this->customerDataBuilder = $objectManager->getObject(Admin::class,
      [
        '_adminSession' => $this->adminSessionMock,
        '_customerRepository' => $this->customerRespositoryMock,
      ]
    );
  }

  /** @cover CustomerDataBuilder::build */
  public function testBuildNewCustomer()
  {
    $this->adminSessionMock->expects($this->once())
      ->method('getCustomerId')
      ->willReturn('');

    $result = $this->customerDataBuilder->build([]);

    $this->assertEquals(
      [
        'customer_id' => null,
        'profile_id' => null,
      ],
      $result
    );
  }

  /** @cover CustomerDataBuilder::build */
  public function testBuildCustomerHasCimProfile()
  {
    $cimProfileId = '123456789';
    $customerId = '1';

    $this->adminSessionMock->expects($this->exactly(3))
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

  /** @cover CustomerDataBuilder::build */
  public function testBuildCustomerWithoutCimProfile()
  {
    $customerId = '1';

    $this->adminSessionMock->expects($this->exactly(3))
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