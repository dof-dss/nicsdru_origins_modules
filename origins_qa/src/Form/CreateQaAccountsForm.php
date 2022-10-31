<?php

namespace Drupal\origins_qa\Form;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
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

    $qac = new QaAccountsManager();
    $successes = $qac->createQaAccounts($prefix, $password, FALSE);

    if ($successes > 0) {
      $this->messenger()->addStatus($this->t('@count QA accounts created successfully.', ['@count' => $successes]));
    }
    $form_state->setRedirect('origins_qa.manager.list');
  }

}
