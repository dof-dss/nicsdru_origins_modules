<?php

namespace Drupal\origins_workflow\Form;

use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Datetime\DateFormatterInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a form for reverting a node revision.
 *
 * @internal
 */
class NewDraftOfPublishedForm extends ConfirmFormBase {

  /**
   * The node revision.
   *
   * @var \Drupal\node\NodeInterface
   */
  protected $nid;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

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
   * @param \Drupal\Core\Datetime\DateFormatterInterface $date_formatter
   *   The date formatter service.
   * @param \Drupal\Component\Datetime\TimeInterface $time
   *   The time service.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, DateFormatterInterface $date_formatter, TimeInterface $time = NULL) {
    $this->entityTypeManager = $entity_type_manager;
    $this->dateFormatter = $date_formatter;
    $this->time = $time;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('date.formatter'),
      $container->get('datetime.time')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'new_draft_of_published';
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return t('Are you sure you want to create a draft of the published revision?');
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
    return t('Create draft');
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
  public function buildForm(array $form, FormStateInterface $form_state, $nid = NULL) {
    $this->nid = $nid;
    $form = parent::buildForm($form, $form_state);

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Load the entity.
    $entity = $this->entityTypeManager->getStorage('node')->load($this->nid);
    $original_revision_timestamp = $entity->getRevisionCreationTime();
    // Create a new revision.
    $entity->setNewRevision();
    // We need this to be a draft.
    $entity->set('moderation_state', 'draft');
    $request_time = $this->time->getRequestTime();
    $entity->setRevisionCreationTime($request_time);
    $entity->setChangedTime($request_time);
    $entity->setRevisionUserId($this->currentUser()->id());
    $entity->revision_log = t('Copy of the published revision from %date.', ['%date' => $this->dateFormatter->format($original_revision_timestamp)]);
    $entity->setRevisionTranslationAffected(TRUE);
    $entity->setUnpublished();
    // Save the new revision.
    $entity->save();

    // Log it.
    $message = t('New revision of (nid @nid) created from published by @user', [
      '@title' => $entity->getTitle(),
      '@nid' => $this->nid,
      '@user' => $this->currentUser()->getAccountName(),
    ]);
    $this->logger('new_draft_of_published')->notice($message);

    // Take the user to the edit form where they can edit this new draft.
    $form_state->setRedirect(
      'entity.node.edit_form',
      ['node' => $this->nid]
    );
  }

}
