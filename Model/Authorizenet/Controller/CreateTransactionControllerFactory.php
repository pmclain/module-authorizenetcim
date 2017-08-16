<?php

namespace Pmclain\AuthorizenetCim\Model\Authorizenet\Controller;

use net\authorize\api\controller\CreateTransactionController;
use Pmclain\AuthorizenetCim\Model\Authorizenet\AbstractFactory;

class CreateTransactionControllerFactory extends AbstractFactory
{
  public function create($sourceData = null)
  {
    return $this->_objectManager->create(CreateTransactionController::class, ['request' => $sourceData]);
  }
}