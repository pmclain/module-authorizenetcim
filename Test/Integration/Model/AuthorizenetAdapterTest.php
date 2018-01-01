<?php

namespace Pmclain\AuthorizenetCim\Model;

use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Customer\Api\Data\CustomerInterface;
use Magento\Framework\ObjectManagerInterface;
use PHPUnit\Framework\TestCase;
use Magento\TestFramework\Helper\Bootstrap;
use Pmclain\Authnet\CustomerProfile;
use Pmclain\Authnet\MerchantAuthentication;
use Pmclain\Authnet\Request\CreateCustomerProfile;
use Pmclain\Authnet\Request\DeleteCustomerProfile;
use Pmclain\Authnet\TransactionRequest;
use Pmclain\AuthorizenetCim\Gateway\Request\AddressDataBuilder;
use Pmclain\AuthorizenetCim\Gateway\Request\CustomerDataBuilder;
use Pmclain\AuthorizenetCim\Gateway\Request\PaymentDataBuilder;
use Pmclain\AuthorizenetCim\Model\Adapter\AuthorizenetAdapter;
use Pmclain\Authnet\PaymentProfile;

class AuthorizenetAdapterTest extends TestCase
{
    const RESULT_OK = 'Ok';
    const RESULT_ERROR = 'Error';

    /**
     * @var ObjectManagerInterface
     */
    private $objectManager;

    /**
     * @var bool
     */
    private $capture = false;

    /**
     * @var string
     */
    private $transactionType = TransactionRequest\TransactionType::TYPE_AUTH_ONLY;

    /**
     * @var float|int
     */
    private $amount = 5.00;

    /**
     * @var int|string
     */
    private $profileId;


    protected function setUp()
    {
        $this->objectManager = Bootstrap::getObjectManager();
        $this->amount = mt_rand(2, 100);
    }

    protected function tearDown()
    {
        if (!isset($this->profileId)) {
            return;
        }

        $deleteRequest = new DeleteCustomerProfile(true);
        $deleteRequest->setMerchantAuthentication($this->getMerchantAuth());
        $deleteRequest->setCustomerProfileId($this->profileId);
    }

    /**
     * @magentoDataFixture loadDataFixtureCustomer
     * @magentoConfigFixture current_store payment/pmclain_authorizenetcim/login 5KP3u95bQpv
     * @magentoConfigFixture current_store payment/pmclain_authorizenetcim/trans_key 346HZ32z3fP4hTG2
     */
    public function testSaleForNewProfile()
    {
        $adapter = $this->getAdapter();

        $result = $adapter->saleForNewProfile($this->getBuilderData());

        $this->assertEquals(
            self::RESULT_OK,
            $result->getMessages()->getData('resultCode')
        );

        $this->profileId = $result->getData('transactionResponse')->getProfile()->getData('customerProfileId');
    }

    /**
     * @magentoDataFixture loadDataFixtureCustomer
     * @magentoConfigFixture current_store payment/pmclain_authorizenetcim/login 5KP3u95bQpv
     * @magentoConfigFixture current_store payment/pmclain_authorizenetcim/trans_key 346HZ32z3fP4hTG2
     */
    public function testSaleForExistingProfile()
    {
        $adapter = $this->getAdapter();
        $data = $this->getBuilderData();

        $createCustomerProfile = new CreateCustomerProfile(true);
        $createCustomerProfile->setMerchantAuthentication($this->getMerchantAuth());
        $createCustomerProfile->setProfile($data[CustomerDataBuilder::CUSTOMER_PROFILE]);
        $profile = $createCustomerProfile->submit();

        $data[CustomerDataBuilder::PROFILE_ID] = $profile['customerProfileId'];

        $result = $adapter->saleForExistingProfile($data);

        $this->assertEquals(
            self::RESULT_OK,
            $result->getMessages()->getData('resultCode')
        );

        $this->profileId = $result->getData('transactionResponse')->getProfile()->getData('customerProfileId');
    }

