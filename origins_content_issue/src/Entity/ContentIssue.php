<?php

declare(strict_types=1);

namespace Drupal\origins_content_issue\Entity;

use Drupal\comment\Plugin\Field\FieldType\CommentItemInterface;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\RevisionableContentEntityBase;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\origins_content_issue\ContentIssueInterface;
use Drupal\user\EntityOwnerTrait;

/**
 * Defines the content issue entity class.
 *
 * @ContentEntityType(
 *   id = "content_issue",
 *   label = @Translation("Content issue"),
 *   label_collection = @Translation("Content issues"),
 *   label_singular = @Translation("content issue"),
 *   label_plural = @Translation("content issues"),
 *   label_count = @PluralTranslation(
 *     singular = "@count content issues",
 *     plural = "@count content issues",
 *   ),
 *   handlers = {
 *     "list_builder" = "Drupal\origins_content_issue\ContentIssueListBuilder",
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "views_data" = "Drupal\views\EntityViewsData",
 *     "form" = {
 *       "add" = "Drupal\origins_content_issue\Form\ContentIssueForm",
 *       "edit" = "Drupal\origins_content_issue\Form\ContentIssueForm",
 *       "delete" = "Drupal\Core\Entity\ContentEntityDeleteForm",
 *       "delete-multiple-confirm" = "Drupal\Core\Entity\Form\DeleteMultipleForm",
 *       "revision-delete" = \Drupal\Core\Entity\Form\RevisionDeleteForm::class,
 *       "revision-revert" = \Drupal\Core\Entity\Form\RevisionRevertForm::class,
 *     },
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\AdminHtmlRouteProvider",
 *       "revision" = \Drupal\Core\Entity\Routing\RevisionHtmlRouteProvider::class,
 *     },
 *   },
 *   base_table = "origins_content_issue",
 *   revision_table = "origins_content_issue_revision",
 *   show_revision_ui = TRUE,
 *   admin_permission = "administer content issue",
 *   entity_keys = {
 *     "id" = "id",
 *     "revision" = "revision_id",
 *     "label" = "label",
 *     "uuid" = "uuid",
 *     "owner" = "uid",
 *   },
 *   revision_metadata_keys = {
 *     "revision_user" = "revision_uid",
 *     "revision_created" = "revision_timestamp",
 *     "revision_log_message" = "revision_log",
 *   },
 *   links = {
 *     "collection" = "/admin/content/content-issues",
 *     "add-form" = "/content-issue/add",
 *     "canonical" = "/content-issue/{content_issue}",
 *     "edit-form" = "/content-issue/{content_issue}/edit",
 *     "delete-form" = "/content-issue/{content_issue}/delete",
 *     "delete-multiple-form" = "/admin/content/content-issue/delete-multiple",
 *     "revision" = "/admin/content/content-issue/{content_issue}/revision/{content_issue_revision}/view",
 *     "revision-delete-form" = "/admin/content/content-issue/{content_issue}/revision/{content_issue_revision}/delete",
 *     "revision-revert-form" = "/admin/content/content-issue/{content_issue}/revision/{content_issue_revision}/revert",
 *     "version-history" = "/admin/content/content-issue/{content_issue}/revisions",
 *   },
 *   field_ui_base_route = "entity.content_issue.settings",
 * )
 */
final class ContentIssue extends RevisionableContentEntityBase implements ContentIssueInterface {

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

