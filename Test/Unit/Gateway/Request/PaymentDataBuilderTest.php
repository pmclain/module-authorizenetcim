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

namespace Pmclain\AuthorizenetCim\Test\Unit\Gateway\Request;

/**
 * TODO: just look at the ridiculousness. the test subject class needs to
 * be refactored and probably broken up into separate builders. this is
 * garbage and i'm ashamed.
 */

use Pmclain\AuthorizenetCim\Gateway\Request\PaymentDataBuilder;
use \PHPUnit_Framework_MockObject_MockObject as MockObject;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Pmclain\AuthorizenetCim\Gateway\Helper\SubjectReader;
use Magento\Payment\Gateway\Data\PaymentDataObjectInterface;
use Magento\Payment\Model\InfoInterface;
use Magento\Payment\Gateway\Data\OrderAdapterInterface;
use Pmclain\AuthorizenetCim\Gateway\Config\Config;
use Pmclain\AuthorizenetCim\Model\Authorizenet\Contract\OpaqueDataTypeFactory;
use Pmclain\AuthorizenetCim\Model\Authorizenet\Contract\PaymentProfileTypeFactory;
use Pmclain\AuthorizenetCim\Model\Authorizenet\Contract\PaymentTypeFactory;
use Pmclain\AuthorizenetCim\Model\Authorizenet\Contract\TransactionRequestTypeFactory;
use Pmclain\AuthorizenetCim\Model\Authorizenet\Contract\OrderTypeFactory;
use Pmclain\AuthorizenetCim\Model\Authorizenet\Contract\CustomerPaymentProfileTypeFactory;
use net\authorize\api\contract\v1\OpaqueDataType;
use net\authorize\api\contract\v1\PaymentType;
use net\authorize\api\contract\v1\TransactionRequestType;
use net\authorize\api\contract\v1\OrderType;
use net\authorize\api\contract\v1\CustomerPaymentProfileType;

class PaymentDataBuilderTest extends \PHPUnit\Framework\TestCase
{
    /** @var PaymentDataBuilder */
    private $paymentDataBuilder;

    /** @var SubjectReader */
    private $subjectReader;

    /** @var PaymentDataObjectInterface|MockObject */
    private $paymentDataObjectMock;

    /** @var Config|MockObject */
    private $configMock;

    /** @var OpaqueDataTypeFactory|MockObject */
    private $opaqueDataFactoryMock;

    /** @var PaymentTypeFactory|MockObject */
    private $paymentFactoryMock;

    /** @var TransactionRequestTypeFactory|MockObject */
    private $transactionRequestFactoryMock;

    /** @var OrderTypeFactory|MockObject */
    private $orderFactoryMock;

    /** @var CustomerPaymentProfileTypeFactory|MockObject */
    private $paymentProfileFactoryMock;

    /** @var OpaqueDataType */
    private $opaqueData;

    /** @var PaymentType */
    private $paymentType;

    /** @var TransactionRequestType */
    private $transactionRequest;

    /** @var OrderType */
    private $orderType;

    /** @var CustomerPaymentProfileType */
    private $customerPaymentProfile;

    /** @var InfoInterface|MockObject */
    private $paymentMock;

    /** @var OrderAdapterInterface|MockObject */
    private $orderMock;

