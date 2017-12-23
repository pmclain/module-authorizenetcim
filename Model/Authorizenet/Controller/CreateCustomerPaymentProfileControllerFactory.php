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

namespace Pmclain\AuthorizenetCim\Model\Authorizenet\Controller;

use net\authorize\api\controller\CreateCustomerPaymentProfileController;
use Pmclain\AuthorizenetCim\Model\Authorizenet\AbstractFactory;

class CreateCustomerPaymentProfileControllerFactory extends AbstractFactory
{
  /**
   * @param $sourceData
   * @return CreateCustomerPaymentProfileController
   */
  public function create($sourceData = null)
  {
    return $this->_objectManager->create(CreateCustomerPaymentProfileController::class, ['request' => $sourceData]);
  }
}