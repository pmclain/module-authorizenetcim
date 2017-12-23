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

namespace Pmclain\AuthorizenetCim\Model\Ui;

use Magento\Checkout\Model\ConfigProviderInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;

class ConfigProvider implements ConfigProviderInterface
{
    const CODE = 'pmclain_authorizenetcim';
    const CC_VAULT_CODE = 'pmclain_authorizenetcim_vault';

    /** @var ScopeConfigInterface */
    protected $_config;

    /**
     * ConfigProvider constructor.
     * @param ScopeConfigInterface $config
     */
    public function __construct(ScopeConfigInterface $config)
    {
        $this->_config = $config;
    }

    public function getConfig()
    {
        return [
            'payment' => [
                self::CODE => [
                    'clientKey' => $this->_getClientKey(),
                    'apiLoginId' => $this->_getApiLoginId(),
                    'useccv' => $this->_getUseCcv(),
                    'vaultCode' => self::CC_VAULT_CODE,
                    'test' => $this->_getIsTest(),
                ]
            ]
        ];
    }

    protected function _getClientKey()
    {
        return $this->_getConfig('client_key');
    }

    protected function _getApiLoginId()
    {
        return $this->_getConfig('login');
    }

    protected function _getIsTest()
    {
        return $this->_getConfig('test');
    }

    protected function _getUseCcv()
    {
        return $this->_getConfig('useccv');
    }

    protected function _getConfig($value)
    {
        return $this->_config->getValue(
            'payment/pmclain_authorizenetcim/' . $value,
            ScopeInterface::SCOPE_STORE
        );
    }
}
