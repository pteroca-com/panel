<?php

namespace App\Core\Security;

use App\Core\Enum\ServerPermissionEnum;

class ServerPermissionChecker
{
    /**
     * @var ServerPermissionEnum[]
     */
    private array $permissions = [];

    /**
     * Tworzy nowy obiekt sprawdzający uprawnienia na podstawie tablicy stringów uprawnień
     */
    public function __construct(array $permissionsArray = [])
    {
        $this->permissions = ServerPermissionEnum::fromArray($permissionsArray);
    }

    /**
     * Sprawdza czy użytkownik ma określone uprawnienie
     */
    public function hasPermission(ServerPermissionEnum $permission): bool
    {
        return in_array($permission, $this->permissions, true);
    }

    /**
     * Sprawdza czy użytkownik ma wszystkie z podanych uprawnień
     * 
     * @param ServerPermissionEnum[] $permissions
     */
    public function hasAllPermissions(array $permissions): bool
    {
        foreach ($permissions as $permission) {
            if (!$this->hasPermission($permission)) {
                return false;
            }
        }
        
        return true;
    }

    /**
     * Sprawdza czy użytkownik ma którekolwiek z podanych uprawnień
     * 
     * @param ServerPermissionEnum[] $permissions
     */
    public function hasAnyPermission(array $permissions): bool
    {
        foreach ($permissions as $permission) {
            if ($this->hasPermission($permission)) {
                return true;
            }
        }
        
        return false;
    }

    /**
     * Sprawdza czy użytkownik ma uprawnienia do konsoli
     */
    public function canAccessConsole(): bool
    {
        return $this->hasPermission(ServerPermissionEnum::CONTROL_CONSOLE);
    }

    /**
     * Sprawdza czy użytkownik ma uprawnienia do zarządzania serwerem (start/stop/restart)
     */
    public function canManageServer(): bool
    {
        return $this->hasAnyPermission([
            ServerPermissionEnum::CONTROL_START,
            ServerPermissionEnum::CONTROL_STOP,
            ServerPermissionEnum::CONTROL_RESTART
        ]);
    }

    /**
     * Sprawdza czy użytkownik ma uprawnienia do zarządzania plikami
     */
    public function canManageFiles(): bool
    {
        return $this->hasAnyPermission([
            ServerPermissionEnum::FILE_CREATE,
            ServerPermissionEnum::FILE_READ,
            ServerPermissionEnum::FILE_READ_CONTENT,
            ServerPermissionEnum::FILE_UPDATE,
            ServerPermissionEnum::FILE_DELETE,
            ServerPermissionEnum::FILE_ARCHIVE,
            ServerPermissionEnum::FILE_SFTP
        ]);
    }

    /**
     * Sprawdza czy użytkownik ma uprawnienia do zarządzania backupami
     */
    public function canManageBackups(): bool
    {
        return $this->hasAnyPermission([
            ServerPermissionEnum::BACKUP_CREATE,
            ServerPermissionEnum::BACKUP_READ,
            ServerPermissionEnum::BACKUP_DELETE,
            ServerPermissionEnum::BACKUP_DOWNLOAD,
            ServerPermissionEnum::BACKUP_RESTORE
        ]);
    }

    /**
     * Sprawdza czy użytkownik ma uprawnienia do zarządzania bazami danych
     */
    public function canManageDatabases(): bool
    {
        return $this->hasAnyPermission([
            ServerPermissionEnum::DATABASE_CREATE,
            ServerPermissionEnum::DATABASE_READ,
            ServerPermissionEnum::DATABASE_UPDATE,
            ServerPermissionEnum::DATABASE_DELETE,
            ServerPermissionEnum::DATABASE_VIEW_PASSWORD
        ]);
    }

    /**
     * Sprawdza czy użytkownik ma uprawnienia do zarządzania użytkownikami
     */
    public function canManageUsers(): bool
    {
        return $this->hasAnyPermission([
            ServerPermissionEnum::USER_CREATE,
            ServerPermissionEnum::USER_READ,
            ServerPermissionEnum::USER_UPDATE,
            ServerPermissionEnum::USER_DELETE
        ]);
    }

    /**
     * Sprawdza czy użytkownik ma uprawnienia do zarządzania alokacjami
     */
    public function canManageAllocations(): bool
    {
        return $this->hasAnyPermission([
            ServerPermissionEnum::ALLOCATION_READ,
            ServerPermissionEnum::ALLOCATION_CREATE,
            ServerPermissionEnum::ALLOCATION_UPDATE,
            ServerPermissionEnum::ALLOCATION_DELETE
        ]);
    }

    /**
     * Sprawdza czy użytkownik ma uprawnienia do zarządzania ustawieniami startowymi
     */
    public function canManageStartup(): bool
    {
        return $this->hasAnyPermission([
            ServerPermissionEnum::STARTUP_READ,
            ServerPermissionEnum::STARTUP_UPDATE,
            ServerPermissionEnum::STARTUP_DOCKER_IMAGE
        ]);
    }

    /**
     * Sprawdza czy użytkownik ma uprawnienia do zarządzania harmonogramami
     */
    public function canManageSchedules(): bool
    {
        return $this->hasAnyPermission([
            ServerPermissionEnum::SCHEDULE_CREATE,
            ServerPermissionEnum::SCHEDULE_READ,
            ServerPermissionEnum::SCHEDULE_UPDATE,
            ServerPermissionEnum::SCHEDULE_DELETE
        ]);
    }

    /**
     * Sprawdza czy użytkownik ma uprawnienia do zarządzania ustawieniami serwera
     */
    public function canManageSettings(): bool
    {
        return $this->hasAnyPermission([
            ServerPermissionEnum::SETTINGS_RENAME,
            ServerPermissionEnum::SETTINGS_REINSTALL
        ]);
    }

    /**
     * Sprawdza czy użytkownik ma uprawnienia do przeglądania aktywności
     */
    public function canViewActivity(): bool
    {
        return $this->hasPermission(ServerPermissionEnum::ACTIVITY_READ);
    }

    /**
     * Zwraca wszystkie uprawnienia użytkownika
     * 
     * @return ServerPermissionEnum[]
     */
    public function getPermissions(): array
    {
        return $this->permissions;
    }
}
