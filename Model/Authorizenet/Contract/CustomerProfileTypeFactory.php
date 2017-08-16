<?php

namespace Pmclain\AuthorizenetCim\Model\Authorizenet\Contract;

use net\authorize\api\contract\v1\CustomerProfileType;
use Pmclain\AuthorizenetCim\Model\Authorizenet\AbstractFactory;

class CustomerProfileTypeFactory extends AbstractFactory
{
  public function create($sourceData = null)
  {
    return $this->_objectManager->create(CustomerProfileType::class);
  }
}