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

namespace Pmclain\AuthorizenetCim\Test\Unit\Gateway\Command;

use Pmclain\AuthorizenetCim\Gateway\Command\CaptureStrategyCommand;
use \PHPUnit_Framework_MockObject_MockObject as MockObject;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Pmclain\AuthorizenetCim\Gateway\Helper\SubjectReader;
use Magento\Payment\Gateway\Data\PaymentDataObjectInterface;
use Magento\Sales\Model\Order\Payment;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Api\SearchCriteria;
use Magento\Sales\Api\TransactionRepositoryInterface;
use Magento\Sales\Api\Data\TransactionSearchResultInterface;
use Magento\Framework\Api\FilterBuilder;
use Magento\Payment\Gateway\Command\CommandPoolInterface;

class CaptureStrategyCommandTest extends \PHPUnit_Framework_TestCase
{
  /** @var CaptureStrategyCommand */
  private $strategyCommand;

  /** @var SubjectReader|MockObject */
  private $subjectReaderMock;

  /** @var PaymentDataObjectInterface|MockObject */
  private $paymentDataObjectMock;

  /** @var Payment|MockObject */
  private $paymentMock;

  /** @var SearchCriteriaBuilder|MockObject */
  private $searchCriteriaBuilderMock;

  /** @var SearchCriteria|MockObject */
  private $searchCriteriaMock;

  /** @var TransactionRepositoryInterface|MockObject */
  private $transactionRepositoryMock;

  /** @var TransactionSearchResultInterface|MockObject */
  private $transactionSearchResultMock;

  /** @var FilterBuilder|MockObject */
  private $filterBuilderMock;

  /** @var CommandPoolInterface|MockObject */
  private $commandPoolMock;

  protected function setUp()
  {
    $objectManager = new ObjectManager($this);

    $this->subjectReaderMock = $this->getMockBuilder(SubjectReader::class)
      ->disableOriginalConstructor()
      ->setMethods(['readPayment'])
      ->getMock();

    $this->paymentDataObjectMock = $this->getMockBuilder(PaymentDataObjectInterface::class)
      ->setMethods(['getPayment'])
      ->getMockForAbstractClass();

    $this->paymentMock = $this->getMockBuilder(Payment::class)
      ->disableOriginalConstructor()
      ->setMethods(['getAuthorizationTransaction', 'getId'])
      ->getMock();

    $this->searchCriteriaBuilderMock = $this->getMockBuilder(SearchCriteriaBuilder::class)
      ->disableOriginalConstructor()
      ->setMethods(['addFilters','create'])
      ->getMock();

    $this->searchCriteriaMock = $this->getMockBuilder(SearchCriteria::class)
      ->disableOriginalConstructor()
      ->getMock();

    $this->transactionRepositoryMock = $this->getMockBuilder(TransactionRepositoryInterface::class)
      ->setMethods(['getList'])
      ->getMockForAbstractClass();

    $this->transactionSearchResultMock = $this->getMockBuilder(TransactionSearchResultInterface::class)
      ->setMethods(['getTotalCount'])
      ->getMockForAbstractClass();

    $this->commandPoolMock = $this->getMockBuilder(CommandPoolInterface::class)
      ->setMethods(['get','execute'])
      ->getMockForAbstractClass();

    $this->filterBuilderMock = $this->getMockBuilder(FilterBuilder::class)
      ->disableOriginalConstructor()
      ->setMethods(['setField','setValue','create'])
      ->getMock();

    $this->subjectReaderMock->expects($this->once())
      ->method('readPayment')
      ->willReturn($this->paymentDataObjectMock);

    $this->paymentDataObjectMock->expects($this->once())
      ->method('getPayment')
      ->willReturn($this->paymentMock);

    $this->searchCriteriaBuilderMock->expects($this->exactly(2))
      ->method('addFilters')
      ->willReturnSelf();

    $this->filterBuilderMock->expects($this->exactly(2))
      ->method('setField')
      ->willReturnSelf();

    $this->filterBuilderMock->expects($this->exactly(2))
      ->method('setValue')
      ->willReturnSelf();

    $this->filterBuilderMock->expects($this->exactly(2))
      ->method('create')
      ->willReturnSelf();

    $this->searchCriteriaBuilderMock->expects($this->once())
      ->method('create')
      ->willReturn($this->searchCriteriaMock);

    $this->transactionRepositoryMock->expects($this->once())
      ->method('getList')
      ->willReturn($this->transactionSearchResultMock);

    $this->transactionSearchResultMock->expects($this->once())
      ->method('getTotalCount')
      ->willReturn(0);

    $this->strategyCommand = $objectManager->getObject(CaptureStrategyCommand::class,
      [
        '_subjectReader' => $this->subjectReaderMock,
        '_searchCriteriaBuilder' => $this->searchCriteriaBuilderMock,
        '_filterBuilder' => $this->filterBuilderMock,
        '_transactionRepository' => $this->transactionRepositoryMock,
        '_commandPool' => $this->commandPoolMock,
      ]
    );
  }

  /** @cover CaptureStrategyCommand::execute */
  public function testSaleExecute()
  {
    $this->paymentMock->expects($this->once())
      ->method('getAuthorizationTransaction')
      ->willReturn(false);

    $this->commandPoolMock->expects($this->once())
      ->method('get')
      ->with(CaptureStrategyCommand::SALE)
      ->willReturnSelf();

    $this->strategyCommand->execute([]);
  }

  /** @cover CaptureStrategyCommand::execute */
  public function testCaptureExecute()
  {
    $this->paymentMock->expects($this->once())
      ->method('getAuthorizationTransaction')
      ->willReturn(true);

    $this->commandPoolMock->expects($this->once())
      ->method('get')
      ->with(CaptureStrategyCommand::CAPTURE)
      ->willReturnSelf();

    $this->strategyCommand->execute([]);
  }
}