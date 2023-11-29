<?php

declare(strict_types = 1);

namespace Drupal\origins_add_content\Controller;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Controller\ControllerResolverInterface;

/**
 * Creates a list of node and entity content types for the Add Content page.
 */
final class AddContentPageController extends ControllerBase {

  /**
   * The controller resolver.
   *
   * @var \Symfony\Component\HttpKernel\Controller\ControllerResolverInterface
   */
  protected ControllerResolverInterface $controllerResolver;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The config factory service.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The controller constructor.
   */
  public function __construct(ControllerResolverInterface $controllerResolver, EntityTypeManagerInterface $entity_type_manager, ConfigFactoryInterface $config_service) {
    $this->controllerResolver = $controllerResolver;
    $this->entityTypeManager = $entity_type_manager;
    $this->configFactory = $config_service;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): self {
    return new self(
      $container->get('controller_resolver'),
      $container->get('entity_type.manager'),
      $container->get('config.factory'),
    );
  }

  /**
   * Builds a list of content types to add.
   */
  public function addContentList(): array {
    $config = $this->configFactory->get('origins_add_content.settings');

    // Call the Core node/add route controller method to generate the initial list.
    $request = new Request([], [], ['_controller' => '\Drupal\node\Controller\NodeController::addPage']);
    $node_controller = $this->controllerResolver->getController($request);
    $build = call_user_func_array($node_controller, []);

    // Add the configured entities but only if the current user has the create permission.
    $entities = $config->get('entities');
    foreach ($entities as $entity_id) {
      if ($this->entityTypeManager->hasDefinition($entity_id)) {
        $access_handler = $this->entityTypeManager->getAccessControlHandler($entity_id);

        if ($access_handler->createAccess()) {
          $entity = $this->entityTypeManager->getDefinition($entity_id);
          $build['#content'][$entity_id] = $entity;
        }
      }
    }

    // Add our theme definition based on Core node_add_list.
    $build['#theme'] = 'content_add_list';

    return $build;
  }

}
