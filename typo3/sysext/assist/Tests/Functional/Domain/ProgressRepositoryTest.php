<?php

declare(strict_types=1);

/*
 * This file is part of the TYPO3 CMS project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */

namespace TYPO3\CMS\Assist\Tests\Functional\Domain;

use PHPUnit\Framework\Attributes\Test;
use Symfony\Component\Uid\Uuid;
use TYPO3\CMS\Assist\Domain\PlatformModel;
use TYPO3\CMS\Assist\Domain\Progress;
use TYPO3\CMS\Assist\Domain\ProgressItem;
use TYPO3\CMS\Assist\Domain\ProgressItemType;
use TYPO3\CMS\Assist\Domain\Repository\ProgressRepository;
use TYPO3\CMS\Assist\Tests\Functional\AssistBasedTestTrait;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

class ProgressRepositoryTest extends FunctionalTestCase
{
    use AssistBasedTestTrait;

    protected array $coreExtensionsToLoad = ['assist'];
    private ProgressRepository $subject;

    public function setUp(): void
    {
        parent::setUp();
        $this->importCSVDataSet(__DIR__ . '/../Fixtures/pages.csv');
        $this->buildAssistSiteConfiguration(
            'ProgressRepositoryTest',
            1,
            '/',
            [self::ASSIST_PLATFORM_NUMB_ENCORE]
        );
        $this->subject = $this->get(ProgressRepository::class);
    }

    #[Test]
    public function addIncreasesSequencePerUuid(): void
    {
        $uuid = Uuid::v4();
        $model = new PlatformModel('typo3/symfony-ai-numb-platform', 'numb-encore');

        $seq0 = $this->subject->append($uuid, $model, new ProgressItem(ProgressItemType::submitted, 'hello'));
        $seq1 = $this->subject->append($uuid, $model, new ProgressItem(ProgressItemType::received, 'world'));
        $seq2 = $this->subject->append($uuid, $model, new ProgressItem(ProgressItemType::submitted, 'follow-up'));

        self::assertSame(0, $seq0);
        self::assertSame(1, $seq1);
        self::assertSame(2, $seq2);
    }

    #[Test]
    public function findByUuidReturnsProgressWithOrderedItems(): void
    {
        $uuid = Uuid::v4();
        $model = new PlatformModel('typo3/symfony-ai-numb-platform', 'numb-encore');

        $this->subject->append($uuid, $model, new ProgressItem(ProgressItemType::submitted, 'first'));
        $this->subject->append($uuid, $model, new ProgressItem(ProgressItemType::received, 'second'));

        $progress = $this->subject->findByUuid($uuid);

        self::assertInstanceOf(Progress::class, $progress);
        self::assertTrue($uuid->equals($progress->uuid));
        self::assertSame((string)$model, (string)$progress->model);
        self::assertCount(2, $progress->items);
        self::assertInstanceOf(ProgressItem::class, $progress->items[0]);
        self::assertSame(ProgressItemType::submitted, $progress->items[0]->type);
        self::assertSame('"first"', $progress->items[0]->payload);
        self::assertSame(ProgressItemType::received, $progress->items[1]->type);
        self::assertSame('"second"', $progress->items[1]->payload);
    }

    #[Test]
    public function findByUuidReturnsNullForUnknownUuid(): void
    {
        $progress = $this->subject->findByUuid(Uuid::v4());
        self::assertNull($progress);
    }

    #[Test]
    public function sequencesAreIndependentPerUuid(): void
    {
        $uuid1 = Uuid::v4();
        $uuid2 = Uuid::v4();
        $model = new PlatformModel('typo3/symfony-ai-numb-platform', 'numb-encore');

        $this->subject->append($uuid1, $model, new ProgressItem(ProgressItemType::submitted, null));
        $this->subject->append($uuid1, $model, new ProgressItem(ProgressItemType::received, null));
        $seq = $this->subject->append($uuid2, $model, new ProgressItem(ProgressItemType::submitted, null));

        self::assertSame(0, $seq);
    }

    #[Test]
    public function addStoresModelAndType(): void
    {
        $uuid = Uuid::v4();
        $model = new PlatformModel('mittwald/symfony-ai-platform', 'gpt-oss-120b');

        $this->subject->append($uuid, $model, new ProgressItem(ProgressItemType::received, 'response'));

        $progress = $this->subject->findByUuid($uuid);
        self::assertInstanceOf(Progress::class, $progress);
        self::assertSame((string)$model, (string)$progress->model);
        self::assertCount(1, $progress->items);
        self::assertSame(ProgressItemType::received, $progress->items[0]->type);
    }
}