    $fields['content_entity_id'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Content entity ID'))
      ->setDescription(t('The identifier of the content entity to which this issue pertains.'))
      ->setRequired(TRUE);

    $fields['content_entity_revision_id'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Content entity revision ID'))
      ->setDescription(t('The revision of the content entity to which this issue pertains.'))
      ->setRequired(TRUE);

    $fields['content_entity_uid'] = BaseFieldDefinition::create('entity_reference')
      ->setRevisionable(TRUE)
      ->setLabel(t('Content author'))
      ->setSetting('target_type', 'user')
      ->setDisplayOptions('form', [
        'type' => 'entity_reference_autocomplete',
        'settings' => [
          'match_operator' => 'CONTAINS',
          'size' => 60,
          'placeholder' => '',
        ],
        'weight' => 20,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'author',
        'weight' => 20,
      ])
      ->setDisplayConfigurable('view', TRUE);

    $fields['label'] = BaseFieldDefinition::create('string')
      ->setRevisionable(TRUE)
      ->setLabel(t('Label'))
      ->setRequired(TRUE)
      ->setSetting('max_length', 255)
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => 1,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayOptions('view', [
        'label' => 'hidden',
        'type' => 'string',
        'weight' => 1,
      ])
      ->setDisplayConfigurable('view', TRUE);

    $fields['description'] = BaseFieldDefinition::create('text_long')
      ->setRevisionable(TRUE)
      ->setLabel(t('Description'))
      ->setDisplayOptions('form', [
        'type' => 'text_textarea',
        'weight' => 5,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayOptions('view', [
        'type' => 'text_default',
        'label' => 'hidden',
        'weight' => 5,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['severity'] = BaseFieldDefinition::create('list_string')
      ->setLabel(t('Severity'))
      ->setSettings([
        'allowed_values' => [
          1 => 'High',
          2 => 'Medium',
          3 => 'Low',
        ],
      ])
      ->setDefaultValue(2)
      ->setCardinality(1)
      ->setRequired(TRUE)
      ->setDisplayOptions('view', [
        'label' => 'visible',
        'type' => 'list_default',
        'weight' => 10,
      ])
      ->setDisplayOptions('form', [
        'type' => 'options_select',
        'weight' => 10,
        'settings' => [],
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['state'] = BaseFieldDefinition::create('list_string')
      ->setLabel(t('State'))
      ->setSettings([
        'allowed_values' => [
          1 => "To do",
          2 => "In progress",
          3 => "Done",
          4 => "Rejected",
          5 => "More info required",
        ],
      ])
      ->setDefaultValue(1)
      ->setCardinality(1)
      ->setRequired(TRUE)
      ->setRevisionable(TRUE)
      ->setDisplayOptions('view', [
        'label' => 'visible',
        'type' => 'list_default',
        'weight' => 10,
      ])
      ->setDisplayOptions('form', [
        'label' => 'inline',
        'type' => 'options_select',
        'weight' => 10,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['comments'] = BaseFieldDefinition::create('text_long')
      ->setRevisionable(TRUE)
      ->setLabel(t('Comments'))
      ->setDisplayOptions('form', [
        'type' => 'text_textarea',
        'weight' => 15,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayOptions('view', [
        'type' => 'text_default',
        'label' => 'above',
        'weight' => 15,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['uid'] = BaseFieldDefinition::create('entity_reference')
      ->setRevisionable(TRUE)
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
        'weight' => 20,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'author',
        'weight' => 20,
      ])
      ->setDisplayConfigurable('view', TRUE);

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Authored on'))
      ->setDescription(t('The time that the content issue was created.'))
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'timestamp',
        'weight' => 25,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayOptions('form', [
        'type' => 'datetime_timestamp',
        'weight' => 25,
      ])
      ->setDisplayConfigurable('view', TRUE);

    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(t('Changed'))
      ->setDescription(t('The time that the content issue was last edited.'));

    $fields['comments'] = BaseFieldDefinition::create('comment')
      ->setLabel(t('Comments'))
      ->setRequired(FALSE)
      ->setSettings(
        array(
          'default_mode'=> 1,
          'per_page'=>10,
          'anonymous'=> 0,
          'form_location'=>1,
          'preview'=> 1,
          'comment_type'=>'content_issue_comment',
          'locked'=>false,
        ))
      ->setDefaultValue([
        'status' => CommentItemInterface::OPEN,
      ])
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'comment_default',
        'weight' => 30,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayOptions('form', [
        'type' => 'comment_default',
        'settings' => array(
          'form_location' => 1,
          'default_mode'=> 1,
          'per_page'=>50,
          'anonymous'=> 0,
          'form_location'=>1,
          'preview'=> 1,
          'locked'=>false
        ),
        'weight' => 30,
      ])
      ->setDisplayConfigurable('view', TRUE);

    return $fields;
  }

}
