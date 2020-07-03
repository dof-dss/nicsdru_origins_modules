<?php

namespace Drupal\Tests\origins_workflow\Functional;

use Drupal\origins_workflow\Controller\AuditController;
use Drupal\Tests\BrowserTestBase;
use Drupal\node\Entity\Node;

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
  public static $modules = ['origins_workflow', 'node'];

  /**
   * Use install profile so that we have all content types, modules etc.
   *
   * @var installprofile
   */
  protected $profile = 'test_profile';

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Set to TRUE to strict check all configuration saved.
   * TNeed to set to FALSE here because some contrib modules have a schema in
   * config/schema that does not match the actual settings exported
   * (eu_cookie_compliance and google_analytics_counter, I'm looking at you).
   *
   * @see \Drupal\Core\Config\Development\ConfigSchemaChecker
   *
   * @var bool
   */
  protected $strictConfigSchema = FALSE;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
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
