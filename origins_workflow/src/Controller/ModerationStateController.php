<?php

namespace Drupal\origins_workflow\Controller;

use Drupal\Component\EventDispatcher\ContainerAwareEventDispatcher;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\content_moderation\ModerationInformationInterface;
use Drupal\node\NodeInterface;
use Drupal\origins_workflow\Event\ModerationStateChangeEvent;
use Drupal\workflows\StateInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Provides customisations to the core Workflow module.
 */
class ModerationStateController extends ControllerBase implements ContainerInjectionInterface {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Service object for all moderation states.
   *
   * @var \Drupal\content_moderation\ModerationInformationInterface
   */
  protected $moderationInformation;

  /**
   * Service object for the messenger service.
   *
   * @var \Drupal\Core\Messenger\MessengerInterface
   */
  protected $messenger;

  /**
   * Request stack object.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected $request;

  /**
   * A logger instance.
   *
   * @var \Psr\Log\LoggerInterface
   */
  protected $logger;

  /**
   * Node storage service object.
   *
   * @var \Drupal\node\NodeStorageInterface|\Drupal\Core\Entity\RevisionableStorageInterface
   */
  protected $nodeStorage;

  /**
   * The Event Dispatcher service.
   *
   * @var \Drupal\Component\EventDispatcher\ContainerAwareEventDispatcher
   */
  protected $eventDispatcher;

