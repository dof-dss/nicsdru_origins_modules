<?php

namespace Drupal\origins_shamrock_admin\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Render\RendererInterface;
use Drupal\origins_shamrock_admin\Form\ShamrockAdminForm;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * Provides a JSON response containing Operation Shamrock data.
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
    $response->headers->set('Access-Control-Allow-Headers', [
      'x-csrf-token',
      'content-type',
      'accept',
      'origin',
      'x-requested-with'
    ]);
    $response->headers->set('Access-Control-Allow-Methods', ['GET']);
    $response->headers->set('Access-Control-Allow-Origin', ['*']);

    // If the Shamrock config has data build the banner, otherwise return
    // that it is not enabled.
    if ($config->get('modified')) {
      $modified = $config->get('modified');

      $build['banner'] = [
        '#theme' => 'origins_shamrock_banner',
        '#title' => $config->get('title'),
        '#body' => $config->get('body'),
        '#link_url' => $config->get('link_url'),
        '#link_text' => $config->get('link_text'),
      ];

      $response->setContent(
        json_encode([
          'enabled' => $config->get('published'),
          'banner_html' => $this->renderer->render($build),
          'styling' => $config->get('styles'),
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
    $response->setClientTtl(300);
    $response->setEtag(sha1($modified));
    $response->setPublic();

    return $response;
  }

}
