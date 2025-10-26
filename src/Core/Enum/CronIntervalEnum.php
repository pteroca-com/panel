<?php

namespace App\Core\Enum;

/**
 * Enum representing cron job execution intervals
 */
enum CronIntervalEnum: string
{
    case EVERY_MINUTE = 'every_minute';
    case EVERY_5_MINUTES = 'every_5_minutes';
    case EVERY_15_MINUTES = 'every_15_minutes';
    case HOURLY = 'hourly';
    case DAILY = 'daily';

    /**
     * Check if the current time matches the interval
     */
    public function shouldExecuteNow(): bool
    {
        $now = new \DateTime();
        $minute = (int) $now->format('i');
        $hour = (int) $now->format('H');

        return match ($this) {
            self::EVERY_MINUTE => true,
            self::EVERY_5_MINUTES => $minute % 5 === 0,
            self::EVERY_15_MINUTES => $minute % 15 === 0,
            self::HOURLY => $minute === 0,
            self::DAILY => $minute === 0 && $hour === 0,
        };
    }

    /**
     * Get human-readable description of the interval
     */
    public function getDescription(): string
    {
        return match ($this) {
            self::EVERY_MINUTE => 'Every minute',
            self::EVERY_5_MINUTES => 'Every 5 minutes',
            self::EVERY_15_MINUTES => 'Every 15 minutes',
            self::HOURLY => 'Hourly (at :00)',
            self::DAILY => 'Daily (at 00:00)',
        };
    }
}
