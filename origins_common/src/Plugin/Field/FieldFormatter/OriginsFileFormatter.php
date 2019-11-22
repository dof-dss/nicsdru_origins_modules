<?php

namespace Drupal\origins_common\Plugin\Field\FieldFormatter;

use Drupal;
use Drupal\Component\Utility\Html;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\Core\Link;
use Drupal\Core\Render\Markup;
use Drupal\file\Plugin\Field\FieldFormatter\FileFormatterBase;

/**
 * Plugin implementation of the 'origins_file_formatter' formatter.
 *
 * @FieldFormatter(
 *   id = "origins_file_formatter",
 *   label = @Translation("Origins file formatter"),
 *   field_types = {
 *     "file"
 *   }
 * )
 */
class OriginsFileFormatter extends FileFormatterBase {

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    $settings = parent::defaultSettings();

    $settings['use_description_as_link_text'] = TRUE;
    $settings['filemime_image'] = FALSE;

    return $settings;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $form = parent::settingsForm($form, $form_state);
    $form['use_description_as_link_text'] = [
      '#title' => $this->t('Use description as link text'),
      '#description' => $this->t('Replace the file name by its description when available'),
      '#type' => 'checkbox',
      '#default_value' => $this->getSetting('use_description_as_link_text'),
    ];
    $form['filemime_image'] = [
      '#title' => $this->t('Show file icon.'),
      '#description' => $this->t('Check this checkbox to display file icon.'),
      '#type' => 'checkbox',
      '#default_value' => $this->getSetting('filemime_image'),
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = parent::settingsSummary();
    $settings = $this->getSettings();
    if ($this->getSetting('use_description_as_link_text')) {
      $summary[] = $this->t('Use description as link text');
    }
    $summary[] = ($settings['filemime_image'] == TRUE) ? $this->t('Display icon : Yes') : $this->t('Display icon : No');
    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = [];
    foreach ($this->getEntitiesToView($items, $langcode) as $delta => $file) {
      $item = $file->_referringItem;
      $mime = $file->getMimeType();
      $simple_mime = origins_common_friendly_mime($mime);
      $file_lang = $file->language()->getId();

      $attributes = [
        'class' => ['file-link'],
        'type' => $mime . '; length=' . $file->getSize(),
      ];

      if ($file_lang != Drupal::languageManager()->getCurrentLanguage()->getId()) {
        $attributes['lang'] = $file_lang;
      }

      if ($this->getSetting('filemime_image') == TRUE) {
        $attributes['class'][] = 'file--ico';
        $attributes['class'][] = 'file--ico-' . strtolower(Html::cleanCssIdentifier($simple_mime));
      }
      else {
        $attributes['class'][] = 'file-link--simple';
      }

      $desc = $this->getSetting('use_description_as_link_text') ? $item->description : NULL;
      $meta = ' <span class="meta">' . $simple_mime . ' (' . format_size($file->getSize()) . ')</span>';
      if (empty($desc)) {
        $link_text = $file->getFilename() . $meta;
      }
      else {
        $link_text = $desc . $meta;
      }
      $link_markup = Markup::create($link_text);

      $options = ['attributes' => $attributes];

      // Output.
      $elements[$delta] = [
        '#type'     => 'inline_template',
        '#template' => Link::fromTextAndUrl($link_markup, Url::fromUri(file_create_url($file->getFileUri()), $options))->toString(),
        '#cache' => [
          'tags' => $file->getCacheTags(),
        ],
      ];
    }

    return $elements;
  }

}

/**
 * Determines the generic icon MIME package based on a file's MIME type.
 *
 * @param string $mime_type
 *   A MIME type.
 *
 * @return string|false
 *   The generic icon MIME package expected for this file.
 */
function origins_common_friendly_mime($mime_type) {
  switch ($mime_type) {
    // Word document types.
    case 'application/msword':
    case 'application/vnd.ms-word.document.macroEnabled.12':
    case 'application/vnd.openxmlformats-officedocument.wordprocessingml.document':
      return 'Word';

    case 'application/vnd.oasis.opendocument.text':
    case 'application/vnd.oasis.opendocument.text-template':
    case 'application/vnd.oasis.opendocument.text-master':
    case 'application/vnd.oasis.opendocument.text-web':
      return 'OpenDocument Text';

    // Spreadsheet document types.
    case 'application/vnd.ms-excel':
    case 'application/vnd.ms-excel.sheet.macroEnabled.12':
    case 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet':
      return 'Excel';

    case 'application/vnd.oasis.opendocument.spreadsheet':
    case 'application/vnd.oasis.opendocument.spreadsheet-template':
      return 'OpenDocument Spreadsheet';

    // Presentation document types.
    case 'application/vnd.ms-powerpoint':
    case 'application/vnd.ms-powerpoint.presentation.macroEnabled.12':
    case 'application/vnd.openxmlformats-officedocument.presentationml.presentation':
      return 'PowerPoint';

    case 'application/vnd.oasis.opendocument.presentation':
    case 'application/vnd.oasis.opendocument.presentation-template':
      return 'OpenDocument Presentation';

    // Compressed archive types.
    case 'application/zip':
    case 'application/x-zip':
      return 'Zip';

    // Script file types.
    case 'text/csv':
      return 'CSV';

    // HTML aliases.
    case 'text/html':
    case 'application/xhtml+xml':
      return 'HTML';

    // Acrobat types.
    case 'application/pdf':
    case 'application/x-pdf':
    case 'applications/vnd.pdf':
    case 'text/pdf':
    case 'text/x-pdf':
      return 'PDF';

    default:
      return $mime_type;
  }
}