  /**
   * Creates a new ModerationStateConstraintValidator instance.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\content_moderation\ModerationInformationInterface $moderation_information
   *   Moderation information service.
   * @param \Drupal\Core\Messenger\MessengerInterface $messenger
   *   Messenger service.
   * @param \Symfony\Component\HttpFoundation\RequestStack $request
   *   Request stack object.
   * @param \Psr\Log\LoggerInterface $logger
   *   The logger interface.
   * @param \Drupal\Component\EventDispatcher\ContainerAwareEventDispatcher $event_dispatcher
   *   The logger interface.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, ModerationInformationInterface $moderation_information, MessengerInterface $messenger, RequestStack $request, LoggerInterface $logger, ContainerAwareEventDispatcher $event_dispatcher) {
    $this->entityTypeManager = $entity_type_manager;
    $this->moderationInformation = $moderation_information;
    $this->messenger = $messenger;
    $this->request = $request;
    $this->logger = $logger;
    $this->eventDispatcher = $event_dispatcher;
    $this->nodeStorage = $this->entityTypeManager->getStorage('node');
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('content_moderation.moderation_information'),
      $container->get('messenger'),
      $container->get('request_stack'),
      $container->get('logger.factory')->get('origins_workflow'),
      $container->get('event_dispatcher')
    );
  }

  /**
   * Change_state of specified entity.
   */
  public function changeState($nid, $new_state) {
    // Load the entity.
    /** @var \Drupal\node\NodeInterface $entity */
    $entity = $this->nodeStorage->load($nid);

    /** @var \Drupal\workflows\StateInterface $new_state_entity */
    $new_state_entity = $this->moderationInformation
      ->getWorkflowForEntity($entity)
      ->getTypePlugin()
      ->getState($new_state);

    if ($entity instanceof NodeInterface && $new_state_entity instanceof StateInterface) {
      // See if this state change is allowed.
      if ($this->transitionAllowed($entity, $new_state)) {

        // Get the latest revision (this is necessary as loading the entity
        // will have given us the latest 'default' revision, which is not
        // what we want if there is a draft of published).
        $vid = $this->nodeStorage->getLatestRevisionId($nid);
        // @phpstan-ignore-next-line
        $entity = $this->nodeStorage->loadRevision($vid);

        // The 'revision_translation_affected' field is poorly documented (and
        // understood) in Drupal core. There is much discussion at
        // https://www.drupal.org/project/drupal/issues/2746541 but the answer
        // seems to be to set it to '1' across the board to solve the problem
        // of revisions not appearing on the revisions tab.
        /** @var \Drupal\Core\Entity\TranslatableRevisionableInterface $entity */
        $entity->setRevisionTranslationAffected(1);

        // Set the owner of the new revision to be the current user
        // and set an appropriate revision log message.
        /** @var \Drupal\Core\Entity\RevisionLogInterface $entity */
        $entity->setRevisionUserId($this->currentUser()->id());
        $revision_log_message = t('Used quick transition to change state to @new_state', [
          '@new_state' => $new_state,
        ]);
        $entity->setRevisionLogMessage($revision_log_message);

        // Request the state change.
        /** @var \Drupal\node\NodeInterface $entity */
        $entity->set('moderation_state', $new_state);
        $entity->save();

        $moderation_event = new ModerationStateChangeEvent($entity, $new_state);
        $this->eventDispatcher->dispatch($moderation_event, $moderation_event::CHANGE);

        // Log it.
        $message = t('State of @title (nid @nid) changed to @new_state by @user', [
          '@title' => $entity->getTitle(),
          '@nid' => $nid,
          '@new_state' => $new_state,
          '@user' => $this->currentUser()->getAccountName(),
        ]);
        $this->logger->notice($message);

        if (!empty($this->request->getCurrentRequest()->query->get('confirm'))) {
          $message = t('Moderation state of "@title" changed to @new_state', [
            '@title' => $entity->getTitle(),
            '@new_state' => $new_state_entity->label(),
          ]);

          $this->messenger->addMessage($message, $this->messenger::TYPE_STATUS);
        }
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
    $current_state = $entity->get('moderation_state')->getString();
    // Check that we are looking at the latest revision.
    if (!$entity->isLatestRevision()) {
      // @phpstan-ignore-next-line
      $revision_ids = $this->nodeStorage->revisionIds($entity);
      $last_revision_id = end($revision_ids);
      // Load the revision.
      /** @var \Drupal\node\NodeInterface $last_revision */
      // @phpstan-ignore-next-line
      $last_revision = $this->nodeStorage->loadRevision($last_revision_id);
      $current_state = $last_revision->get('moderation_state')->getString();
    }
    // Check permissions of current user.
    $current_user = $this->currentUser();
    $transition_allowed = FALSE;
    if (($current_state == 'draft') && ($new_state == 'draft')) {
      if ($current_user->hasPermission('use nics_editorial_workflow transition create_new_draft')) {
        $transition_allowed = TRUE;
      }
    }
    elseif (($current_state == 'draft') && ($new_state == 'needs_review')) {
      if ($current_user->hasPermission('use nics_editorial_workflow transition submit_for_review')) {
        $transition_allowed = TRUE;
      }
    }
    elseif (($current_state == 'draft') && ($new_state == 'published')) {
      // Is this user allowed to use the 'quick publish' transition ?
      if ($current_user->hasPermission('use nics_editorial_workflow transition quick_publish')) {
        $transition_allowed = TRUE;
      }
    }
    elseif (($current_state == 'published') && ($new_state == 'published')) {
      // Is this user allowed to use the 'quick publish' transition ?
      if ($current_user->hasPermission('use nics_editorial_workflow transition quick_publish')) {
        $transition_allowed = TRUE;
      }
    }
    elseif (($current_state == 'needs_review') && ($new_state == 'draft')) {
      if ($current_user->hasPermission('use nics_editorial_workflow transition reject')) {
        $transition_allowed = TRUE;
      }
    }
    elseif (($current_state == 'needs_review') && ($new_state == 'published')) {
      if ($current_user->hasPermission('use nics_editorial_workflow transition publish')) {
        $transition_allowed = TRUE;
      }
    }
    elseif (($current_state == 'needs_review') && ($new_state == 'archived')) {
      if ($current_user->hasPermission('use nics_editorial_workflow transition archive')) {
        $transition_allowed = TRUE;
      }
    }
    elseif (($current_state == 'published') && ($new_state == 'needs_review')) {
      if ($current_user->hasPermission('unpublish')) {
        $transition_allowed = TRUE;
      }
    }
    elseif (($current_state == 'published') && ($new_state == 'archived')) {
      if ($current_user->hasPermission('use nics_editorial_workflow transition archive')) {
        $transition_allowed = TRUE;
      }
    }
    elseif (($current_state == 'draft') && ($new_state == 'archived')) {
      if ($current_user->hasPermission('use nics_editorial_workflow transition archive')) {
        $transition_allowed = TRUE;
      }
    }
    elseif (($current_state == 'archived') && ($new_state == 'draft')) {
      if ($current_user->hasPermission('use nics_editorial_workflow transition restore_to_draft')) {
        $transition_allowed = TRUE;
      }
    }
    elseif (($current_state == 'archived') && ($new_state == 'published')) {
      if ($current_user->hasPermission('use nics_editorial_workflow transition restore')) {
        $transition_allowed = TRUE;
      }
    }
    elseif (($current_state == 'published') && ($new_state == 'draft')) {
      if ($current_user->hasPermission('use nics_editorial_workflow transition draft_of_published')) {
        $transition_allowed = TRUE;
      }
    }
    return $transition_allowed;
  }

}
