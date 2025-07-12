<?php

namespace App\Core\Enum;

enum ServerPermissionEnum: string
{
    case CONTROL_CONSOLE = 'control.console';
    case CONTROL_START = 'control.start';
    case CONTROL_STOP = 'control.stop';
    case CONTROL_RESTART = 'control.restart';
    
    case USER_READ = 'user.read';
    case USER_CREATE = 'user.create';
    case USER_UPDATE = 'user.update';
    case USER_DELETE = 'user.delete';

    case ACTIVITY_READ = 'activity.read';
    
    case FILE_CREATE = 'file.create';
    case FILE_READ = 'file.read';
    case FILE_READ_CONTENT = 'file.read-content';
    case FILE_UPDATE = 'file.update';
    case FILE_DELETE = 'file.delete';
    case FILE_ARCHIVE = 'file.archive';
    case FILE_SFTP = 'file.sftp';
    
    case BACKUP_CREATE = 'backup.create';
    case BACKUP_READ = 'backup.read';
    case BACKUP_DELETE = 'backup.delete';
    case BACKUP_DOWNLOAD = 'backup.download';
    case BACKUP_RESTORE = 'backup.restore';
    
    case ALLOCATION_READ = 'allocation.read';
    case ALLOCATION_CREATE = 'allocation.create';
    case ALLOCATION_UPDATE = 'allocation.update';
    case ALLOCATION_DELETE = 'allocation.delete';
    
    case STARTUP_READ = 'startup.read';
    case STARTUP_UPDATE = 'startup.update';
    case STARTUP_DOCKER_IMAGE = 'startup.docker-image';
    
    case DATABASE_CREATE = 'database.create';
    case DATABASE_READ = 'database.read';
    case DATABASE_UPDATE = 'database.update';
    case DATABASE_DELETE = 'database.delete';
    case DATABASE_VIEW_PASSWORD = 'database.view_password';
    
    case SCHEDULE_CREATE = 'schedule.create';
    case SCHEDULE_READ = 'schedule.read';
    case SCHEDULE_UPDATE = 'schedule.update';
    case SCHEDULE_DELETE = 'schedule.delete';
    
    case SETTINGS_RENAME = 'settings.rename';
    case SETTINGS_REINSTALL = 'settings.reinstall';
    
    case WEBSOCKET_CONNECT = 'websocket.connect';
    
    public static function getPermissionGroups(): array
    {
        return [
            'control' => [
                self::CONTROL_CONSOLE,
                self::CONTROL_START,
                self::CONTROL_STOP,
                self::CONTROL_RESTART,
            ],
            'user' => [
                self::USER_CREATE,
                self::USER_READ,
                self::USER_UPDATE,
                self::USER_DELETE,
            ],
            'file' => [
                self::FILE_CREATE,
                self::FILE_READ,
                self::FILE_READ_CONTENT,
                self::FILE_UPDATE,
                self::FILE_DELETE,
                self::FILE_ARCHIVE,
                self::FILE_SFTP,
            ],
            'backup' => [
                self::BACKUP_CREATE,
                self::BACKUP_READ,
                self::BACKUP_DELETE,
                self::BACKUP_DOWNLOAD,
                self::BACKUP_RESTORE,
            ],
            'allocation' => [
                self::ALLOCATION_READ,
                self::ALLOCATION_CREATE,
                self::ALLOCATION_UPDATE,
                self::ALLOCATION_DELETE,
            ],
            'startup' => [
                self::STARTUP_READ,
                self::STARTUP_UPDATE,
                self::STARTUP_DOCKER_IMAGE,
            ],
            'database' => [
                self::DATABASE_CREATE,
                self::DATABASE_READ,
                self::DATABASE_UPDATE,
                self::DATABASE_DELETE,
                self::DATABASE_VIEW_PASSWORD,
            ],
            'schedule' => [
                self::SCHEDULE_CREATE,
                self::SCHEDULE_READ,
                self::SCHEDULE_UPDATE,
                self::SCHEDULE_DELETE,
            ],
            'settings' => [
                self::SETTINGS_RENAME,
                self::SETTINGS_REINSTALL,
            ],
            'activity' => [
                self::ACTIVITY_READ,
            ],
            'websocket' => [
                self::WEBSOCKET_CONNECT,
            ],
        ];
    }
    
    public static function fromArray(array $permissions): \App\Core\DTO\Collection\ServerPermissionCollection
    {
        $result = [];
        
        foreach ($permissions as $permission) {
            try {
                $result[] = self::from($permission);
            } catch (\ValueError $e) {
                // Ignore invalid permissions
            }
        }
        
        return new \App\Core\DTO\Collection\ServerPermissionCollection($result);
    }
}
