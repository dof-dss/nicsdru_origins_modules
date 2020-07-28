<?php

namespace Drupal\origins_workflow\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Datetime\DateFormatterInterface;
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
   * @param \Drupal\Core\Datetime\DateFormatterInterface $date_formatter
   *   The date formatter service.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, LoggerInterface $logger, DateFormatterInterface $date_formatter) {
    $this->entityTypeManager = $entity_type_manager;
    $this->logger = $logger;
    $this->dateFormatter = $date_formatter;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('logger.factory')->get('origins_workflow'),
      $container->get('date.formatter')
    );
  }

  /**
   * Change_state of specified entity.
   */
  public function changeState($nid, $new_state) {
    // Load the entity.
    $entity = $this->entityTypeManager->getStorage('node')->load($nid);
    if ($entity) {
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
    // Redirect user to current page (although the 'destination'
    // url argument will override this).
    return $this->redirect('view.workflow_moderation.needs_review');
  }

  /**
   * Create new draft of published revision.
   */
  public function newDraftOfPublished($nid) {
    // Load the entity.
    $entity = $this->entityTypeManager->getStorage('node')->load($nid);
    $original_revision_timestamp = $entity->getRevisionCreationTime();
    // Create a new revision.
    $entity->setNewRevision();
    $request_time = \Drupal::time()->getRequestTime();
    $entity->setRevisionCreationTime($request_time);
    $entity->setChangedTime($request_time);
    $entity->setRevisionUserId($this->currentUser()->id());

    $entity->revision_log = t('Copy of the published revision from %date.', ['%date' => $this->dateFormatter->format($original_revision_timestamp)]);

    $entity->setRevisionTranslationAffected(TRUE);
    // Save the new revision.
    $entity->setUnpublished()->save();
    //$entity->save();

    // Log it.
    $message = t('New revision of (nid @nid) created from published by @user', [
      '@title' => $entity->getTitle(),
      '@nid' => $nid,
      '@user' => $this->currentUser()->getAccountName(),
    ]);
    $this->logger->notice($message);

    // Need to flush caches so that the new revision will appear.
    //return $this->redirect('entity.node.version_history', ['node' => $nid]);

    return $this->redirect('entity.node.edit_form', ['node' => $nid]);
  }

}
