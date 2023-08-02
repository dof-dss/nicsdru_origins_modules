<?php

namespace Drupal\origins_workflow\Event;

use Drupal\Component\EventDispatcher\Event;
use Drupal\Core\Entity\EntityInterface;

/**
 * Event for when an entity moderation state is changed using Origins Workflow.
 */
class ModerationStateChangeEvent extends Event {

  const CHANGE = 'moderation_state.change';

  /**
   * The entity for ehich moderation state has changed.
   *
   * @var \Drupal\Core\Entity\EntityInterface
   */
  protected $entity;

  /**
   * The moderation state.
   *
   * @var string
   */
  protected $state;

  /**
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity for which moderation state was changed.
   * @param string $state
   *   The requested moderation state for the entity.
   */
  public function __construct(EntityInterface $entity, string $state) {
    $this->entity = $entity;
    $this->state = $state;
  }

  /**
   * Returns the entity for which the moderation state was changed.
   *
   * @return \Drupal\Core\Entity\EntityInterface|EntityInterface
   *   The entity which had moderation state changed..
   */
  public function getEntity(): EntityInterface {
    return $this->entity;
  }

  /**
   * Returns the requested moderation state for the entity.
   *
   * @return string
   *   The moderation state.
   */
  public function getState(): string {
    return $this->state;
  }

  /**
   * Returns the published status.
   *
   * @return bool
   *   True if published, false if not.
   */
  public function isPublished(): bool {
    return ($this->state === 'published');
  }

  /**
   * Returns the archived status.
   *
   * @return bool
   *   True if archived, false if not.
   */
  public function isArchived(): bool {
    return ($this->state === 'archived');
  }

}
