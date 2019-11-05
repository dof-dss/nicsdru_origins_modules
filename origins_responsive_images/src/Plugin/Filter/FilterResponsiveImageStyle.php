<?php

namespace Drupal\origins_responsive_images\Plugin\Filter;

use Drupal\Component\Utility\Html;
use Drupal\filter\FilterProcessResult;
use Drupal\filter\Plugin\FilterBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provides a filter to render inline images as responsive images.
 *
 * @Filter(
 *   id = "filter_responsive_image_style",
 *   module = "origins_responsive_images",
 *   title = @Translation("Display responsive images"),
 *   description = @Translation("Uses the data-responsive-image-style attribute on &lt;img&gt; tags to display responsive images."),
 *   type = Drupal\filter\Plugin\FilterInterface::TYPE_TRANSFORM_REVERSIBLE
 * )
 */
class FilterResponsiveImageStyle extends FilterBase {

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $image_styles = \Drupal::entityTypeManager()->getStorage('responsive_image_style')->loadMultiple();
    $form['responsive_styles'] = [
      '#type' => 'markup',
      '#markup' => 'Select the responsive styles that are available in the editor',
    ];
    foreach ($image_styles as $image_style) {
      $form['responsive_style_' . $image_style->id()] = [
        '#type' => 'checkbox',
        '#title' => $image_style->label(),
        '#default_value' => isset($this->settings['responsive_style_' . $image_style->id()]) ? $this->settings['responsive_style_' . $image_style->id()] : 0,
      ];
    }
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function process($text, $langcode) {
    if (stristr($text, 'data-responsive-image-style') !== FALSE && stristr($text, 'data-image-style') == FALSE) {
      $image_styles = \Drupal::entityTypeManager()->getStorage('responsive_image_style')->loadMultiple();

      $dom = Html::load($text);
      $xpath = new \DOMXPath($dom);
      foreach ($xpath->query('//*[@data-entity-type="media" and @data-entity-uuid and @data-responsive-image-style]') as $node) {
        $file_uuid = $node->getAttribute('data-entity-uuid');
        $image_style_id = $node->getAttribute('data-responsive-image-style');

        // If the image style is not a valid one, then don't transform the HTML.
        if (empty($file_uuid) || !isset($image_styles[$image_style_id])) {
          continue;
        }

        // Retrieved matching file in array for the specified uuid.
        $matching_files = \Drupal::entityTypeManager()->getStorage('media')->loadByProperties(['uuid' => $file_uuid]);
        $file = reset($matching_files);

        // Stop further element processing, if it's not a valid file.
        if (!$file) {
          continue;
        }

        $fid = $file->field_media_image->target_id;
        $thisfile = \Drupal::entityTypeManager()->getStorage('file')->loadByProperties(['fid' => $fid]);
        $thisfile = reset($thisfile);

        $image = \Drupal::service('image.factory')->get($thisfile->getFileUri());

        // Stop further element processing, if it's not a valid image.
        if (!$image->isValid()) {
          continue;
        }

        $width = $image->getWidth();
        $height = $image->getHeight();

        $node->removeAttribute('width');
        $node->removeAttribute('height');
        $node->removeAttribute('src');

        // Make sure all non-regenerated attributes are retained.
        $attributes = [];
        for ($i = 0; $i < $node->attributes->length; $i++) {
          $attr = $node->attributes->item($i);
          $attributes[$attr->name] = $attr->value;
        }

        // Set up image render array.
        // (all embedded images are floated right in Origins sites).
        $image = [
          '#theme' => 'responsive_image',
          '#uri' => $thisfile->getFileUri(),
          '#width' => $width,
          '#height' => $height,
          '#attributes' => $attributes,
          '#responsive_image_style_id' => $image_style_id,
          '#prefix' => "<div class='align-right'>",
          '#suffix' => "</div>",
        ];

        $altered_html = \Drupal::service('renderer')->render($image);

        // Load the altered HTML into a new DOMDocument and
        // retrieve the elements.
        $alt_nodes = Html::load(trim($altered_html))->getElementsByTagName('body')
          ->item(0)
          ->childNodes;

        foreach ($alt_nodes as $alt_node) {
          // Import the updated node from the new DOMDocument into the original
          // one, importing also the child nodes of the updated node.
          $new_node = $dom->importNode($alt_node, TRUE);
          // Add the image node(s)!
          // The order of the children is reversed later on,
          // so insert them in reversed order now.
          $node->parentNode->insertBefore($new_node, $node);
        }
        // Finally, remove the original image node.
        $node->parentNode->removeChild($node);
      }

      return new FilterProcessResult(Html::serialize($dom));
    }

    return new FilterProcessResult($text);
  }

  /**
   * {@inheritdoc}
   */
  public function tips($long = FALSE) {
    if ($long) {
      $image_styles = \Drupal::entityTypeManager()->getStorage('responsive_image_style')->loadMultiple();
      $list = '<code>' . implode('</code>, <code>', array_keys($image_styles)) . '</code>';
      return t('
        <p>You can make images responsive by adding a <code>data-responsive-image-style</code> attribute, whose value is one of the responsive image style machine names: !responsive-image-style-machine-name-list.</p>', ['!responsive-image-style-machine-name-list' => $list]);
    }
    else {
      return t('You can make images responsive by adding a data-responsive-image-style attribute.');
    }
  }

}
