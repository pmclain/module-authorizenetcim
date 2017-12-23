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

namespace Pmclain\AuthorizenetCim\Gateway\Response;

use Pmclain\AuthorizenetCim\Gateway\Helper\SubjectReader;
use Magento\Payment\Gateway\Response\HandlerInterface;
use Magento\Payment\Model\InfoInterface;
use Magento\Sales\Api\Data\OrderPaymentExtensionInterface;
use Magento\Sales\Api\Data\OrderPaymentExtensionInterfaceFactory;
use Magento\Vault\Api\Data\PaymentTokenInterface;
use Magento\Vault\Model\CreditCardTokenFactory;
use Pmclain\AuthorizenetCim\Gateway\Config\Config;

class VaultDetailsHandler implements HandlerInterface
{
  /** @var CreditCardTokenFactory */
  protected $_paymentTokenFactory;

  /** @var OrderPaymentExtensionInterfaceFactory */
  protected $_paymentExtensionFactory;

  /** @var SubjectReader */
  protected $_subjectReader;

  /** @var Config */
  protected $_config;

  public function __construct(
    CreditCardTokenFactory $creditCardTokenFactory,
    OrderPaymentExtensionInterfaceFactory $paymentExtensionFactory,
    Config $config,
    SubjectReader $subjectReader
  ) {
    $this->_paymentTokenFactory = $creditCardTokenFactory;
    $this->_paymentExtensionFactory = $paymentExtensionFactory;
    $this->_subjectReader = $subjectReader;
    $this->_config = $config;
  }

  public function handle(array $subject, array $response)
  {
    $paymentDO = $this->_subjectReader->readPayment($subject);
    $transaction = $this->_subjectReader->readTransaction($response);
    $transaction = $transaction->getTransactionResponse();
    $payment = $paymentDO->getPayment();

    if(!$payment->getAdditionalInformation('is_active_payment_token_enabler')) {
      return;
    }

    $paymentToken = $this->getVaultPaymentToken($transaction, $payment);
    if (null !== $paymentToken) {
      $extensionAttributes = $this->_getExtensionAttributes($payment);
      $extensionAttributes->setVaultPaymentToken($paymentToken);
    }
  }

  private function getVaultPaymentToken($transaction, $payment)
  {
    // Check token existing in gateway response
    $paymentProfileId = $transaction->getProfile()->getCustomerPaymentProfileId();
    if (!isset($paymentProfileId)) {
      return null;
    }

    /** @var PaymentTokenInterface $paymentToken */
    $paymentToken = $this->_paymentTokenFactory->create();
    $paymentToken->setGatewayToken($paymentProfileId);
    $paymentToken->setExpiresAt($this->_getExpirationDate($payment));

    $paymentToken->setTokenDetails($this->_convertDetailsToJSON([
      'type' => $payment->getAdditionalInformation('cc_type'),
      'maskedCC' => $payment->getAdditionalInformation('cc_last4'),
      'expirationDate' => $payment->getAdditionalInformation('cc_exp_month') . '/' . $payment->getAdditionalInformation('cc_exp_year')
    ]));

    return $paymentToken;
  }

  private function _getExpirationDate($payment)
  {
    $expDate = new \DateTime(
      trim($payment->getAdditionalInformation('cc_exp_year'))
      . '-'
      . trim($payment->getAdditionalInformation('cc_exp_month'))
      . '-'
      . '01'
      . ' '
      . '00:00:00',
      new \DateTimeZone('UTC')
    );
    $expDate->add(new \DateInterval('P1M'));
    return $expDate->format('Y-m-d 00:00:00');
  }

  private function _convertDetailsToJSON($details)
  {
    $json = \Zend_Json::encode($details);
    return $json ? $json : '{}';
  }

  /**
   * Get payment extension attributes
   * @param InfoInterface $payment
   * @return OrderPaymentExtensionInterface
   */
  private function _getExtensionAttributes(InfoInterface $payment)
  {
    $extensionAttributes = $payment->getExtensionAttributes();
    if (null === $extensionAttributes) {
      $extensionAttributes = $this->_paymentExtensionFactory->create();
      $payment->setExtensionAttributes($extensionAttributes);
    }
    return $extensionAttributes;
  }
}