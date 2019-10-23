<?php

namespace Drupal\origins_toc\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\node\NodeInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;

/**
 * Provides a 'TableOfContentsBlock' block.
 *
 * @Block(
 *  id = "table_of_contents_block",
 *  admin_label = @Translation("Table of Contents"),
 * )
 */
class TableOfContentsBlock extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * Drupal\Core\Entity\EntityTypeManagerInterface definition.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructs a new TableOfContentsBlock object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param string $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The EntityTypeManagerInterface definition.
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    EntityTypeManagerInterface $entity_type_manager
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $build = [];
    $node = \Drupal::routeMatch()->getParameter('node');

    if ($node instanceof NodeInterface) {
      if ($node->hasField('field_toc_enable')) {
        $toc_enabled = (bool) $node->get('field_toc_enable')->getString();

        if ($toc_enabled) {
          $node_type = \Drupal::entityTypeManager()->getStorage('node_type')->load($node->getType());
          $toc_settings = $node_type->getThirdPartySettings('origins_toc');

          if (!empty($toc_settings) && $node->hasField($toc_settings['toc_source_field'])) {
            $view_builder = \Drupal::entityTypeManager()->getViewBuilder('node');
            $view = $view_builder->view($node, 'full');

            if (empty($view)) {
              return;
            }
            $content = \Drupal::service('renderer')->render($view);
            $regex = '/<' . $toc_settings['toc_element'] . '.*(toc-\d+).*>(.*)<\/' . $toc_settings['toc_element'] . '>/m';

            preg_match_all($regex, $content, $matches, PREG_SET_ORDER, 0);

            $items = [];
            foreach ($matches as $match) {
              $items[] = [
                '#title' => $match[2],
                '#type' => 'link',
                '#url' => $node->toUrl('canonical')->setOption('fragment', $match[1]),
              ];
            }

            if ($items) {
              $build['items'] = [
                '#theme' => 'item_list',
                '#items' => $items,
              ];
            }

          }
        }
      }
    }
    return $build;
  }

}
