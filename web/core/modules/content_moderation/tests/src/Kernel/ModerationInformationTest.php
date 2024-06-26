<?php

declare(strict_types=1);

namespace Drupal\Tests\content_moderation\Kernel;

use Drupal\entity_test\Entity\EntityTestMulRevPub;
use Drupal\entity_test\Entity\EntityTestRev;
use Drupal\KernelTests\KernelTestBase;
use Drupal\language\Entity\ConfigurableLanguage;
use Drupal\Tests\content_moderation\Traits\ContentModerationTestTrait;

/**
 * @coversDefaultClass \Drupal\content_moderation\ModerationInformation
 * @group content_moderation
 */
class ModerationInformationTest extends KernelTestBase {

  use ContentModerationTestTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'content_moderation',
    'entity_test',
    'user',
    'workflows',
    'language',
    'content_translation',
  ];

  /**
   * The moderation information service.
   *
   * @var \Drupal\content_moderation\ModerationInformationInterface
   */
  protected $moderationInformation;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->installEntitySchema('entity_test_rev');
    $this->installEntitySchema('entity_test_mulrevpub');
    $this->installEntitySchema('content_moderation_state');
    $this->installConfig(['content_moderation']);

    $this->moderationInformation = $this->container->get('content_moderation.moderation_information');

    ConfigurableLanguage::createFromLangcode('de')->save();

    $workflow = $this->createEditorialWorkflow();
    $workflow->getTypePlugin()->addEntityTypeAndBundle('entity_test_mulrevpub', 'entity_test_mulrevpub');
    $workflow->getTypePlugin()->addEntityTypeAndBundle('entity_test_rev', 'entity_test_rev');
    $workflow->save();

    $this->container->get('content_translation.manager')->setEnabled('entity_test_mulrevpub', 'entity_test_mulrevpub', TRUE);
  }

  /**
   * @covers ::getDefaultRevisionId
   */
  public function testGetDefaultRevisionId(): void {
    $entity_test_rev = EntityTestRev::create([
      'name' => 'Default Revision',
      'moderation_state' => 'published',
    ]);
    $entity_test_rev->save();

    $entity_test_rev->name = 'Pending revision';
    $entity_test_rev->moderation_state = 'draft';
    $entity_test_rev->save();

    // Check that moderation information service returns the correct default
    // revision ID.
    $default_revision_id = $this->moderationInformation->getDefaultRevisionId('entity_test_rev', $entity_test_rev->id());
    $this->assertSame(1, $default_revision_id);
  }

  /**
   * @covers ::isDefaultRevisionPublished
   * @dataProvider isDefaultRevisionPublishedTestCases
   */
  public function testIsDefaultRevisionPublished($initial_state, $final_state, $initial_is_default_published, $final_is_default_published): void {
    $entity = EntityTestMulRevPub::create([
      'moderation_state' => $initial_state,
    ]);
    $entity->save();
    $this->assertEquals($initial_is_default_published, $this->moderationInformation->isDefaultRevisionPublished($entity));

    $entity->moderation_state = $final_state;
    $entity->save();
    $this->assertEquals($final_is_default_published, $this->moderationInformation->isDefaultRevisionPublished($entity));
  }

  /**
   * Test cases for ::testIsDefaultRevisionPublished.
   */
  public static function isDefaultRevisionPublishedTestCases() {
    return [
      'Draft to draft' => [
        'draft',
        'draft',
        FALSE,
        FALSE,
      ],
      'Draft to published' => [
        'draft',
        'published',
        FALSE,
        TRUE,
      ],
      'Published to published' => [
        'published',
        'published',
        TRUE,
        TRUE,
      ],
      'Published to draft' => [
        'published',
        'draft',
        TRUE,
        TRUE,
      ],
    ];
  }

  /**
   * @covers ::isDefaultRevisionPublished
   */
  public function testIsDefaultRevisionPublishedMultilingual(): void {
    $entity = EntityTestMulRevPub::create([
      'moderation_state' => 'draft',
    ]);
    $entity->save();
    $this->assertEquals('draft', $entity->moderation_state->value);

    $translated = $entity->addTranslation('de');
    $translated->moderation_state = 'published';
    $translated->save();
    $this->assertEquals('published', $translated->moderation_state->value);

    // Test a scenario where the default revision exists with the default
    // language in a draft state and a non-default language in a published
    // state. The method returns TRUE if any of the languages for the default
    // revision are in a published state.
    $this->assertTrue($this->moderationInformation->isDefaultRevisionPublished($entity));
  }

  /**
   * @covers ::hasPendingRevision
   */
  public function testHasPendingRevision(): void {
    $entity = EntityTestMulRevPub::create([
      'moderation_state' => 'published',
    ]);
    $entity->save();

    // Add a translation as a new revision.
    $translated = $entity->addTranslation('de');
    $translated->moderation_state = 'published';
    $translated->setNewRevision(TRUE);

    // Test a scenario where the default revision exists with the default
    // language in a published state and a non-default language in an unsaved
    // state.
    $this->assertFalse($this->moderationInformation->hasPendingRevision($translated));

    // Save the translation and assert there is no pending revision.
    $translated->save();
    $this->assertFalse($this->moderationInformation->hasPendingRevision($translated));

    // Create a new draft for the translation and assert there is a pending
    // revision.
    $translated->moderation_state = 'draft';
    $translated->setNewRevision(TRUE);
    $translated->save();
    $this->assertTrue($this->moderationInformation->hasPendingRevision($translated));
  }

  /**
   * @covers ::getOriginalState
   */
  public function testGetOriginalState(): void {
    $entity = EntityTestMulRevPub::create([
      'moderation_state' => 'published',
    ]);
    $entity->save();
    $entity->moderation_state = 'foo';
    $this->assertEquals('published', $this->moderationInformation->getOriginalState($entity)->id());
  }

  /**
   * @covers ::getOriginalState
   */
  public function testGetOriginalStateMultilingual(): void {
    $entity = EntityTestMulRevPub::create([
      'moderation_state' => 'draft',
    ]);
    $entity->save();

    $translated = $entity->addTranslation('de', $entity->toArray());
    $translated->moderation_state = 'published';
    $translated->save();

    $translated->moderation_state = 'foo';
    $this->assertEquals('published', $this->moderationInformation->getOriginalState($translated)->id());
  }

}
