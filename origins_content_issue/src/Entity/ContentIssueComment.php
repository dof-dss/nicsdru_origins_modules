<?php

declare(strict_types=1);

namespace Drupal\origins_content_issue\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;
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
 *     "route_provider" = {
 *       "html" = "Drupal\origins_content_issue\Routing\ContentIssueCommentHtmlRouteProvider",
 *     },
 *   },
 *   base_table = "origins_content_issue_comment",
 *   admin_permission = "administer content issue comment",
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
final class ContentIssueComment extends ContentEntityBase implements ContentIssueCommentInterface {

  use EntityChangedTrait;
  use EntityOwnerTrait;

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
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type): array {

    $fields = parent::baseFieldDefinitions($entity_type);

    $fields['content_issue_entity'] = BaseFieldDefinition::create('entity_reference')
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
