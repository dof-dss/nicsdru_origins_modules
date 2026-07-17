<?php

declare(strict_types=1);

namespace Drupal\origins_help\Form;

use Drupal\Core\Cache\Cache;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\State\StateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Settings form for Origins Help Confluence integration.
 *
 * All values are stored in State (database only) rather than exportable
 * configuration, as they include credentials and are environment-specific.
 */
final class SettingsForm extends FormBase {

  public function __construct(
    private readonly StateInterface $state,
  ) {}

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): self {
    return new self($container->get('state'));
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'origins_help_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {
    $token_set = (bool) $this->state->get('origins_help.confluence_api_token');

    $form['confluence_base_url'] = [
      '#type' => 'url',
      '#title' => $this->t('Confluence base URL'),
      '#description' => $this->t('The root URL of your Atlassian site, e.g. <code>https://yourcompany.atlassian.net</code>.'),
      '#default_value' => $this->state->get('origins_help.confluence_base_url', ''),
      '#required' => TRUE,
    ];

    $form['confluence_email'] = [
      '#type' => 'email',
      '#title' => $this->t('Atlassian account email'),
      '#description' => $this->t('Email address of the Atlassian account that owns the API token.'),
      '#default_value' => $this->state->get('origins_help.confluence_email', ''),
      '#required' => TRUE,
    ];

    $form['confluence_api_token'] = [
      '#type' => 'password',
      '#title' => $this->t('API token'),
      '#description' => $token_set
        ? $this->t('A token is already saved. Leave blank to keep the existing token.')
        : $this->t('Generate a token at <a href="https://id.atlassian.com/manage-profile/security/api-tokens" target="_blank" rel="noopener noreferrer">id.atlassian.com</a>.'),
      '#required' => !$token_set,
    ];

    $form['confluence_parent_page_id'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Parent page ID'),
      '#description' => $this->t('Numeric ID of the Confluence page whose children will be listed as help links. Find the ID in the page URL or via the Confluence page information dialog.'),
      '#default_value' => $this->state->get('origins_help.confluence_parent_page_id', ''),
      '#required' => TRUE,
    ];

    $form['confluence_project_id'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Project ID'),
      '#description' => $this->t('Only display pages that have this label in Confluence. Leave blank to display all pages. Labels are case-insensitive (e.g. <code>my-project</code>).'),
      '#default_value' => $this->state->get('origins_help.confluence_project_id', ''),
    ];

    $form['confluence_max_depth'] = [
      '#type' => 'select',
      '#title' => $this->t('Sub-page depth'),
      '#description' => $this->t('How many levels of child pages to display. Each additional level makes one API call per parent page.'),
      '#options' => [
        1 => $this->t('1 — direct children only'),
        2 => $this->t('2 — children and grandchildren'),
        3 => $this->t('3 — three levels deep'),
      ],
      '#default_value' => $this->state->get('origins_help.confluence_max_depth', 2),
    ];

    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Save settings'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {
    $this->state->set(
      'origins_help.confluence_base_url',
      rtrim((string) $form_state->getValue('confluence_base_url'), '/')
    );
    $this->state->set('origins_help.confluence_email', $form_state->getValue('confluence_email'));
    $this->state->set('origins_help.confluence_parent_page_id', $form_state->getValue('confluence_parent_page_id'));
    $this->state->set('origins_help.confluence_project_id', trim((string) $form_state->getValue('confluence_project_id')));
    $this->state->set('origins_help.confluence_max_depth', (int) $form_state->getValue('confluence_max_depth'));

    $token = $form_state->getValue('confluence_api_token');
    if (!empty($token)) {
      $this->state->set('origins_help.confluence_api_token', $token);
    }

    // Invalidate the cached Confluence page tree so the next help page load
    // fetches fresh data with the updated settings.
    Cache::invalidateTags(['origins_help:confluence']);

    $this->messenger()->addStatus($this->t('Settings saved.'));
  }

}
