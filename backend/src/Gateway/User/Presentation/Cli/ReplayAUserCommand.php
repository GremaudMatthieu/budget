<?php

declare(strict_types=1);

namespace App\Gateway\User\Presentation\Cli;

use App\SharedContext\Domain\Ports\Outbound\CommandBusInterface;
use App\SharedContext\Domain\ValueObjects\UserId;
use App\UserContext\Application\Commands\ReplayAUserEventsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;

final class ReplayAUserCommand extends Command
{
    protected static $defaultName = 'app:replay-a-user';

    public function __construct(private readonly CommandBusInterface $commandBus)
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->setName(self::$defaultName)
            ->setDescription('Replays events for a user.')
            ->addArgument('userId', InputArgument::OPTIONAL, 'The ID of the user')
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
            $userId = $helper->ask($input, $output, new Question('Please enter the user ID: '));
        } else {
            $userId = $input->getArgument('userId');
        }

        $this->commandBus->execute(
            new ReplayAUserEventsCommand(
                UserId::fromString($userId),
            ),
        );

        $output->writeln('<info>User replayed successfully.</info>');

        return Command::SUCCESS;
    }
}
