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

namespace Pmclain\AuthorizenetCim\Model\Adapter;

use Pmclain\AuthorizenetCim\Gateway\Config\Config;
use Magento\Framework\Exception\PaymentException;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Pmclain\Authnet\TransactionRequest;
use Pmclain\Authnet\CustomerProfile;
use Pmclain\Authnet\MerchantAuthentication;
use Pmclain\AuthorizenetCim\Gateway\Request\AddressDataBuilder;
use Pmclain\AuthorizenetCim\Gateway\Request\CustomerDataBuilder;
use Pmclain\AuthorizenetCim\Gateway\Request\PaymentDataBuilder;
use Pmclain\Authnet\Request\CreateCustomerProfileFactory;
use Pmclain\Authnet\Request\CreateCustomerProfile;
use Pmclain\Authnet\ValidationModeFactory;
use Pmclain\Authnet\ValidationMode;
use Magento\Framework\DataObjectFactory;
use Pmclain\AuthorizenetCim\Model\Authorizenet\Payment;
use Pmclain\Authnet\Request\CreateTransactionFactory;
use Pmclain\Authnet\Request\CreateTransaction;
use Pmclain\Authnet\Request\CreateCustomerPaymentProfileFactory;
use Pmclain\Authnet\Request\CreateCustomerPaymentProfile;

class AuthorizenetAdapter
{
    const ERROR_CODE_DUPLICATE = 'E00039';

    /**
     * @var CustomerRepositoryInterface
     */
    protected $customerRepository;

    /**
     * @var Config
     */
    protected $config;

    /**
     * @var MerchantAuthentication
     */
    protected $merchantAuth;

    /**
     * @var CreateCustomerProfileFactory
     */
    protected $createCustomerProfileFactory;

    /**
     * @var ValidationModeFactory
     */
    protected $validationModeFactory;

    /**
     * @var DataObjectFactory
     */
    protected $dataObjectFactory;

    /**
     * @var Payment
     */
    protected $paymentProfile;

    /**
     * @var CreateTransactionFactory
     */
    protected $createTransactionFactory;

    /**
     * @var CreateCustomerPaymentProfileFactory
     */
    protected $createPaymentProfileFactory;

    /**
     * AuthorizenetAdapter constructor.
     * @param CustomerRepositoryInterface $customerRepository
     * @param Config $config
     * @param MerchantAuthentication $merchantAuthentication
     * @param CreateCustomerProfileFactory $createCustomerProfileFactory
     * @param ValidationModeFactory $validationModeFactory
     * @param DataObjectFactory $dataObjectFactory
     * @param Payment $paymentProfile
     * @param CreateTransactionFactory $createTransactionFactory
     * @param CreateCustomerPaymentProfileFactory $createPaymentProfileFactory
     */
    public function __construct(
        CustomerRepositoryInterface $customerRepository,
        Config $config,
        MerchantAuthentication $merchantAuthentication,
        CreateCustomerProfileFactory $createCustomerProfileFactory,
        ValidationModeFactory $validationModeFactory,
        DataObjectFactory $dataObjectFactory,
        Payment $paymentProfile,
        CreateTransactionFactory $createTransactionFactory,
        CreateCustomerPaymentProfileFactory $createPaymentProfileFactory
    ) {
        $this->customerRepository = $customerRepository;
        $this->config = $config;
        $this->merchantAuth = $merchantAuthentication;
        $this->createCustomerProfileFactory = $createCustomerProfileFactory;
        $this->validationModeFactory = $validationModeFactory;
        $this->dataObjectFactory = $dataObjectFactory;
        $this->paymentProfile = $paymentProfile;
        $this->createTransactionFactory = $createTransactionFactory;
        $this->createPaymentProfileFactory = $createPaymentProfileFactory;
        $this->initMerchantAuthentication();
    }

    /**
     * @param TransactionRequest $transaction
     * @return array
     */
    public function refund($transaction)
    {
        return $this->submitTransactionRequest($transaction);
    }

    /**
     * @param TransactionRequest $transaction
     * @return array
     */
    public function void($transaction)
    {
        return $this->submitTransactionRequest($transaction);
    }

    /**
     * @param TransactionRequest $transaction
     * @return array
     */
    public function submitForSettlement($transaction)
    {
        return $this->submitTransactionRequest($transaction);
    }

    /**
     * @param array $data
     * @return array
     */
    public function saleForNewProfile(array $data)
    {
        $data[PaymentDataBuilder::PAYMENT]->setBillTo($data[AddressDataBuilder::BILL_TO]);
        $data[CustomerDataBuilder::CUSTOMER_PROFILE]->setPaymentProfile($data[PaymentDataBuilder::PAYMENT]);
        $customerProfileResponse = $this->createCustomerProfile($data[CustomerDataBuilder::CUSTOMER_PROFILE]);

        $data[PaymentDataBuilder::PAYMENT_PROFILE] = $customerProfileResponse->getData('customerPaymentProfileIdList')[0];
        $this->paymentProfile->setProfileId($data[PaymentDataBuilder::PAYMENT_PROFILE]);
        $data[CustomerDataBuilder::PROFILE_ID] = $customerProfileResponse->getData('customerProfileId');

        if ($data[CustomerDataBuilder::CUSTOMER_ID]) {
            $this->saveCustomerProfileId(
                $data[CustomerDataBuilder::CUSTOMER_ID],
                $data[CustomerDataBuilder::PROFILE_ID]
            );
        }

        return $this->sale($data);
    }

