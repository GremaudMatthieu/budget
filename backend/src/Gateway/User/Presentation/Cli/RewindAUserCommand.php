<?php

declare(strict_types=1);

namespace App\Gateway\User\Presentation\Cli;

use App\SharedContext\Domain\Ports\Outbound\CommandBusInterface;
use App\SharedContext\Domain\ValueObjects\UserId;
use App\UserContext\Application\Commands\RewindAUserFromEventsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;

final class RewindAUserCommand extends Command
{
    protected static $defaultName = 'app:rewind-a-user';

    public function __construct(private readonly CommandBusInterface $commandBus)
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->setName(self::$defaultName)
            ->setDescription('Rewinds events for a user.')
            ->addArgument(
                'userId',
                InputArgument::OPTIONAL,
                'The ID of the user',
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
            $userId = $helper->ask(
                $input,
                $output,
                new Question('Please enter the user ID: '),
            );
            $desiredDateTime = $helper->ask(
                $input,
                $output,
                new Question('Please enter the desired date and time (Y-m-d H:i:s): '),
            );
        } else {
            $userId = $input->getArgument('userId');
            $desiredDateTime = $input->getArgument('desiredDateTime');
        }

        $desiredDateTime = \DateTimeImmutable::createFromFormat('Y-m-d H:i:s', $desiredDateTime);
        if (!$desiredDateTime) {
            $output->writeln('<error>Invalid date format. Use Y-m-d H:i:s.</error>');
            return Command::FAILURE;
        }

        $this->commandBus->execute(
            new RewindAUserFromEventsCommand(
                UserId::fromString($userId),
                $desiredDateTime,
            ),
        );

        $output->writeln('<info>User rewound successfully.</info>');

        return Command::SUCCESS;
    }
}
