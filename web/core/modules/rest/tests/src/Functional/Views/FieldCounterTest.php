<?php

declare(strict_types=1);

namespace Drupal\Tests\rest\Functional\Views;

use Drupal\node\Entity\Node;
use Drupal\Tests\views\Functional\ViewTestBase;
use Drupal\views\Views;

/**
 * Tests the display of counter field.
 *
 * @group rest
 * @see \Drupal\rest\Plugin\views\display\RestExport
 * @see \Drupal\rest\Plugin\views\row\DataFieldRow
 */
class FieldCounterTest extends ViewTestBase {

  /**
   * @var \Drupal\views\ViewExecutable
   */
  protected $view;

  /**
   * The views that are used by this test.
   *
   * @var array
   */
  public static $testViews = ['test_field_counter_display'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * The modules that need to be installed for this test.
   *
   * @var array
   */
  protected static $modules = [
    'entity_test',
    'rest_test_views',
    'node',
    'field',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp($import_test_views = TRUE, $modules = ['rest_test_views']): void {
    parent::setUp($import_test_views, $modules);

    // Create some test content.
    for ($i = 1; $i <= 10; $i++) {
      Node::create([
        'type' => 'article',
        'title' => 'Article test ' . $i,
      ])->save();
    }

    $this->enableViewsTestModule();

    $this->view = Views::getView('test_field_counter_display');
    $this->view->setDisplay('rest_export_1');
  }

  /**
   * Tests the display of an excluded title field when used as a token.
   */
  public function testExcludedTitleTokenDisplay(): void {
    $actual_json = $this->drupalGet($this->view->getPath(), ['query' => ['_format' => 'json']]);
    $this->assertSession()->statusCodeEquals(200);

    $expected = [
      ['counter' => '1'],
      ['counter' => '2'],
      ['counter' => '3'],
      ['counter' => '4'],
      ['counter' => '5'],
      ['counter' => '6'],
      ['counter' => '7'],
      ['counter' => '8'],
      ['counter' => '9'],
      ['counter' => '10'],
    ];
    $this->assertSame(json_encode($expected), $actual_json);
  }

}
