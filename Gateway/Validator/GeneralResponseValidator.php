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

namespace Pmclain\AuthorizenetCim\Gateway\Validator;

use Magento\Payment\Gateway\Validator\AbstractValidator;
use Pmclain\AuthorizenetCim\Gateway\Helper\SubjectReader;
use Magento\Payment\Gateway\Validator\ResultInterfaceFactory;

class GeneralResponseValidator extends AbstractValidator
{
  /** @var SubjectReader */
  protected $_subjectReader;

  public function __construct(
    ResultInterfaceFactory $resultInterfaceFactory,
    SubjectReader $subjectReader
  ) {
    parent::__construct($resultInterfaceFactory);
    $this->_subjectReader = $subjectReader;
  }

  public function validate(array $subject) {
    $response = $this->_subjectReader->readResponseObject($subject);

    $isValid = true;
    $errorMessages = [];

    foreach ($this->getResponseValidators() as $validator) {
      $validationResult = $validator($response);

      if(!$validationResult[0]) {
        $isValid = $validationResult[0];
        $errorMessages = array_merge($errorMessages, $validationResult[1]);
        break;
      }
    }

    return $this->createResult($isValid, $errorMessages);
  }

  protected function getResponseValidators() {
    return [
      function ($response) {
        return [
          $response->getMessages()->getResultCode() === 'Ok',
          [__($response->getMessages()->getMessage()[0]->getText())]
        ];
      }
    ];
  }
}