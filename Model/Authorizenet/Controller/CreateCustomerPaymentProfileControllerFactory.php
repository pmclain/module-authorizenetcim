<?php

namespace Pmclain\AuthorizenetCim\Model\Authorizenet\Controller;

use net\authorize\api\controller\CreateCustomerPaymentProfileController;
use Pmclain\AuthorizenetCim\Model\Authorizenet\AbstractFactory;

class CreateCustomerPaymentProfileControllerFactory extends AbstractFactory
{
  public function create($sourceData = null)
  {
    return $this->_objectManager->create(CreateCustomerPaymentProfileController::class, ['request' => $sourceData]);
  }
}