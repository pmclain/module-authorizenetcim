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

namespace Pmclain\AuthorizenetCim\Gateway\Request\CustomerDataBuilder;

use Pmclain\AuthorizenetCim\Gateway\Request\CustomerDataBuilder;
use Pmclain\AuthorizenetCim\Gateway\Helper\SubjectReader;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Customer\Model\Session;
use Magento\Backend\Model\Session\Quote;

class Admin extends CustomerDataBuilder
{
    /** @var Quote */
    protected $_adminSession;

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
        Quote $session
    ) {
        parent::__construct(
            $subjectReader,
            $customerSession,
            $customerRepository
        );
        $this->_adminSession = $session;
    }

    public function build(array $subject)
    {
        if ($this->_adminSession->getCustomerId()) {
            return [
                'customer_id' => $this->_adminSession->getCustomerId(),
                'profile_id' => $this->_getCimProfileId()
            ];
        }

        return [
            'customer_id' => null,
            'profile_id' => null
        ];
    }

    /** @return string|null */
    protected function _getCimProfileId()
    {
        $customer = $this->_customerRepository->getById($this->_adminSession->getCustomerId());
        $cimProfileId = $customer->getCustomAttribute('authorizenet_cim_profile_id');

        return $cimProfileId ? $cimProfileId->getValue() : null;
    }
}
