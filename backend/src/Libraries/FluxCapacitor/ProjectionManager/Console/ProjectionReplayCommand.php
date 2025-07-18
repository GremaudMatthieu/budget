<?php

declare(strict_types=1);

namespace App\Libraries\FluxCapacitor\ProjectionManager\Console;

use App\Libraries\FluxCapacitor\ProjectionManager\Ports\ProjectionManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'projections:replay',
    description: 'Replay projections from event store',
)]
final class ProjectionReplayCommand extends Command
{
    public function __construct(
        private readonly ProjectionManagerInterface $projectionManager,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('projection', InputArgument::OPTIONAL, 'Specific projection class to replay')
            ->addOption('all', 'a', InputOption::VALUE_NONE, 'Replay all projections')
            ->addOption('from-date', null, InputOption::VALUE_REQUIRED, 'Replay from specific date (Y-m-d H:i:s)')
            ->addOption('reset-first', 'r', InputOption::VALUE_NONE, 'Reset projection before replay')
            ->addOption('batch-size', 'b', InputOption::VALUE_REQUIRED, 'Batch size for processing events (default: 5000)', 5000)
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $projectionClass = $input->getArgument('projection');
        $replayAll = $input->getOption('all');
        $fromDate = $input->getOption('from-date');
        $resetFirst = $input->getOption('reset-first');
        $batchSize = (int) $input->getOption('batch-size');

        $io->title('🔄 Projection Replay');

        if (!$projectionClass && !$replayAll) {
            $io->error('Please specify a projection class or use --all flag');

            return Command::FAILURE;
        }

        try {
            $fromDateTime = $fromDate ? new \DateTimeImmutable($fromDate) : null;

            if ($replayAll) {
                $io->info('Replaying all projections...');

                if ($resetFirst) {
                    $io->note('Resetting all projections first...');
                    foreach ($this->projectionManager->getAllProjections() as $projection) {
                        $this->projectionManager->resetProjection($projection);
                    }
                }

                $progressBar = $io->createProgressBar();
                $progressBar->start();
                $results = $this->projectionManager->replayAllProjections($fromDateTime, $batchSize);
                $progressBar->finish();
                $io->newLine(2);
                $rows = [];
                $totalEvents = 0;

                foreach ($results as $projection => $eventCount) {
                    $shortName = basename(str_replace('\\', '/', $projection));
                    $rows[] = [$shortName, number_format($eventCount)];
                    $totalEvents += $eventCount;
                }

                $io->table(['Projection', 'Events Replayed'], $rows);
                $io->success('Completed replay of '.count($results)." projections ({$totalEvents} total events)");

            } else {
                $io->info("Replaying projection: {$projectionClass}");

                if ($resetFirst) {
                    $io->note('Resetting projection first...');
                    $this->projectionManager->resetProjection($projectionClass);
                }

                $eventCount = $this->projectionManager->replayProjection($projectionClass, $fromDateTime, $batchSize);
                $io->success("✅ Replayed {$eventCount} events for ".basename(str_replace('\\', '/', $projectionClass)));
            }

        } catch (\Exception $e) {
            $io->error("Replay failed: {$e->getMessage()}");

            return Command::FAILURE;
        }

        return Command::SUCCESS;
    }
}
