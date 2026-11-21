<?php

declare(strict_types=1);

namespace Thuraaung\NrcKit\Tests;

use PHPUnit\Framework\TestCase;

final class DocumentationTest extends TestCase
{
    public function test_installation_command_matches_overview(): void
    {
        $pattern = '/<!-- installation-command:start -->\s*```[^\n]*\n([\s\S]*?)```\s*<!-- installation-command:end -->/';
        $overview = file_get_contents(__DIR__ . '/../README.md');
        $installation = file_get_contents(__DIR__ . '/../docs/installation.md');

        $this->assertNotFalse($overview);
        $this->assertNotFalse($installation);
        $this->assertSame(1, preg_match($pattern, $overview, $overviewMatch));
        $this->assertSame(1, preg_match($pattern, $installation, $installationMatch));
        $this->assertNotSame('', trim($overviewMatch[1]));
        $this->assertSame(trim($overviewMatch[1]), trim($installationMatch[1]));
    }
}
