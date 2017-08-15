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

namespace Pmclain\AuthorizenetCim\Gateway\Request\PaymentDataBuilder;

use Pmclain\AuthorizenetCim\Gateway\Request\PaymentDataBuilder;

class Vault extends PaymentDataBuilder
{
  public function build(array $subject)
  {
    $result = parent::build($subject);

    $paymentDataObject = $this->_subjectReader->readPayment($subject);
    $payment = $paymentDataObject->getPayment();

    $extensionAttributes = $payment->getExtensionAttributes();
    $paymentToken = $extensionAttributes->getVaultPaymentToken();

    $result['payment_profile'] = $paymentToken->getGatewayToken();

    return $result;
  }
}