    public function setUp()
    {
        $objectManager = new ObjectManager($this);

        $this->subjectReader = $objectManager->getObject(SubjectReader::class);

        $this->paymentDataObjectMock = $this->getMockBuilder(PaymentDataObjectInterface::class)
            ->setMethods(['getPayment', 'getOrder'])
            ->getMockForAbstractClass();

        $this->paymentMock = $this->getMockBuilder(InfoInterface::class)
            ->setMethods(['getAdditionalInformation'])
            ->getMockForAbstractClass();

        $this->orderMock = $this->getMockBuilder(OrderAdapterInterface::class)
            ->setMethods(['getOrderIncrementId'])
            ->getMockForAbstractClass();

        $this->opaqueDataFactoryMock = $this->getMockBuilder(OpaqueDataTypeFactory::class)
            ->disableOriginalConstructor()
            ->setMethods(['create'])
            ->getMock();

        $this->opaqueData = $objectManager->getObject(OpaqueDataType::class);

        $this->paymentFactoryMock = $this->getMockBuilder(PaymentTypeFactory::class)
            ->disableOriginalConstructor()
            ->setMethods(['create'])
            ->getMock();

        $this->paymentType = $objectManager->getObject(PaymentType::class);

        $this->paymentProfileFactoryMock = $this->getMockBuilder(PaymentProfileTypeFactory::class)
            ->disableOriginalConstructor()
            ->setMethods(['create'])
            ->getMock();

        $this->customerPaymentProfile = $objectManager->getObject(CustomerPaymentProfileType::class);

        $this->orderFactoryMock = $this->getMockBuilder(OpaqueDataTypeFactory::class)
            ->disableOriginalConstructor()
            ->setMethods(['create'])
            ->getMock();

        $this->orderType = $objectManager->getObject(OrderType::class);

        $this->transactionRequestFactoryMock = $this->getMockBuilder(TransactionRequestTypeFactory::class)
            ->disableOriginalConstructor()
            ->setMethods(['create'])
            ->getMock();

        $this->transactionRequest = $objectManager->getObject(TransactionRequestType::class);

        $this->opaqueDataFactoryMock->expects($this->any())
            ->method('create')
            ->willReturn($this->opaqueData);

        $this->paymentFactoryMock->expects($this->any())
            ->method('create')
            ->willReturn($this->paymentType);

        $this->paymentProfileFactoryMock->expects($this->any())
            ->method('create')
            ->willReturn($this->customerPaymentProfile);

        $this->orderFactoryMock->expects($this->any())
            ->method('create')
            ->willReturn($this->orderType);

        $this->transactionRequestFactoryMock->expects($this->any())
            ->method('create')
            ->willReturn($this->transactionRequest);

        $this->transactionRequest = $objectManager->getObject(TransactionRequestType::class);

        $this->paymentDataBuilder = $objectManager->getObject(
            PaymentDataBuilder::class,
            [
                '_subjectReader' => $this->subjectReader,
                '_opaqueDataFactory' => $this->opaqueDataFactoryMock,
                '_paymentFactory' => $this->paymentFactoryMock,
                '_transactionRequestFactory' => $this->transactionRequestFactoryMock,
                '_orderFactory' => $this->orderFactoryMock,
                '_paymentProfileFactory' => $this->paymentProfileFactoryMock
            ]
        );
    }

    /** @cover PaymentDataBuilder::build */
    public function testBuild()
    {
        $ccToken = 'testToken';
        $orderIncrementId = '123456789';
        $curreny = 'USD';
        $saveInVault = false;
        $cardInfo = [
            'cc_type' => 'VI',
            'cc_last4' => '1111',
            'cc_exp_month' => '01',
            'cc_exp_year' => '2019'
        ];
        $amount = '10.01';

        $this->paymentDataObjectMock->expects($this->once())
            ->method('getPayment')
            ->willReturn($this->paymentMock);

        $this->paymentDataObjectMock->expects($this->once())
            ->method('getOrder')
            ->willReturn($this->orderMock);

        $this->paymentMock->expects($this->at(0))
            ->method('getAdditionalInformation')
            ->with('cc_token')
            ->willReturn($ccToken);

        $this->paymentMock->expects($this->at(1))
            ->method('getAdditionalInformation')
            ->with('is_active_payment_token_enabler')
            ->willReturn($saveInVault);

        $this->paymentMock->expects($this->at(2))
            ->method('getAdditionalInformation')
            ->with('cc_type')
            ->willReturn($cardInfo['cc_type']);

        $this->paymentMock->expects($this->at(3))
            ->method('getAdditionalInformation')
            ->with('cc_last4')
            ->willReturn($cardInfo['cc_last4']);

        $this->paymentMock->expects($this->at(4))
            ->method('getAdditionalInformation')
            ->with('cc_exp_month')
            ->willReturn($cardInfo['cc_exp_month']);

        $this->paymentMock->expects($this->at(5))
            ->method('getAdditionalInformation')
            ->with('cc_exp_year')
            ->willReturn($cardInfo['cc_exp_year']);

        $this->orderMock->expects($this->any())
            ->method('getOrderIncrementId')
            ->willReturn($orderIncrementId);

        $result = $this->paymentDataBuilder->build([
            'payment' => $this->paymentDataObjectMock,
            'amount' => $amount
        ]);

        $this->assertInstanceOf(
            TransactionRequestType::class,
            $result['transaction_request']
        );

        $this->assertInstanceOf(
            CustomerPaymentProfileType::class,
            $result['payment']
        );

        $this->assertFalse($result['save_in_vault']);

        $this->assertEquals($cardInfo, $result['payment_info']);
    }
}
