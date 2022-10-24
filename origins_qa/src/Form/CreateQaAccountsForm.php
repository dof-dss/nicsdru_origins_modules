<?php

namespace Drupal\origins_qa\Form;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\user\Entity\User;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Set all QA account passwords.
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

    $form['actions'] = [
      '#type' => 'actions',
    ];
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Update'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {

    $prefix = $form_state->getValue('prefix');
    $password = $form_state->getValue('password');

    // Create test users.
    $name_list = [
      '_author' => 'author_user',
      '_authenticated' => '',
      '_super' => 'supervisor_user',
      '_editor' => 'editor_user',
      '_admin' => 'administrator',
    ];
    $successes = 0;
    foreach ($name_list as $name => $role) {
      $name = strtolower($prefix) . $name;
      $user = user_load_by_name($name);
      if (empty($user)) {
        $msg = t('Creating user @name', ['@name' => $name]);
        \Drupal::logger('origins_qa')->notice($msg);
        $this->messenger()->addMessage($msg);
        $user = User::create([
          'name' => $name,
          'mail' => $name . '@localhost',
          'status' => 1,
          'pass' => $password,
          'roles' => [$role, 'authenticated', 'qa'],
        ]);
        $user->save();
        $successes++;
      }
      else {
        $msg = t('Did not create user @name as already exists.', ['@name' => $name]);
        \Drupal::logger('origins_qa')->notice($msg);
        $this->messenger()->addMessage($msg);
      }
    }
    if ($successes > 0) {
      $this->messenger()->addStatus($this->t('QA accounts created successfully.'));
    }
    $form_state->setRedirect('origins_qa.manager.list');
  }

}
