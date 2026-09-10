<?php

namespace Drupal\origins_internal_link_checker\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\origins_internal_link_checker\UrlList;

/**
 * Implements admin form to allow setting of audit text.
 */
class LinkCheckerForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames(): array {
    return [
      'origins_internal_link_checker.linksettings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'origins_internal_link_checker_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {
    $config = $this->config('origins_internal_link_checker.linksettings');

    $form['site_url_list'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Site domain aliases'),
      '#description' => $this->t('The current domain is handled automatically. Enter any other domain aliases, such as preview or staging domains, one per line. Include http:// or https:// and an optional port, but do not include a path.'),
      '#default_value' => $config->get('site_url_list'),
    ];

    $form['site_url_list_exclude'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Site URLs to exclude'),
      '#description' => $this->t('Enter complete internal URLs that must remain absolute, one per line.'),
      '#default_value' => $config->get('site_url_list_exclude'),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state): void {
    foreach (['site_url_list' => FALSE, 'site_url_list_exclude' => TRUE] as $form_key => $allow_path) {
      $value = (string) $form_state->getValue($form_key);
      $invalid_urls = UrlList::invalidUrls($value, $allow_path);
      if ($invalid_urls) {
        $form_state->setErrorByName($form_key, $this->t(
          'Enter complete HTTP or HTTPS URLs. Invalid entries: @urls',
          ['@urls' => implode(', ', $invalid_urls)],
        ));
      }
      $form_state->setValue($form_key, UrlList::normalise($value));
    }
    parent::validateForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {
    parent::submitForm($form, $form_state);

    $this->config('origins_internal_link_checker.linksettings')
      ->set('site_url_list', $form_state->getValue('site_url_list'))
      ->set('site_url_list_exclude', $form_state->getValue('site_url_list_exclude'))
      ->save();
  }

}
