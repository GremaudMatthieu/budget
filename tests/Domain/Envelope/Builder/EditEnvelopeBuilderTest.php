<?php

declare(strict_types=1);

namespace App\Tests\Domain\Envelope\Builder;

use App\Domain\Envelope\Builder\EditEnvelopeBuilder;
use App\Domain\Envelope\Dto\UpdateEnvelopeDtoInterface;
use App\Domain\Envelope\Entity\Envelope;
use App\Domain\Envelope\Entity\EnvelopeInterface;
use App\Domain\Envelope\Exception\ChildrenTargetBudgetsExceedsParentEnvelopeTargetBudgetException;
use App\Domain\Envelope\Exception\EnvelopeCurrentBudgetExceedsParentEnvelopeTargetBudgetException;
use App\Domain\Envelope\Exception\SelfParentEnvelopeException;
use App\Domain\Envelope\Validator\CurrentBudgetValidator;
use App\Domain\Envelope\Validator\TargetBudgetValidator;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class EditEnvelopeBuilderTest extends TestCase
{
    private TargetBudgetValidator&MockObject $targetBudgetValidator;
    private CurrentBudgetValidator&MockObject $currentBudgetValidator;
    private EditEnvelopeBuilder $editEnvelopeBuilder;

    protected function setUp(): void
    {
        $this->targetBudgetValidator = $this->createMock(TargetBudgetValidator::class);
        $this->currentBudgetValidator = $this->createMock(CurrentBudgetValidator::class);
        $this->editEnvelopeBuilder = new EditEnvelopeBuilder(
            $this->targetBudgetValidator,
            $this->currentBudgetValidator
        );
    }

    public function testSetParentEnvelope(): void
    {
        $parentEnvelope = $this->createMock(EnvelopeInterface::class);
        $result = $this->editEnvelopeBuilder->setParentEnvelope($parentEnvelope);

        $this->assertSame($this->editEnvelopeBuilder, $result);
    }

    public function testSetUpdateEnvelopeDto(): void
    {
        $updateEnvelopeDto = $this->createMock(UpdateEnvelopeDtoInterface::class);
        $result = $this->editEnvelopeBuilder->setUpdateEnvelopeDto($updateEnvelopeDto);

        $this->assertSame($this->editEnvelopeBuilder, $result);
    }

    public function testSetEnvelope(): void
    {
        $envelope = $this->createMock(EnvelopeInterface::class);
        $result = $this->editEnvelopeBuilder->setEnvelope($envelope);

        $this->assertSame($this->editEnvelopeBuilder, $result);
    }

    /**
     * @throws ChildrenTargetBudgetsExceedsParentEnvelopeTargetBudgetException
     * @throws EnvelopeCurrentBudgetExceedsParentEnvelopeTargetBudgetException
     * @throws SelfParentEnvelopeException
     */
    public function testBuildSuccess(): void
    {
        $updateEnvelopeDto = $this->createMock(UpdateEnvelopeDtoInterface::class);
        $updateEnvelopeDto->method('getTargetBudget')->willReturn('1000.00');
        $updateEnvelopeDto->method('getCurrentBudget')->willReturn('500.00');
        $updateEnvelopeDto->method('getTitle')->willReturn('Test Title');

        $parentEnvelope = $this->createMock(EnvelopeInterface::class);
        $parentEnvelope->method('getId')->willReturn(1);
        $parentEnvelope->method('getTargetBudget')->willReturn('1000.00');
        $parentEnvelope->method('getCurrentBudget')->willReturn('500.00');

        $envelope = $this->createMock(EnvelopeInterface::class);
        $envelope->method('getId')->willReturn(2);
        $envelope->method('getTargetBudget')->willReturn('1000.00');
        $envelope->method('getCurrentBudget')->willReturn('500.00');
        $envelope->method('getParent')->willReturn($parentEnvelope);
        $envelope->method('getTitle')->willReturn('Test Title');

        $this->targetBudgetValidator->expects($this->once())
            ->method('validate')
            ->with('1000.00', $parentEnvelope, $envelope);

        $this->currentBudgetValidator->expects($this->once())
            ->method('validate')
            ->with('500.00', $parentEnvelope);

        $this->editEnvelopeBuilder->setUpdateEnvelopeDto($updateEnvelopeDto);
        $this->editEnvelopeBuilder->setParentEnvelope($parentEnvelope);
        $this->editEnvelopeBuilder->setEnvelope($envelope);

        $result = $this->editEnvelopeBuilder->build();

        $this->assertSame($envelope, $result);
        $this->assertSame('1000.00', $envelope->getTargetBudget());
        $this->assertSame('500.00', $envelope->getCurrentBudget());
        $this->assertSame('Test Title', $envelope->getTitle());
    }

    /**
     * @throws ChildrenTargetBudgetsExceedsParentEnvelopeTargetBudgetException
     * @throws EnvelopeCurrentBudgetExceedsParentEnvelopeTargetBudgetException
     * @throws SelfParentEnvelopeException
     */
    public function testSelfParentEnvelopeException(): void
    {
        $updateEnvelopeDto = $this->createMock(UpdateEnvelopeDtoInterface::class);
        $updateEnvelopeDto->method('getTargetBudget')->willReturn('1000.00');
        $updateEnvelopeDto->method('getCurrentBudget')->willReturn('500.00');
        $updateEnvelopeDto->method('getTitle')->willReturn('Test Title');

        $envelope = $this->createMock(EnvelopeInterface::class);
        $envelope->method('getCurrentBudget')->willReturn('500.00');
        $envelope->method('getParent')->willReturn($envelope);

        $this->targetBudgetValidator->expects($this->never())
            ->method('validate');

        $this->currentBudgetValidator->expects($this->never())
            ->method('validate');

        $this->editEnvelopeBuilder->setUpdateEnvelopeDto($updateEnvelopeDto);
        $this->editEnvelopeBuilder->setParentEnvelope($envelope);
        $this->editEnvelopeBuilder->setEnvelope($envelope);

        $this->expectException(SelfParentEnvelopeException::class);

        $this->editEnvelopeBuilder->build();
    }

    /**
     * @throws ChildrenTargetBudgetsExceedsParentEnvelopeTargetBudgetException
     * @throws SelfParentEnvelopeException
     */
    public function testBuildFailureDueToCurrentBudgetExceedsParentTarget(): void
    {
        $updateEnvelopeDto = $this->createMock(UpdateEnvelopeDtoInterface::class);
        $updateEnvelopeDto->method('getTargetBudget')->willReturn('1000.00');
        $updateEnvelopeDto->method('getCurrentBudget')->willReturn('1500.00');
        $updateEnvelopeDto->method('getTitle')->willReturn('Test Title');

        $parentEnvelope = $this->createMock(EnvelopeInterface::class);
        $parentEnvelope->method('getId')->willReturn(1);
        $parentEnvelope->method('getTargetBudget')->willReturn('1000.00');
        $parentEnvelope->method('getCurrentBudget')->willReturn('500.00');

        $envelope = $this->createMock(EnvelopeInterface::class);
        $envelope->method('getCurrentBudget')->willReturn('500.00');
        $envelope->method('getParent')->willReturn($parentEnvelope);
        $envelope->method('getId')->willReturn(2);


        $this->targetBudgetValidator->expects($this->once())
            ->method('validate')
            ->with('1000.00', $parentEnvelope, $envelope);

        $this->currentBudgetValidator->expects($this->once())
            ->method('validate')
            ->with('1500.00', $parentEnvelope)
            ->willThrowException(new EnvelopeCurrentBudgetExceedsParentEnvelopeTargetBudgetException(EnvelopeCurrentBudgetExceedsParentEnvelopeTargetBudgetException::MESSAGE, 400));

        $this->editEnvelopeBuilder->setUpdateEnvelopeDto($updateEnvelopeDto);
        $this->editEnvelopeBuilder->setParentEnvelope($parentEnvelope);
        $this->editEnvelopeBuilder->setEnvelope($envelope);

        $this->expectException(EnvelopeCurrentBudgetExceedsParentEnvelopeTargetBudgetException::class);

        $this->editEnvelopeBuilder->build();
    }

    /**
     * @throws ChildrenTargetBudgetsExceedsParentEnvelopeTargetBudgetException
     * @throws SelfParentEnvelopeException
     */
    public function testUpdateParentCurrentBudgetThrowsException(): void
    {
        $updateEnvelopeDto = $this->createMock(UpdateEnvelopeDtoInterface::class);
        $updateEnvelopeDto->method('getTargetBudget')->willReturn('1000.00');
        $updateEnvelopeDto->method('getCurrentBudget')->willReturn('1500.00');
        $updateEnvelopeDto->method('getTitle')->willReturn('Test Title');

        $parentEnvelope = new Envelope();
        $parentEnvelope->setId(1);
        $parentEnvelope->setTargetBudget('1000.00');
        $parentEnvelope->setCurrentBudget('500.00');

        $envelope = new Envelope();
        $envelope->setId(2);
        $envelope->setCurrentBudget('500.00');
        $envelope->setParent($parentEnvelope);

        $this->targetBudgetValidator->expects($this->once())
            ->method('validate')
            ->with('1000.00', $parentEnvelope, $envelope);

        $this->currentBudgetValidator->expects($this->once())
            ->method('validate')
            ->with('1500.00', $parentEnvelope);

        $this->editEnvelopeBuilder->setUpdateEnvelopeDto($updateEnvelopeDto);
        $this->editEnvelopeBuilder->setParentEnvelope($parentEnvelope);
        $this->editEnvelopeBuilder->setEnvelope($envelope);

        $this->expectException(EnvelopeCurrentBudgetExceedsParentEnvelopeTargetBudgetException::class);

        $this->editEnvelopeBuilder->build();
    }
}