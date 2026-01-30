<?php

namespace ComposerAuditGuard\Command;

use ComposerAuditGuard\AuditExecutor;
use ComposerAuditGuard\ConfigLoader;
use ComposerAuditGuard\JUnitReporter;
use ComposerAuditGuard\ResultFormatter;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Helper\ProgressBar;

class AuditGuardCommand extends Command
{
    private AuditExecutor $auditExecutor;
    private ResultFormatter $formatter;
    private ConfigLoader $configLoader;
    private JUnitReporter $junitReporter;

    public function __construct()
    {
        parent::__construct();
        $this->auditExecutor = new AuditExecutor();
        $this->formatter = new ResultFormatter();
        $this->configLoader = new ConfigLoader();
        $this->junitReporter = new JUnitReporter();
    }

    protected function configure(): void
    {
        $this
            ->setName('audit')
            ->setDescription('Run security audit on composer dependencies')
            ->addOption(
                'dev',
                null,
                InputOption::VALUE_NONE,
                'Include dev dependencies in scan'
            )
            ->addOption(
                'blacklist',
                null,
                InputOption::VALUE_REQUIRED,
                'Comma-separated list of packages to ignore'
            )
            ->addOption(
                'junit',
                null,
                InputOption::VALUE_NONE,
                'Generate JUnit XML report for CI/CD integration'
            )
            ->addOption(
                'output',
                null,
                InputOption::VALUE_REQUIRED,
                'Path for JUnit XML output (default: ./audit-results.xml)'
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        try {
            if (!$this->auditExecutor->isValidComposerProject()) {
                $output->writeln('<error>Error: No composer.json found in current directory</error>');
                return Command::FAILURE;
            }

            $configFile = $this->configLoader->loadConfig();
            $mergedConfig = $this->configLoader->mergeConfig(
                $input->getOption('blacklist'),
                $input->getOption('dev'),
                $configFile
            );

            $blacklist = $mergedConfig['blacklist'];
            $includeDev = $mergedConfig['includeDev'];

            $progressBar = new ProgressBar($output, 100);
            $progressBar->setFormat('Running security audit... [%bar%] %percent:3s%%');
            $progressBar->start();

            for ($i = 0; $i < 90; $i += 10) {
                $progressBar->advance(10);
                usleep(100000); // 0.1 seconds
            }

            $auditResult = $this->auditExecutor->runAudit($includeDev);

            $progressBar->setProgress(100);
            $progressBar->finish();
            $progressBar->clear();
            
            $output->write("\r\033[K");

            $results = $this->formatter->formatResults($auditResult, $blacklist);
            $this->formatter->printResults($results);

            if ($input->getOption('junit')) {
                $outputPath = $this->junitReporter->saveReport(
                    $results,
                    $input->getOption('output')
                );
                $output->writeln(sprintf("\n<info>JUnit XML report saved: %s</info>", $outputPath));
            }

            return $results['totalCount'] > 0 ? Command::FAILURE : Command::SUCCESS;

        } catch (\Exception $e) {
            $output->writeln(sprintf("\n<error>Error: %s</error>", $e->getMessage()));
            return 2;
        }
    }
}
