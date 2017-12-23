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

namespace Pmclain\AuthorizenetCim\Gateway\Config;

class Config extends \Magento\Payment\Gateway\Config\Config
{
  const KEY_ACTIVE = 'active';
  const KEY_USE_CCV = 'useccv';
  const KEY_LOGIN = 'login';
  const KEY_TRANSACTION_KEY = 'trans_key';
  const KEY_TEST = 'test';
  const KEY_CURRENCY = 'currency';
  const KEY_VALIDATION_MODE = 'validation_mode';

  /** @return bool */
  public function isActive()
  {
    return (bool) $this->getValue(self::KEY_ACTIVE);
  }

  /** @return bool */
  public function isCcvEnabled()
  {
    return (bool) $this->getValue(self::KEY_USE_CCV);
  }

  /** @return string */
  public function getApiLoginId()
  {
    return $this->getValue(self::KEY_LOGIN);
  }

  /** @return string */
  public function getTransactionKey()
  {
    return $this->getValue(self::KEY_TRANSACTION_KEY);
  }

  /** @return bool */
  public function isTest()
  {
    return (bool) $this->getValue(self::KEY_TEST);
  }

  /** @return string */
  public function getCurrency()
  {
    return $this->getValue(self::KEY_CURRENCY);
  }

  /** @return string */
  public function getValidationMode()
  {
    return $this->getValue(self::KEY_VALIDATION_MODE);
  }
}