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
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Customer\Model\Session;
use Pmclain\Authnet\CustomerProfileFactory;
use Pmclain\Authnet\CustomerProfile;

class CustomerDataBuilder implements BuilderInterface
{
    const CUSTOMER_ID = 'customer_id';
    const PROFILE_ID = 'profile_id';
    const CUSTOMER_PROFILE = 'customer_profile';

    /**
     * @var SubjectReader
     */
    protected $subjectReader;

    /**
     * @var Session
     */
    protected $session;

    /**
     * @var CustomerRepositoryInterface
     */
    protected $customerRepository;

    /**
     * @var CustomerProfileFactory
     */
    protected $customerProfileFactory;

    /**
     * CustomerDataBuilder constructor.
     * @param SubjectReader $subjectReader
     * @param Session $session
     * @param CustomerRepositoryInterface $customerRepository
     * @param CustomerProfileFactory $customerProfileFactory
     */
    public function __construct(
        SubjectReader $subjectReader,
        Session $session,
        CustomerRepositoryInterface $customerRepository,
        CustomerProfileFactory $customerProfileFactory
    ) {
        $this->subjectReader = $subjectReader;
        $this->session = $session;
        $this->customerRepository = $customerRepository;
        $this->customerProfileFactory = $customerProfileFactory;
    }

    /**
     * @param array $subject
     * @return array
     */
    public function build(array $subject)
    {
        $paymentDataObject = $this->subjectReader->readPayment($subject);
        $order = $paymentDataObject->getOrder();
        $email = $order->getBillingAddress()->getEmail();

        $customerId = null;
        $profileId = null;

        if ($this->session->isLoggedIn()) {
            $customerId = $this->session->getCustomerId();
            $profileId = $this->getCimProfileId();
        }

        /**
         * @var CustomerProfile $customerProfile
         */
        $customerProfile = $this->customerProfileFactory->create();
        $customerProfile->setCustomerId($customerId);
        $customerProfile->setEmail($email ?: $order->getOrderIncrementId() . '_' . mt_rand() . '-' . time());

        return [
            self::CUSTOMER_ID => $customerId,
            self::PROFILE_ID => $profileId,
            self::CUSTOMER_PROFILE => $customerProfile,
        ];
    }

    /**
     * @return string|null
     */
    protected function getCimProfileId()
    {
        $customer = $this->customerRepository->getById($this->session->getCustomerId());
        $cimProfileId = $customer->getCustomAttribute('authorizenet_cim_profile_id');

        return $cimProfileId ? $cimProfileId->getValue() : null;
    }
}
