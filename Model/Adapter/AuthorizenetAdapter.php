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

namespace Pmclain\AuthorizenetCim\Model\Adapter;

use Pmclain\AuthorizenetCim\Gateway\Config\Config;
use Magento\Framework\Exception\PaymentException;
use Magento\Customer\Api\CustomerRepositoryInterface;
use net\authorize\api\constants\ANetEnvironment;
use net\authorize\api\contract\v1\MerchantAuthenticationType;
use Pmclain\AuthorizenetCim\Model\Authorizenet\Payment;
use Pmclain\AuthorizenetCim\Model\Authorizenet\Controller\CreateTransactionControllerFactory;
use Pmclain\AuthorizenetCim\Model\Authorizenet\Controller\CreateCustomerPaymentProfileControllerFactory;
use Pmclain\AuthorizenetCim\Model\Authorizenet\Controller\CreateCustomerProfileControllerFactory;
use Pmclain\AuthorizenetCim\Model\Authorizenet\Contract\CreateTransactionRequestFactory;
use Pmclain\AuthorizenetCim\Model\Authorizenet\Contract\CustomerProfileTypeFactory;
use Pmclain\AuthorizenetCim\Model\Authorizenet\Contract\CreateCustomerPaymentProfileRequestFactory;
use Pmclain\AuthorizenetCim\Model\Authorizenet\Contract\CreateCustomerProfileRequestFactory;
use Pmclain\AuthorizenetCim\Model\Authorizenet\Contract\CustomerProfilePaymentTypeFactory;
use Pmclain\AuthorizenetCim\Model\Authorizenet\Contract\PaymentProfileTypeFactory;

class AuthorizenetAdapter
{
    /** @var MerchantAuthenticationType */
    protected $_merchangeAuthentication;

    /** @var CreateCustomerProfileRequestFactory */
    protected $_createCustomerProfileRequestFactory;

    /** @var CreateCustomerPaymentProfileRequestFactory */
    protected $_createCustomerPaymentProfileRequestFactory;

    /** @var CustomerProfileTypeFactory */
    protected $_customerProfileFactory;

    /** @var CustomerProfilePaymentTypeFactory */
    protected $_customerProfilePaymentFactory;

    /** @var CreateTransactionRequestFactory */
    protected $_createTransactionRequestFactory;

    /** @var PaymentProfileTypeFactory */
    protected $_paymentProfileFactory;

    /** @var CustomerRepositoryInterface */
    protected $_customerRepository;

    /** @var CreateTransactionControllerFactory */
    protected $_createTransactionControllerFactory;

    /** @var CreateCustomerPaymentProfileControllerFactory */
    protected $_createCustomerPaymentProfileControllerFactory;

    /** @var CreateCustomerProfileControllerFactory */
    protected $_createCustomerProfileControllerFactory;

    /** @var Config */
    protected $_config;

    /** @var Payment */
    protected $paymentProfile;

    public function __construct(
        MerchantAuthenticationType $merchantAuthenticationType,
        CreateCustomerProfileRequestFactory $createCustomerProfileRequestFactory,
        CreateCustomerPaymentProfileRequestFactory $createCustomerPaymentProfileRequestFactory,
        CustomerProfileTypeFactory $customerProfileTypeFactory,
        CustomerProfilePaymentTypeFactory $customerProfilePaymentTypeFactory,
        CreateTransactionRequestFactory $createTransactionRequestFactory,
        PaymentProfileTypeFactory $paymentProfileTypeFactory,
        CustomerRepositoryInterface $customerRepository,
        CreateTransactionControllerFactory $createTransactionControllerFactory,
        CreateCustomerProfileControllerFactory $createCustomerProfileControllerFactory,
        CreateCustomerPaymentProfileControllerFactory $createCustomerPaymentProfileControllerFactory,
        Config $config,
        Payment $payment
    ) {
        $this->_merchangeAuthentication = $merchantAuthenticationType;
        $this->_createCustomerProfileRequestFactory = $createCustomerProfileRequestFactory;
        $this->_createCustomerPaymentProfileRequestFactory = $createCustomerPaymentProfileRequestFactory;
        $this->_customerProfileFactory = $customerProfileTypeFactory;
        $this->_customerProfilePaymentFactory = $customerProfilePaymentTypeFactory;
        $this->_createTransactionRequestFactory = $createTransactionRequestFactory;
        $this->_paymentProfileFactory = $paymentProfileTypeFactory;
        $this->_customerRepository = $customerRepository;
        $this->_config = $config;
        $this->_createTransactionControllerFactory = $createTransactionControllerFactory;
        $this->_createCustomerProfileControllerFactory = $createCustomerProfileControllerFactory;
        $this->_createCustomerPaymentProfileControllerFactory = $createCustomerPaymentProfileControllerFactory;
        $this->paymentProfile = $payment;
        $this->_initMerchantAuthentication();
    }

