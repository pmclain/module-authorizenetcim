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

namespace Pmclain\AuthorizenetCim\Gateway\Http\Client;

use Pmclain\AuthorizenetCim\Model\Adapter\AuthorizenetAdapter;
use Magento\Payment\Gateway\Http\ClientException;
use Magento\Payment\Gateway\Http\ClientInterface;
use Magento\Payment\Gateway\Http\TransferInterface;
use Magento\Payment\Model\Method\Logger;
use Psr\Log\LoggerInterface;

abstract class AbstractTransaction implements ClientInterface
{
  /** @var LoggerInterface */
  protected $_logger;

  /** @var Logger */
  protected $_customLogger;

  /** @var AuthorizenetAdapter */
  protected $_adapter;

  /**
   * AbstractTransaction constructor.
   * @param LoggerInterface $logger
   * @param Logger $customLogger
   * @param AuthorizenetAdapter $adapter
   */
  public function __construct(
    LoggerInterface $logger,
    Logger $customLogger,
    AuthorizenetAdapter $adapter
  ) {
    $this->_logger = $logger;
    $this->_customLogger = $customLogger;
    $this->_adapter = $adapter;
  }

  public function placeRequest(TransferInterface $transferObject)
  {
    $data = $transferObject->getBody();
    $log = [
      'request' => $data,
      'client' => static::class
    ];

    $response['object'] = [];

    try {
      $response['object'] = $this->process($data);
    } catch (\Exception $e) {
      $message = __($e->getMessage() ?: 'Sorry, but something went wrong.');
      $this->_logger->critical($message);
      throw new ClientException($message);
    } finally {
      $log['response'] = (array) $response['object'];
      $this->_customLogger->debug($log);
    }

    return $response;
  }

  abstract protected function process(array $data);
}