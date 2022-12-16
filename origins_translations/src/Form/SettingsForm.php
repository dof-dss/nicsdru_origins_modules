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
   *
   * @var \Drupal\Core\State\StateInterface
   */
  protected $state;

  /**
   * Settings Form constructor.
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

    $form['domain'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Replace domain'),
      '#description' => $this->t("Typically you would enter the live site domain if using this on a development site as Google Translate may not have access to local or restricted environments."),
      '#default_value' => $this->config('origins_translations.settings')->get('domain'),
    ];

    $form['ui-appearance'] = [
      '#type' => 'details',
      '#title' => $this->t('Appearance'),
      'ui-position' => [
        '#type' => 'select',
        '#title' => $this->t('Translation menu positioning'),
        '#description' => $this->t(
          'By default the menu is static (not positioned). If parent HTML
          containers of the translation block are non-static positioned
          elements (their css position is set to relative, absolute, fixed or
          sticky) it is probably best to go with the default here. For other
          positioning options to work effectively, parent containers should
          have static positioning.'
        ),
        '#options' => [
          'default' => 'static',
          'ot-tl' => 'top left',
          'ot-tr' => 'top right',
          'ot-br' => 'bottom right',
          'ot-bl' => 'bottom left',
        ],
        '#default_value' => $this->config('origins_translations.settings')->get('ui-position'),
      ],
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
    $this->configFactory()->getEditable('origins_translations.settings')
      ->set('domain', trim($form_state->getValue('domain'), ' /'))
      ->set('ui-position', $form_state->getValue('ui-position'))
      ->save();

    $this->state->set('origins_translations.settings.apikey', trim($form_state->getValue('apikey')));

    parent::submitForm($form, $form_state);
  }

}
