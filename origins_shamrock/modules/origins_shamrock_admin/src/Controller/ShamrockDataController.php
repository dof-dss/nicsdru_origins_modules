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
   * @var Drupal\Core\Render\RendererInterface
   */
  protected $renderer;

  /**
   * Constructs a new ContactListingController object.
   */
  public function __construct(RendererInterface $renderer ) {
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

    $banner = $this->renderer->render($build);

    $response = new JsonResponse();
    $response->setContent(json_encode([
      'enabled' => $config->get('published'),
      'banner' => $banner,
    ]));

    $response->setLastModified(new \DateTime('@' . $config->get('modified')));

    return $response;
  }

}
