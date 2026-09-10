<?php

namespace Drupal\origins_internal_link_checker;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Tracks whether the current process is actively importing a migration.
 */
class MigrationExecutionContext implements EventSubscriberInterface {

  /**
   * Number of migration import scopes currently active.
   */
  private int $activeImports = 0;

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents(): array {
    // String event names avoid requiring the optional Migrate module.
    return [
      'migrate.pre_import' => 'onPreImport',
      'migrate.post_import' => 'onPostImport',
    ];
  }

  /**
   * Marks the start of a migration import scope.
   */
  public function onPreImport(): void {
    $this->activeImports++;
  }

  /**
   * Marks the end of a migration import scope.
   */
  public function onPostImport(): void {
    $this->activeImports = max(0, $this->activeImports - 1);
  }

  /**
   * Reports whether a migration import scope is active.
   */
  public function isImportActive(): bool {
    return $this->activeImports > 0;
  }

}
