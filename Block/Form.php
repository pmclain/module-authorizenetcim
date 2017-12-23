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

namespace Pmclain\AuthorizenetCim\Block;

use Magento\Payment\Block\Form\Cc;
use Pmclain\AuthorizenetCim\Gateway\Config\Config;
use Magento\Payment\Model\Config as PaymentConfig;
use Magento\Framework\View\Element\Template\Context;
use Magento\Payment\Helper\Data as Helper;
use Pmclain\AuthorizenetCim\Model\Ui\ConfigProvider;

class Form extends Cc
{
  /** @var Config */
  protected $_config;

  /** @var Helper */
  protected $_helper;

  public function __construct(
    Context $context,
    PaymentConfig $paymentConfig,
    Config $config,
    Helper $helper,
    array $data = []
  ) {
    parent::__construct($context, $paymentConfig, $data);
    $this->_config = $config;
    $this->_helper = $helper;
  }

  /** @return bool */
  public function useCcv()
  {
    return $this->_config->isCcvEnabled();
  }

  /** @return bool */
  public function isVaultEnabled()
  {
    $storeId = $this->_storeManager->getStore()->getId();
    $vaultPayment = $this->getVaultPayment();
    return $vaultPayment->isActive($storeId);
  }

  /** @return \Magento\Vault\Model\VaultPaymentInterface */
  private function getVaultPayment()
  {
    return $this->_helper->getMethodInstance(ConfigProvider::CC_VAULT_CODE);
  }
}