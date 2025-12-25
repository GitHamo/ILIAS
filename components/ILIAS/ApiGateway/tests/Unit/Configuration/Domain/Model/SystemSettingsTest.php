<?php

declare(strict_types=1);

namespace Tests\Unit\Configuration\Domain\Model;

use ILIAS\ApiGateway\Configuration\Domain\Enum\SystemSetting;
use ILIAS\ApiGateway\Configuration\Domain\Model\Setting;
use ILIAS\ApiGateway\Configuration\Domain\Model\SystemSettings;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

#[CoversClass(SystemSettings::class)]
final class SystemSettingsTest extends TestCase
{
    public function testFindReturnsSettingWhenKeyExists(): void
    {
        $expected = 'my_client_id';
        $systemSettings = SystemSettings::create([
            SystemSetting::CLIENT_ID->value => $expected,
            SystemSetting::BASE_URL->value => 'https://ilias.example.com'
        ]);

        $actual = $systemSettings->find(SystemSetting::CLIENT_ID);

        self::assertInstanceOf(Setting::class, $actual);
        self::assertSame(SystemSetting::CLIENT_ID->value, $actual->getKey());
        self::assertSame($expected, $actual->asString());
    }

    public function testFindReturnsNullWhenKeyDoesNotExist(): void
    {
        $systemSettings = SystemSettings::create([
            SystemSetting::BASE_URL->value => 'https://ilias.example.com'
        ]);

        $actual = $systemSettings->find(SystemSetting::CLIENT_ID);

        self::assertNull($actual);
    }

    public function testFindReturnsNullWhenSystemSettingsIsEmpty(): void
    {
        $systemSettings = SystemSettings::create([]);

        $actual = $systemSettings->find(SystemSetting::CLIENT_ID);

        self::assertNull($actual);
    }

    /**
     *
     * SystemSettings::create() test cases
     *
     */

    public function testCreationWithValidData(): void
    {
        $settingsData = [
            SystemSetting::CLIENT_ID->value => $clientId = 'test_client',
            SystemSetting::BASE_URL->value => $baseUrl = 'https://ilias.example.com',
        ];

        $systemSettings = SystemSettings::create($settingsData);

        $clientIdSetting = $systemSettings->find(SystemSetting::CLIENT_ID);
        $baseUrlSetting = $systemSettings->find(SystemSetting::BASE_URL);

        self::assertNotNull($clientIdSetting);
        self::assertSame($clientId, $clientIdSetting->asString());
        self::assertNotNull($baseUrlSetting);
        self::assertSame($baseUrl, $baseUrlSetting->asString());
    }

    public function testCreationWithEmptyArray(): void
    {
        $systemSettings = SystemSettings::create([]);

        $clientIdSetting = $systemSettings->find(SystemSetting::CLIENT_ID);
        $baseUrlSetting = $systemSettings->find(SystemSetting::BASE_URL);

        self::assertNull($clientIdSetting);
        self::assertNull($baseUrlSetting);
    }

    public function testCreationIgnoresInvalidKeys(): void
    {
        $settingsData = [
            'invalid_key' => 'some_value',
            SystemSetting::CLIENT_ID->value => 'test_client',
        ];

        $systemSettings = SystemSettings::create($settingsData);
        $actual = $systemSettings->find(SystemSetting::CLIENT_ID);

        self::assertNotNull($actual);
    }

    public function testCreationIgnoresNullValues(): void
    {
        $settingsData = [
            SystemSetting::CLIENT_ID->value => null,
            SystemSetting::BASE_URL->value => $baseUrl = 'https://ilias.example.com',
        ];

        $systemSettings = SystemSettings::create($settingsData);

        $clientIdSetting = $systemSettings->find(SystemSetting::CLIENT_ID);
        $baseUrlSetting = $systemSettings->find(SystemSetting::BASE_URL);

        self::assertNull($clientIdSetting);
        self::assertNotNull($baseUrlSetting);
        self::assertSame($baseUrl, $baseUrlSetting->asString());
    }

    #[DataProvider('valuesForCastingDataProvider')]
    public function testCreationCastsValuesToString(mixed $value, string $expectedString): void
    {
        $settingsData = [
            SystemSetting::CLIENT_ID->value => $value,
        ];

        $systemSettings = SystemSettings::create($settingsData);
        $actual = $systemSettings->find(SystemSetting::CLIENT_ID);

        self::assertNotNull($actual);
        self::assertSame($expectedString, $actual->asString());
    }

    public function testCreationWithMixedData(): void
    {
        $settingsData = [
            SystemSetting::CLIENT_ID->value => $clientId = 'test_client',
            'invalid_key' => 'some_value',
            SystemSetting::BASE_URL->value => null,
        ];

        $systemSettings = SystemSettings::create($settingsData);
        $clientIdSetting = $systemSettings->find(SystemSetting::CLIENT_ID);
        $baseUrlSetting = $systemSettings->find(SystemSetting::BASE_URL);

        self::assertNotNull($clientIdSetting);
        self::assertSame($clientId, $clientIdSetting->asString());
        self::assertNull($baseUrlSetting);
    }

    /**
     * @return array<string, array{mixed, string}>
     */
    public static function valuesForCastingDataProvider(): array
    {
        return [
            'Integer' => [123, '123'],
            'Float' => [1.23, '1.23'],
            'Boolean true' => [true, '1'],
            'Boolean false' => [false, ''],
        ];
    }
}
