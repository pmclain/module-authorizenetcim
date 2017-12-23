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

namespace Pmclain\AuthorizenetCim\Observer;

use Magento\Framework\Event\Observer;
use Magento\Payment\Observer\AbstractDataAssignObserver;

class DataAssignObserver extends AbstractDataAssignObserver
{
  /**
   * @param Observer $observer
   * @return void
   */
  public function execute(Observer $observer)
  {
    $paymentInfo = $this->readPaymentModelArgument($observer);
    $data = $this->readDataArgument($observer);
    $additionalData = $data->getDataByKey('additional_data');

    if (isset($additionalData['public_hash'])) {
      return;
    }

    $paymentInfo->setAdditionalInformation('cc_token', $additionalData['cc_token']);
    $paymentInfo->setAdditionalInformation('cc_last4', $additionalData['cc_last4']);
    $paymentInfo->setAdditionalInformation('cc_exp_month', $additionalData['cc_exp_month']);
    $paymentInfo->setAdditionalInformation('cc_exp_year', $additionalData['cc_exp_year']);
    $paymentInfo->setAdditionalInformation('cc_type', $additionalData['cc_type']);
  }
}