<?php

declare(strict_types=1);

namespace Drupal\origins_help\Form;

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
      '#description' => $this->t('Numeric ID of the Confluence page whose immediate children will be listed as help links. Find the ID in the page URL or via the Confluence page information dialog.'),
      '#default_value' => $this->state->get('origins_help.confluence_parent_page_id', ''),
      '#required' => TRUE,
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

    $token = $form_state->getValue('confluence_api_token');
    if (!empty($token)) {
      $this->state->set('origins_help.confluence_api_token', $token);
    }

    $this->messenger()->addStatus($this->t('Settings saved.'));
  }

}
