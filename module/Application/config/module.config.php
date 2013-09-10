<?php

/**
 * Zend Framework (http://framework.zend.com/)
 *
 * @link      http://github.com/zendframework/ZendSkeletonApplication for the canonical source repository
 * @copyright Copyright (c) 2005-2013 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace Application;

use Application\Service\Auth\AuthenticationService;
use Application\Controller\UserController;

return array(
	'router' => array(
		'routes' => array(
			'home' => array(
				'type' => 'Zend\Mvc\Router\Http\Literal',
				'options' => array(
					'route' => '/',
					'defaults' => array(
						'controller' => 'Application\Controller\Index',
						'action' => 'index',
					),
				),
			),
			// The following is a route to simplify getting started creating
			// new controllers and actions without needing to create a new
			// module. Simply drop new controllers in, and you can access them
			// using the path /application/:controller/:action
			'application' => array(
				'type' => 'Literal',
				'options' => array(
					'route' => '/application',
					'defaults' => array(
						'__NAMESPACE__' => 'Application\Controller',
						'controller' => 'Index',
						'action' => 'index',
					),
				),
				'may_terminate' => true,
				'child_routes' => array(
					'default' => array(
						'type' => 'Segment',
						'options' => array(
							'route' => '/[:controller[/:action]]',
							'constraints' => array(
								'controller' => '[a-zA-Z][a-zA-Z0-9_-]*',
								'action' => '[a-zA-Z][a-zA-Z0-9_-]*',
							),
							'defaults' => array(
							),
						),
					),
				),
			),
			UserController::ROUTE_USER_MANAGE => array(
				'type' => 'Segment',
				'options' => array(
					'route' => '/users[/:action][/:user][/:role]',
					'constraints' => array(
						'action' => '[a-zA-Z][a-zA-Z0-9_-]*',
						'user' => '\d+',
						'role' => '[a-zA-Z]+',
					),
					'defaults' => array(
						'__NAMESPACE__' => 'Application\Controller',
						'controller' => 'User',
						'action' => 'index',
						'role' => 'user',
					),
				),
				'may_terminate' => true,
			),
			UserController::ROUTE_RESET => array(
				'type' => 'Segment',
				'options' => array(
					'route' => '/reset/:userHash/:loginHash[/[:reset]]',
					'constraints' => array(
						'userHash' => '[a-zA-Z0-9_-]+',
						'loginHash' => '[a-zA-Z0-9_-]+',
						'reset' => '[0|1]',
					),
					'defaults' => array(
						'__NAMESPACE__' => 'Application\Controller',
						'controller' => 'User',
						'action' => 'reset',
					),
				),
				'may_terminate' => true,
			),
		),
	),
	'service_manager' => array(
		'abstract_factories' => array(
			'Zend\Cache\Service\StorageCacheAbstractServiceFactory',
			'Zend\Log\LoggerAbstractServiceFactory',
		),
		'aliases' => array(
			'translator' => 'MvcTranslator',
			'application_auth_service' => 'zfcuser_auth_service',
		),
		'factories' => array(
			// override default authentication service to use a service which returns a null user.
			'zfcuser_auth_service' => function ($sm) {
				return new AuthenticationService(
						$sm->get('ZfcUser\Authentication\Storage\Db'), $sm->get('ZfcUser\Authentication\Adapter\AdapterChain')
				);
			},
		),
		'invokables' => array(
			'email' => 'Application\Service\Email',
			'token' => 'Application\Service\Token',
			'invitation' => 'Application\Service\Invitation',
			'permission_service' => 'Application\Service\Permission',
			'route_service' => 'Application\Service\RouteListener',
			'page_vars' => 'Application\Service\PageVars',
			'page_response' => 'Application\Service\PageResponse',
			'e2f' => 'Application\Service\EntityToForm',
			'event_hook' => 'Application\Service\EventHook',
			'create_account_service' => 'Application\Service\CreateAccountListener',
			/** user services * */
			// update
			'user_update_factory' => 'Application\Factory\Update\User',
			'user_update_service' => 'Application\Service\Update\User',
			'user_create_service' => 'Application\Service\Update\User\Create',
			'user_reset_service' => 'Application\Service\Update\User\Reset',
			// search
			'user_search_factory' => 'Application\Factory\Search\User',
			'user_search_service' => 'Application\Service\Search\User',
		),
	),
	'translator' => array(
		'locale' => 'en_US',
		'translation_file_patterns' => array(
			array(
				'type' => 'gettext',
				'base_dir' => __DIR__ . '/../language',
				'pattern' => '%s.mo',
			),
		),
	),
	'controllers' => array(
		'invokables' => array(
			'Application\Controller\Index' => 'Application\Controller\IndexController',
			'Application\Controller\User' => 'Application\Controller\UserController',
		),
	),
	'controller_plugins' => array(
		'invokables' => array(
			'jsonResponse' => 'Application\Controller\Plugin\JsonResponse',
		),
	),
	'view_manager' => array(
		'display_not_found_reason' => true,
		'display_exceptions' => true,
		'doctype' => 'HTML5',
		'not_found_template' => 'error/404',
		'exception_template' => 'error/index',
		'template_map' => array(
			'layout/layout' => __DIR__ . '/../view/layout/layout.phtml',
			'application/index/index' => __DIR__ . '/../view/application/index/index.phtml',
			'error/404' => __DIR__ . '/../view/error/404.phtml',
			'error/index' => __DIR__ . '/../view/error/index.phtml',
		),
		'template_path_stack' => array(
			__DIR__ . '/../view',
			__DIR__ . '/../view/partials',
		),
		'strategies' => array(
			'ViewJsonStrategy',
		),
	),
	'view_helpers' => array(
		'factories' => array(
			'flashMessages' => function($sm) {
				$flashmessenger = $sm->getServiceLocator()
						->get('ControllerPluginManager')
						->get('flashmessenger');

				$messages = new \Application\View\Helper\FlashMessages();
				$messages->setFlashMessenger($flashmessenger);

				return $messages;
			},
			'userIdentity' => function ($sm) {
				$locator = $sm->getServiceLocator();
				$viewHelper = new View\Helper\UserIdentity();
				$viewHelper->setAuthService($locator->get('zfcuser_auth_service'));
				return $viewHelper;
			},
		),
		'invokables' => array(
			'initNg' => 'Application\View\Helper\InitNg',
			'pageNg' => 'Application\View\Helper\PageNg',
		),
	),
	// Placeholder for console routes
	'console' => array(
		'router' => array(
			'routes' => array(
			),
		),
	),
	'doctrine' => array(
		'driver' => array(
			__NAMESPACE__ . '_entity' => array(
				'class' => 'Doctrine\ORM\Mapping\Driver\AnnotationDriver',
				'cache' => 'array',
				'paths' => array(__DIR__ . '/../src/' . __NAMESPACE__ . '/Entity')
			),
			'orm_default' => array(
				'drivers' => array(
					__NAMESPACE__ . '\Entity' => __NAMESPACE__ . '_entity'
				)
			)
		),
	),
);
