<?php

declare(strict_types=1);

namespace Drupal\origins_qa\Form;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Config\TypedConfigManagerInterface;
use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Configures the Origins QA tools.
 */
final class QaSettingsForm extends ConfigFormBase {

  /**
   * Constructs a QaSettingsForm object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   * @param \Drupal\Core\Config\TypedConfigManagerInterface $typed_config_manager
   *   The typed config manager.
   * @param \Drupal\Core\Entity\EntityTypeBundleInfoInterface $bundleInfo
   *   The entity type bundle info service.
   */
  public function __construct(
    ConfigFactoryInterface $config_factory,
    TypedConfigManagerInterface $typed_config_manager,
    protected EntityTypeBundleInfoInterface $bundleInfo,
  ) {
    parent::__construct($config_factory, $typed_config_manager);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): static {
    return new static(
      $container->get('config.factory'),
      $container->get('config.typed'),
      $container->get('entity_type.bundle.info'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'origins_qa_settings';
  }

  /**
   * {@inheritdoc}
   *
   * @return string[]
   *   The editable config names.
   */
  protected function getEditableConfigNames(): array {
    return ['origins_qa.settings'];
  }

  /**
   * {@inheritdoc}
   *
   * @param array<string, mixed> $form
   *   The form structure.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   *
   * @return array<string, mixed>
   *   The form structure.
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {
    $form['node_delete_bundle_order'] = [
      '#type' => 'details',
      '#title' => $this->t('Automated test node deletion'),
      '#description' => $this->t('Drag content types into the order in which their automated test nodes are deleted by "Delete automated test nodes". Nodes of the same content type are deleted newest first. Content types added since this was last saved are deleted last. Automated test nodes of ignored content types are never deleted.'),
      '#open' => TRUE,
    ];

    $form['node_delete_bundle_order']['bundles'] = [
      '#type' => 'table',
      '#header' => [
        $this->t('Content type'),
        $this->t('Machine name'),
        $this->t('Ignore'),
        $this->t('Weight'),
      ],
      '#empty' => $this->t('There are no content types.'),
      '#tabledrag' => [
        [
          'action' => 'order',
          'relationship' => 'sibling',
          'group' => 'bundle-weight',
        ],
      ],
    ];

    $ignored = (array) $this->config('origins_qa.settings')->get('node_delete_ignored_bundles');
    $weight = 0;
    foreach ($this->getOrderedBundles() as $bundle => $label) {
      $form['node_delete_bundle_order']['bundles'][$bundle] = [
        '#attributes' => ['class' => ['draggable']],
        '#weight' => $weight,
        'label' => ['#plain_text' => $label],
        'machine_name' => ['#plain_text' => $bundle],
        'ignore' => [
          '#type' => 'checkbox',
          '#title' => $this->t('Ignore @bundle', ['@bundle' => $label]),
          '#title_display' => 'invisible',
          '#default_value' => \in_array($bundle, $ignored, TRUE),
        ],
        'weight' => [
          '#type' => 'weight',
          '#title' => $this->t('Weight for @bundle', ['@bundle' => $label]),
          '#title_display' => 'invisible',
          '#default_value' => $weight,
          '#delta' => 100,
          '#attributes' => ['class' => ['bundle-weight']],
        ],
      ];
      $weight++;
    }

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   *
   * @param array<mixed> $form
   *   The form structure.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {
    $rows = $form_state->getValue('bundles') ?: [];
    \uasort($rows, static fn(array $a, array $b): int => (int) $a['weight'] <=> (int) $b['weight']);

    $ignored = \array_keys(\array_filter($rows, static fn(array $row): bool => !empty($row['ignore'])));

    $this->config('origins_qa.settings')
      ->set('node_delete_bundle_order', \array_map('strval', \array_keys($rows)))
      ->set('node_delete_ignored_bundles', \array_map('strval', $ignored))
      ->save();

    parent::submitForm($form, $form_state);
  }

  /**
   * Gets the node bundles, ordered by the saved deletion order.
   *
   * @return array<string, string>
   *   Bundle labels keyed by machine name. Bundles missing from the saved
   *   order are appended alphabetically.
   */
  protected function getOrderedBundles(): array {
    $bundles = [];
    foreach ($this->bundleInfo->getBundleInfo('node') as $bundle => $info) {
      $bundles[(string) $bundle] = (string) ($info['label'] ?? $bundle);
    }
    \asort($bundles, SORT_NATURAL | SORT_FLAG_CASE);

    $order = (array) $this->config('origins_qa.settings')->get('node_delete_bundle_order');
    $ordered = [];
    foreach ($order as $bundle) {
      if (isset($bundles[$bundle])) {
        $ordered[$bundle] = $bundles[$bundle];
      }
    }

    return $ordered + $bundles;
  }

}
