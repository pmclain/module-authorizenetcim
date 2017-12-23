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

class TokenDataBuilder implements BuilderInterface
{
  /** @var SubjectReader */
  protected $_subjectReader;

  public function __construct(
   SubjectReader $subjectReader
  ) {
    $this->_subjectReader = $subjectReader;
  }

  public function build(array $subject)
  {
    $paymentDataObject = $this->_subjectReader->readPayment($subject);
    $payment = $paymentDataObject->getPayment();

    $extensionAttributes = $payment->getExtensionAttributes();
    $paymentToken = $extensionAttributes->getVaultPaymentToken();

    return ['payment_profile' => $paymentToken->getGatewayToken()];
  }
}