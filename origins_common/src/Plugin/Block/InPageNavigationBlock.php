<?php

namespace Drupal\origins_common\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Component\Utility\Html;
use Drupal\Core\Config\ConfigFactory;
use Psr\Log\LoggerInterface;

/**
 * Provides a 'InPageNavigationBlock' block.
 *
 * @Block(
 *  id = "in_page_navigation_block",
 *  admin_label = @Translation("In page navigation block"),
 *  category = "NICS Origins",
 * )
 */
class InPageNavigationBlock extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * @var string
   */
  protected $themeName;

  /**
   * @var \Psr\Log\LoggerInterface
   */
  protected $logger;

  /**
   * Class constructor.
   *
   * @param array $configuration
   *   Plugin configuration.
   * @param string $plugin_id
   *   Plugin ID.
   * @param mixed $plugin_definition
   *   Plugin definition.
   * @param \Drupal\Core\Config\ConfigFactory $config
   *   Config factory instance.
   * @param \Psr\Log\LoggerInterface $logger
   *   Logger instance.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, ConfigFactory $config, LoggerInterface $logger) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->themeName = $config->get('system.theme')->get('default');
    $this->logger = $logger;
  }

  /**
   * {@inheritdoc}
   *
   * @param \Symfony\Component\DependencyInjection\ContainerInterface $container
   *   Dependency injection container.
   * @param array $configuration
   *   Block configuration.
   * @param string $plugin_id
   *   Plugin ID.
   * @param mixed $plugin_definition
   *   Plugin definition.
   *
   * @return static
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('config.factory'),
      $container->get('logger.factory')->get('origins_common')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [] + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state) {
    // Fetch regions for the default theme and generate select options.
    $regions = system_region_list($this->themeName, REGIONS_VISIBLE);
    array_walk($regions, function ($data, $id) {
      $regions[$id] = $data->render();
    });

    $form['ipn_source'] = [
      '#type' => 'select',
      '#title' => $this->t('Navigation source (@theme regions)', ['@theme' => $this->themeName]),
      '#description' => $this->t('Region acting as the source for navigation links.'),
      '#options' => $regions,
      '#default_value' => $this->configuration['ipn_source'] ?? '',
      '#required' => TRUE,
      '#size' => 1,
      '#weight' => '0',
    ];

    $form['ipn_element'] = [
      '#type' => 'select',
      '#title' => $this->t('HTML element'),
      '#description' => $this->t('Element in the region to generate navigation links for.'),
      '#options' => [
        'h2' => 'H2 - Heading 2',
        'h3' => 'H3 - Heading 3',
        'h4' => 'H4 - Heading 4',
        'h5' => 'H5 - Heading 5',
      ],
      '#default_value' => $this->configuration['ipn_element'] ?? '',
      '#required' => TRUE,
      '#size' => 2,
      '#weight' => '0',
    ];

    $form['ipn_exclusions'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Elements to exclude'),
      '#description' => $this->t('Comma separated css selectors to exclude.'),
      '#default_value' => $this->configuration['ipn_exclusions'] ?? '',
      '#weight' => '0',
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    $block = $form_state->getFormObject()->getEntity();

    $this->configuration['machine_name'] = $block->id();
    $this->configuration['ipn_source'] = $form_state->getValue('ipn_source');
    $this->configuration['ipn_element'] = $form_state->getValue('ipn_element');
    $this->configuration['ipn_exclusions'] = $form_state->getValue('ipn_exclusions');
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $build = [];
    $id = Html::getId($this->configuration['machine_name']);
    $source = $this->configuration['ipn_source'];
    $element = $this->configuration['ipn_element'];
    $exclusions = $this->configuration['ipn_exclusions'];

    if (empty($source) || empty($element)) {
      $this->logger->error($this->t('Undefined source and/or element for In Page Navigation block with id: %id', ['%id' => $id]));
      return;
    }

    // Build data for this block instance to pass to DrupalSettings.
    $js = [
      'element' => $element,
      'source' => $source,
      'exclusions' => $exclusions,
    ];

    $build['#attached']['library'][] = 'origins_common/in-page-navigation';
    $build['#attached']['drupalSettings']['origins_common']['in_page_navigation'][$id] = $js;
    $build['#markup'] = '<ul></ul>';

    return $build;
  }

}
