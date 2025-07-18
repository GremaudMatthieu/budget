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
    name: 'projections:reset',
    description: 'Reset projection read models (truncate tables)',
)]
final class ProjectionResetCommand extends Command
{
    public function __construct(
        private readonly ProjectionManagerInterface $projectionManager,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('projection', InputArgument::OPTIONAL, 'Specific projection class to reset')
            ->addOption('all', 'a', InputOption::VALUE_NONE, 'Reset all projections')
            ->addOption('force', 'f', InputOption::VALUE_NONE, 'Force reset without confirmation')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $projectionClass = $input->getArgument('projection');
        $resetAll = $input->getOption('all');
        $force = $input->getOption('force');

        $io->title('🗑️  Projection Reset');

        if (!$projectionClass && !$resetAll) {
            $io->error('Please specify a projection class or use --all flag');

            return Command::FAILURE;
        }

        try {
            if ($resetAll) {
                $projections = $this->projectionManager->getAllProjections();
                $io->warning('This will reset ALL projections:');
                $io->listing(array_map(fn ($p) => basename(str_replace('\\', '/', $p)), $projections));

                if (!$force && !$io->confirm('Are you sure you want to reset all projections?', false)) {
                    $io->note('Operation cancelled');

                    return Command::SUCCESS;
                }

                foreach ($projections as $projection) {
                    $this->projectionManager->resetProjection($projection);
                    $io->info('✅ Reset: '.basename(str_replace('\\', '/', $projection)));
                }

                $io->success('All projections have been reset');
            } else {
                if (!$force && !$io->confirm("Reset projection {$projectionClass}?", false)) {
                    $io->note('Operation cancelled');

                    return Command::SUCCESS;
                }

                $this->projectionManager->resetProjection($projectionClass);
                $io->success("✅ Projection {$projectionClass} has been reset");
            }

        } catch (\Exception $e) {
            $io->error("Reset failed: {$e->getMessage()}");

            return Command::FAILURE;
        }

        return Command::SUCCESS;
    }
}
