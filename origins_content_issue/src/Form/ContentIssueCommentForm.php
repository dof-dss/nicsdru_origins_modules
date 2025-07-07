<?php

declare(strict_types=1);

namespace Drupal\origins_content_issue\Form;

use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\CloseDialogCommand;
use Drupal\Core\Ajax\CloseModalDialogCommand;
use Drupal\Core\Ajax\InvokeCommand;
use Drupal\Core\Ajax\MessageCommand;
use Drupal\Core\Ajax\PrependCommand;
use Drupal\Core\Ajax\ReplaceCommand;
use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

/**
 * Form controller for the content issue comment entity edit forms.
 */
final class ContentIssueCommentForm extends ContentEntityForm {

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

  public function formSubmit($form, FormStateInterface $form_state) {
    $form_state->disableRedirect(); // Ensure no redirect happens
  }

  public function ajaxSubmitAdd($form, FormStateInterface $form_state) {
    $entity = $form_state->getformObject()->getEntity();
    $issueManager = \Drupal::service('content_issue.manager');

    $response = new AjaxResponse();
    $comment_build = $issueManager->renderComment($entity->id());
    $response->addCommand(new PrependCommand('.content-issue-comments', $comment_build));
    $response->addCommand(new MessageCommand('Comment created.'));
    $response->addCommand(new CloseDialogCommand());

    return $response;
  }

  public function ajaxSubmitEdit($form, FormStateInterface $form_state) {
    $entity = $form_state->getformObject()->getEntity();
    $issueManager = \Drupal::service('content_issue.manager');

    $response = new AjaxResponse();
    $comment_build = $issueManager->renderComment($entity->id());

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
