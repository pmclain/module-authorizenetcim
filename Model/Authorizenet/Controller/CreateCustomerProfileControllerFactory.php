<?php

namespace Pmclain\AuthorizenetCim\Model\Authorizenet\Controller;

use net\authorize\api\controller\CreateCustomerProfileController;
use Pmclain\AuthorizenetCim\Model\Authorizenet\AbstractFactory;

class CreateCustomerProfileControllerFactory extends AbstractFactory
{
  public function create($sourceData = null)
  {
    return $this->_objectManager->create(CreateCustomerProfileController::class, ['request' => $sourceData]);
  }
}