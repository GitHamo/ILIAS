<?php

/**
 * This file is part of ILIAS, a powerful learning management system
 * published by ILIAS open source e-Learning e.V.
 *
 * ILIAS is licensed with the GPL-3.0,
 * see https://www.gnu.org/licenses/gpl-3.0.en.html
 * You should have received a copy of said license along with the
 * source code, too.
 *
 * If this is not the case or you just want to try ILIAS, you'll find
 * us at:
 * https://www.ilias.de
 * https://github.com/ILIAS-eLearning
 *
 *********************************************************************/

declare(strict_types=1);

use ILIAS\ApiGateway\RateLimiting\Domain\RateLimiting;
use ILIAS\ApiGateway\RateLimiting\Infrastructure\DatabaseRateLimitRepository;
use ILIAS\ApiGateway\RateLimiting\Infrastructure\RateLimitingService;
use ILIAS\Cron\CronJob;
use ILIAS\Cron\Job\JobResult;
use ILIAS\Cron\Job\Schedule\JobScheduleType;

class ilApiGatewayCronDeleteExpiredRateLimits extends CronJob
{
    private const int EXPIRY_WINDOW_IN_SECONDS = 60; // 1 minute
    private const string MODULE_PREFIX = ilObjApiGateway::TYPE;
    private const string PREFIX = self::MODULE_PREFIX . '_';
    private const string PREFIX_CRON = 'cron_';
    private const string CRON_JOB_NAME = 'delete_expired_rate_limits';

    protected ilLanguage $lng;
    protected RateLimiting $rate_limiting_service;

    public function __construct()
    {
        global $DIC;

        $this->lng = $DIC->language();
        $this->lng->loadLanguageModule(self::MODULE_PREFIX);
        $this->rate_limiting_service = new RateLimitingService(new DatabaseRateLimitRepository());
    }

    public function getId(): string
    {
        return self::PREFIX . self::CRON_JOB_NAME;
    }

    public function getTitle(): string
    {
        return $this->lng->txt(self::PREFIX_CRON . self::CRON_JOB_NAME . '_title');
    }

    public function getDescription(): string
    {
        return $this->lng->txt(self::PREFIX_CRON . self::CRON_JOB_NAME . '_desc');
    }

    public function hasAutoActivation(): bool
    {
        return true;
    }

    public function hasFlexibleSchedule(): bool
    {
        return true;
    }

    #[Override]
    public function getValidScheduleTypes(): array
    {
        return [
            JobScheduleType::WEEKLY,
            JobScheduleType::MONTHLY,
            JobScheduleType::QUARTERLY,
            JobScheduleType::YEARLY,
        ];
    }

    public function getDefaultScheduleType(): JobScheduleType
    {
        return JobScheduleType::QUARTERLY;
    }

    public function getDefaultScheduleValue(): ?int
    {
        return 1;
    }

    #[Override]
    public function isManuallyExecutable(): bool
    {
        return true;
    }

    public function run(): JobResult
    {
        $result = new JobResult();

        // calculate the cutoff as a DateTimeImmutable from x seconds ago
        $cutoff = new DateTimeImmutable(sprintf('-%d seconds', self::EXPIRY_WINDOW_IN_SECONDS));
        $num_destroyed_limits = $this->rate_limiting_service->purgeExpired($cutoff);

        $result->setStatus(JobResult::STATUS_OK);
        $result->setMessage("Number of destroyed rate limits: $num_destroyed_limits");

        return $result;
    }
}
