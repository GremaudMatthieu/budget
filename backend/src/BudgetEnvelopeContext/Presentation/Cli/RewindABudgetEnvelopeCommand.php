<?php

declare(strict_types=1);

namespace App\BudgetEnvelopeContext\Presentation\Cli;

use App\BudgetEnvelopeContext\Application\Commands\RewindABudgetEnvelopeFromEventsCommand;
use App\BudgetEnvelopeContext\Domain\Ports\Outbound\CommandBusInterface;
use App\BudgetEnvelopeContext\Domain\ValueObjects\BudgetEnvelopeId;
use App\BudgetEnvelopeContext\Domain\ValueObjects\BudgetEnvelopeUserId;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;

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
            ->addArgument('budgetEnvelopeId', InputArgument::OPTIONAL, 'The ID of the budget envelope')
            ->addArgument('budgetEnvelopeUserId', InputArgument::OPTIONAL, 'The ID of the budget envelope user')
            ->addArgument('desiredDateTime', InputArgument::OPTIONAL, 'The desired date and time to rewind to (Y-m-d H:i:s format)')
            ->addOption('interactive', 'i', InputOption::VALUE_NONE, 'If set, the command will ask for input interactively');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        if ($input->getOption('interactive')) {
            $helper = $this->getHelper('question');
            $budgetEnvelopeId = $helper->ask($input, $output, new Question('Please enter the budget envelope ID: '));
            $budgetEnvelopeUserId = $helper->ask($input, $output, new Question('Please enter the budget envelope user ID: '));
            $desiredDateTime = $helper->ask($input, $output, new Question('Please enter the desired date and time (Y-m-d H:i:s): '));
        } else {
            $budgetEnvelopeId = $input->getArgument('budgetEnvelopeId');
            $budgetEnvelopeUserId = $input->getArgument('budgetEnvelopeUserId');
            $desiredDateTime = $input->getArgument('desiredDateTime');
        }

        $desiredDateTime = \DateTimeImmutable::createFromFormat('Y-m-d H:i:s', $desiredDateTime);
        if (!$desiredDateTime) {
            $output->writeln('<error>Invalid date format. Use Y-m-d H:i:s.</error>');
            return Command::FAILURE;
        }

        $this->commandBus->execute(
            new RewindABudgetEnvelopeFromEventsCommand(
                BudgetEnvelopeId::fromString($budgetEnvelopeId),
                BudgetEnvelopeUserId::fromString($budgetEnvelopeUserId),
                $desiredDateTime,
            ),
        );

        $output->writeln('<info>Budget envelope rewound successfully.</info>');

        return Command::SUCCESS;
    }
}