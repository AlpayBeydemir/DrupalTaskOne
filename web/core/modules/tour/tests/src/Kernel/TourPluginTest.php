<?php

declare(strict_types=1);

namespace Drupal\Tests\tour\Kernel;

use Drupal\KernelTests\KernelTestBase;

/**
 * Tests the functionality of tour plugins.
 *
 * @group tour
 * @group legacy
 */
class TourPluginTest extends KernelTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = ['tour'];

  /**
   * Stores the tour plugin manager.
   *
   * @var \Drupal\tour\TipPluginManager
   */
  protected $pluginManager;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->installConfig(['tour']);
    $this->pluginManager = $this->container->get('plugin.manager.tour.tip');
  }

  /**
   * Tests tour plugins.
   */
  public function testTourPlugins(): void {
    $this->assertCount(1, $this->pluginManager->getDefinitions(), 'Only tour plugins for the enabled modules were returned.');
  }

}
