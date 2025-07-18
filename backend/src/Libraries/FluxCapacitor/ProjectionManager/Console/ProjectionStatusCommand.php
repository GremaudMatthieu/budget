<?php

declare(strict_types=1);

namespace App\Libraries\FluxCapacitor\ProjectionManager\Console;

use App\Libraries\FluxCapacitor\ProjectionManager\Ports\ProjectionManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'projections:status',
    description: 'Show status of all projections',
)]
final class ProjectionStatusCommand extends Command
{
    public function __construct(
        private readonly ProjectionManagerInterface $projectionManager,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $io->title('📊 Projections Status');

        try {
            $projections = $this->projectionManager->getAllProjections();

            $rows = [];
            $totalRows = 0;

            foreach ($projections as $projectionClass) {
                $status = $this->projectionManager->getProjectionStatus($projectionClass);

                $shortName = basename(str_replace('\\', '/', $projectionClass));
                $lastUpdate = $status['last_update']
                    ? new \DateTime($status['last_update'])->format(\DateTime::ATOM)
                    : 'Never';

                $rows[] = [
                    $shortName,
                    $status['table'],
                    number_format($status['row_count']),
                    $lastUpdate,
                ];

                $totalRows += $status['row_count'];
            }

            $io->table(['Projection', 'Table', 'Rows', 'Last Update'], $rows);

            $io->section('📈 Summary');
            $io->definitionList(
                ['Total Projections' => count($projections)],
                ['Total Rows' => number_format($totalRows)],
            );

            if (0 === $totalRows) {
                $io->warning('⚠️  No data in projections - you may need to run projections:replay --all');
            } else {
                $io->success('✅ Projections appear to be populated');
            }

        } catch (\Exception $e) {
            $io->error("Status check failed: {$e->getMessage()}");

            return Command::FAILURE;
        }

        return Command::SUCCESS;
    }
}
