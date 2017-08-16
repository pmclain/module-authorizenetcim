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

namespace Pmclain\AuthorizenetCim\Model\Authorizenet\Contract;

use net\authorize\api\contract\v1\CustomerPaymentProfileType;
use Pmclain\AuthorizenetCim\Model\Authorizenet\AbstractFactory;

class CustomerPaymentProfileTypeFactory extends AbstractFactory
{
  /**
   * @param $sourceData
   * @return CustomerPaymentProfileType
   */
  public function create($sourceData = null)
  {
    return $this->_objectManager->create(CustomerPaymentProfileType::class);
  }
}