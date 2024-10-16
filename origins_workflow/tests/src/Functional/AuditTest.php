<?php

namespace Drupal\Tests\origins_workflow\Functional;

use Drupal\Tests\BrowserTestBase;
use Drupal\node\Entity\Node;
use Drupal\origins_workflow\Controller\AuditController;

/**
 * Tests audit workflow.
 *
 * @group nidirect_common
 */
class AuditTest extends BrowserTestBase {

  /**
   * Modules to install.
   *
   * @var array
   */
  protected static $modules = ['origins_workflow', 'node'];

  /**
   * Use install profile so that we have all content types, modules etc.
   *
   * @var string
   */
  protected $profile = 'test_profile';

  /**
   * Set default theme.
   *
   * Drupal\Tests\BrowserTestBase::$defaultTheme is required. See
   * https://www.drupal.org/node/3083055, which includes recommendations
   * on which theme to use.
   *
   * @var string
   */
  protected $defaultTheme = 'classy';

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
   * User account service object.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $account;

  /**
   * Set to TRUE to strict check all configuration saved.
   *
   * Need to set to FALSE here because some contrib modules have a schema in
   * config/schema that does not match the actual settings exported
   * (eu_cookie_compliance and google_analytics_counter, I'm looking at you).
   *
   * @var bool
   */
  protected $strictConfigSchema = FALSE;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->entityTypeManager = $this->container->get('entity_type.manager');
    $this->logger = $this->container->get('logger.factory')->get('audit_test');
    $this->account = $this->container->get('current_user');
  }

  /**
   * Tests the behaviour when creating an article.
   */
  public function testArticleNodeCreate() {
    $this->newNodeCreateTest('article');
  }

  /**
   * Tests the behaviour when creating a contact.
   */
  public function testContactNodeCreate() {
    $this->newNodeCreateTest('contact');
  }

  /**
   * Tests the behavior when creating a page.
   */
  public function testPageNodeCreate() {
    $this->newNodeCreateTest('page');
  }

  /**
   * Tests the behavior when creating a health condition.
   */
  public function testHealthConditionNodeCreate() {
    $this->newNodeCreateTest('health_condition');
  }

  /**
   * Test the specified content type.
   */
  public function newNodeCreateTest($type) {
    // Create a new node.
    $node = $this->drupalCreateNode([
      'type' => $type,
      'title' => 'audit testing ' . $type,
      'moderation_state' => 'published',
    ]);
    $this->assertTrue(Node::load($node->id()), 'Node created.');
    $nid = $node->id();
    $new_node = Node::load($nid);
    // 'Next audit due' date should have been set automatically
    // to six months in the future.
    $sixm = date('Y-m-d', strtotime("+6 months"));
    $this->assertEquals($sixm, $new_node->get('field_next_audit_due')->value);
    // Now reset the audit due date to today.
    $today = date('Y-m-d', \Drupal::time()->getCurrentTime());
    $new_node->set('field_next_audit_due', $today);
    $new_node->save();
    // Audit the node.
    $auditer = new AuditController($this->entityTypeManager, $this->logger, $this->account);
    $auditer->confirmAudit($nid);
    // 'Next audit due' date should now have bumped to 6 months time.
    $audited_node = Node::load($nid);
    $this->assertEquals($sixm, $audited_node->get('field_next_audit_due')->value);
  }

}
