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
                    $transactionResponse = $response->getTransactionResponse();
                    return [
                        count($transactionResponse->getErrors()) === 0,
                        [__($transactionResponse->getMessages()[0]->getDescription())]
                    ];
                }
            ]
        );
    }
}