    /**
     * @param array $data
     * @return array
     */
    public function saleForExistingProfile(array $data)
    {
        $data[PaymentDataBuilder::PAYMENT]->setBillTo($data[AddressDataBuilder::BILL_TO]);
        $customerPaymentProfileResponse = $this->createCustomerPaymentProfile($data);

        //TODO: if this has an error it should throw an exception. invalid authnet
        // profile_id really mess this up

        $data[PaymentDataBuilder::PAYMENT_PROFILE] = $customerPaymentProfileResponse->getData('customerPaymentProfileId');

        $this->paymentProfile->setProfileId($data[PaymentDataBuilder::PAYMENT_PROFILE]);

        return $this->sale($data);
    }

    /**
     * @param array $data
     * @return array
     */
    public function saleForVault(array $data)
    {
        return $this->sale($data);
    }

    /**
     * @param array $data
     * @return array
     */
    protected function sale(array $data)
    {
        $data[PaymentDataBuilder::TRANSACTION_REQUEST]->setCustomerProfileId($data[CustomerDataBuilder::PROFILE_ID]);
        $data[PaymentDataBuilder::TRANSACTION_REQUEST]->setPaymentProfileId($data[PaymentDataBuilder::PAYMENT_PROFILE]);

        if ($data[PaymentDataBuilder::CAPTURE]) {
            $data[PaymentDataBuilder::TRANSACTION_REQUEST]->setTransactionType(TransactionRequest\TransactionType::TYPE_AUTH_CAPTURE);
        }

        return $this->submitTransactionRequest($data[PaymentDataBuilder::TRANSACTION_REQUEST]);
    }

    /**
     * @param $customerId
     * @param $customerProfileId
     * @return $this
     */
    protected function saveCustomerProfileId($customerId, $customerProfileId)
    {
        $customer = $this->customerRepository->getById($customerId);
        $customer->setCustomAttribute(
            'authorizenet_cim_profile_id',
            $customerProfileId
        );

        $this->customerRepository->save($customer);

        return $this;
    }

    /**
     * @param TransactionRequest $transaction
     * @return array
     */
    protected function submitTransactionRequest($transaction)
    {
        /**
         * @var CreateTransaction $createTransaction
         */
        $createTransaction = $this->createTransactionFactory->create(['sandbox' => $this->getIsSandbox()]);
        $createTransaction->setMerchantAuthentication($this->merchantAuth);
        $createTransaction->setTransactionRequest($transaction);

        return $this->createDataObject($createTransaction->submit());
    }

    /**
     * @param CustomerProfile $customerProfile
     * @return \Magento\Framework\DataObject
     * @throws PaymentException
     */
    protected function createCustomerProfile($customerProfile)
    {
        /**
         * @var CreateCustomerProfile $customerProfileRequest
         */
        $customerProfileRequest = $this->createCustomerProfileFactory->create(['sandbox' => $this->getIsSandbox()]);
        $customerProfileRequest->setProfile($customerProfile);
        $customerProfileRequest->setMerchantAuthentication($this->merchantAuth);
        $customerProfileRequest->setValidationMode($this->getValidationMode());

        $result = $this->createDataObject($customerProfileRequest->submit());

        if ($result->getMessages()->getData('resultCode') === 'Error') {
            if ($result->getMessages()->getMessage()[0]->getCode() !== self::ERROR_CODE_DUPLICATE) {
                throw new PaymentException(
                    __('Profile could not be created.')
                );
            }
        }

        return $result;
    }

    /**
     * @param array $data
     * @return \Magento\Framework\DataObject
     * @throws PaymentException
     */
    protected function createCustomerPaymentProfile(array $data)
    {
        /** @var CreateCustomerPaymentProfile $createPaymentProfileRequest */
        $createPaymentProfileRequest = $this->createPaymentProfileFactory->create(['sandbox' => $this->getIsSandbox()]);
        $createPaymentProfileRequest->setMerchantAuthentication($this->merchantAuth);
        $createPaymentProfileRequest->setCustomerProfileId($data[CustomerDataBuilder::PROFILE_ID]);
        $createPaymentProfileRequest->setPaymentProfile($data[PaymentDataBuilder::PAYMENT]);
        $createPaymentProfileRequest->setValidationMode($this->getValidationMode());

        $result = $this->createDataObject($createPaymentProfileRequest->submit());

        if ($result->getMessages()->getData('resultCode') === 'Error') {
            if ($result->getMessages()->getMessage()[0]->getCode() !== self::ERROR_CODE_DUPLICATE) {
                throw new PaymentException(
                    __('Profile could not be created.')
                );
            }
        }

        return $result;
    }

    /**
     * @return $this
     */
    protected function initMerchantAuthentication()
    {
        $this->merchantAuth->setLoginId($this->config->getApiLoginId());
        $this->merchantAuth->setTransactionKey($this->config->getTransactionKey());

        return $this;
    }

    /**
     * @return bool
     */
    protected function getIsSandbox()
    {
        return $this->config->isTest();
    }

    /**
     * @return ValidationMode
     */
    protected function getValidationMode()
    {
        /**
         * @var ValidationMode $validationMode
         */
        $validationMode = $this->validationModeFactory->create();

        try {
            $validationMode->set($this->config->getValidationMode());
            return $validationMode;
        } catch (\Pmclain\Authnet\Exception\InputException $e) {
            return $validationMode;
        }
    }

    /**
     * @param array $data
     * @return array|\Magento\Framework\DataObject
     */
    protected function createDataObject($data)
    {
        $convert = false;
        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $data[$key] = $this->createDataObject($value);
            }
            if (!is_numeric($key)) {
                $convert = true;
            }
        }
        return $convert ? $this->dataObjectFactory->create(['data' => $data]) : $data;
    }
}
