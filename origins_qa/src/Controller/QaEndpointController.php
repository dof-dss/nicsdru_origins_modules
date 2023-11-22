<?php declare(strict_types = 1);

namespace Drupal\origins_qa\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * Returns responses for Origins Quality Assurance routes.
 */
final class QaEndpointController extends ControllerBase {

  /**
   * Enable QA accounts.
   */
  public function qa_users_enable() {
    $response = new JsonResponse();

    $ok = true;

    if ($ok) {
      $response->setStatusCode(200);
    } else {
      $response->setStatusCode(418);
    }

    return $response;
  }

  /**
   * Disable QA accounts.
   */
  public function qa_users_disable() {
    $response = new JsonResponse();

    $ok = false;

    if ($ok) {
      $response->setStatusCode(200);
    } else {
      $response->setStatusCode(418);
    }

    return $response;
  }

}
