<?php

namespace Drupal\origins_forms;

use Drupal\Core\Config\ConfigFactoryInterface;
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
   * The config factory service.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * Constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager,  ConfigFactoryInterface $config_service) {
    $this->entityTypeManager = $entity_type_manager;
    $this->configFactory = $config_service;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('config.factory'),
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
   *
   * @return bool
   *   Whether or not this is a unique title in this bundle.
   */
  public function isTitleUnique(string $title, string $bundle, array $exclude = []) {
    $config = $this->configFactory->get('origins_forms.settings');

    $excluded_bundles = array_keys(array_filter($config->get('unique_title_excluded_bundles') ?? []));

    if (in_array($bundle, $excluded_bundles)) {
      return TRUE;
    }

    // Merge excluded nids from config with $exclude parameter nids.
    $excluded_nids_raw = $config->get('unique_title_exclude_ids_list') ?? '';
    if (!empty($excluded_nids_raw)) {
      foreach (explode(PHP_EOL, $excluded_nids_raw) as $nid) {
        $exclude[] = trim($nid);
      }
    }

    $result = $this->entityTypeManager->getStorage('node')->loadByProperties([
      'type' => $bundle,
      'title' => $title,
    ]);

    foreach ($result as $node) {
      if (!in_array($node->id(), $exclude)) {
        return FALSE;
      }
    }

    return TRUE;
  }

}