    /**
     * @magentoDataFixture loadDataFixtureCustomer
     * @magentoConfigFixture current_store payment/pmclain_authorizenetcim/login 5KP3u95bQpv
     * @magentoConfigFixture current_store payment/pmclain_authorizenetcim/trans_key 346HZ32z3fP4hTG2
     */
    public function testSaleForVault()
    {
        $adapter = $this->getAdapter();
        $data = $this->getBuilderData();

        $data[CustomerDataBuilder::CUSTOMER_PROFILE]->setPaymentProfile($data[PaymentDataBuilder::PAYMENT]);

        $createCustomerProfile = new CreateCustomerProfile(true);
        $createCustomerProfile->setMerchantAuthentication($this->getMerchantAuth());
        $createCustomerProfile->setProfile($data[CustomerDataBuilder::CUSTOMER_PROFILE]);
        $profile = $createCustomerProfile->submit();

        $data[CustomerDataBuilder::PROFILE_ID] = $profile['customerProfileId'];
        $data[PaymentDataBuilder::PAYMENT_PROFILE] = $profile['customerPaymentProfileIdList'][0];

        $result = $adapter->saleForExistingProfile($data);

        $this->assertEquals(
            self::RESULT_OK,
            $result->getMessages()->getData('resultCode')
        );

        $this->profileId = $result->getData('transactionResponse')->getProfile()->getData('customerProfileId');
    }

    /**
     * @return AuthorizenetAdapter
     */
    private function getAdapter()
    {
        return $this->objectManager->create(AuthorizenetAdapter::class);
    }

    /**
     * @return CustomerInterface
     */
    private function getCustomerFixture()
    {
        /** @var CustomerRepositoryInterface $customerRepository */
        $customerRepository = $this->objectManager->create(CustomerRepositoryInterface::class);

        return $customerRepository->getById(1);
    }

    /**
     * @return array
     */
    private function getBuilderData()
    {
        return [
            PaymentDataBuilder::PAYMENT => $this->getPaymentProfile(),
            AddressDataBuilder::BILL_TO => $this->getBillToAddress(),
            CustomerDataBuilder::CUSTOMER_PROFILE => $this->getCustomerProfile(),
            CustomerDataBuilder::CUSTOMER_ID => $this->getCustomerFixture()->getId(),
            PaymentDataBuilder::TRANSACTION_REQUEST => $this->getTransactionRequest(),
            PaymentDataBuilder::CAPTURE => $this->capture,
        ];
    }

    private function getPaymentProfile()
    {
        $payment = new PaymentProfile\Payment\CreditCard();
        $payment->setCardNumber('4111111111111111');
        $payment->setExpirationDate(date('Y-m', strtotime('+1 year')));
        $payment->setCardCode('123');

        $paymentProfile = new PaymentProfile();
        $paymentProfile->setPayment($payment);
        $paymentProfile->setCustomerType(PaymentProfile\CustomerType::INDIVIDUAL);
        $paymentProfile->setDefaultPaymentProfile(true);
        
        return $paymentProfile;
    }

    private function getBillToAddress()
    {
        $address = new PaymentProfile\Address();
        $address->setFirstname('Test');
        $address->setLastname('Customer');
        $address->setAddress('123 Abc St');
        $address->setCity('Nope');
        $address->setState('MI');
        $address->setZip('12345');

        return $address;
    }

    private function getCustomerProfile()
    {
        $customer = $this->getCustomerFixture();

        $customerProfile = new CustomerProfile();
        $customerProfile->setCustomerId($customer->getId());
        $customerProfile->setEmail($customer->getEmail());

        return $customerProfile;
    }

    private function getTransactionRequest()
    {
        $request = new TransactionRequest();
        $request->setTransactionType($this->transactionType);
        $request->setAmount($this->amount);
        
        return $request;
    }

    private function getMerchantAuth()
    {
        return new MerchantAuthentication(
            '5KP3u95bQpv',
            '346HZ32z3fP4hTG2'
        );
    }
    
    public static function loadDataFixtureCustomer()
    {
        include __DIR__ . '/../_files/customer.php';
    }
}
