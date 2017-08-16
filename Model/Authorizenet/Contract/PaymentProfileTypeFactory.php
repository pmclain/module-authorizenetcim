<?php

namespace Pmclain\AuthorizenetCim\Model\Authorizenet\Contract;

use net\authorize\api\contract\v1\PaymentProfileType;
use Pmclain\AuthorizenetCim\Model\Authorizenet\AbstractFactory;

class PaymentProfileTypeFactory extends AbstractFactory
{
  public function create($sourceData = null)
  {
    return $this->_objectManager->create(PaymentProfileType::class);
  }
}