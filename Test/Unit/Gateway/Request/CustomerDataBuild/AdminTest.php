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

namespace Pmclain\AuthorizenetCim\Test\Unit\Gateway\Request\CustomerDataBuilder;

use Pmclain\Authnet\CustomerProfile;
use Pmclain\Authnet\CustomerProfileFactory;
use Pmclain\AuthorizenetCim\Gateway\Helper\SubjectReader;
use Pmclain\AuthorizenetCim\Gateway\Request\CustomerDataBuilder\Admin;
use \PHPUnit_Framework_MockObject_MockObject as MockObject;
use Magento\Backend\Model\Session\Quote;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Customer\Api\Data\CustomerInterface;
use Magento\Framework\Api\AttributeInterface;
use Magento\Customer\Model\Session;
use Magento\Payment\Gateway\Data\PaymentDataObjectInterface;
use Magento\Payment\Gateway\Data\OrderAdapterInterface;
use Magento\Payment\Gateway\Data\AddressAdapterInterface;

class AdminTest extends \PHPUnit\Framework\TestCase
{
    const EMAIL = 'user_1@example.com';
    const PROFILE_ID = '12345';
    const CUSTOMER_ID = '12';

    /** @var Admin */
    private $customerDataBuilder;

    /** @var SubjectReader */
    private $subjectReader;

    /** @var CustomerRepositoryInterface|MockObject */
    private $customerRepositoryMock;

    /** @var CustomerInterface|MockObject */
    private $customerMock;

    /** @var Quote|MockObject */
    private $adminSessionMock;

    /** @var AttributeInterface|MockObject */
    private $attributeMock;

    /** @var CustomerProfileFactory|MockObject */
    private $customerProfileFactoryMock;

    /** @var Session|MockObject */
    private $sessionMock;

    /** @var PaymentDataObjectInterface|MockObject */
    private $paymentDataObjectMock;

    /** @var OrderAdapterInterface|MockObject */
    private $orderMock;

    /** @var AddressAdapterInterface|MockObject */
    private $addressMock;

    protected function setUp()
    {
        $this->subjectReader = new SubjectReader();
        $this->customerRepositoryMock = $this->createMock(CustomerRepositoryInterface::class);
        $this->customerMock = $this->createMock(CustomerInterface::class);
        $this->adminSessionMock = $this->getMockBuilder(Quote::class)
            ->disableOriginalConstructor()
            ->setMethods(['getCustomerId'])
            ->getMock();
        $this->attributeMock = $this->createMock(AttributeInterface::class);
        $this->customerProfileFactoryMock = $this->createMock(CustomerProfileFactory::class);
        $this->sessionMock = $this->createMock(Session::class);
        $this->paymentDataObjectMock = $this->createMock(PaymentDataObjectInterface::class);
        $this->orderMock = $this->createMock(OrderAdapterInterface::class);
        $this->addressMock = $this->createMock(AddressAdapterInterface::class);

        $this->paymentDataObjectMock->method('getOrder')
            ->willReturn($this->orderMock);

        $this->orderMock->method('getBillingAddress')
            ->willReturn($this->addressMock);

        $this->addressMock->method('getEmail')
            ->willReturn(self::EMAIL);

        $this->customerProfileFactoryMock->method('create')
            ->willReturn(new CustomerProfile());

        $this->customerRepositoryMock->method('getById')
            ->willReturn($this->customerMock);

        $this->customerMock->method('getCustomAttribute')
            ->with('authorizenet_cim_profile_id')
            ->willReturn($this->attributeMock);

        $this->customerDataBuilder = new Admin(
            $this->subjectReader,
            $this->sessionMock,
            $this->customerRepositoryMock,
            $this->adminSessionMock,
            $this->customerProfileFactoryMock
        );
    }

    /** @cover CustomerDataBuilder::build */
    public function testBuildNewCustomer()
    {
        $this->adminSessionMock->method('getCustomerId')
            ->willReturn('');

        $result = $this->customerDataBuilder->build(['payment' => $this->paymentDataObjectMock]);

        $this->assertInstanceOf(CustomerProfile::class, $result[Admin::CUSTOMER_PROFILE]);
        $this->assertNull($result[Admin::PROFILE_ID]);
        $this->assertNull($result[Admin::CUSTOMER_ID]);
    }

    /** @cover CustomerDataBuilder::build */
    public function testBuildCustomerHasCimProfile()
    {
        $this->adminSessionMock->method('getCustomerId')
            ->willReturn(self::CUSTOMER_ID);

        $this->attributeMock->method('getValue')
            ->willReturn(self::PROFILE_ID);

        $result = $this->customerDataBuilder->build(['payment' => $this->paymentDataObjectMock]);

        $this->assertInstanceOf(CustomerProfile::class, $result[Admin::CUSTOMER_PROFILE]);
        $this->assertEquals(self::PROFILE_ID, $result[Admin::PROFILE_ID]);
        $this->assertEquals(self::CUSTOMER_ID, $result[Admin::CUSTOMER_ID]);
    }

    /** @cover CustomerDataBuilder::build */
    public function testBuildCustomerWithoutCimProfile()
    {
        $this->adminSessionMock->method('getCustomerId')
            ->willReturn(self::CUSTOMER_ID);

        $this->attributeMock->method('getValue')
            ->willReturn(null);

        $result = $this->customerDataBuilder->build(['payment' => $this->paymentDataObjectMock]);

        $this->assertInstanceOf(CustomerProfile::class, $result[Admin::CUSTOMER_PROFILE]);
        $this->assertNull($result[Admin::PROFILE_ID]);
        $this->assertEquals(self::CUSTOMER_ID, $result[Admin::CUSTOMER_ID]);
    }
}
