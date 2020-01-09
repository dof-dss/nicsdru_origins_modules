<?php

namespace Drupal\origins_toc\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Render\Renderer;
use Drupal\node\NodeInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Routing\CurrentRouteMatch;

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
   * Drupal\Core\Routing\CurrentRouteMatch definition.
   *
   * @var \Drupal\Core\Routing\CurrentRouteMatch
   */
  protected $currentRoute;

  /**
   * Drupal\Core\Render\Renderer definition.
   *
   * @var \Drupal\Core\Render\Renderer
   */
  protected $renderer;

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
   * @param \Drupal\Core\Routing\CurrentRouteMatch $current_route
   *   Request Stack definition.
   * @param \Drupal\Core\Render\Renderer $renderer
   *   Drupal Core Renderer definition.
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    EntityTypeManagerInterface $entity_type_manager,
    CurrentRouteMatch $current_route,
    Renderer $renderer
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->entityTypeManager = $entity_type_manager;
    $this->currentRoute = $current_route;
    $this->renderer = $renderer;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.manager'),
      $container->get('current_route_match'),
      $container->get('renderer')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $build = [];
    $node = $this->currentRoute->getParameter('node');

    // Ensure we're only dealing with node entities.
    if ($node instanceof NodeInterface) {
      if ($node->hasField('field_toc_enable')) {
        $toc_enabled = (bool) $node->get('field_toc_enable')->getString();

        if ($toc_enabled) {
          $node_type = $this->entityTypeManager->getStorage('node_type')->load($node->getType());
          $toc_settings = $node_type->getThirdPartySettings('origins_toc');

          if (!empty($toc_settings) && $node->hasField($toc_settings['toc_source_field'])) {
            $view_builder = $this->entityTypeManager->getViewBuilder('node');
            $view = $view_builder->view($node, 'full');

            if (empty($view)) {
              return $build;
            }
            $content = $this->renderer->render($view);

            // Match specified elements with a 'toc-' id attribute.
            // Complex regex explained:
            // - Delimited with '/' at beginning and end of string.
            // - Match groups captured with ():
            // - First is the toc ID (toc-\d): \d means any digit.
            // - Second is the link text (([<>]+): match at least one character that isn't angle brackets.
            //   this helps avoid matching the entirety of a single line string when we want to extract multiple
            //   matches within it.
            // - Modifier flags i/m: case-insensitive, multiline.
            $regex = '/<' . $toc_settings['toc_element'] . ' id="(toc-\d+)">([^<>]+)<\/' . $toc_settings['toc_element'] . '>/im';
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
