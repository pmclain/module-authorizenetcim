<?php

namespace Pmclain\AuthorizenetCim\Model\Authorizenet\Contract;

use net\authorize\api\contract\v1\CreateCustomerProfileRequest;
use Pmclain\AuthorizenetCim\Model\Authorizenet\AbstractFactory;

class CreateCustomerProfileRequestFactory extends AbstractFactory
{
  public function create($sourceData = null)
  {
    return $this->_objectManager->create(CreateCustomerProfileRequest::class);
  }
}