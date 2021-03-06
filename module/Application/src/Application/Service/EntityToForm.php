<?php

namespace Application\Service;

use DoctrineModule\Stdlib\Hydrator\DoctrineObject as DoctrineHydrator;
//use Application\Stdlib\Hydrator\DoctrineObject as DoctrineHydrator;
use Zend\Form\Annotation\AnnotationBuilder;

/**
 * service: e2f
 */
class EntityToForm extends BaseService
{

	public function convertEntity($entity)
	{
		if (empty($entity)) {
			return null;
		}
		$className = get_class($entity);
		$builder = new AnnotationBuilder();
		// use the entity annotations and create a zend form object
		$form = $builder->createForm($className);
		// set strategy for how to transfer data between form elements and
		$form->setHydrator(new DoctrineHydrator($this->_entityManager(), $className));
		// populate form with entity
		$form->bind($entity);
		return $form;
	}

	public function __invoke($entity)
	{
		return $this->convertEntity($entity);
	}

}

