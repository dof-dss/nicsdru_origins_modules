<?php

namespace Drupal\origins_migrations\EventSubscriber;

use Drupal\Core\Database\Database;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Logger\LoggerChannelFactory;
use Drupal\migrate\Event\MigrateEvents;
use Drupal\migrate\Event\MigrateImportEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Class PostMigrationSubscriber.
 *
 * Post Migrate processes.
 */
class PostMigrationSubscriber implements EventSubscriberInterface {

  /**
   * Drupal\Core\Logger\LoggerChannel definition.
   *
   * @var \Drupal\Core\Logger\LoggerChannel
   */
  protected $logger;

  /**
   * Stores the entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * PostMigrationSubscriber constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The Entity manager.
   * @param \Drupal\Core\Logger\LoggerChannelFactory $logger
   *   Drupal logger.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, LoggerChannelFactory $logger) {
    $this->entityTypeManager = $entity_type_manager;
    $this->logger = $logger->get('origins_migrations');
  }

  /**
   * Get subscribed events.
   *
   * @inheritdoc
   */
  public static function getSubscribedEvents() {
    $events[MigrateEvents::POST_IMPORT][] = ['onMigratePostImport'];
    return $events;
  }

  /**
   * Handle post import migration event.
   *
   * @param \Drupal\migrate\Event\MigrateImportEvent $event
   *   The import event object.
   */
  public function onMigratePostImport(MigrateImportEvent $event) {
    $event_id = $event->getMigration()->getBaseId();

    if ($event_id === 'upgrade_d7_path_redirect') {
      $this->processRedirects();
    }
  }

  /**
   * Delete duplicate re-directs.
   */
  protected function processRedirects() {
    // Retrieve all redirects.
    $redirect_storage = $this->entityTypeManager->getStorage('redirect');
    $redirects = $redirect_storage->loadMultiple();
    $path_alias_storage = $this->entityTypeManager->getStorage('path_alias');

    foreach ($redirects as $redirect) {
      // @phpstan-ignore-next-line
      $redirectpath = $redirect->getSource()['path'];

      // Load alias against redirects to look for duplicates.
      $alias_objects = $path_alias_storage->loadByProperties([
        'alias' => '/' . $redirectpath
      ]);

      // Delete any duplicate entries.
      if (count($alias_objects) >= 1) {
        $redirect_storage->delete([$redirect]);
        $this->logger->notice('Duplicate redirect deleted!');
      }
    }
  }

}
