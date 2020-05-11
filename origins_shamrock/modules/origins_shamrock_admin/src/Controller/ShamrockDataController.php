<?php

namespace Drupal\origins_shamrock_admin\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\JsonResponse;
use Drupal\origins_shamrock_admin\Form\ShamrockAdminForm;
/**
 * Class ShamrockDataController.
 */
class ShamrockDataController extends ControllerBase {

  /**
   * index.
   *
   * @return string
   *   Return banner data json array.
   */
  public function index() {
    $config = $this->config(ShamrockAdminForm::SETTINGS);

    $build['banner'] = [
      '#theme' => 'origins_shamrock_banner',
      '#title' => $config->get('title'),
      '#body' => $config->get('body'),
      '#url' => $config->get('url'),
    ];

    $renderer = \Drupal::service('renderer');
    $banner = $renderer->render($build);

    $response = new JsonResponse();
    $response->setContent(json_encode([
      'enabled' => $config->get('published'),
      'banner' => $banner,
    ]));

    $response->setLastModified(new \DateTime('@' . $config->get('modified')));

    return $response;
  }

}
