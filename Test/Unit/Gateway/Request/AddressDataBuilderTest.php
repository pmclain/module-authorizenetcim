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

namespace Pmclain\AuthorizenetCim\Test\Unit\Gateway\Request;

use Pmclain\AuthorizenetCim\Gateway\Request\AddressDataBuilder;
use \PHPUnit_Framework_MockObject_MockObject as MockObject;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Pmclain\AuthorizenetCim\Gateway\Helper\SubjectReader;
use Pmclain\AuthorizenetCim\Model\Authorizenet\Contract\CustomerAddressTypeFactory;
use Magento\Payment\Gateway\Data\PaymentDataObjectInterface;
use Magento\Payment\Gateway\Data\OrderAdapterInterface;
use Magento\Payment\Gateway\Data\AddressAdapterInterface;
use net\authorize\api\contract\v1\CustomerAddressType;

class AddressDataBuilderTest extends \PHPUnit\Framework\TestCase
{
  /** @var AddressDataBuilder */
  private $addressDataBuilder;

  /** @var SubjectReader */
  private $subjectReader;

  /** @var CustomerAddressTypeFactory|MockObject */
  private $customerAddressFactoryMock;

  /** @var PaymentDataObjectInterface|MockObject */
  private $paymentDataObjectMock;

  /** @var OrderAdapterInterface|MockObject */
  private $orderMock;

  /** @var AddressAdapterInterface|MockObject */
  private $addressMock;

  /** @var CustomerAddressType */
  private $customerAddress;

  protected function setUp()
  {
    $objectManager = new ObjectManager($this);

    $this->subjectReader = $objectManager->getObject(SubjectReader::class);

    $this->customerAddressFactoryMock = $this->getMockBuilder(CustomerAddressTypeFactory::class)
      ->disableOriginalConstructor()
      ->setMethods(['create'])
      ->getMock();

    $this->customerAddress = $objectManager->getObject(CustomerAddressType::class);

    $this->paymentDataObjectMock = $this->getMockBuilder(PaymentDataObjectInterface::class)
      ->setMethods(['getOrder'])
      ->getMockForAbstractClass();

    $this->orderMock = $this->getMockBuilder(OrderAdapterInterface::class)
      ->setMethods(['getBillingAddress'])
      ->getMockForAbstractClass();

    $this->addressMock = $this->getMockBuilder(AddressAdapterInterface::class)
      ->setMethods([
        'getFirstname','getLastname','getCompany','getStreetLine1','getCity',
        'getRegionCode','getPostcode','getCountryId','getTelephone','getEmail'
      ])->getMockForAbstractClass();

    $this->addressDataBuilder = $objectManager->getObject(AddressDataBuilder::class,
      [
        '_subjectReader' => $this->subjectReader,
        '_customerAddressFactory' => $this->customerAddressFactoryMock,
      ]
    );
  }

  public function testBuild()
  {
    $this->paymentDataObjectMock->expects($this->once())
      ->method('getOrder')
      ->willReturn($this->orderMock);

    $this->orderMock->expects($this->once())
      ->method('getBillingAddress')
      ->willReturn($this->addressMock);

    $this->customerAddressFactoryMock->expects($this->once())
      ->method('create')
      ->willReturn($this->customerAddress);

    $addressData = [
      'John' => ['Firstname', 'FirstName'],
      'Doe' => ['Lastname', 'LastName'],
      'Acme Co' => ['Company', 'Company'],
      '123 Abc St' => ['StreetLine1', 'Address'],
      'Lawton' => ['City', 'City'],
      'MI' => ['RegionCode', 'State'],
      '49065' => ['Postcode', 'Zip'],
      'US' => ['CountryId', 'Country'],
      '(555) 229-3326' => ['Telephone', 'PhoneNumber'],
      'roni_cost@example.com' => ['Email', 'Email']
    ];

    foreach ($addressData as $value => $fieldNames) {
      $this->addressMock->expects($this->once())
        ->method('get' . $fieldNames[0])
        ->willReturn($value);
    }

    $result = $this->addressDataBuilder->build(['payment' => $this->paymentDataObjectMock]);
    $customerAddress = $result['bill_to_address'];

    foreach ($addressData as $value => $fieldNames) {
      $this->assertEquals(
        $value,
        $customerAddress->{'get' . $fieldNames[1]}()
      );
    }
  }
}