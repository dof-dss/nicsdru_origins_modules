<?php

namespace Drupal\Tests\origins_workflow\Kernel;

use Drupal\datetime\Plugin\Field\FieldType\DateTimeItem;
use Drupal\KernelTests\Core\Entity\EntityKernelTestBase;
use Drupal\node\Entity\Node;
use Drupal\node\Entity\NodeType;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\origins_workflow\Controller\AuditController;

/**
 * Tests audit workflow.
 *
 * @group nidirect_common
 */
class AuditTest extends EntityKernelTestBase {

  /**
   * Modules to install.
   *
   * @var string[]
   */
  protected static $modules = [
    'system',
    'user',
    'field',
    'text',
    'filter',
    'node',
    'datetime',
    'workflows',
    'content_moderation',

    // If you still install full origins_workflow config that includes views/metatag:
    'views',
    'metatag',
    'metatag_views',
    'token',

    'origins_workflow',
  ];

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Logger channel service object.
   *
   * @var \Drupal\Core\Logger\LoggerChannelInterface
   */
  protected $logger;

  /**
   * Current user service (account proxy).
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected $account;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    // Schemas needed for creating/loading nodes and field config entities.
    $this->installEntitySchema('user');
    $this->installEntitySchema('node');
    $this->installEntitySchema('field_storage_config');
    $this->installEntitySchema('field_config');
    $this->installSchema('node', ['node_access']);

    // If origins_workflow has config you still want installed (workflow, views, etc).
    // If you want to avoid unrelated config/schema churn, remove this and instead
    // just set the auditsettings config directly.
    $this->installConfig(['origins_workflow']);

    // Ensure the bundles exist in this minimal Kernel environment.
    foreach (['article', 'contact', 'page', 'health_condition'] as $bundle) {
      if (!NodeType::load($bundle)) {
        NodeType::create([
          'type' => $bundle,
          'name' => ucfirst(str_replace('_', ' ', $bundle)),
        ])->save();
      }
    }

    // Create the field storage once (date-only datetime field).
    if (!FieldStorageConfig::loadByName('node', 'field_next_audit_due')) {
      FieldStorageConfig::create([
        'field_name' => 'field_next_audit_due',
        'entity_type' => 'node',
        'type' => 'datetime',
        'settings' => [
          'datetime_type' => DateTimeItem::DATETIME_TYPE_DATE,
        ],
        'cardinality' => 1,
      ])->save();
    }

    // Attach field to each bundle.
    foreach (['article', 'contact', 'page', 'health_condition'] as $bundle) {
      if (!FieldConfig::loadByName('node', $bundle, 'field_next_audit_due')) {
        FieldConfig::create([
          'field_name' => 'field_next_audit_due',
          'entity_type' => 'node',
          'bundle' => $bundle,
          'label' => 'Next audit due',
          'required' => FALSE,
        ])->save();
      }
    }

    $this->entityTypeManager = $this->container->get('entity_type.manager');
    $this->logger = $this->container->get('logger.factory')->get('audit_test');
    $this->account = $this->container->get('current_user');
  }

  public function testArticleNodeCreate(): void {
    $this->newNodeCreateTest('article');
  }

  public function testContactNodeCreate(): void {
    $this->newNodeCreateTest('contact');
  }

  public function testPageNodeCreate(): void {
    $this->newNodeCreateTest('page');
  }

  public function testHealthConditionNodeCreate(): void {
    $this->newNodeCreateTest('health_condition');
  }

  /**
   * Test the specified content type.
   */
  protected function newNodeCreateTest(string $type): void {
    $node = Node::create([
      'type' => $type,
      'title' => 'audit testing ' . $type,
      'moderation_state' => 'published',
    ]);
    $node->save();

    $reloaded = Node::load($node->id());
    $this->assertNotNull($reloaded);

    // Manually set audit due date to today (simulate "needs audit").
    $today = (new \DateTimeImmutable('@' . \Drupal::time()->getCurrentTime()))
      ->setTimezone(new \DateTimeZone('UTC'))
      ->format('Y-m-d');

    $reloaded->set('field_next_audit_due', $today);
    $reloaded->save();

    // Run audit.
    $auditer = new AuditController(
      $this->entityTypeManager,
      $this->logger,
      $this->account
    );
    $auditer->confirmAudit($reloaded->id());

    // Assert audit date bumped by 6 months.
    $expected = date('Y-m-d', strtotime('+6 months', \Drupal::time()->getCurrentTime()));

    $audited = Node::load($reloaded->id());
    $this->assertSame($expected, $audited->get('field_next_audit_due')->value);
  }

}
