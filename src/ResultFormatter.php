<?php

namespace ComposerAuditGuard;

class ResultFormatter
{
    public function formatResults(array $auditResult, array $blacklist): array
    {
        $grouped = [
            'critical' => [],
            'high' => [],
            'moderate' => [],
            'low' => []
        ];
        $ignored = [];
        $blacklistWarnings = [];
        
        $blacklistSet = array_map('strtolower', $blacklist);
        $foundPackages = [];
        $installedVersions = $auditResult['installedVersions'] ?? [];

        if (isset($auditResult['advisories']) && is_array($auditResult['advisories'])) {
            foreach ($auditResult['advisories'] as $packageName => $advisories) {
                $packageVulns = [];
                
                foreach ($advisories as $advisory) {
                    $severity = strtolower($advisory['severity'] ?? 'moderate');
                    if ($severity === 'medium') {
                        $severity = 'moderate';
                    }
                    
                    $title = $advisory['title'] ?? 'Unknown vulnerability';
                    
                    if (!isset($packageVulns[$severity])) {
                        $packageVulns[$severity] = [
                            'count' => 0,
                            'titles' => []
                        ];
                    }
                    $packageVulns[$severity]['count']++;
                    $packageVulns[$severity]['titles'][] = $title;
                }
                
                $foundPackages[] = strtolower($packageName);
                $isBlacklisted = in_array(strtolower($packageName), $blacklistSet, true);
                
                $installedVersion = $installedVersions[$packageName] ?? null;

                foreach ($packageVulns as $severity => $vulnData) {
                    if ($isBlacklisted) {
                        $ignored[] = [
                            'name' => $packageName,
                            'severity' => $severity,
                            'count' => $vulnData['count'],
                            'version' => $installedVersion
                        ];
                    } else {
                        $vulnEntry = [
                            'name' => $packageName,
                            'count' => $vulnData['count'],
                            'titles' => $vulnData['titles'],
                            'version' => $installedVersion
                        ];

                        if (isset($grouped[$severity])) {
                            $grouped[$severity][] = $vulnEntry;
                        } else {
                            $grouped['moderate'][] = $vulnEntry;
                        }
                    }
                }
            }
        }

        $foundPackagesLower = array_unique(array_map('strtolower', $foundPackages));
        foreach ($blacklist as $blacklistedPkg) {
            if (!in_array(strtolower($blacklistedPkg), $foundPackagesLower, true)) {
                $blacklistWarnings[] = sprintf(
                    'Warning: Blacklisted package "%s" not found in audit results',
                    $blacklistedPkg
                );
            }
        }

        $totalCount = count($grouped['critical']) + 
                      count($grouped['high']) + 
                      count($grouped['moderate']) + 
                      count($grouped['low']);

        return [
            'grouped' => $grouped,
            'ignored' => $ignored,
            'blacklistWarnings' => $blacklistWarnings,
            'totalCount' => $totalCount
        ];
    }

    public function printResults(array $results): void
    {
        echo "\n" . $this->bold("=== Security Audit Results ===") . "\n\n";

        $this->printSeverityGroup('Critical', $results['grouped']['critical'], 'red');
        $this->printSeverityGroup('High', $results['grouped']['high'], 'yellow');
        $this->printSeverityGroup('Moderate', $results['grouped']['moderate'], 'blue');
        $this->printSeverityGroup('Low', $results['grouped']['low'], 'gray');

        if (count($results['ignored']) > 0) {
            echo "\n" . $this->color('Ignored (Blacklisted):', 'cyan', true) . "\n";
            foreach ($results['ignored'] as $pkg) {
                echo $this->color(sprintf("  - %s (%s)", $pkg['name'], $pkg['severity']), 'cyan') . "\n";
            }
        }

        if (count($results['blacklistWarnings']) > 0) {
            echo "\n";
            foreach ($results['blacklistWarnings'] as $warning) {
                echo $this->color($warning, 'yellow') . "\n";
            }
        }

        echo "\n" . $this->bold("=== Summary ===") . "\n";
        $countColor = $results['totalCount'] > 0 ? 'red' : 'green';
        echo sprintf(
            "Total vulnerabilities found: %s\n",
            $this->color((string)$results['totalCount'], $countColor)
        );
        echo sprintf("Ignored (blacklisted): %d\n", count($results['ignored']));

        if ($results['totalCount'] === 0) {
            echo "\n" . $this->color('✓ No vulnerabilities found!', 'green') . "\n";
        } else {
            $plural = $results['totalCount'] === 1 ? 'y' : 'ies';
            $verb = $results['totalCount'] === 1 ? 's' : '';
            echo "\n" . $this->color(
                sprintf('✗ %d vulnerabilit%s require%s attention!', $results['totalCount'], $plural, $verb),
                'red'
            ) . "\n";
        }
    }

    private function printSeverityGroup(string $severityLabel, array $vulnerabilities, string $color): void
    {
        if (count($vulnerabilities) > 0) {
            echo "\n" . $this->color($severityLabel . ':', $color, true) . "\n";
            foreach ($vulnerabilities as $vuln) {
                $countStr = $vuln['count'] > 1 ? sprintf(" (%d vulnerabilities)", $vuln['count']) : '';
                $versionStr = isset($vuln['version']) ? sprintf(" [installed: %s]", $vuln['version']) : '';
                echo $this->color(sprintf("  - %s%s%s", $vuln['name'], $countStr, $versionStr), $color) . "\n";
            }
        }
    }

    private function color(string $text, string $color, bool $bold = false): string
    {
        $colors = [
            'red' => '31',
            'green' => '32',
            'yellow' => '33',
            'blue' => '34',
            'cyan' => '36',
            'gray' => '90'
        ];

        $colorCode = $colors[$color] ?? '37';
        $style = $bold ? '1;' : '';
        
        return "\033[{$style}{$colorCode}m{$text}\033[0m";
    }

    private function bold(string $text): string
    {
        return "\033[1m{$text}\033[0m";
    }
}
