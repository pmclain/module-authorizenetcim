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

namespace Pmclain\AuthorizenetCim\Gateway\Config;

use Pmclain\AuthorizenetCim\Gateway\Helper\SubjectReader;
use Magento\Payment\Gateway\Config\ValueHandlerInterface;
use Magento\Sales\Api\Data\OrderPaymentInterface;

class CanVoidHandler implements ValueHandlerInterface
{
  /** @var SubjectReader */
  private $_subjectReader;

  /**
   * CanVoidHandler constructor.
   * @param SubjectReader $subjectReader
   */
  public function __construct(SubjectReader $subjectReader)
  {
    $this->_subjectReader = $subjectReader;
  }

  /**
   * @param array $subject
   * @param null|int $storeId
   * @return bool
   */
  public function handle(array $subject, $storeId = null)
  {
    $paymentDataObject = $this->_subjectReader->readPayment($subject);
    $payment = $paymentDataObject->getPayment();

    return $payment instanceof OrderPaymentInterface && !(bool)$payment->getAmountPaid();
  }
}