    /**
     * @param \net\authorize\api\contract\v1\TransactionRequestType $transaction
     * @return \net\authorize\api\contract\v1\CreateTransactionResponse
     */
    public function refund($transaction)
    {
        return $this->_submitTransactionRequest($transaction);
    }

    /**
     * @param \net\authorize\api\contract\v1\TransactionRequestType $transaction
     * @return \net\authorize\api\contract\v1\CreateTransactionResponse
     */
    public function void($transaction)
    {
        return $this->_submitTransactionRequest($transaction);
    }

    /**
     * @param \net\authorize\api\contract\v1\TransactionRequestType $transaction
     * @return \net\authorize\api\contract\v1\CreateTransactionResponse
     */
    public function submitForSettlement($transaction)
    {
        return $this->_submitTransactionRequest($transaction);
    }

    /**
     * @param array $data
     * @return \net\authorize\api\contract\v1\CreateTransactionResponse
     */
    public function saleForNewProfile(array $data)
    {
        $data['payment']->setBillTo($data['bill_to_address']);
        $paymentProfiles = [$data['payment']];

        $customerProfile = $this->_customerProfileFactory->create();
        $customerProfile->setMerchantCustomerId($this->_createCustomerMerchantId($data));
        $customerProfile->setDescription($this->_createCustomerDescription($data));
        $customerProfile->setPaymentProfiles($paymentProfiles);
        $customerProfile->setEmail($data['bill_to_address']->getEmail());
        $customerProfileResponse = $this->_createCustomerProfile($customerProfile);

        $data['payment_profile'] = $customerProfileResponse->getCustomerPaymentProfileIdList()[0];
        $this->paymentProfile->setProfileId($data['payment_profile']);
        $data['profile_id'] = $customerProfileResponse->getCustomerProfileId();

        if ($data['customer_id']) {
            $this->_saveCustomerProfileId(
                $data['customer_id'],
                $data['profile_id']
            );
        }

        return $this->_sale($data);
    }

    /**
     * @param array $data
     * @return \net\authorize\api\contract\v1\CreateTransactionResponse
     */
    public function saleForExistingProfile(array $data)
    {
        $data['payment']->setBillTo($data['bill_to_address']);
        $customerPaymentProfileResponse = $this->_createCustomerPaymentProfile($data);

        //TODO: if this has an error it should throw an exception. invalid authnet
        // profile_id really mess this up

        $data['payment_profile'] = $customerPaymentProfileResponse->getCustomerPaymentProfileId();

        $this->paymentProfile->setProfileId($data['payment_profile']);

        return $this->_sale($data);
    }

    /**
     * @param array $data
     * @return \net\authorize\api\contract\v1\CreateTransactionResponse
     */
    public function saleForVault(array $data)
    {
        return $this->_sale($data);
    }

    /**
     * @param array $data
     * @return \net\authorize\api\contract\v1\CreateTransactionResponse
     */
    protected function _sale(array $data)
    {
        $paymentProfile = $this->_paymentProfileFactory->create();
        $paymentProfile->setPaymentProfileId($data['payment_profile']);

        $customerProfilePayment = $this->_customerProfilePaymentFactory->create();
        $customerProfilePayment->setCustomerProfileId($data['profile_id']);
        $customerProfilePayment->setPaymentProfile($paymentProfile);

        $data['transaction_request']->setProfile($customerProfilePayment);

        if ($data['capture']) {
            $data['transaction_request']->setTransactionType('authCaptureTransaction');
        }

        return $this->_submitTransactionRequest($data['transaction_request']);
    }

