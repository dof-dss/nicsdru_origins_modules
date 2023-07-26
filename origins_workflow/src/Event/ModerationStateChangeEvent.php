<?php

namespace Drupal\origins_workflow\Event;

use Drupal\Component\EventDispatcher\Event;

class ModerationStateChangeEvent extends Event {

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
   * @param string $state
   */
  public function __construct(EntityInterface $entity, string $state)
  {
    $this->entity = $entity;
    $this->state = $state;
  }

  /**
   * @return \Drupal\Core\Entity\EntityInterface|EntityInterface
   */
  public function getEntity(): EntityInterface
  {
    return $this->entity;
  }

  /**
   * @return string
   */
  public function getState(): string
  {
    return $this->state;
  }




}
