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

namespace Pmclain\AuthorizenetCim\Model\Ui\Adminhtml;

use Pmclain\AuthorizenetCim\Model\Ui\ConfigProvider;
use Magento\Vault\Api\Data\PaymentTokenInterface;
use Magento\Framework\View\Element\Template;
use Magento\Vault\Model\Ui\TokenUiComponentInterface;
use Magento\Vault\Model\Ui\TokenUiComponentProviderInterface;
use Magento\Vault\Model\Ui\TokenUiComponentInterfaceFactory;
use Magento\Framework\UrlInterface;

class TokenUiComponentProvider implements TokenUiComponentProviderInterface
{
  /** @var TokenUiComponentInterfaceFactory */
  private $_componentFactory;

  /** @var \Magento\Framework\UrlInterface */
  private $_urlBuilder;

  /**
   * @param TokenUiComponentInterfaceFactory $componentFactory
   * @param UrlInterface $urlBuilder
   */
  public function __construct(
    TokenUiComponentInterfaceFactory $componentFactory,
    UrlInterface $urlBuilder
  ) {
    $this->_componentFactory = $componentFactory;
    $this->_urlBuilder = $urlBuilder;
  }

  /**
   * Get UI component for token
   * @param PaymentTokenInterface $paymentToken
   * @return TokenUiComponentInterface
   */
  public function getComponentForToken(PaymentTokenInterface $paymentToken)
  {
    $jsonDetails = json_decode($paymentToken->getTokenDetails() ?: '{}', true);
    $component = $this->_componentFactory->create(
      [
        'config' => [
          'code' => ConfigProvider::CC_VAULT_CODE,
          TokenUiComponentProviderInterface::COMPONENT_DETAILS => $jsonDetails,
          TokenUiComponentProviderInterface::COMPONENT_PUBLIC_HASH => $paymentToken->getPublicHash(),
          'template' => 'Pmclain_AuthorizenetCim::form/vault.phtml'
        ],
        'name' => Template::class
      ]
    );

    return $component;
  }
}