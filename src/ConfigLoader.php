<?php

namespace ComposerAuditGuard;

class ConfigLoader
{
    private const CONFIG_FILE = '.auditguardrc.json';

    public function loadConfig(?string $cwd = null): ?array
    {
        $cwd = $cwd ?? getcwd();
        $configPath = $cwd . '/' . self::CONFIG_FILE;

        if (!file_exists($configPath)) {
            return null;
        }

        $content = file_get_contents($configPath);
        
        if ($content === false) {
            throw new \RuntimeException('Error reading config file: ' . $configPath);
        }

        $config = json_decode($content, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \RuntimeException('Error parsing config file: ' . json_last_error_msg());
        }

        return $config;
    }

    public function mergeConfig(?string $cliBlacklist, ?bool $cliIncludeDev, ?array $configFile): array
    {
        $blacklist = [];
        $includeDev = false;

        if ($configFile !== null) {
            if (isset($configFile['blacklist']) && is_array($configFile['blacklist'])) {
                $blacklist = $configFile['blacklist'];
            }
            if (isset($configFile['includeDev'])) {
                $includeDev = (bool) $configFile['includeDev'];
            }
        }

        if ($cliBlacklist !== null) {
            $blacklist = array_map('trim', explode(',', $cliBlacklist));
        }

        if ($cliIncludeDev !== null) {
            $includeDev = $cliIncludeDev;
        }

        return [
            'blacklist' => $blacklist,
            'includeDev' => $includeDev
        ];
    }
}
