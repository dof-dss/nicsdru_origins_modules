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
   * UniqueTitleValidator constructor.
   * @param EntityTypeManagerInterface $entity_type_manager
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
   * @return bool
   *   Whether or not this is a unique title in this bundle.
   */
  public function isTitleUnique(string $title, string $bundle) {
    $result = $this->entityTypeManager->getStorage('node')->loadByProperties([
      'type' => $bundle,
      'title' => $title,
    ]);

    return (empty($result));
  }

}
