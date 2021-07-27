<?php

namespace Drupal\origins_translations\Form;

use Drupal\Component\Render\FormattableMarkup;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Link;
use Drupal\Core\State\StateInterface;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;


/**
 * Configure Origins Translations languages for this site.
 */
class LanguagesForm extends ConfigFormBase {

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
    return 'origins_translations_languages';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['origins_translations.settings.languages'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

//
//
//    $translationClient = new TranslateClient([
//      'key' => 'AIzaSyD4RhmhRngu5sSoiAHSniF5IHmK7dYxrqc'
////      'credentials' => json_decode(file_get_contents('/app/nics-321013-d27dc8c23901.json'), true)
//    ]);
//
//    $response = $translationClient->localizedLanguages();
////    $response = $translationClient->getSupportedLanguages('projects/nics-321013', ['displayLanguageCode' => 'en']);
//
////    $languages = $response->getLanguages();
//    $langs = [];
////
////    foreach ($languages as $language) {
////      $langs[$language->language_code] = $language->display_name;
////    }
//
//    ksm($response, $langs);

    $config = $this->config('origins_translations.languages');

    $languages = $config->getRawData();
    unset($languages['_core']);

    $form['languages'] = [
      '#type' => 'table',
      '#header' => [
        $this->t('Operations'),
        $this->t('Language'),
        $this->t('Enabled'),
        $this->t('Translate this page'),
        $this->t('Select a language'),
      ],
    ];

    foreach ($languages as $code => $language) {
      $form['languages']['#rows'][$code] = [
        ['data' => [
          '#type' => 'dropbutton',
          '#links' => [
            'edit' => [
              'title' => $this->t('Edit'),
              'url' => Url::fromRoute('origins_translations.settings.languages.edit', ['code' => $code])
            ],
            'toggle' => [
              'title' => $this->t('Enable/Disable'),
              'url' => Url::fromRoute('origins_translations.settings.languages.toggle', ['code' => $code])
            ]
          ],
        ]],
        $language['0'],
        $language['1'],
        $language['2'],
        $language['3'],
      ];
    }


    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);
  }

}
