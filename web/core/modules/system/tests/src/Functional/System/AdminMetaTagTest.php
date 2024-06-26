<?php

declare(strict_types=1);

namespace Drupal\Tests\system\Functional\System;

use Drupal\Tests\BrowserTestBase;

/**
 * Confirm that the fingerprinting meta tag appears as expected.
 *
 * @group system
 */
class AdminMetaTagTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * Verify that the meta tag HTML is generated correctly.
   */
  public function testMetaTag(): void {
    [$version] = explode('.', \Drupal::VERSION);
    $string = '<meta name="Generator" content="Drupal ' . $version . ' (https://www.drupal.org)" />';
    $this->drupalGet('node');
    $this->assertSession()->responseContains($string);
  }

}
