<?php

namespace App\Core\Service\Email;

use App\Core\Contract\UserInterface;
use App\Core\Entity\EmailLog;
use App\Core\Entity\Server;
use App\Core\Enum\EmailTypeEnum;
use App\Core\Enum\ProductPriceTypeEnum;
use App\Core\Enum\SettingEnum;
use App\Core\Repository\EmailLogRepository;
use App\Core\Service\SettingService;

class EmailNotificationService
{
    public function __construct(
        private readonly EmailLogRepository $emailLogRepository,
        private readonly SettingService $settingService,
    ) {}

    public function shouldSendRenewalNotification(
        Server $server,
        \DateTimeInterface $previousExpiresAt,
        \DateTimeInterface $newExpiresAt
    ): bool {
        if (!$this->isRenewalNotificationEnabled()) {
            return false;
        }

        $lastNotification = $this->emailLogRepository->findLastByServerAndType(
            $server,
            EmailTypeEnum::RENEW_PRODUCT
        );

        if (!$this->hasMinimumTimePassed($lastNotification)) {
            return false;
        }

        return $this->isRenewalSignificant($server, $previousExpiresAt, $newExpiresAt);
    }

    public function logEmailSent(
        UserInterface $user,
        EmailTypeEnum $emailType,
        ?Server $server = null,
        ?string $subject = null,
        ?array $metadata = null
    ): void {
        $emailLog = (new EmailLog())
            ->setUser($user)
            ->setEmailType($emailType)
            ->setEmailAddress($user->getEmail())
            ->setServer($server)
            ->setSubject($subject)
            ->setMetadata($metadata);

        $this->emailLogRepository->save($emailLog);
    }

    private function isRenewalNotificationEnabled(): bool
    {
        return (bool) $this->settingService->getSetting(
            SettingEnum::RENEWAL_NOTIFICATION_ENABLED->value
        );
    }

    private function hasMinimumTimePassed(?EmailLog $lastNotification): bool
    {
        if ($lastNotification === null) {
            return true;
        }

        $minPeriodHours = (int) $this->settingService->getSetting(
            SettingEnum::RENEWAL_NOTIFICATION_MIN_PERIOD_HOURS->value
        );

        $minDateTime = new \DateTime();
        $minDateTime->sub(new \DateInterval(sprintf('PT%dH', $minPeriodHours)));

        return $lastNotification->getSentAt() < $minDateTime;
    }

    private function isRenewalSignificant(
        Server $server,
        \DateTimeInterface $previousExpiresAt,
        \DateTimeInterface $newExpiresAt
    ): bool {
        $selectedPrice = $server->getServerProduct()->getSelectedPrice();
        $renewalPeriod = $previousExpiresAt->diff($newExpiresAt);

        if ($selectedPrice->getType() === ProductPriceTypeEnum::ON_DEMAND) {
            $minOnDemandHours = (int) $this->settingService->getSetting(
                SettingEnum::RENEWAL_NOTIFICATION_ON_DEMAND_MIN_HOURS->value
            );

            $totalHours = $renewalPeriod->days * 24 + $renewalPeriod->h;
            return $totalHours >= $minOnDemandHours;
        }

        return true;
    }
}
