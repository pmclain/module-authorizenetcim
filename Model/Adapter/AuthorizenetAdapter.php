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

use Magento\Framework\Exception\PaymentException;
use net\authorize\api\contract\v1\MerchantAuthenticationType;
use Pmclain\AuthorizenetCim\Gateway\Config\Config;
use Magento\Customer\Api\CustomerRepositoryInterface;
use net\authorize\api\contract\v1\CreateCustomerProfileRequest;
use net\authorize\api\contract\v1\CreateCustomerPaymentProfileRequest;
use net\authorize\api\controller\CreateCustomerProfileController;
use net\authorize\api\controller\CreateCustomerPaymentProfileController;
use net\authorize\api\constants\ANetEnvironment;
use net\authorize\api\contract\v1\CustomerProfileType;
use net\authorize\api\contract\v1\CustomerProfilePaymentType;
use net\authorize\api\contract\v1\CreateTransactionRequest;
use net\authorize\api\controller\CreateTransactionController;
use net\authorize\api\contract\v1\PaymentProfileType;

class AuthorizenetAdapter
{
  /** @var MerchantAuthenticationType */
  protected $_merchangeAuthentication;

  /** @var CreateCustomerProfileRequest */
  protected $_createCustomerProfileRequest;

  /** @var CreateCustomerPaymentProfileRequest */
  protected $_createCustomerPaymentProfileRequest;

  /** @var CustomerProfileType */
  protected $_customerProfile;

  /** @var CustomerProfilePaymentType */
  protected $_customerProfilePayment;

  /** @var CreateTransactionRequest */
  protected $_createTransactionRequest;

  /** @var PaymentProfileType */
  protected $_paymentProfile;

  /** @var CustomerRepositoryInterface */
  protected $_customerRepository;

  /** @var Config */
  protected $_config;

  public function __construct(
    MerchantAuthenticationType $merchantAuthenticationType,
    CreateCustomerProfileRequest $createCustomerProfileRequest,
    CreateCustomerPaymentProfileRequest $createCustomerPaymentProfileRequest,
    CustomerProfileType $customerProfileType,
    CustomerProfilePaymentType $customerProfilePayment,
    CreateTransactionRequest $createTransactionRequest,
    PaymentProfileType $paymentProfileType,
    CustomerRepositoryInterface $customerRepository,
    Config $config
  ) {
    $this->_merchangeAuthentication = $merchantAuthenticationType;
    $this->_createCustomerProfileRequest = $createCustomerProfileRequest;
    $this->_createCustomerPaymentProfileRequest = $createCustomerPaymentProfileRequest;
    $this->_customerProfile = $customerProfileType;
    $this->_customerProfilePayment = $customerProfilePayment;
    $this->_createTransactionRequest = $createTransactionRequest;
    $this->_paymentProfile = $paymentProfileType;
    $this->_customerRepository = $customerRepository;
    $this->_config = $config;
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
    $this->_customerProfile->setMerchantCustomerId($this->_createCustomerMerchantId($data));
    $this->_customerProfile->setDescription($this->_createCustomerDescription($data));
    $this->_customerProfile->setPaymentProfiles($paymentProfiles);
    $this->_customerProfile->setEmail($data['bill_to_address']->getEmail());
    $customerProfileResponse = $this->_createCustomerProfile();

    $data['payment_profile'] = $customerProfileResponse->getCustomerPaymentProfileIdList()[0];
    $data['profile_id'] = $customerProfileResponse->getCustomerProfileId();

    if ($data['customer_id']) {
      $this->_saveCustomerProfileId($data['customer_id'], $data['profile_id']);
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

    $data['payment_profile'] = $customerPaymentProfileResponse->getCustomerPaymentProfileId();

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
    $this->_paymentProfile->setPaymentProfileId($data['payment_profile']);
    $this->_customerProfilePayment->setCustomerProfileId($data['profile_id']);
    $this->_customerProfilePayment->setPaymentProfile($this->_paymentProfile);

    $data['transaction_request']->setProfile($this->_customerProfilePayment);

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
    $customer->setCustomAttribute('authorizenet_cim_profile_id', $customerProfileId);

    $this->_customerRepository->save($customer);

    return $this;
  }

  /**
   * @param \net\authorize\api\contract\v1\TransactionRequestType $transaction
   * @return \net\authorize\api\contract\v1\CreateTransactionResponse
   */
  protected function _submitTransactionRequest($transaction)
  {
    $this->_createTransactionRequest->setMerchantAuthentication($this->_merchangeAuthentication);
    $this->_createTransactionRequest->setTransactionRequest($transaction);

    $controller = new CreateTransactionController($this->_createTransactionRequest);

    $result = $controller->executeWithApiResponse($this->_getEnvironment());

    return $result;
  }

  /**
   * @return \net\authorize\api\contract\v1\CreateCustomerProfileResponse
   * @throws PaymentException
   */
  protected function _createCustomerProfile()
  {
    $this->_createCustomerProfileRequest->setProfile($this->_customerProfile);
    $this->_createCustomerProfileRequest->setMerchantAuthentication($this->_merchangeAuthentication);
    $this->_createCustomerProfileRequest->setValidationMode($this->_config->getValidationMode());

    $controller = new CreateCustomerProfileController($this->_createCustomerProfileRequest);

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

  /**
   * @param array $data
   * @return \net\authorize\api\contract\v1\CreateCustomerPaymentProfileResponse
   */
  protected function _createCustomerPaymentProfile(array $data)
  {
    $this->_createCustomerPaymentProfileRequest->setMerchantAuthentication($this->_merchangeAuthentication);
    $this->_createCustomerPaymentProfileRequest->setCustomerProfileId($data['profile_id']);
    $this->_createCustomerPaymentProfileRequest->setPaymentProfile($data['payment']);
    $this->_createCustomerPaymentProfileRequest->setValidationMode($this->_config->getValidationMode());

    $controller = new CreateCustomerPaymentProfileController($this->_createCustomerPaymentProfileRequest);

    return $controller->executeWithApiResponse($this->_getEnvironment());
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