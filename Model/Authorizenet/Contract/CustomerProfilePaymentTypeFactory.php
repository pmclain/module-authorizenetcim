<?php

namespace Pmclain\AuthorizenetCim\Model\Authorizenet\Contract;

use net\authorize\api\contract\v1\CustomerProfilePaymentType;
use Pmclain\AuthorizenetCim\Model\Authorizenet\AbstractFactory;

class CustomerProfilePaymentTypeFactory extends AbstractFactory
{
  public function create($sourceData = null)
  {
    return $this->_objectManager->create(CustomerProfilePaymentType::class);
  }
}