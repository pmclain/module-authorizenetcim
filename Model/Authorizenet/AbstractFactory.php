<?php

namespace Pmclain\AuthorizenetCim\Model\Authorizenet;

use Magento\Framework\ObjectManagerInterface;

abstract class AbstractFactory
{
  /** @var ObjectManagerInterface */
  protected $_objectManager;

  public function __construct(ObjectManagerInterface $objectManager)
  {
    $this->_objectManager = $objectManager;
  }

  abstract public function create($sourceData = null);
}