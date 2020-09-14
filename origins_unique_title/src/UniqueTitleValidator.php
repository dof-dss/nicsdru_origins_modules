<?php

namespace Drupal\origins_unique_title;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Checks whether a content title is unique in a given entity bundle.
 */
class UniqueTitleValidator {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager) {
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager')
    );
  }

  /**
   * Function to assess whether a node in a given bundle is unique.
   *
   * @param string $title
   *   The title of the content.
   * @param string $bundle
   *   The machine id of the bundle, eg: page.
   * @param array $exclude
   *   List of node ids to exclude from the check, if any.
   * @return bool
   *   Whether or not this is a unique title in this bundle.
   */
  public function isTitleUnique(string $title, string $bundle, array $exclude = []) {
    $is_unique = TRUE;

    $result = $this->entityTypeManager->getStorage('node')->loadByProperties([
      'type' => $bundle,
      'title' => $title,
    ]);

    if (!empty($result)) {
      // Ignore any node ids in the exclude list.
      foreach ($result as $node) {
        if (!in_array($node->id(), $exclude)) {
          $is_unique = FALSE;
        }
      }
    }

    return $is_unique;
  }

}
