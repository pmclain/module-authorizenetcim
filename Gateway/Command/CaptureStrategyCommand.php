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

namespace Pmclain\AuthorizenetCim\Gateway\Command;

use Magento\Payment\Gateway\CommandInterface;
use Magento\Payment\Gateway\Command\CommandPoolInterface;
use Magento\Sales\Api\Data\OrderPaymentInterface;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Sales\Api\TransactionRepositoryInterface;
use Magento\Sales\Api\Data\TransactionInterface;
use Magento\Framework\Api\FilterBuilder;
use Pmclain\AuthorizenetCim\Gateway\Helper\SubjectReader;
use Magento\Payment\Gateway\Helper\ContextHelper;

class CaptureStrategyCommand implements CommandInterface
{
  const SALE = 'sale';
  const CAPTURE = 'settlement';

  /** @var SearchCriteriaBuilder */
  private $_searchCriteriaBuilder;

  /** @var TransactionRepositoryInterface */
  private $_transactionRepository;

  /** @var FilterBuilder */
  private $_filterBuilder;

  /** @var SubjectReader */
  private $_subjectReader;

  /** @var CommandPoolInterface */
  private $_commandPool;

  /**
   * CaptureStrategyCommand constructor.
   * @param SearchCriteriaBuilder $searchCriteriaBuilder
   * @param TransactionRepositoryInterface $transactionRepository
   * @param FilterBuilder $filterBuilder
   * @param SubjectReader $subjectReader
   * @param CommandPoolInterface $commandPool
   */
  public function __construct(
    SearchCriteriaBuilder $searchCriteriaBuilder,
    TransactionRepositoryInterface $transactionRepository,
    FilterBuilder $filterBuilder,
    SubjectReader $subjectReader,
    CommandPoolInterface $commandPool
  ) {
    $this->_searchCriteriaBuilder = $searchCriteriaBuilder;
    $this->_transactionRepository = $transactionRepository;
    $this->_filterBuilder = $filterBuilder;
    $this->_subjectReader = $subjectReader;
    $this->_commandPool = $commandPool;
  }

  /**
   * @param array $commandSubject
   * @return void
   */
  public function execute(array $commandSubject) {
    $paymentDataObject = $this->_subjectReader->readPayment($commandSubject);
    $paymentInfo = $paymentDataObject->getPayment();
    ContextHelper::assertOrderPayment($paymentInfo);

    $command = $this->getCommand($paymentInfo);
    $this->_commandPool->get($command)->execute($commandSubject);
  }

  /**
   * @param OrderPaymentInterface $payment
   * @return string
   */
  private function getCommand(OrderPaymentInterface $payment) {
    $existsCapture = $this->isExistsCaptureTransaction($payment);
    if(!$payment->getAuthorizationTransaction() && !$existsCapture) {
      return self::SALE;
    }

    if(!$existsCapture) {
      return self::CAPTURE;
    }
  }

  /**
   * @param OrderPaymentInterface $payment
   * @return bool
   */
  private function isExistsCaptureTransaction(OrderPaymentInterface $payment) {
    $this->_searchCriteriaBuilder->addFilters(
      [
        $this->_filterBuilder
          ->setField('payment_id')
          ->setValue($payment->getId())
          ->create()
      ]
    );

    $this->_searchCriteriaBuilder->addFilters(
      [
        $this->_filterBuilder
          ->setField('txn_type')
          ->setValue(TransactionInterface::TYPE_CAPTURE)
          ->create()
      ]
    );

    $searchCriteria = $this->_searchCriteriaBuilder->create();

    $count = $this->_transactionRepository->getList($searchCriteria)->getTotalCount();
    return (boolean) $count;
  }
}