<?php

namespace Application\Service\Update\User;

use Application\Entity\Base\User as UserEntity;
use Application\Service\Update\User as UserUpdateService;
use Application\Service\Update\User\Create\Exception\InvalidFileHeadings as InvalidFileHeadingsException;
use Zend\Form\Form;
use Application\Entity\Base;
use Application\Service\Permission;
use Application\Form\Validator\DoctrineNoRecordExists;

/**
 * service: user_create_service
 */
class Create extends UserUpdateService
{
	// batch upload file param

	const BATCH_FILE_UPLOAD_NAME = 'upload_invites';

	// for batch uploading storage
	/**
	 *
	 * @var array
	 */
	protected $_successes = array();

	/**
	 *
	 * @var array
	 */
	protected $_failures = array();

	/**
	 *
	 * @var boolean
	 */
	protected $_isBatch = false;

	/**
	 *
	 * @var array
	 */
	protected $_headings = array();

	public function __construct()
	{
		$this->_validationGroup = array(
			static::BASE_FORM_NAME => array(
				'email', 'username', 'state',
			),
		);
	}

	/**
	 * Add validators to make sure duplicate usernames and emails are not registered.
	 * @return type
	 */
	protected function _getFormConfigCallables()
	{
		if (empty($this->_formConfigCallables)) {
			$em = $this->_entityManager();
			$this->_formConfigCallables[static::BASE_FORM_NAME] = function($key, Form $form, Base $entity) use ($em) {
						$form->getInputFilter()->get('email')->getValidatorChain()->addValidator(new DoctrineNoRecordExists(array(
							'class' => 'Application\Entity\Base\User',
							'property' => 'email',
							'entityManager' => $em,
							'errorMessage' => 'A user with the email address "%value%" has already been registered.',
						)));
						$form->getInputFilter()->get('username')->getValidatorChain()->addValidator(new DoctrineNoRecordExists(array(
							'class' => 'Application\Entity\Base\User',
							'property' => 'username',
							'errorMessage' => 'A user with the user name "%value%" has already been registered.',
							'entityManager' => $em,
						)));
						return $form;
					};
		}
		return $this->_formConfigCallables;
	}

	public function isBatch($batch = null)
	{
		if ($batch === null) {
			return $this->_isBatch;
		}
		$this->_isBatch = $batch;
		return $this;
	}

	protected function _prePrepare()
	{
		$this->_setFormData('state', UserEntity::STATE_ACTIVE);
	}

	public function update()
	{
		if ($this->isBatch()) {
			return $this->_batchUpdate();
		}
		return parent::update();
	}

	protected function _batchUpdate()
	{
		/* @var $request Request */
		$request = $this->getServiceLocator()->get('request');
		$files = $request->getFiles()->toArray();

		if (!isset($files[static::BATCH_FILE_UPLOAD_NAME])) {
			$this->_success = false;
			$this->_message = 'No file uploaded.';
			return;
		}
		// open the file
		if (($handle = fopen($files[static::BATCH_FILE_UPLOAD_NAME]['tmp_name'], "r")) !== false) {
			$roleFactory = new \Application\Factory\Role();
			$role = $this->_getParam('role');
			// iterate over file contents
			try {
				while (($data = fgetcsv($handle)) !== false) {
					if (empty($data)) {
						continue;
					}
					// validate file contents
					if (!$this->_haveHeadings($data)) {
						continue;
					}
					// set the parameter data to associative array
					$this->_params = array_combine($this->_getRowKeys($data), $this->_getRowData($data));
					// create a new entity
					$this->_entity = $roleFactory->createUser($role);
					// persist it
					$this->_entityManager()->persist($this->_entity);
					// execute create logic on this row of data
					$this->_prepare();
					$this->_validate();
					$this->_postValidate();
					if ($this->success()) {
						$this->_successes[] = $this->message() . var_export();
					} else {
						$this->_failures[] = $this->message();
					}
				}
			} catch (InvalidFileHeadingsException $e) {
				$this->_success = false;
				$this->_message = $e->getMessage();
			}
		}

		return $this;
	}

	/**
	 * If no headings have been set, check that this file has the correct headings.
	 * @param type $data
	 * @return boolean
	 */
	protected function _haveHeadings(array $data)
	{
		if (empty($this->_headings)) {
			// check that the headings contain the proper
			$this->_headings = $this->_validateHeadings($data);
			return false;
		}
		return true;
	}

	/**
	 *
	 * @param array $data
	 * @return array
	 * @throws InvalidFileHeadingsException
	 */
	protected function _validateHeadings(array $data)
	{
		$headings = array();
		foreach ($data as $columnName) {
			$headings[] = $this->_sanitizeHeading($columnName);
		}
		return $headings;
//		throw new InvalidFileHeadingsException('File headings are not properly formatted.');
	}

	protected function _sanitizeHeading($heading)
	{
		return strtolower(trim(str_replace(' ', '', $heading)));
	}

	/**
	 *
	 * @param array $data
	 * @return array
	 */
	protected function _getRowKeys(array $data)
	{
		return $this->_headings;
	}

	/**
	 * @param array $data
	 * @return array
	 */
	protected function _getRowData(array $data)
	{
		return $data;
	}

	public function responseData()
	{
		if ($this->isBatch()) {
			return array(
				'failures' => $this->_failures,
				'successes' => $this->_successes,
			);
		} else {
			return parent::responseData();
		}
	}

	protected function _getSuccessMessage()
	{
		parent::_getSuccessMessage();
	}

	protected function _successData()
	{
		parent::_successData();
	}

}

