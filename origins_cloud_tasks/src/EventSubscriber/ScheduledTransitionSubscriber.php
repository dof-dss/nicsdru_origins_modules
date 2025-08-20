<?php

declare(strict_types=1);

namespace Drupal\origins_cloud_tasks\EventSubscriber;

use Drupal\scheduled_transitions\Event\ScheduledTransitionsEvents;
use Drupal\scheduled_transitions\Event\ScheduledTransitionsNewRevisionEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * @todo Add description for this subscriber.
 */
final class ScheduledTransitionSubscriber implements EventSubscriberInterface {

  /**
   * Kernel request event handler.
   */
  public function onNewRevision(ScheduledTransitionsNewRevisionEvent $event): void {
    $scheduledTransition = $event->getScheduledTransition();
    $entity = $scheduledTransition->getEntity();
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents(): array {
    return [
      ScheduledTransitionsEvents::NEW_REVISION => ['onNewRevision'],
    ];
  }

}
