<?php

declare(strict_types=1);

namespace Drupal\Tests\Core\Access;

use Drupal\Core\Access\AccessResultForbidden;
use Drupal\Tests\UnitTestCase;

/**
 * @coversDefaultClass \Drupal\Core\Access\AccessResultForbidden
 * @group Access
 */
class AccessResultForbiddenTest extends UnitTestCase {

  /**
   * Tests the construction of an AccessResultForbidden object.
   *
   * @covers ::__construct
   * @covers ::getReason
   */
  public function testConstruction(): void {

    $a = new AccessResultForbidden();
    $this->assertEquals(NULL, $a->getReason());

    $reason = $this->getRandomGenerator()->string();
    $b = new AccessResultForbidden($reason);
    $this->assertEquals($reason, $b->getReason());
  }

  /**
   * Tests setReason()
   *
   * @covers ::setReason
   */
  public function testSetReason(): void {
    $a = new AccessResultForbidden();

    $reason = $this->getRandomGenerator()->string();
    $return = $a->setReason($reason);

    $this->assertSame($reason, $a->getReason());
    $this->assertSame($a, $return);
  }

}
