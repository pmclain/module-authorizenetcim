<?php

namespace Pmclain\AuthorizenetCim\Model\Authorizenet\Contract;

use net\authorize\api\contract\v1\CreateCustomerPaymentProfileRequest;
use Pmclain\AuthorizenetCim\Model\Authorizenet\AbstractFactory;

class CreateCustomerPaymentProfileRequestFactory extends AbstractFactory
{
  public function create($sourceData = null)
  {
    return $this->_objectManager->create(CreateCustomerPaymentProfileRequest::class);
  }
}