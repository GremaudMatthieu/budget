<?php

declare(strict_types=1);

namespace App\Application\Envelope\CommandHandler;

use App\Application\Envelope\Command\CreateEnvelopeCommand;
use App\Domain\Envelope\Exception\ChildrenTargetBudgetsExceedsParentException;
use App\Domain\Envelope\Factory\EnvelopeFactoryInterface;
use App\Domain\Envelope\Repository\EnvelopeCommandRepositoryInterface;
use App\Domain\Shared\Adapter\LoggerInterface;

readonly class CreateEnvelopeCommandHandler
{
    public function __construct(
        private EnvelopeCommandRepositoryInterface $envelopeCommandRepository,
        private EnvelopeFactoryInterface $envelopeFactory,
        private LoggerInterface $logger,
    ) {
    }

    /**
     * @throws ChildrenTargetBudgetsExceedsParentException
     */
    public function __invoke(CreateEnvelopeCommand $command): void
    {
        $createEnvelopeDTO = $command->getCreateEnvelopeDTO();
        $parentEnvelope = $command->getParentEnvelope();

        if ($parentEnvelope && $parentEnvelope->exceedsTargetBudget(floatval($createEnvelopeDTO->getTargetBudget()))) {
            $this->logger->error(
                "Total target budget of child envelopes exceeds the parent envelope's target budget."
            );
            throw new ChildrenTargetBudgetsExceedsParentException(
                "Total target budget of child envelopes exceeds the parent envelope's target budget.",
                400,
            );
        }

        $this->envelopeCommandRepository->save(
            $this->envelopeFactory->createEnvelope(
                $createEnvelopeDTO,
                $parentEnvelope
            )
        );
    }
}