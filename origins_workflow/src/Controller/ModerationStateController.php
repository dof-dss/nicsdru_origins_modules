<?php

namespace Drupal\origins_workflow\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class ModerationStateController.
 */
class ModerationStateController extends ControllerBase implements ContainerInjectionInterface {
  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * A logger instance.
   *
   * @var \Psr\Log\LoggerInterface
   */
  protected $logger;

  /**
   * Creates a new ModerationStateConstraintValidator instance.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Psr\Log\LoggerInterface $logger
   *   The logger interface.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, LoggerInterface $logger) {
    $this->entityTypeManager = $entity_type_manager;
    $this->logger = $logger;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('logger.factory')->get('origins_workflow')
    );
  }

  /**
   * Change_state of specified entity.
   */
  public function changeState($nid, $new_state) {
    // Load the entity.
    $entity = $this->entityTypeManager->getStorage('node')->load($nid);
    if ($entity) {
      // See if this state change is allowed.
      if ($this->transitionAllowed($entity, $new_state)) {
        // Request the state change.
        $entity->set('moderation_state', $new_state);
        $entity->save();
        // Log it.
        $message = t('State of @title (nid @nid) changed to @new_state by @user', [
          '@title' => $entity->getTitle(),
          '@nid' => $nid,
          '@new_state' => $new_state,
          '@user' => $this->currentUser()->getAccountName(),
        ]);
        $this->logger->notice($message);
      }
      else {
        $message = t('State change of @title (nid @nid) to @new_state denied to @user', [
          '@title' => $entity->getTitle(),
          '@nid' => $nid,
          '@new_state' => $new_state,
          '@user' => $this->currentUser()->getAccountName(),
        ]);
        $this->logger->error($message);
      }
    }
    // Redirect user to current page (although the 'destination'
    // url argument will override this).
    return $this->redirect('view.workflow_moderation.needs_review');
  }

  /**
   * Check user permission for state change.
   */
  private function transitionAllowed($entity, String $new_state) {
    // Get the current moderation state.
    $current_state = $entity->moderation_state->value;
    // Check that we are looking at the latest revision.
    if (!$entity->isLatestRevision()) {
      $revision_ids = $this->entityTypeManager->getStorage('node')->revisionIds($entity);
      $last_revision_id = end($revision_ids);
      // Load the revision.
      $last_revision = $this->entityTypeManager->getStorage('node')->loadRevision($last_revision_id);
      $current_state = $last_revision->moderation_state->value;
    }
    // Check permissions of current user.
    $current_user = $this->currentUser();
    $transition_allowed = FALSE;
    if (($current_state == 'draft') && ($new_state == 'published')) {
      // Is this user allowed to use the 'quick publish' transition ?
      if ($current_user->hasPermission('use editorial transition quick_publish')) {
        $transition_allowed = TRUE;
      }
    }
    elseif (($current_state == 'draft') && ($new_state == 'draft')) {
      if ($current_user->hasPermission('use editorial transition create_new_draft')) {
        $transition_allowed = TRUE;
      }
    }
    elseif (($current_state == 'draft') && ($new_state == 'needs_review')) {
      if ($current_user->hasPermission('use editorial transition submit_for_review')) {
        $transition_allowed = TRUE;
      }
    }
    elseif (($current_state == 'needs_review') && ($new_state == 'draft')) {
      if ($current_user->hasPermission('use editorial transition reject')) {
        $transition_allowed = TRUE;
      }
    }
    elseif (($current_state == 'needs_review') && ($new_state == 'published')) {
      if ($current_user->hasPermission('use editorial transition publish')) {
        $transition_allowed = TRUE;
      }
    }
    elseif (($current_state == 'published') && ($new_state == 'draft')) {
      if ($current_user->hasPermission('use editorial transition draft_of_published')) {
        $transition_allowed = TRUE;
      }
    }
    return $transition_allowed;
  }

}
