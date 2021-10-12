<?php

namespace Drupal\origins_workflow\Breadcrumb;

use Drupal\Core\Breadcrumb\Breadcrumb;
use Drupal\Core\Breadcrumb\BreadcrumbBuilderInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Link;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\node\NodeInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Generates the breadcrumb trail when reverting revisions.
 *
 * @package Drupal\origins_workflow
 */
class RevisionRevertBreadcrumb implements BreadcrumbBuilderInterface {

  /**
   * Core EntityTypeManager instance.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Route matches from the service container parameters.
   *
   * @var array
   */
  protected $routeMatches;

  /**
   * Class constructor.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, array $route_matches) {
    $this->entityTypeManager = $entity_type_manager;
    $this->routeMatches = $route_matches;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(EntityTypeManagerInterface $entity_type_manager, ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('breadcrumb.revert.matches')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function applies(RouteMatchInterface $route_match) {
    $match = FALSE;

    if (in_array($route_match->getRouteName(), $this->routeMatches)) {
      $match = TRUE;
    }

    return $match;
  }

  /**
   * {@inheritdoc}
   */
  public function build(RouteMatchInterface $route_match) {

    $breadcrumb = new Breadcrumb();
    $nid = $route_match->getParameter('nid');
    $node = $this->entityTypeManager->getStorage('node')->load($nid);

    $links[] = Link::createFromRoute(t('Home'), '<front>');
    if ($node instanceof NodeInterface) {
      $links[] = Link::fromTextAndUrl($node->getTitle(), $node->toUrl());
    }
    $links[] = Link::createFromRoute(t('revisions'), 'entity.node.version_history', ['node' => $nid]);

    $breadcrumb->setLinks($links);
    $breadcrumb->addCacheContexts(['url.path']);

    return $breadcrumb;
  }

}
