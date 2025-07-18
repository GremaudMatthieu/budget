<?php

declare(strict_types=1);

namespace App\Gateway\BudgetEnvelope\Presentation\Cli;

use App\BudgetEnvelopeContext\Application\Commands\ReplayABudgetEnvelopeEventsCommand;
use App\BudgetEnvelopeContext\Domain\ValueObjects\BudgetEnvelopeId;
use App\SharedContext\Domain\Ports\Outbound\CommandBusInterface;
use App\SharedContext\Domain\ValueObjects\UserId;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;

#[AsCommand(
    name: 'app:replay-a-budget-envelope',
    description: 'Replays events for a budget envelope.',
)]
final class ReplayABudgetEnvelopeCommand extends Command
{
    protected static $defaultName = 'app:replay-a-budget-envelope';

    public function __construct(private readonly CommandBusInterface $commandBus)
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->setName(self::$defaultName)
            ->setDescription('Replays events for a budget envelope.')
            ->addArgument(
                'budgetEnvelopeId',
                InputArgument::OPTIONAL,
                'The ID of the budget envelope',
            )
            ->addArgument(
                'budgetEnvelopeUserId',
                InputArgument::OPTIONAL,
                'The ID of the budget envelope user',
            )
            ->addOption(
                'interactive',
                'i',
                InputOption::VALUE_NONE,
                'If set, the command will ask for input interactively',
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        if ($input->getOption('interactive')) {
            $helper = $this->getHelper('question');
            $budgetEnvelopeId = $helper->ask(
                $input,
                $output,
                new Question('Please enter the budget envelope ID: '),
            );
            $budgetEnvelopeUserId = $helper->ask(
                $input,
                $output,
                new Question('Please enter the budget envelope user ID: '),
            );
        } else {
            $budgetEnvelopeId = $input->getArgument('budgetEnvelopeId');
            $budgetEnvelopeUserId = $input->getArgument('budgetEnvelopeUserId');
        }

        $this->commandBus->execute(
            new ReplayABudgetEnvelopeEventsCommand(
                BudgetEnvelopeId::fromString($budgetEnvelopeId),
                UserId::fromString($budgetEnvelopeUserId),
            ),
        );

        $output->writeln('<info>Budget envelope replayed successfully.</info>');

        return Command::SUCCESS;
    }
}
