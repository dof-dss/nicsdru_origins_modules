<?php

namespace Drupal\Tests\origins_internal_link_checker\Unit;

use Drupal\origins_internal_link_checker\MigrationExecutionContext;
use Drupal\Tests\UnitTestCase;

/**
 * Tests explicit migration execution tracking.
 *
 * @coversDefaultClass \Drupal\origins_internal_link_checker\MigrationExecutionContext
 * @group origins_internal_link_checker
 */
class MigrationExecutionContextTest extends UnitTestCase {

  /**
   * Tests the subscribed Migrate event names.
   *
   * @covers ::getSubscribedEvents
   */
  public function testSubscribedEvents(): void {
    $this->assertSame([
      'migrate.pre_import' => 'onPreImport',
      'migrate.post_import' => 'onPostImport',
    ], MigrationExecutionContext::getSubscribedEvents());
  }

  /**
   * Tests nested import scopes and defensive unmatched post events.
   *
   * @covers ::onPreImport
   * @covers ::onPostImport
   * @covers ::isImportActive
   */
  public function testImportScopeTracking(): void {
    $context = new MigrationExecutionContext();
    $this->assertFalse($context->isImportActive());

    $context->onPreImport();
    $context->onPreImport();
    $this->assertTrue($context->isImportActive());

    $context->onPostImport();
    $this->assertTrue($context->isImportActive());

    $context->onPostImport();
    $this->assertFalse($context->isImportActive());

    $context->onPostImport();
    $this->assertFalse($context->isImportActive());
  }

}
