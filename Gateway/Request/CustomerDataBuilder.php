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

class CustomerDataBuilder implements BuilderInterface
{
    /** @var SubjectReader */
    protected $_subjectReader;

    /** @var Session */
    protected $_session;

    /** @var CustomerRepositoryInterface */
    protected $_customerRepository;

    public function __construct(
        SubjectReader $subjectReader,
        Session $session,
        CustomerRepositoryInterface $customerRepository
    ) {
        $this->_subjectReader = $subjectReader;
        $this->_session = $session;
        $this->_customerRepository = $customerRepository;
    }

    public function build(array $subject)
    {
        if ($this->_session->isLoggedIn()) {
            return [
                'customer_id' => $this->_session->getCustomerId(),
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
        $customer = $this->_customerRepository->getById($this->_session->getCustomerId());
        $cimProfileId = $customer->getCustomAttribute('authorizenet_cim_profile_id');

        return $cimProfileId ? $cimProfileId->getValue() : null;
    }
}
