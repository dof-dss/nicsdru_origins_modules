<?php

namespace Drupal\origins_qa\Form;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element\Form;
use Drupal\origins_qa\Controller\QaAccountsManager;
use Drupal\user\Entity\User;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Create QA accounts.
 */
class CreateQaAccountsForm extends FormBase {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Form constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager) {
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'origins_qa_create_accounts';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    $roles = $this->entityTypeManager->getStorage('user_role')->loadMultiple();
    $role_options = [];

    foreach ($roles as $role) {
      $role_options[$role->id()] = $role->label();
    }

    $role_options = array_diff($role_options, [
      'anonymous' => 'anonymous user',
      'authenticated' => 'authenticated user',
      'administrator' => 'Administrator'
    ]);

    $form['info'] = [
      '#type' => 'item',
      '#title' => $this->t('(Existing QA accounts will not be affected)'),
    ];

    $form['prefix'] = [
      '#type' => 'textfield',
      '#title' => $this->t('User prefix'),
      '#default_value' => 'nw_test',
      '#required' => TRUE,
    ];

    $form['password'] = [
      '#type' => 'password',
      '#title' => $this->t('Password'),
      '#required' => TRUE,
    ];

    $form['roles_only'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Create for selected roles only.'),
    ];

    $form['roles_container'] = [
      '#type' => 'fieldset',
      '#states' => [
        'visible' => [
          ':input[name="roles_only"]' => ['checked' => TRUE],
        ],
      ],
    ];

    $form['roles_container']['roles'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Select roles.'),
      '#options' => $role_options,
    ];

    $form['actions'] = [
      '#type' => 'actions',
    ];
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Create'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();

    $prefix = $values['prefix'];
    $password = $values['password'];
    $roles_only = $values['roles_only'];

    $qac = new QaAccountsManager();

    if ($roles_only) {
      $roles = array_keys(array_filter($values['roles']));
      $successes = $qac->createQaAccountsForRoles($prefix, $password, $roles);
    }
    else {
      $successes = $qac->createQaAccounts($prefix, $password, FALSE);
    }

    if ($successes > 0) {
      $this->messenger()->addStatus($this->t('@count QA accounts created successfully.', ['@count' => $successes]));
    }
    $form_state->setRedirect('origins_qa.manager.list');
  }

}
