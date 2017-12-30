<?php
/**
 * Pmclain_AuthorizenetCim extension
 * NOTICE OF LICENSE
 *
 * This source file is subject to the OSL 3.0 License
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * https://opensource.org/licenses/osl-3.0.php
 *
 * @category  Pmclain
 * @package   Pmclain_AuthorizenetCim
 * @copyright Copyright (c) 2017-2018
 * @license   Open Software License (OSL 3.0)
 */

namespace Pmclain\AuthorizenetCim\Gateway\Validator\ResponseValidator;

use Pmclain\AuthorizenetCim\Gateway\Validator\GeneralResponseValidator;

class Authorize extends GeneralResponseValidator
{
    protected function getResponseValidators()
    {
        return array_merge(
            parent::getResponseValidators(),
            [
                function ($response) {
                    $transactionResponse = $response->getData('transactionResponse');
                    return [
                        count($transactionResponse->getErrors()) === 0,
                        [__($transactionResponse->getMessages()[0]->getDescription())]
                    ];
                }
            ]
        );
    }
}
