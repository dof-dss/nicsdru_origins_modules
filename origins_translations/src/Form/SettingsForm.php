<?php

namespace Drupal\origins_translations\Form;

use Drupal\Component\Utility\UrlHelper;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\State\StateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;


/**
 * Configure Origins Translations settings for this site.
 */
class SettingsForm extends ConfigFormBase {

  /**
   * The state service.
   */
  protected $state;

  /**
   * MediaSettingsForm constructor.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
   *   The config factory.
   * @param \Drupal\Core\State\StateInterface $state
   *   The state service.
   */
  public function __construct(ConfigFactoryInterface $configFactory, StateInterface $state) {
    parent::__construct($configFactory);
    $this->state = $state;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('state')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'origins_translations_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['origins_translations.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    $form['apikey'] = [
      '#type' => 'textfield',
      '#title' => $this->t('API key'),
      '#description' => $this->t("Create an API key at https://console.cloud.google.com/apis/credentials"),
      '#default_value' =>  $this->state->get('origins_translations.settings.apikey'),
    ];

    $form['domain'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Replace domain'),
      '#description' => $this->t("Typically you would enter the live site domain if using this on a development site which Google Translate won't have access to."),
      '#default_value' => $this->config('origins_translations.settings')->get('domain'),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $domain = trim($form_state->getValue('domain'));

    if (!empty($domain) && UrlHelper::isValid($domain) === FALSE) {
      $form_state->setErrorByName('domain', $this->t('Domain must be a valid URL.'));
    }

    parent::validateForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->config('origins_translations.settings')
      ->set('domain', trim($form_state->getValue('domain'),' /'))
      ->save();

    $this->state->set('origins_translations.settings.apikey', trim($form_state->getValue('apikey')));

    parent::submitForm($form, $form_state);
  }

}
