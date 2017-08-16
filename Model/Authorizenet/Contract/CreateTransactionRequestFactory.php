<?php

namespace Pmclain\AuthorizenetCim\Model\Authorizenet\Contract;

use net\authorize\api\contract\v1\CreateTransactionRequest;
use Pmclain\AuthorizenetCim\Model\Authorizenet\AbstractFactory;

class CreateTransactionRequestFactory extends AbstractFactory
{
  public function create($sourceData = null)
  {
    return $this->_objectManager->create(CreateTransactionRequest::class);
  }
}