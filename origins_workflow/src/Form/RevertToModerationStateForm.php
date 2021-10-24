<?php

namespace Drupal\origins_workflow\Form;

use Drupal\Component\Datetime\TimeInterface;
use Drupal\content_moderation\ModerationInformationInterface;
use Drupal\Core\Datetime\DateFormatterInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\Url;
use Drupal\node\NodeInterface;
use Drupal\workflows\StateInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Provides a form for reverting a node revision to a specific moderation state.
 *
 * @internal
 */
class RevertToModerationStateForm extends ConfirmFormBase {

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
   * The date formatter service.
   *
   * @var \Drupal\Core\Datetime\DateFormatterInterface
   */
  protected $dateFormatter;

  /**
   * The time service.
   *
   * @var \Drupal\Component\Datetime\TimeInterface
   */
  protected $time;

  /**
   * Constructs a new NodeRevisionRevertForm.
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
   * @param \Drupal\Core\Datetime\DateFormatterInterface $date_formatter
   *   The date formatter service.
   * @param \Drupal\Component\Datetime\TimeInterface $time
   *   The time service.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, ModerationInformationInterface $moderation_information, MessengerInterface $messenger, RequestStack $request, LoggerInterface $logger, DateFormatterInterface $date_formatter, TimeInterface $time = NULL) {
    $this->entityTypeManager = $entity_type_manager;
    $this->moderationInformation = $moderation_information;
    $this->messenger = $messenger;
    $this->request = $request;
    $this->logger = $logger;
    $this->dateFormatter = $date_formatter;
    $this->time = $time;
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
      $container->get('date.formatter'),
      $container->get('datetime.time')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'revert_revision_to_moderation_state';
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return t('Are you sure you want to revert the revision @vid as @new_state?', [
      '@vid' => $this->vid,
      '@new_state' => $this->new_state,
    ]);
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    return new Url('entity.node.version_history', ['node' => $this->nid]);
  }

  /**
   * {@inheritdoc}
   */
  public function getConfirmText() {
    return t('Revert');
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    return '';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $nid = NULL, $vid = NULL, $new_state = 'draft') {
    $this->nid = $nid;
    $this->vid = $vid;
    $this->new_state = $new_state;
    $form = parent::buildForm($form, $form_state);

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {

    // Load the node revision we are reverting.
    $node = $this->entityTypeManager->getStorage('node')->loadRevision($this->vid);

    // Get the moderation state entity.
    $newStateEntity = $this->moderationInformation
      ->getWorkflowForEntity($node)
      ->getTypePlugin()
      ->getState($this->new_state);

    // Revert the node revision if its valid node and we are reverting to a valid state.
    if ($node instanceof NodeInterface && $newStateEntity instanceof StateInterface) {

      // Details of old revision to be reverted.
      $old_state = $node->get('moderation_state')->getString();
      $old_timestamp = $node->getRevisionCreationTime();

      // Create new revision in the desired state only if user is allowed.
      if ($this->transitionAllowed($old_state, $this->new_state)) {

        // Create a new revision.
        $node->setNewRevision();

        // The 'revision_translation_affected' field is poorly documented (and
        // understood) in Drupal core. There is much discussion at
        // https://www.drupal.org/project/drupal/issues/2746541 but the answer
        // seems to be to set it to '1' across the board to solve the problem
        // of revisions not appearing on the revisions tab.
        $node->setRevisionTranslationAffected(1);

        // Set the owner of the new revision to be the current user
        // and set an appropriate revision log message.
        $node->setRevisionUserId($this->currentUser()->id());

        // Request the state change.
        $node->set('moderation_state', $new_state);

        // Set created and changed times.
        $request_time = $this->time->getRequestTime();
        $node->setRevisionCreationTime($request_time);
        $node->setChangedTime($request_time);

        // Set revision log message.
        $revision_log_message = t('Copy of revision @vid', [
          '@vid' => $this->vid
        ]);
        $node->setRevisionLogMessage($revision_log_message);

        // Save the revision.
        $node->setUnpublished();
        $node->save();

        // Log it.
        $message = t('Revision @vid of %title (node:@nid) reverted from %old_state to %new_state by %user', [
          '@vid' => $this->vid,
          '%title' => $node->getTitle(),
          '@nid' => $this->nid,
          '%old_state' => $old_state,
          '%new_state' => $this->new_state,
          '%user' => $this->currentUser()->getAccountName(),
        ]);
        $this->logger->notice($message);

        // Show a status message.
        $this->messenger->addStatus($this->t('Reverted revision %vid from %old_timestamp as %new_state', [
          '%vid' => $this->vid,
          '%old_timestamp' => $this->dateFormatter->format($old_timestamp),
          '%new_state' => $this->new_state,
        ]));
      }
      else {
        $message = t('Revert revision @vid of @title (nid @nid) to @new_state denied to @user', [
          '@vid' => $this->vid,
          '@title' => $node->getTitle(),
          '@nid' => $this->nid,
          '@new_state' => $this->new_state,
          '@user' => $this->currentUser()->getAccountName(),
        ]);
        $this->logger->error($message);
      }
    }

    // Take the user back to the node revisions overview.
    $form_state->setRedirect(
      'entity.node.version_history',
      ['node' => $this->nid]
    );
  }

  /**
   * Check user permission for state change.
   */
  private function transitionAllowed(String $old_state, String $new_state) {
    // Check permissions of current user.
    $current_user = $this->currentUser();
    $transition_allowed = FALSE;
    if (($old_state == 'draft') && ($new_state == 'draft')) {
      if ($current_user->hasPermission('use nics_editorial_workflow transition create_new_draft')) {
        $transition_allowed = TRUE;
      }
    }
    elseif (($old_state == 'draft') && ($new_state == 'needs_review')) {
      if ($current_user->hasPermission('use nics_editorial_workflow transition submit_for_review')) {
        $transition_allowed = TRUE;
      }
    }
    elseif (($old_state == 'draft') && ($new_state == 'published')) {
      // Is this user allowed to use the 'quick publish' transition ?
      if ($current_user->hasPermission('use nics_editorial_workflow transition quick_publish')) {
        $transition_allowed = TRUE;
      }
    }
    elseif (($old_state == 'published') && ($new_state == 'published')) {
      // Is this user allowed to use the 'quick publish' transition ?
      if ($current_user->hasPermission('use nics_editorial_workflow transition quick_publish')) {
        $transition_allowed = TRUE;
      }
    }
    elseif (($old_state == 'needs_review') && ($new_state == 'draft')) {
      if ($current_user->hasPermission('use nics_editorial_workflow transition reject')) {
        $transition_allowed = TRUE;
      }
    }
    elseif (($old_state == 'needs_review') && ($new_state == 'published')) {
      if ($current_user->hasPermission('use nics_editorial_workflow transition publish')) {
        $transition_allowed = TRUE;
      }
    }
    elseif (($old_state == 'published') && ($new_state == 'needs_review')) {
      if ($current_user->hasPermission('unpublish')) {
        $transition_allowed = TRUE;
      }
    }
    elseif (($old_state == 'published') && ($new_state == 'archived')) {
      if ($current_user->hasPermission('use nics_editorial_workflow transition archive')) {
        $transition_allowed = TRUE;
      }
    }
    elseif (($old_state == 'draft') && ($new_state == 'archived')) {
      if ($current_user->hasPermission('use nics_editorial_workflow transition archive')) {
        $transition_allowed = TRUE;
      }
    }
    elseif (($old_state == 'archived') && ($new_state == 'draft')) {
      if ($current_user->hasPermission('use nics_editorial_workflow transition restore_to_draft')) {
        $transition_allowed = TRUE;
      }
    }
    elseif (($old_state == 'archived') && ($new_state == 'published')) {
      if ($current_user->hasPermission('use nics_editorial_workflow transition restore')) {
        $transition_allowed = TRUE;
      }
    }
    elseif (($old_state == 'published') && ($new_state == 'draft')) {
      if ($current_user->hasPermission('use nics_editorial_workflow transition draft_of_published')) {
        $transition_allowed = TRUE;
      }
    }
    return $transition_allowed;
  }

}
