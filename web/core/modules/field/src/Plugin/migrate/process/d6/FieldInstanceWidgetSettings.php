<?php

namespace Drupal\field\Plugin\migrate\process\d6;

use Drupal\migrate\Attribute\MigrateProcess;
use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\Row;

// cspell:ignore filefield imagefield optionwidgets

/**
 * Get the field instance widget settings.
 */
#[MigrateProcess('field_instance_widget_settings')]
class FieldInstanceWidgetSettings extends ProcessPluginBase {

  /**
   * {@inheritdoc}
   *
   * Get the field instance default/mapped widget settings.
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    [$widget_type, $widget_settings] = $value;
    return $this->getSettings($widget_type, $widget_settings);
  }

  /**
   * Merge the default D8 and specified D6 settings for a widget type.
   *
   * @param string $widget_type
   *   The widget type.
   * @param array $widget_settings
   *   The widget settings from D6 for this widget.
   *
   * @return array
   *   A valid array of settings.
   */
  public function getSettings($widget_type, $widget_settings) {
    $progress = $widget_settings['progress_indicator'] ?? 'throbber';
    $size = $widget_settings['size'] ?? 60;
    $rows = $widget_settings['rows'] ?? 5;

    $settings = [
      'text_textfield' => [
        'size' => $size,
        'placeholder' => '',
      ],
      'text_textarea' => [
        'rows' => $rows,
        'placeholder' => '',
      ],
      'number' => [
        'placeholder' => '',
      ],
      'email_textfield' => [
        'placeholder' => '',
      ],
      'link' => [
        'placeholder_url' => '',
        'placeholder_title' => '',
      ],
      'filefield_widget' => [
        'progress_indicator' => $progress,
      ],
      'imagefield_widget' => [
        'progress_indicator' => $progress,
        'preview_image_style' => 'thumbnail',
      ],
      'optionwidgets_onoff' => [
        'display_label' => FALSE,
      ],
      'phone_textfield' => [
        'placeholder' => '',
      ],
    ];

    return $settings[$widget_type] ?? [];
  }

}
