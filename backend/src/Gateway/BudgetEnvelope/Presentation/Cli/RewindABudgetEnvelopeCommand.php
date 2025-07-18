<?php

declare(strict_types=1);

namespace App\Gateway\BudgetEnvelope\Presentation\Cli;

use App\BudgetEnvelopeContext\Application\Commands\RewindABudgetEnvelopeFromEventsCommand;
use App\BudgetEnvelopeContext\Domain\ValueObjects\BudgetEnvelopeId;
use App\SharedContext\Domain\Ports\Outbound\CommandBusInterface;
use App\SharedContext\Domain\ValueObjects\UserId;
use App\SharedContext\Domain\ValueObjects\UtcClock;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;


#[AsCommand(
    name: 'app:rewind-a-budget-envelope',
    description: 'Rewinds events for a budget envelope.',
)]
final class RewindABudgetEnvelopeCommand extends Command
{
    protected static $defaultName = 'app:rewind-a-budget-envelope';

    public function __construct(private readonly CommandBusInterface $commandBus)
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->setName(self::$defaultName)
            ->setDescription('Rewinds events for a budget envelope.')
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
            ->addArgument(
                'desiredDateTime',
                InputArgument::OPTIONAL,
                'The desired date and time to rewind to (Y-m-d H:i:s format)',
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
            $desiredDateTime = $helper->ask(
                $input,
                $output,
                new Question('Please enter the desired date and time: '),
            );
        } else {
            $budgetEnvelopeId = $input->getArgument('budgetEnvelopeId');
            $budgetEnvelopeUserId = $input->getArgument('budgetEnvelopeUserId');
            $desiredDateTime = $input->getArgument('desiredDateTime');
        }

        $desiredDateTime = UtcClock::fromStringToImmutable($desiredDateTime);
        if (!$desiredDateTime) {
            $output->writeln('<error>Invalid date format.</error>');
            return Command::FAILURE;
        }

        $this->commandBus->execute(
            new RewindABudgetEnvelopeFromEventsCommand(
                BudgetEnvelopeId::fromString($budgetEnvelopeId),
                UserId::fromString($budgetEnvelopeUserId),
                $desiredDateTime,
            ),
        );

        $output->writeln('<info>Budget envelope rewound successfully.</info>');

        return Command::SUCCESS;
    }
}
