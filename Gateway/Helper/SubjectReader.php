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

namespace Pmclain\AuthorizenetCim\Gateway\Helper;

use Magento\Payment\Gateway\Helper;

class SubjectReader
{
    public function readResponseObject(array $subject)
    {
        $response = Helper\SubjectReader::readResponse($subject);

        if (!is_object($response['object'])) {
            throw new \InvalidArgumentException('Response object does not exist.');
        }

        return $response['object'];
    }

    public function readPayment(array $subject)
    {
        return Helper\SubjectReader::readPayment($subject);
    }

    public function readTransaction(array $subject)
    {
        if (!is_object($subject['object'])) {
            throw new \InvalidArgumentException('Response object does not exist');
        }

        return $subject['object'];
    }

    public function readAmount(array $subject)
    {
        return Helper\SubjectReader::readAmount($subject);
    }
}
