<?php

declare(strict_types=1);

namespace Drupal\origins_content_issue\Entity;

use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\RevisionableContentEntityBase;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Mail\MailManagerInterface;
use Drupal\Core\Url;
use Drupal\node\Entity\Node;
use Drupal\origins_content_issue\ContentIssueInterface;
use Drupal\user\Entity\User;
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
 *     "access" = "Drupal\origins_content_issue\ContentIssueAccessControlHandler",
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\AdminHtmlRouteProvider",
 *       "revision" = \Drupal\Core\Entity\Routing\RevisionHtmlRouteProvider::class,
 *     },
 *   },
 *   base_table = "origins_content_issue",
 *   revision_table = "origins_content_issue_revision",
 *   show_revision_ui = TRUE,
 *   admin_permission = "administer content issue",
 *   collection_permission = "view content issue",
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
 *     "collection" = "/admin/content/content-issues/{content_issue?}",
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

    if ($this->isNew()) {
      // Assign the issue to the author of the node with the issue.
      $node_id = $this->get('content_entity_id')->getString();
      if (!empty($node_id)) {
        $node = Node::load($node_id);
        $assign_to = User::load($node->getOwnerId());
        if (!empty($assign_to)) {
          $this->set('assigned_to', $assign_to);
        }
      }
    }
    else {
      $config = \Drupal::config('origins_content_issue.settings');
      $notify_reporter = explode(',', $config->get('notify_reporter') ?? []);

      if (array_filter($notify_reporter)) {

        $new_state = $this->get('state')->getString();
        $old_state = $this->original->get('state')->getString();

        // Notify if the state has changed and config is set to notify for that state change.
        if ($new_state != $old_state && in_array($new_state, $notify_reporter)) {
          $reporter = $this->getOwner();

          // Extract the field label from definitions, so we can convert the stored key to a readable label.
          $fields = \Drupal::service('entity_field.manager')->getFieldDefinitions('content_issue', 'content_issue');
          $state_values = $fields['state']->getSetting('allowed_values');

          $params = [
            'subject' => $this->label(),
            'site' => \Drupal::config('system.site')->get('name'),
            'title' => $this->label(),
            'link' => Url::fromRoute('entity.content_issue.collection', [
              'entity_id' => $this->get('content_entity_id')->getString(),
              'revision_id' => $this->get('content_entity_revision_id')->getString(),
            ]),
            'old_state' => $state_values[$old_state],
            'new_state' => $state_values[$new_state],
          ];

          $this->mailManager->mail('origins_content_issue', 'content_issue_state_change', $reporter->getEmail(), 'en', $params, NULL, TRUE);
        }
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function postSave(EntityStorageInterface $storage, $update = TRUE): void {
    // Process new content issues only.
    if (!$update) {
      $author = $this->getOwner();
      $assigned_to = $this->get('assigned_to')?->first()?->get('entity')?->getTarget()?->getValue();
      $notify = \Drupal::config('origins_content_issue.settings')->get('notify_on_create') ?? TRUE;

      // Email assigned user if they are not the reporter and notifications are enabled.
      if ($author->id() != $assigned_to->id() && $notify) {
        $node = Node::load($this->get('content_entity_id')->getString());

        $params = [
          'site' => \Drupal::config('system.site')->get('name'),
          'bundle' => $node->bundle(),
          'title' => $node->label(),
          'description' => $this->get('description')->getValue()[0]['value'],
          'link' => Url::fromRoute('entity.content_issue.collection', [
            'entity_id' => $this->get('content_entity_id')->getString(),
            'revision_id' => $this->get('content_entity_revision_id')->getString(),
          ]),
          'subject' => substr($node->label(), 0, 50) . '...',
        ];

        $this->mailManager->mail('origins_content_issue', 'new_content_issue', $assigned_to->getEmail(), 'en', $params, NULL, TRUE);
      }
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

    $fields['assigned_to'] = BaseFieldDefinition::create('entity_reference')
      ->setRevisionable(TRUE)
      ->setLabel(t('Assigned to'))
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

    return $fields;
  }

  /**
   * Returns the comment entities for this content issue.
   */
  public function getComments() {

    $query = $this->entityTypeManager()->getStorage('content_issue_comment')->getQuery();

    $ids = $query->condition('issue_entity', $this->id())
      ->sort('changed', 'DESC')
      ->accessCheck(TRUE)
      ->execute();

    return $this->entityTypeManager()->getStorage('content_issue_comment')->loadMultiple($ids);
  }

}
