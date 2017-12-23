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

namespace Pmclain\AuthorizenetCim\Block\Vault;

use Magento\Framework\View\Element\Template;
use Magento\Vault\Model\CustomerTokenManagement;
use Pmclain\AuthorizenetCim\Model\Ui\ConfigProvider;
use Magento\Payment\Model\CcConfigProvider;

class Tokens extends \Magento\Framework\View\Element\Template
{
    /** @var CustomerTokenManagement */
    protected $customerTokenManagement;

    /** @var CcConfigProvider */
    protected $configProvider;

    /** @var array */
    protected $icons;

    public function __construct(
        Template\Context $context,
        CustomerTokenManagement $customerTokenManagement,
        CcConfigProvider $configProvider,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->customerTokenManagement = $customerTokenManagement;
        $this->configProvider = $configProvider;
    }

    /** @return \Magento\Vault\Api\Data\PaymentTokenInterface[] */
    public function getCustomerTokens()
    {
        $tokens = $this->customerTokenManagement->getCustomerSessionTokens();
        $methodTokens = [];

        foreach ($tokens as $token) {
            if ($token->getPaymentMethodCode() === ConfigProvider::CODE) {
                $methodTokens[] = $token;
            }
        }

        return $methodTokens;
    }

    /**
     * @param string $code
     * @return false|array
     */
    public function getIcon($code)
    {
        if (isset($this->getIcons()[$code])) {
            return $this->getIcons()[$code];
        }

        return false;
    }

    /** @return array */
    protected function getIcons()
    {
        if ($this->icons) {
            return $this->icons;
        }

        $this->icons = $this->configProvider->getIcons();

        return $this->icons;
    }
}
