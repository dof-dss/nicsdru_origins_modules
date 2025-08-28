<?php

declare(strict_types=1);

namespace Drupal\origins_content_issue\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Mail\MailManagerInterface;
use Drupal\origins_content_issue\ContentIssueCommentInterface;
use Drupal\user\EntityOwnerTrait;

/**
 * Defines the content issue comment entity class.
 *
 * @ContentEntityType(
 *   id = "content_issue_comment",
 *   label = @Translation("Content Issue Comment"),
 *   label_collection = @Translation("Content Issue Comments"),
 *   label_singular = @Translation("content issue comment"),
 *   label_plural = @Translation("content issue comments"),
 *   label_count = @PluralTranslation(
 *     singular = "@count content issue comments",
 *     plural = "@count content issue comments",
 *   ),
 *   handlers = {
 *     "views_data" = "Drupal\views\EntityViewsData",
 *     "form" = {
 *       "add" = "Drupal\origins_content_issue\Form\ContentIssueCommentForm",
 *       "edit" = "Drupal\origins_content_issue\Form\ContentIssueCommentForm",
 *       "delete" = "Drupal\Core\Entity\ContentEntityDeleteForm",
 *     },
 *    "access" = "Drupal\origins_content_issue\ContentIssueCommentAccessControlHandler",
 *     "route_provider" = {
 *       "html" = "Drupal\origins_content_issue\Routing\ContentIssueCommentHtmlRouteProvider",
 *     },
 *   },
 *   base_table = "origins_content_issue_comment",
 *   admin_permission = "administer content issue comments",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "id",
 *     "uuid" = "uuid",
 *     "owner" = "uid",
 *   },
 *   links = {
 *     "add-form" = "/content-issue-comment/add",
 *     "canonical" = "/content-issue-comment/{content_issue_comment}",
 *     "edit-form" = "/content-issue-comment/{content_issue_comment}",
 *     "delete-form" = "/content-issue-comment/{content_issue_comment}/delete",
 *   },
 * )
 */
final class ContentIssueComment extends ContentEntityBase {

  use EntityChangedTrait;
  use EntityOwnerTrait;


  /**
   * Mail manager service.
   *
   * @var \Drupal\Core\Mail\MailManagerInterface
   */
  protected MailManagerInterface $mailManager;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $values, $entity_type, $bundle = FALSE, $translations = []) {
    parent::__construct($values, $entity_type, $bundle, $translations);
    $this->mailManager = \Drupal::service('plugin.manager.mail');
  }

  /**
   * {@inheritdoc}
   */
  public function preSave(EntityStorageInterface $storage): void {
    parent::preSave($storage);

    if (!$this->getOwnerId()) {
      $this->setOwnerId(0);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function postSave(EntityStorageInterface $storage, $update = TRUE): void {
    $config = \Drupal::config('origins_content_issue.settings');
    $notify = $config->get('notify_on_create') ?? TRUE;

    if ($notify) {
      $site_name = \Drupal::config('system.site')->get('name');
      $current_user = \Drupal::currentUser();
      $content_issue = $content_issue = $this->get('issue_entity')->entity;

      // @phpstan-ignore-next-line
      $reported_by = $content_issue->getOwner();
      // @phpstan-ignore-next-line
      $assigned_to = $content_issue->get('assigned_to')->entity;
      $email_to = [];

      if ($current_user->id() != $reported_by->id()) {
        $email_to[] = $reported_by->getEmail();
      }

      if ($current_user->id() != $assigned_to->id()) {
        $email_to[] = $assigned_to->getEmail();
      }

      $params = [
        'site' => $site_name,
        'issue_title' => $content_issue->label(),
        'comment' => $this->get('comment')->getValue()[0]['value'],
        'link' => $content_issue->toUrl('canonical', ['absolute' => TRUE])
          ->toString(),
        'subject' => t('New Comment for @issue', ['@issue' => $content_issue->label()]),
      ];

      $this->mailManager->mail('origins_content_issue', 'content_issue_comment', implode(',', $email_to), 'en', $params, NULL, TRUE);
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type): array {

    $fields = parent::baseFieldDefinitions($entity_type);

    $fields['issue_entity'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Content issue'))
      ->setSetting('target_type', 'content_issue')
      ->setDescription(t('Content issue this comment is for.'))
      ->setRequired(TRUE);

    $fields['comment'] = BaseFieldDefinition::create('text_long')
      ->setLabel(t('Comment'))
      ->setDisplayOptions('form', [
        'type' => 'text_textarea',
        'weight' => 10,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayOptions('view', [
        'type' => 'text_default',
        'label' => 'above',
        'weight' => 10,
      ])
      ->setDisplayConfigurable('view', TRUE);

    $fields['uid'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Author'))
      ->setSetting('target_type', 'user')
      ->setDefaultValueCallback(self::class . '::getDefaultEntityOwner')
      ->setDisplayOptions('form', [
        'type' => 'entity_reference_autocomplete',
        'settings' => [
          'match_operator' => 'CONTAINS',
          'size' => 60,
          'placeholder' => '',
        ],
        'weight' => 15,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'author',
        'weight' => 15,
      ])
      ->setDisplayConfigurable('view', TRUE);

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Authored on'))
      ->setDescription(t('The time that comment was created.'))
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'timestamp',
        'weight' => 20,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayOptions('form', [
        'type' => 'datetime_timestamp',
        'weight' => 20,
      ])
      ->setDisplayConfigurable('view', TRUE);

    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(t('Changed'))
      ->setDescription(t('The time that the comment was last edited.'));

    return $fields;
  }

}
