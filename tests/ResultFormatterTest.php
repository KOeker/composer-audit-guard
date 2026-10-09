<?php

declare(strict_types=1);

use ComposerAuditGuard\ResultFormatter;
use PHPUnit\Framework\TestCase;

final class ResultFormatterTest extends TestCase
{
    public function testBlacklistKeepsOtherAdvisoriesVisible(): void
    {
        $audit = [
            'advisories' => [
                'vendor/ignored' => [['severity' => 'high', 'title' => 'Ignored advisory']],
                'vendor/actionable' => [['severity' => 'critical', 'title' => 'Actionable advisory']],
            ],
            'installedVersions' => [
                'vendor/ignored' => '1.0.0',
                'vendor/actionable' => '2.0.0',
            ],
        ];

        $result = (new ResultFormatter())->formatResults($audit, ['VENDOR/IGNORED']);

        self::assertSame(1, $result['totalCount']);
        self::assertSame('vendor/actionable', $result['grouped']['critical'][0]['name']);
        self::assertSame('vendor/ignored', $result['ignored'][0]['name']);
        self::assertSame([], $result['blacklistWarnings']);
    }
}
