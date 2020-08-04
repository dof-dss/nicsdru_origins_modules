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
class RevisionTest extends BrowserTestBase {

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
  protected function setUp() {
    parent::setUp();

    $this->entityTypeManager = $this->container->get('entity_type.manager');
    $this->logger = $this->container->get('logger.factory')->get('audit_test');
    $this->account = $this->container->get('current_user');
  }

  /**
   * Test the creation of a new draft.
   */
  public function newDraftCreateTest($type) {
    // Create a new node.
    $node = $this->drupalCreateNode([
      'type' => 'article',
      'title' => 'revision testing ',
      'moderation_state' => 'published',
    ]);
    $this->assertTrue(Node::load($node->id()), 'Node created.');
    $nid = $node->id();
    $this->drupalGet('/node/' . $nid . '/revisions');
    $this->assertResponse(200);
  }

}
