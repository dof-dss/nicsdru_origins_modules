<?php

namespace Drupal\origins_shamrock_admin\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Drupal\origins_shamrock_admin\Form\ShamrockAdminForm;
use Drupal\Core\Render\RendererInterface;

/**
 * Class ShamrockDataController.
 */
class ShamrockDataController extends ControllerBase {

  /**
   * Drupal renderer.
   *
   * @var \Drupal\Core\Render\RendererInterface
   */
  protected $renderer;

  /**
   * Constructs controller services.
   */
  public function __construct(RendererInterface $renderer) {
    $this->renderer = $renderer;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('renderer'),
    );
  }

  /**
   * Returns banner data.
   *
   * @return string
   *   Return banner data json array.
   */
  public function index() {
    $config = $this->config(ShamrockAdminForm::SETTINGS);
    $response = new JsonResponse();

    // If the Shamrock config has data build the banner, otherwise return
    // that it is not enabled.
    if ($config->get('modified')) {
      $modified = $config->get('modified');

      $build['banner'] = [
        '#theme' => 'origins_shamrock_banner',
        '#title' => $config->get('title'),
        '#body' => $config->get('body'),
        '#url' => $config->get('url'),
      ];

      $response->setContent(
        json_encode([
          'enabled' => $config->get('published'),
          'banner' => $this->renderer->render($build),
        ])
      );
    }
    else {
      $modified = time();
      $response->setContent(
        json_encode([
          'enabled' => FALSE,
        ])
      );
    }

    $response->setLastModified(new \DateTime('@' . $modified));

    return $response;
  }

}
