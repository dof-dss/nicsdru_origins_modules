<?php

namespace Drupal\origins_media;

/**
 * Provides utility methods for file MIME types.
 */
class PrettyMimes {

  /**
   * Convenience function to expose as service.
   *
   * Function to avoid repetition between preprocess functions and classes.
   *
   * @return string[]
   *   The keyed array of raw mime type keys and easier to read values.
   */
  public static function getMimeTypes() {
    $mimeTypes = [
      'application/pdf' => 'Adobe PDF',
      'application/msword' => 'Microsoft Word',
      'application/vnd.openxmlformats-officedocument.wordprocessingml.document' => 'Microsoft Word',
      'application/vnd.ms-excel' => 'Microsoft Excel',
      'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' => 'Microsoft Excel',
      'application/vnd.ms-excel.sheet.macroEnabled.12' => 'Microsoft Excel (macros enabled)',
      'application/vnd.ms-powerpoint' => 'Microsoft Powerpoint',
      'application/vnd.openxmlformats-officedocument.presentationml.presentation' => 'Microsoft Powerpoint',
      'application/vnd.oasis.opendocument.text' => 'OpenDocument text',
      'application/vnd.oasis.opendocument.spreadsheet' => 'OpenDocument spreadsheet',
      'application/vnd.oasis.opendocument.presentation' => 'OpenDocument presentation',
      'application/zip' => 'ZIP archive',
      'image/jpeg' => 'JPEG image',
      'image/jpg' => 'JPG image',
      'image/gif' => 'GIF image',
      'image/png' => 'PNG image',
      'image/svg+xml' => 'SVG image',
      'text/csv' => 'Comma-separated values',
      'text/html' => 'HTML (HyperText Markup Language)',
    ];

    return $mimeTypes;
  }

  /**
   * Returns a simplified file type value for a mime type.
   *
   * Useful for CSS extensions,
   * eg: in origins_media/css/media-library-styles.css.
   *
   * @return string[]
   *   The keyed array of raw mime type keys and easier to read values.
   */
  public static function getSimpleMimeTypes() {
    $simpleMimes = [
      'application/pdf' => 'pdf',
      'application/msword' => 'word',
      'application/vnd.openxmlformats-officedocument.wordprocessingml.document' => 'word',
      'application/vnd.ms-excel' => 'excel',
      'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' => 'excel',
      'application/vnd.ms-excel.sheet.macroEnabled.12' => 'excel',
      'application/vnd.ms-powerpoint' => 'powerpoint',
      'application/vnd.openxmlformats-officedocument.presentationml.presentation' => 'powerpoint',
      'application/vnd.oasis.opendocument.text' => 'opendocument-text',
      'application/vnd.oasis.opendocument.spreadsheet' => 'opendocument-spreadsheet',
      'application/vnd.oasis.opendocument.presentation' => 'opendocument-presentation',
      'application/zip' => 'zip',
      'text/csv' => 'csv',
      'text/html' => 'html',
    ];

    return $simpleMimes;
  }

}
