<?php

declare(strict_types=1);

namespace Drupal\origins_qa\Form;

use Drupal\Core\Batch\BatchBuilder;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\Query\QueryInterface;
use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\Url;
use Drupal\origins_qa\Batch\DeleteTestEntitiesBatch;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

/**
 * Base form to confirm and delete entities created by automated tests.
 *
 * Test entities are identified by their label (node title, media name)
 * starting with a known prefix.
 */
abstract class DeleteTestEntitiesFormBase extends ConfirmFormBase {

  /**
   * Title prefix that identifies automated test nodes.
   */
  public const NODE_TITLE_PREFIX = 'Automated Test -';

  /**
   * Name prefix that identifies automated test media.
   */
  public const MEDIA_TITLE_PREFIX = 'Automated Test -';

  /**
   * Number of entities deleted per batch operation.
   */
  protected const BATCH_SIZE = 25;

  /**
   * Maximum number of labels listed on the confirmation page.
   */
  protected const PREVIEW_LIMIT = 20;

  /**
   * The entity IDs that will be deleted.
   *
   * @var int[]
   */
  protected array $ids = [];

  /**
   * Constructs a DeleteTestEntitiesFormBase object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager.
   */
  final public function __construct(
    protected EntityTypeManagerInterface $entityTypeManager,
  ) {}

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): static {
    return new static(
      $container->get('entity_type.manager'),
    );
  }

  /**
   * Gets the ID of the entity type to delete.
   *
   * @return string
   *   The entity type ID.
   */
  abstract protected function getTargetEntityTypeId(): string;

  /**
   * Gets the label prefix that identifies test entities.
   *
   * @return string
   *   The prefix to match at the start of the entity label.
   */
  abstract protected function getLabelPrefix(): string;

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'origins_qa_delete_test_' . $this->getTargetEntityTypeId();
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion(): TranslatableMarkup {
    return $this->t('Delete @count with a @label starting with %prefix?', [
      '@count' => $this->getEntityType()->getCountLabel(\count($this->ids)),
      '@label' => $this->getLabelFieldName(),
      '%prefix' => $this->getLabelPrefix(),
    ]);
  }

  /**
   * {@inheritdoc}
   */
  public function getConfirmText(): TranslatableMarkup {
    return $this->t('Delete test @type', ['@type' => $this->getEntityType()->getPluralLabel()]);
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl(): Url {
    return Url::fromRoute('origins_qa.manager.list');
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
   *
   * @throws \Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException
   *   Thrown on production environments.
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {
    if (getenv('PLATFORM_ENVIRONMENT_TYPE') === 'production') {
      throw new AccessDeniedHttpException('Test content cannot be bulk deleted on a production environment.');
    }

    $this->ids = $this->findTestEntities();

    if ($this->ids === []) {
      $form['empty'] = [
        '#markup' => $this->t('There are no @type with a @label starting with %prefix.', [
          '@type' => $this->getEntityType()->getPluralLabel(),
          '@label' => $this->getLabelFieldName(),
          '%prefix' => $this->getLabelPrefix(),
        ]),
      ];
      $form['back'] = [
        '#type' => 'link',
        '#title' => $this->t('Back'),
        '#url' => $this->getCancelUrl(),
      ];
      return $form;
    }

    $form = parent::buildForm($form, $form_state);

    $preview = $this->entityTypeManager->getStorage($this->getTargetEntityTypeId())
      ->loadMultiple(\array_slice($this->ids, 0, self::PREVIEW_LIMIT));
    $items = [];
    foreach ($preview as $entity) {
      $items[] = $entity->label() . ' (' . $entity->bundle() . ', ' . $entity->id() . ')';
    }
    $remaining = \count($this->ids) - \count($items);
    if ($remaining > 0) {
      $items[] = $this->formatPlural($remaining, '…and 1 more', '…and @count more');
    }

    $form['preview'] = [
      '#theme' => 'item_list',
      '#items' => $items,
      '#weight' => -10,
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   *
   * @param array<string, mixed> $form
   *   The form structure.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {
    // Query again so entities created since the form loaded are included.
    $ids = $this->findTestEntities();
    $entity_type_id = $this->getTargetEntityTypeId();

    $batch = (new BatchBuilder())
      ->setTitle($this->t('Deleting automated test @type', ['@type' => $this->getEntityType()->getPluralLabel()]))
      ->setFinishCallback([DeleteTestEntitiesBatch::class, 'finished']);
    foreach (\array_chunk($ids, self::BATCH_SIZE) as $chunk) {
      $batch->addOperation([DeleteTestEntitiesBatch::class, 'process'], [$entity_type_id, $chunk]);
    }
    batch_set($batch->toArray());

    $form_state->setRedirectUrl($this->getCancelUrl());
  }

  /**
   * Finds entities whose label starts with the test prefix.
   *
   * Access checks are bypassed so that test content is found regardless of
   * domain or entity access grants; the route is protected by a restricted
   * permission instead.
   *
   * @return int[]
   *   The matching entity IDs.
   */
  protected function findTestEntities(): array {
    $entity_type = $this->getEntityType();
    $query = $this->entityTypeManager->getStorage($entity_type->id())->getQuery()
      ->accessCheck(FALSE)
      ->condition($entity_type->getKey('label'), $this->getLabelPrefix(), 'STARTS_WITH')
      ->sort($entity_type->getKey('id'));
    $this->alterTestEntityQuery($query);

    return \array_map('intval', \array_values($query->execute()));
  }

  /**
   * Allows subclasses to add conditions to the test entity query.
   *
   * @param \Drupal\Core\Entity\Query\QueryInterface $query
   *   The entity query used to find test entities.
   */
  protected function alterTestEntityQuery(QueryInterface $query): void {}

  /**
   * Gets the definition of the entity type being deleted.
   *
   * @return \Drupal\Core\Entity\EntityTypeInterface
   *   The entity type definition.
   */
  protected function getEntityType(): EntityTypeInterface {
    return $this->entityTypeManager->getDefinition($this->getTargetEntityTypeId());
  }

  /**
   * Gets a human-readable name for the label field, e.g. "title" or "name".
   *
   * @return string
   *   The label field's machine name.
   */
  protected function getLabelFieldName(): string {
    return (string) $this->getEntityType()->getKey('label');
  }

}
