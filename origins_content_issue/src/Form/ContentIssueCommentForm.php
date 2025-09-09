<?php

declare(strict_types=1);

namespace Drupal\origins_content_issue\Form;

use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\CloseDialogCommand;
use Drupal\Core\Ajax\CloseModalDialogCommand;
use Drupal\Core\Ajax\MessageCommand;
use Drupal\Core\Ajax\PrependCommand;
use Drupal\Core\Ajax\ReplaceCommand;
use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Entity\EntityRepositoryInterface;
use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\origins_content_issue\ContentIssueManager;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Form controller for the content issue comment entity edit forms.
 */
final class ContentIssueCommentForm extends ContentEntityForm {

  /**
   * {@inheritdoc}
   */
  public function __construct(
    EntityRepositoryInterface $entity_repository,
    EntityTypeBundleInfoInterface $entity_type_bundle_info,
    TimeInterface $time,
    private readonly ContentIssueManager $contentIssueManager,
  ) {
    parent::__construct($entity_repository, $entity_type_bundle_info, $time);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity.repository'),
      $container->get('entity_type.bundle.info'),
      $container->get('datetime.time'),
      $container->get('content_issue.manager'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildForm($form, $form_state);

    $comment_id = $form_state->getValue('comment_id');

    $form['issue_entity'] = [
      '#type' => 'hidden',
      '#default_value' => $form_state->getValue('issue_entity')
    ];

    $form['uid']['#attributes']['class'][] = 'hidden';
    $form['created']['#attributes']['class'][] = 'hidden';

    $callback = (empty($comment_id)) ? '::ajaxSubmitAdd' : '::ajaxSubmitEdit';

    // Make sure it's an AJAX form.
    $form['actions']['submit']['#ajax'] = [
      'callback' => $callback,
      'event' => 'click',
      'progress' => [
        'type' => 'throbber',
        'message' => t('Saving...'),
      ],
    ];

    $form['actions']['cancel'] = [
      '#type' => 'link',
      '#title' => $this->t('Cancel'),
      '#url' => Url::fromRoute('origins_content_issue.comment.close_modal'),
      '#attributes' => [
        'class' => ['use-ajax', 'action-link'],
        'data-dialog-close' => 'true',
      ],
    ];

    $form['#submit'][] = '::formSubmit';
    $form_state->setRedirect(NULL);

    return $form;
  }

  /**
   * Comment form submit callback.
   */
  public function formSubmit(array $form, FormStateInterface $form_state) {
    $form_state->disableRedirect();
  }

  /**
   * Submit callback for creating a content issue comment.
   */
  public function ajaxSubmitAdd(array $form, FormStateInterface $form_state) {
    // @phpstan-ignore-next-line
    $entity = $form_state->getformObject()->getEntity();

    $response = new AjaxResponse();
    $comment_build = $this->contentIssueManager->renderComment($entity->id());
    $response->addCommand(new PrependCommand('.content-issue-comments', $comment_build));
    $response->addCommand(new MessageCommand('Comment created.'));
    $response->addCommand(new CloseDialogCommand());

    return $response;
  }

  /**
   * Submit callback for updating a content issue comment.
   */
  public function ajaxSubmitEdit(array $form, FormStateInterface $form_state) {
    // @phpstan-ignore-next-line
    $entity = $form_state->getformObject()->getEntity();

    $response = new AjaxResponse();
    $comment_build = $this->contentIssueManager->renderComment($entity->id());

    $response->addCommand(new ReplaceCommand('.content-issue-comment[data-comment-id="' . $entity->id() . '"]', $comment_build, []));
    $response->addCommand(new CloseModalDialogCommand());

    return $response;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $result = parent::save($form, $form_state);

    $message_args = ['%label' => $this->entity->toLink()->toString()];
    $logger_args = [
      '%label' => $this->entity->label(),
      'link' => $this->entity->toLink($this->t('View'))->toString(),
    ];

    switch ($result) {
      case SAVED_NEW:
        $this->messenger()->addStatus($this->t('New content issue comment %label has been created.', $message_args));
        $this->logger('origins_content_issue')->notice('New content issue comment %label has been created.', $logger_args);
        break;

      case SAVED_UPDATED:
        $this->messenger()->addStatus($this->t('The content issue comment %label has been updated.', $message_args));
        $this->logger('origins_content_issue')->notice('The content issue comment %label has been updated.', $logger_args);
        break;

      default:
        throw new \LogicException('Could not save the entity.');
    }

    $response = new AjaxResponse();
    $response->addCommand(new CloseDialogCommand());
    return $response;
  }

}
