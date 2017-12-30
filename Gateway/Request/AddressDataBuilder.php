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

namespace Pmclain\AuthorizenetCim\Gateway\Request;

use Magento\Payment\Gateway\Request\BuilderInterface;
use Pmclain\AuthorizenetCim\Gateway\Helper\SubjectReader;
use Pmclain\Authnet\PaymentProfile\AddressFactory;
use Pmclain\Authnet\PaymentProfile\Address;

class AddressDataBuilder implements BuilderInterface
{
    const BILL_TO = 'bill_to';
    const SHIP_TO = 'ship_to';

    /**
     * @var SubjectReader
     */
    protected $subjectReader;

    /**
     * @var AddressFactory
     */
    protected $addressFactory;

    /**
     * AddressDataBuilder constructor.
     * @param SubjectReader $subjectReader
     * @param AddressFactory $addressFactory
     */
    public function __construct(
        SubjectReader $subjectReader,
        AddressFactory $addressFactory
    ) {
        $this->subjectReader = $subjectReader;
        $this->addressFactory = $addressFactory;
    }

    public function build(array $buildSubject)
    {
        $paymentDataObject = $this->subjectReader->readPayment($buildSubject);

        $order = $paymentDataObject->getOrder();
        $billingAddress = $order->getBillingAddress();
        $shippingAddress = $order->getShippingAddress();

        $result[self::BILL_TO] = $this->convertAddress($billingAddress);

        if ($shippingAddress) {
            $result[self::SHIP_TO] = $this->convertAddress($shippingAddress);
        }

        return $result;
    }

    /**
     * @param \Magento\Payment\Gateway\Data\AddressAdapterInterface $magentoAddress
     * @return Address
     */
    private function convertAddress($magentoAddress)
    {
        /**
         * @var Address $address
         */
        $address = $this->addressFactory->create();
        $address->setFirstName($magentoAddress->getFirstname());
        $address->setLastName($magentoAddress->getLastname());
        $address->setCompany($magentoAddress->getCompany());
        $address->setAddress($magentoAddress->getStreetLine1());
        $address->setCity($magentoAddress->getCity());
        $address->setState($magentoAddress->getRegionCode());
        $address->setZip($magentoAddress->getPostcode());
        $address->setCountry($magentoAddress->getCountryId());
        $address->setPhoneNumber($magentoAddress->getTelephone());

        return $address;
    }
}
