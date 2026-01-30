<?php

namespace ComposerAuditGuard;

class JUnitReporter
{
    public function saveReport(array $results, ?string $outputPath = null): string
    {
        $xml = $this->generateJUnitXML($results);
        
        $finalPath = $outputPath ?? './audit-results.xml';
        
        $dir = dirname($finalPath);
        if (!is_dir($dir)) {
            mkdir($dir, 0777, true);
        }
        
        file_put_contents($finalPath, $xml);
        
        return $finalPath;
    }

    private function generateJUnitXML(array $results): string
    {
        $totalTests = max(1, $results['totalCount'] + count($results['ignored']) + count($results['blacklistWarnings']));
        $failures = $results['totalCount'] + count($results['blacklistWarnings']);
        $timestamp = date('c');

        $xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        $xml .= sprintf('<testsuites name="composer-audit-guard" tests="%d" failures="%d" time="0">%s', $totalTests, $failures, "\n");
        $xml .= sprintf('  <testsuite name="Security Audit" tests="%d" failures="%d" timestamp="%s">%s', $totalTests, $failures, $timestamp, "\n");

        foreach (['critical', 'high', 'moderate', 'low'] as $severity) {
            foreach ($results['grouped'][$severity] as $vuln) {
                $xml .= sprintf('    <testcase name="%s" classname="security.%s">%s', $this->escapeXml($vuln['name']), $severity, "\n");
                $xml .= sprintf('      <failure message="%s vulnerability/vulnerabilities found" type="%s">%s', strtoupper($severity), $severity, "\n");
                $xml .= sprintf('        Package: %s%s', $this->escapeXml($vuln['name']), "\n");
                if (isset($vuln['version'])) {
                    $xml .= sprintf('        Installed Version: %s%s', $this->escapeXml($vuln['version']), "\n");
                }
                $xml .= sprintf('        Vulnerability Count: %d%s', $vuln['count'], "\n");
                $xml .= sprintf('        Severity: %s%s', $severity, "\n");
                if (!empty($vuln['titles'])) {
                    $xml .= '        Titles:' . "\n";
                    foreach ($vuln['titles'] as $title) {
                        $xml .= sprintf('          - %s%s', $this->escapeXml($title), "\n");
                    }
                }
                $xml .= '      </failure>' . "\n";
                $xml .= '    </testcase>' . "\n";
            }
        }

        foreach ($results['ignored'] as $pkg) {
            $xml .= sprintf('    <testcase name="%s" classname="security.ignored">%s', $this->escapeXml($pkg['name']), "\n");
            $xml .= sprintf('      <skipped message="Package is blacklisted (severity: %s)" />%s', $pkg['severity'], "\n");
            $xml .= '    </testcase>' . "\n";
        }

        foreach ($results['blacklistWarnings'] as $warning) {
            $pkgName = preg_replace('/^Warning: Blacklisted package "(.+)" not found.*/', '$1', $warning);
            $xml .= sprintf('    <testcase name="%s" classname="security.blacklist.warning">%s', $this->escapeXml($pkgName), "\n");
            $xml .= '      <failure message="Blacklisted package has no security issues" type="blacklist_cleanup_required">' . "\n";
            $xml .= sprintf('        Package: %s%s', $this->escapeXml($pkgName), "\n");
            $xml .= '        Issue: Package is on blacklist but has no security vulnerabilities' . "\n";
            $xml .= '        Action Required: Remove this package from blacklist or verify it\'s still needed' . "\n";
            $xml .= '      </failure>' . "\n";
            $xml .= '    </testcase>' . "\n";
        }

        if ($results['totalCount'] === 0 && count($results['ignored']) === 0 && count($results['blacklistWarnings']) === 0) {
            $xml .= '    <testcase name="No vulnerabilities found" classname="security.clean" time="0">' . "\n";
            $xml .= '    </testcase>' . "\n";
        }

        $xml .= '  </testsuite>' . "\n";
        $xml .= '</testsuites>' . "\n";

        return $xml;
    }

    private function escapeXml(string $text): string
    {
        return htmlspecialchars($text, ENT_XML1 | ENT_QUOTES, 'UTF-8');
    }
}
