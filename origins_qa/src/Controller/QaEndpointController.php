<?php declare(strict_types = 1);

namespace Drupal\origins_qa\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * Returns responses for Origins Quality Assurance routes.
 */
final class QaEndpointController extends ControllerBase {


  /**
   * The current request.
   *
   * @var \Symfony\Component\HttpFoundation\Request
   */
  protected $request;

  /**
   * Constructs a QaEndpointController object.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The current request.
   */
  public function __construct(Request $request) {
    $this->request = $request;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('request_stack')->getCurrentRequest()
    );
  }

  /**
   * Enable QA accounts.
   */
  public function qa_users_status($status = 'disable', $token) {
    // Check we have an HTTPS connection.
    // TODO: invalidate the token if it's sent using HTTP.
    if (!$this->request->isSecure()) {
      return new JsonResponse(null, 400);
    }

    // If we're on the production environment reject the request.
    if (getenv('PLATFORM_BRANCH') === 'main') {
      return new JsonResponse(null, 405);
    }

    // Reject if the token is incorrect.
    if ($token != getenv('QA_ENDPOINT_TOKEN')) {
      return new JsonResponse(null, 401);
    }


    $response = new JsonResponse();

    $ok = true;

    if ($ok) {
      $response->setStatusCode(200);
    } else {
      $response->setStatusCode(418);
    }

    return $response;
  }



}