    /**
     * @param $customerId
     * @param $customerProfileId
     * @return $this
     */
    protected function _saveCustomerProfileId($customerId, $customerProfileId)
    {
        $customer = $this->_customerRepository->getById($customerId);
        $customer->setCustomAttribute(
            'authorizenet_cim_profile_id',
            $customerProfileId
        );

        $this->_customerRepository->save($customer);

        return $this;
    }

    /**
     * @param \net\authorize\api\contract\v1\TransactionRequestType $transaction
     * @return \net\authorize\api\contract\v1\CreateTransactionResponse
     */
    protected function _submitTransactionRequest($transaction)
    {
        $transactionRequest = $this->_createTransactionRequestFactory->create();
        $transactionRequest->setMerchantAuthentication($this->_merchangeAuthentication);
        $transactionRequest->setTransactionRequest($transaction);

        $controller = $this->_createTransactionControllerFactory->create($transactionRequest);

        return $controller->executeWithApiResponse($this->_getEnvironment());
    }

    /**
     * @param \net\authorize\api\contract\v1\CustomerProfileType $customerProfile
     * @return \net\authorize\api\contract\v1\CreateCustomerProfileResponse
     * @throws PaymentException
     */
    protected function _createCustomerProfile($customerProfile)
    {
        $customerProfileRequest = $this->_createCustomerProfileRequestFactory->create();
        $customerProfileRequest->setProfile($customerProfile);
        $customerProfileRequest->setMerchantAuthentication($this->_merchangeAuthentication);
        $customerProfileRequest->setValidationMode($this->_config->getValidationMode());

        $controller = $this->_createCustomerProfileControllerFactory->create($customerProfileRequest);

        $result = $controller->executeWithApiResponse($this->_getEnvironment());

        if ($result->getMessages()->getResultCode() === 'Error') {
            throw new PaymentException(
                __('Profile could not be created.')
            );
        }

        return $result;
    }

    /**
     * @param array $data
     * @return \net\authorize\api\contract\v1\CreateCustomerPaymentProfileResponse
     */
    protected function _createCustomerPaymentProfile(array $data)
    {
        $customerPaymentProfileRequest = $this->_createCustomerPaymentProfileRequestFactory->create();
        $customerPaymentProfileRequest->setMerchantAuthentication($this->_merchangeAuthentication);
        $customerPaymentProfileRequest->setCustomerProfileId($data['profile_id']);
        $customerPaymentProfileRequest->setPaymentProfile($data['payment']);
        $customerPaymentProfileRequest->setValidationMode($this->_config->getValidationMode());

        $controller = $this->_createCustomerPaymentProfileControllerFactory->create($customerPaymentProfileRequest);

        return $controller->executeWithApiResponse($this->_getEnvironment());
    }

    /**
     * @param array $data
     * @return bool|mixed
     */
    protected function _createCustomerMerchantId(array $data)
    {
        if ($data['customer_id']) {
            return $data['customer_id'];
        }

        return false;
    }

    /**
     * @param array $data
     * @return string
     */
    protected function _createCustomerDescription(array $data)
    {
        if ($data['customer_id']) {
            return $data['bill_to_address']->getEmail();
        }

        return $data['guest_description'];
    }

    /** @return $this */
    protected function _initMerchantAuthentication()
    {
        $this->_merchangeAuthentication->setName($this->_config->getApiLoginId());
        $this->_merchangeAuthentication->setTransactionKey($this->_config->getTransactionKey());

        return $this;
    }

    /** @return string */
    protected function _getEnvironment()
    {
        if ($this->_config->isTest()) {
            return ANetEnvironment::SANDBOX;
        }

        return ANetEnvironment::PRODUCTION;
    }
}
