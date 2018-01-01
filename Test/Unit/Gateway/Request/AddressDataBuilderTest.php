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
use Pmclain\AuthorizenetCim\Gateway\Helper\SubjectReader;
use Magento\Payment\Gateway\Data\PaymentDataObjectInterface;
use Magento\Payment\Gateway\Data\OrderAdapterInterface;
use Magento\Payment\Gateway\Data\AddressAdapterInterface;
use Pmclain\Authnet\PaymentProfile\Address;
use Pmclain\Authnet\PaymentProfile\AddressFactory;

class AddressDataBuilderTest extends \PHPUnit\Framework\TestCase
{
    /** @var AddressDataBuilder */
    private $addressDataBuilder;

    /** @var SubjectReader */
    private $subjectReader;

    /** @var AddressFactory|MockObject */
    private $addressFactoryMock;

    /** @var PaymentDataObjectInterface|MockObject */
    private $paymentDataObjectMock;

    /** @var OrderAdapterInterface|MockObject */
    private $orderMock;

    /** @var AddressAdapterInterface|MockObject */
    private $addressMock;

    protected function setUp()
    {
        $this->subjectReader = new SubjectReader();

        $this->addressFactoryMock = $this->createMock(AddressFactory::class);
        $this->paymentDataObjectMock = $this->createMock(PaymentDataObjectInterface::class);
        $this->orderMock = $this->createMock(OrderAdapterInterface::class);
        $this->addressMock = $this->createMock(AddressAdapterInterface::class);

        $this->addressDataBuilder = new AddressDataBuilder(
            $this->subjectReader,
            $this->addressFactoryMock
        );
    }

    public function testBuild()
    {
        $this->paymentDataObjectMock->method('getOrder')
            ->willReturn($this->orderMock);

        $this->orderMock->method('getBillingAddress')
            ->willReturn($this->addressMock);

        $this->addressFactoryMock->method('create')
            ->willReturn(new Address());

        $addressData = [
            'John' => ['Firstname', Address::FIELD_FIRSTNAME],
            'Doe' => ['Lastname',  Address::FIELD_LASTNAME],
            'Acme Co' => ['Company', Address::FIELD_COMPANY],
            '123 Abc St' => ['StreetLine1', Address::FIELD_ADDRESS],
            'Lawton' => ['City', Address::FIELD_CITY],
            'MI' => ['RegionCode', Address::FIELD_STATE],
            '49065' => ['Postcode', Address::FIELD_ZIP],
            'US' => ['CountryId', Address::FIELD_COUNTRY],
            '(555) 229-3326' => ['Telephone', Address::FIELD_PHONE_NUMBER],
        ];

        foreach ($addressData as $value => $fieldNames) {
            $this->addressMock->method('get' . $fieldNames[0])
                ->willReturn($value);
        }

        $result = $this->addressDataBuilder->build(['payment' => $this->paymentDataObjectMock]);
        $customerAddress = $result[AddressDataBuilder::BILL_TO]->toArray();

        foreach ($addressData as $value => $fieldNames) {
            $this->assertEquals(
                $value,
                $customerAddress[$fieldNames[1]]
            );
        }
    }
}
