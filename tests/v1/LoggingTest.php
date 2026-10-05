<?php

namespace PahappaLimited\CommsSDK\Tests\v1;

use PahappaLimited\CommsSDK\v1\CommsSDK;
use PahappaLimited\CommsSDK\v1\utils\NumberValidator;
use PHPUnit\Framework\TestCase;
use Psr\Log\AbstractLogger;

class LoggingTest extends TestCase
{
    protected function tearDown(): void
    {
        CommsSDK::setLogger(null);
    }

    public function testMessagesGoToTheConfiguredLogger(): void
    {
        $logger = new class extends AbstractLogger {
            public array $records = [];

            public function log($level, $message, array $context = []): void
            {
                $this->records[] = [$level, strtr($message, ['{number}' => $context['number'] ?? ''])];
            }
        };
        CommsSDK::setLogger($logger);

        NumberValidator::validateNumbers(['123', '']);

        $this->assertContains(['error', 'Number (123) is not valid!'], $logger->records);
        $this->assertContains(['warning', 'Number () cannot be null or empty!'], $logger->records);
    }

    public function testNothingIsPrintedWithoutALogger(): void
    {
        $this->expectOutputString('');
        NumberValidator::validateNumbers(['123']);
    }
}
