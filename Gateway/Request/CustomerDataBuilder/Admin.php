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

namespace Pmclain\AuthorizenetCim\Gateway\Request\CustomerDataBuilder;

use Pmclain\AuthorizenetCim\Gateway\Request\CustomerDataBuilder;
use Pmclain\AuthorizenetCim\Gateway\Helper\SubjectReader;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Customer\Model\Session;
use Magento\Backend\Model\Session\Quote;
use Pmclain\Authnet\CustomerProfileFactory;
use Pmclain\Authnet\CustomerProfile;

class Admin extends CustomerDataBuilder
{
    /**
     * @var Quote
     */
    protected $adminSession;

    /**
     * Admin constructor.
     * @param SubjectReader $subjectReader
     * @param Session $customerSession
     * @param CustomerRepositoryInterface $customerRepository
     * @param Quote $session
     */
    public function __construct(
        SubjectReader $subjectReader,
        Session $customerSession,
        CustomerRepositoryInterface $customerRepository,
        Quote $session,
        CustomerProfileFactory $customerProfileFactory
    ) {
        parent::__construct(
            $subjectReader,
            $customerSession,
            $customerRepository,
            $customerProfileFactory
        );
        $this->adminSession = $session;
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

        if ($this->adminSession->getCustomerId()) {
            $customerId = $this->adminSession->getCustomerId();
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
        $customer = $this->customerRepository->getById($this->adminSession->getCustomerId());
        $cimProfileId = $customer->getCustomAttribute('authorizenet_cim_profile_id');

        return $cimProfileId ? $cimProfileId->getValue() : null;
    }
}
