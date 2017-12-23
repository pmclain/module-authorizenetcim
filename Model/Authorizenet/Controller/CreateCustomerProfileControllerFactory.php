<?php
/**
 * Pmclain_AuthorizenetCim extension
 * NOTICE OF LICENSE
 *
 * This source file is subject to the GPL v3 License
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * https://www.gnu.org/licenses/gpl.txt
 *
 * @category  Pmclain
 * @package   Pmclain_AuthorizenetCim
 * @copyright Copyright (c) 2017
 * @license   https://www.gnu.org/licenses/gpl.txt GPL v3 License
 */

namespace Pmclain\AuthorizenetCim\Model\Authorizenet\Controller;

use net\authorize\api\controller\CreateCustomerProfileController;
use Pmclain\AuthorizenetCim\Model\Authorizenet\AbstractFactory;

class CreateCustomerProfileControllerFactory extends AbstractFactory
{
    /**
     * @param $sourceData
     * @return CreateCustomerProfileController
     */
    public function create($sourceData = null)
    {
        return $this->_objectManager->create(
            CreateCustomerProfileController::class,
            ['request' => $sourceData]
        );
    }
}
