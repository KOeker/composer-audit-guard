<?php

namespace ComposerAuditGuard;

use ComposerAuditGuard\Command\AuditGuardCommand;
use ComposerAuditGuard\Command\InitCommand;
use Exception;
use Symfony\Component\Console\Application;

/**
 * Main CLI application wrapper
 * Used in: bin/composer-audit-guard
 */
class AuditGuardCLI
{
    /**
     * @throws Exception
     */
    public function run(): int
    {
        $version = $this->getVersion();
        $application = new Application('composer-audit-guard', $version);
        
        $auditCommand = new AuditGuardCommand();
        $initCommand = new InitCommand();
        
        $application->add($auditCommand);
        $application->add($initCommand);
        $application->setDefaultCommand('audit', false);

        return $application->run();
    }

    private function getVersion(): string
    {
        $composerJsonPaths = [
            __DIR__ . '/../composer.json',
            __DIR__ . '/../../../../composer.json'
        ];

        foreach ($composerJsonPaths as $path) {
            if (file_exists($path)) {
                $content = file_get_contents($path);
                if ($content !== false) {
                    $data = json_decode($content, true);
                    if (isset($data['version'])) {
                        return $data['version'];
                    }
                }
            }
        }

        return 'dev-main';
    }
}
