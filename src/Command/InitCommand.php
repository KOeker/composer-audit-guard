<?php

namespace ComposerAuditGuard\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class InitCommand extends Command
{
    protected function configure(): void
    {
        $this
            ->setName('init')
            ->setDescription('Create a .auditguardrc.json config file in the current directory');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $configPath = getcwd() . '/.auditguardrc.json';

        if (file_exists($configPath)) {
            $output->writeln('<comment>  .auditguardrc.json already exists in this directory</comment>');
            $output->writeln(sprintf('   Location: %s', $configPath));
            return Command::SUCCESS;
        }

        $defaultConfig = [
            'blacklist' => [],
            'includeDev' => false
        ];

        try {
            file_put_contents(
                $configPath,
                json_encode($defaultConfig, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)
            );
            
            $output->writeln('<info>✓ Created .auditguardrc.json</info>');
            $output->writeln(sprintf('  Location: %s', $configPath));
            $output->writeln("\n<info>  Edit this file to configure your audit settings:</info>");
            $output->writeln('<info>   - Add packages to the blacklist array</info>');
            $output->writeln('<info>   - Set includeDev to true to scan dev dependencies</info>');
            
            return Command::SUCCESS;
        } catch (\Exception $e) {
            $output->writeln(sprintf('<error>Error creating config file: %s</error>', $e->getMessage()));
            return Command::FAILURE;
        }
    }
}
