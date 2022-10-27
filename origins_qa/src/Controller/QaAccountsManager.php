<?php

namespace Drupal\origins_qa\Controller;

use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\OpenModalDialogCommand;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Form\FormBuilder;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\user\Entity\User;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * Controller for Origins QA.
 */
class QaAccountsManager extends ControllerBase {

  /**
   * The form builder.
   *
   * @var \Drupal\Core\Form\FormBuilder
   */
  protected $formBuilder;

  /**
   * {@inheritdoc}
   *
   * @param \Drupal\Core\Form\FormBuilder $formBuilder
   *   The form builder.
   */
  public function __construct(FormBuilder $formBuilder = NULL) {
    // Note that $formBuilder will be NULL if calling from drush.
    $this->formBuilder = $formBuilder;
  }

  /**
   * {@inheritdoc}
   *
   * @param \Symfony\Component\DependencyInjection\ContainerInterface $container
   *   The Drupal service container.
   *
   * @return static
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('form_builder')
    );
  }

  /**
   * Returns a list of QA accounts.
   */
  public function list() {
    $build = [];

    // Fetch all the accounts belonging to the 'qa' role.
    $accounts = $this->entityTypeManager()
      ->getListBuilder('user')
      ->getStorage()
      ->loadByProperties([
        'roles' => 'qa'
      ]);

    $header = [
      'username' => $this->t('Username'),
      'status' => $this->t('Status'),
      'last_access' => $this->t('Last access'),
      'operations' => $this->t('Operations'),
    ];

    $rows = [];

    foreach ($accounts as $account) {
      /** @var \Drupal\user\UserInterface $account */
      $rows[] = [
        $account->label(),
        ($account->isActive()) ? 'Enabled' : 'Disabled',
        ($account->getLastAccessedTime() == 0) ? 'Never' : date('d F Y', $account->getLastAccessedTime()),
        [
          'data' => [
            '#type' => 'dropbutton',
            '#links' => [
              'edit' => [
                'title' => $this->t('Edit'),
                'url' => Url::fromRoute('entity.user.edit_form', ['user' => $account->id()]),
              ],

            ],
          ],
        ],
      ];
    }

    $build['qa_accounts'] = [
      '#type' => 'table',
      '#header' => $header,
      '#rows' => $rows,
      '#empty' => $this->t("There are no accounts on this site associated with the 'qa' (Quality Assurance) role. You will need to assign test accounts to that role for them to show up in this table."),
    ];

    // If there are some QA accounts then allow bulk password change.
    if (!empty($accounts)) {
      $build['open_modal'] = [
        '#type' => 'link',
        '#title' => $this->t('Set passwords for all QA accounts'),
        '#url' => Url::fromRoute('origins_qa.manager.password_form_modal'),
        '#attributes' => [
          'class' => [
            'use-ajax',
            'button',
            'button-action',
            'button--primary'
          ],
        ],
      ];
      $build['#attached']['library'][] = 'core/drupal.dialog.ajax';
    }

    // Always allow QA account creation.
    $build['open_modal_2'] = [
      '#type' => 'link',
      '#title' => $this->t('Create QA accounts'),
      '#url' => Url::fromRoute('origins_qa.manager.qa_account_create_form_modal'),
      '#attributes' => [
        'class' => [
          'use-ajax',
          'button',
          'button-action',
          'button--primary'
        ],
      ],
    ];
    $build['#attached']['library'][] = 'core/drupal.dialog.ajax';

    return $build;
  }

  /**
   * Toggles all QA accounts to either active or blocked.
   */
  public function toggleAll($action) {
    $accounts = $this->entityTypeManager()
      ->getListBuilder('user')
      ->getStorage()
      ->loadByProperties([
        'roles' => 'qa'
      ]);

    foreach ($accounts as $account) {
      /** @var \Drupal\user\UserInterface $account */
      if ($action === 'enable') {
        if (!$account->isActive()) {
          $account->activate();
          $account->save();
        }
      }
      else {
        if ($account->isActive()) {
          $account->block();
          $account->save();
        }
      }
    }

    $this->messenger()->addMessage('Updated all QA accounts.');

    return $this->redirect('origins_qa.manager.list');
  }

  /**
   * Ajax callback for displaying the password form.
   */
  public function displayPasswordForm() {
    $response = new AjaxResponse();

    $modal_form = $this->formBuilder->getForm('Drupal\origins_qa\Form\QaPasswordSetForm');
    $response->addCommand(new OpenModalDialogCommand('QA Password form', $modal_form, ['width' => '300']));

    return $response;
  }

  /**
   * Ajax callback for displaying the user creation form.
   */
  public function displayAccountCreationForm() {
    $response = new AjaxResponse();

    $modal_form = $this->formBuilder->getForm('Drupal\origins_qa\Form\CreateQaAccountsForm');
    $response->addCommand(new OpenModalDialogCommand('QA Account creation form', $modal_form, ['width' => '300']));

    return $response;
  }

}
