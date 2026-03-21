<?php

namespace App\Core\Service\Server;

use App\Core\Entity\Server;
use App\Core\Enum\ServerHealthStatusEnum;
use DateTime;

class ServerHealthStatusService
{
    public function checkServerHealth(Server $server): ServerHealthStatusEnum
    {
        if ($server->getDeletedAt() !== null) {
            return ServerHealthStatusEnum::DELETED;
        }

        $now = new DateTime();
        $expiresAt = $server->getExpiresAt();
        $isExpired = $expiresAt < $now;
        $isSuspended = $server->getIsSuspended();

        // Expired but cron hasn't suspended it yet — most critical inconsistency
        if ($isExpired && !$isSuspended) {
            return ServerHealthStatusEnum::EXPIRED_NOT_SUSPENDED;
        }

        // Expired and properly suspended by cron — expected post-expiry state
        if ($isExpired) {
            return ServerHealthStatusEnum::EXPIRED;
        }

        // Not expired but suspended — manual admin action
        if ($isSuspended) {
            return ServerHealthStatusEnum::SUSPENDED;
        }

        $criticalThreshold = (new DateTime())->modify('+3 days');
        if ($expiresAt < $criticalThreshold) {
            return ServerHealthStatusEnum::EXPIRING_CRITICAL;
        }

        $soonThreshold = (new DateTime())->modify('+7 days');
        if ($expiresAt < $soonThreshold && !$server->isAutoRenewal()) {
            return ServerHealthStatusEnum::EXPIRING_SOON;
        }

        return ServerHealthStatusEnum::HEALTHY;
    }
}
