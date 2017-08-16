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

namespace Pmclain\AuthorizenetCim\Gateway\Request;

use Magento\Payment\Gateway\Request\BuilderInterface;
use Pmclain\AuthorizenetCim\Gateway\Helper\SubjectReader;
use Pmclain\AuthorizenetCim\Model\Authorizenet\Contract\CustomerAddressTypeFactory;

class AddressDataBuilder implements BuilderInterface
{
  /** @var SubjectReader */
  protected $_subjectReader;

  /** @var CustomerAddressTypeFactory */
  protected $_customerAddressFactory;

  public function __construct(
    SubjectReader $subjectReader,
    CustomerAddressTypeFactory $customerAddressTypeFactory
  ) {
    $this->_subjectReader = $subjectReader;
    $this->_customerAddressFactory = $customerAddressTypeFactory;
  }

  public function build(array $buildSubject)
  {
    $paymentDataObject = $this->_subjectReader->readPayment($buildSubject);

    $order = $paymentDataObject->getOrder();
    $billingAddress = $order->getBillingAddress();

    $customerAddress = $this->_customerAddressFactory->create();
    $customerAddress->setFirstName($billingAddress->getFirstname());
    $customerAddress->setLastName($billingAddress->getLastname());
    $customerAddress->setCompany($billingAddress->getCompany());
    $customerAddress->setAddress($billingAddress->getStreetLine1());
    $customerAddress->setCity($billingAddress->getCity());
    $customerAddress->setState($billingAddress->getRegionCode());
    $customerAddress->setZip($billingAddress->getPostcode());
    $customerAddress->setCountry($billingAddress->getCountryId());
    $customerAddress->setPhoneNumber($billingAddress->getTelephone());
    $customerAddress->setEmail($billingAddress->getEmail());

    return ['bill_to_address' => $customerAddress];
  }
}