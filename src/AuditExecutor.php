<?php

namespace ComposerAuditGuard;

use Symfony\Component\Process\Process;
use Symfony\Component\Process\Exception\ProcessFailedException;

class AuditExecutor
{
    public function runAudit(bool $includeDev = false): array
    {
        try {
            $command = ['composer', 'audit', '--format=json'];
            
            if (!$includeDev) {
                $command[] = '--no-dev';
            }

            $process = new Process($command);
            $process->setTimeout(300); // 5 Minuten Timeout
            $process->run();

            $output = $process->getOutput();
            
            if (empty($output)) {
                throw new \RuntimeException('No output from composer audit command');
            }

            $result = json_decode($output, true);
            
            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new \RuntimeException('Failed to parse composer audit output: ' . json_last_error_msg());
            }

            $result['installedVersions'] = $this->getInstalledVersions();

            return $result;
        } catch (ProcessFailedException $e) {
            $output = $e->getProcess()->getOutput();
            
            if (!empty($output)) {
                $result = json_decode($output, true);
                if (json_last_error() === JSON_ERROR_NONE) {
                    $result['installedVersions'] = $this->getInstalledVersions();
                    return $result;
                }
            }

            throw new \RuntimeException('composer audit failed: ' . $e->getMessage());
        } catch (\Exception $e) {
            if (str_contains($e->getMessage(), 'not found')) {
                throw new \RuntimeException('composer is not installed or not found in PATH');
            }

            if (str_contains($e->getMessage(), 'composer.json')) {
                throw new \RuntimeException('No composer.json found in current directory');
            }

            throw new \RuntimeException('composer audit failed: ' . $e->getMessage());
        }
    }

    private function getInstalledVersions(): array
    {
        $lockFile = getcwd() . '/composer.lock';
        
        if (!file_exists($lockFile)) {
            return [];
        }

        $lockContent = file_get_contents($lockFile);
        if ($lockContent === false) {
            return [];
        }

        $lockData = json_decode($lockContent, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            return [];
        }

        $versions = [];
        
        if (isset($lockData['packages']) && is_array($lockData['packages'])) {
            foreach ($lockData['packages'] as $package) {
                if (isset($package['name']) && isset($package['version'])) {
                    $versions[$package['name']] = $package['version'];
                }
            }
        }

        if (isset($lockData['packages-dev']) && is_array($lockData['packages-dev'])) {
            foreach ($lockData['packages-dev'] as $package) {
                if (isset($package['name']) && isset($package['version'])) {
                    $versions[$package['name']] = $package['version'];
                }
            }
        }

        return $versions;
    }

    public function isValidComposerProject(): bool
    {
        return file_exists(getcwd() . '/composer.json');
    }
}
