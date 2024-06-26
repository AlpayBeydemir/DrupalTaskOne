<?php

declare(strict_types=1);

namespace Drupal\Tests\locale\Unit;

use Drupal\locale\SourceString;
use Drupal\locale\StringStorageException;
use Drupal\Tests\UnitTestCase;

/**
 * @coversDefaultClass \Drupal\locale\StringBase
 * @group locale
 */
class StringBaseTest extends UnitTestCase {

  /**
   * @covers ::save
   */
  public function testSaveWithoutStorage(): void {
    $string = new SourceString(['source' => 'test']);
    $this->expectException(StringStorageException::class);
    $this->expectExceptionMessage('The string cannot be saved because its not bound to a storage: test');
    $string->save();
  }

  /**
   * @covers ::delete
   */
  public function testDeleteWithoutStorage(): void {
    $string = new SourceString(['lid' => 1, 'source' => 'test']);
    $this->expectException(StringStorageException::class);
    $this->expectExceptionMessage('The string cannot be deleted because its not bound to a storage: test');
    $string->delete();
  }

}
