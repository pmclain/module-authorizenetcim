<?php

namespace Pmclain\AuthorizenetCim\Setup;

use Magento\Eav\Setup\EavSetupFactory;
use Magento\Framework\Setup\InstallDataInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Eav\Model\AttributeRepository;
use Magento\Customer\Model\Customer;

class InstallData implements InstallDataInterface
{
  /** @var EavSetupFactory */
  private $_eavSetupFactory;

  /** @var AttributeRepository */
  private $_eavAttributeRepository;

  /**
   * InstallData constructor.
   * @param EavSetupFactory $eavSetupFactory
   * @param AttributeRepository $attributeRepository
   */
  public function __construct(
    EavSetupFactory $eavSetupFactory,
    AttributeRepository $attributeRepository
  ) {
    $this->_eavSetupFactory = $eavSetupFactory;
    $this->_eavAttributeRepository = $attributeRepository;
  }

  public function install(ModuleDataSetupInterface $setup, ModuleContextInterface $context)
  {
    $eavSetup = $this->_eavSetupFactory->create(['setup' => $setup]);
    $eavSetup->addAttribute(
      Customer::ENTITY,
      'authorizenet_cim_profile_id',
      [
        'type' => 'varchar',
        'label' => 'Authorize.net CIM Profile ID',
        'input' => 'text',
        'required' => false,
        'sort_order' => 100,
        'system' => false,
        'position' => 100
      ]
    );

    $authorizenetCimProfileIdAttribute = $this->_eavAttributeRepository->get(Customer::ENTITY, 'authorizenet_cim_profile_id');
    $authorizenetCimProfileIdAttribute->setData('used_in_forms', ['adminhtml_customer']);
    $this->_eavAttributeRepository->save($authorizenetCimProfileIdAttribute);
  }
}