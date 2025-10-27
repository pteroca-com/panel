# Event-Driven Architecture - TODO Lista

**Data ostatniej aktualizacji:** 2025-10-26
**Status:** Analiza brakujących implementacji EDA w projekcie PteroCA

---

## Spis Treści

1. [Status Obecnej Implementacji](#status-obecnej-implementacji)
2. [Brakujące Implementacje - Warstwa API](#brakujące-implementacje---warstwa-api)
3. [Brakujące Implementacje - Panel Admina](#brakujące-implementacje---panel-admina)
4. [Brakujące Implementacje - Warstwa CLI](#brakujące-implementacje---warstwa-cli)
5. [Brakujące Implementacje - Inne Kontrolery](#brakujące-implementacje---inne-kontrolery)
6. [Priorytetyzacja Implementacji](#priorytetyzacja-implementacji)
7. [Rekomendacje Implementacyjne](#rekomendacje-implementacyjne)

---

## Status Obecnej Implementacji

### ✅ JUŻ ZAIMPLEMENTOWANE

Zgodnie z dokumentacją [EVENT_DRIVEN_ARCHITECTURE.md](./EVENT_DRIVEN_ARCHITECTURE.md), następujące obszary **mają już eventy EDA**:

1. **Rejestracja użytkownika** (`RegistrationController`)
   - UserRegistrationRequestedEvent
   - UserRegistrationValidatedEvent
   - UserAboutToBeCreatedEvent
   - UserCreatedEvent
   - UserRegisteredEvent
   - UserEmailVerificationRequestedEvent
   - UserEmailVerifiedEvent
   - UserRegistrationFailedEvent
   - EmailVerificationResendRequestedEvent
   - EmailVerificationResentEvent

2. **Logowanie/Wylogowanie** (`AuthorizationController`)
   - UserLoginRequestedEvent
   - UserLoginAttemptedEvent
   - UserLoginValidatedEvent
   - UserAuthenticationSuccessfulEvent
   - UserLoggedInEvent
   - UserAuthenticationFailedEvent
   - UserLogoutRequestedEvent
   - UserLoggedOutEvent

3. **Dashboard** (`DashboardController`)
   - DashboardAccessedEvent
   - DashboardDataLoadedEvent

4. **Lista serwerów użytkownika** (`ServerController` - `/servers`)
   - ServersListAccessedEvent
   - ServersListDataLoadedEvent

5. **Store** (`StoreController`)
   - StoreAccessedEvent
   - StoreDataLoadedEvent
   - StoreCategoryAccessedEvent
   - StoreCategoryDataLoadedEvent
   - StoreProductViewedEvent
   - StoreProductDataLoadedEvent

6. **Doładowanie portfela** (`BalanceController`)
   - BalanceRechargePageAccessedEvent
   - BalanceRechargeFormDataLoadedEvent
   - BalancePaymentCallbackAccessedEvent
   - BalancePaymentValidatedEvent
   - BalanceAboutToBeAddedEvent
   - BalanceAddedEvent
   - PaymentFinalizedEvent

7. **Koszyk i zakupy** (`CartController`)
   - CartTopUpPageAccessedEvent
   - CartTopUpDataLoadedEvent
   - CartPaymentRedirectEvent
   - CartConfigurePageAccessedEvent
   - CartConfigureDataLoadedEvent
   - CartBuyRequestedEvent
   - CartRenewPageAccessedEvent
   - CartRenewDataLoadedEvent
   - CartRenewBuyRequestedEvent

8. **Zakup serwera** (`CreateServerService`)
   - ServerPurchaseValidatedEvent
   - ServerAboutToBeCreatedEvent
   - ServerCreatedOnPterodactylEvent
   - ServerEntityCreatedEvent
   - ServerProductCreatedEvent
   - ServerBalanceChargedEvent
   - ServerPurchaseCompletedEvent

9. **Przedłużenie serwera** (`RenewServerService`)
   - ServerRenewalValidatedEvent
   - ServerAboutToBeRenewedEvent
   - ServerExpirationExtendedEvent
   - ServerUnsuspendedEvent
   - ServerRenewalBalanceChargedEvent
   - ServerRenewalCompletedEvent

10. **Strony statyczne** (`PageController`)
    - PageAccessedEvent
    - PageDataLoadedEvent

11. **Resetowanie hasła** (`PasswordRecoveryController`)
    - PasswordResetRequestedEvent
    - PasswordResetTokenGeneratedEvent
    - PasswordResetEmailSentEvent
    - PasswordResetValidatedEvent
    - PasswordAboutToBeChangedEvent
    - PasswordChangedEvent
    - PasswordResetCompletedEvent
    - PasswordResetFailedEvent

12. **SSO** (`SSOLoginRedirectController`)
    - SSORedirectRequestedEvent
    - SSOTokenGeneratedEvent
    - SSORedirectInitiatedEvent
    - SSOFailedEvent

13. **Konto użytkownika** (`UserAccountCrudController`)
    - UserAccountUpdateRequestedEvent
    - PterodactylAccountSyncedEvent
    - UserAccountUpdatedEvent

14. **Płatności użytkownika** (`UserPaymentCrudController`)
    - PaymentContinueRequestedEvent
    - PaymentContinuedEvent
    - PaymentContinueFailedEvent

15. **Eventy generyczne**
    - FormBuildEvent
    - FormSubmitEvent
    - ViewDataEvent

16. **CRUD Panel Admina** (`AbstractPanelController`)
    - **Eventy konfiguracyjne:**
      - CrudConfiguredEvent - konfiguracja CRUD
      - CrudActionsConfiguredEvent - konfiguracja akcji
      - CrudFiltersConfiguredEvent - konfiguracja filtrów
      - CrudFieldsConfiguredEvent - konfiguracja pól
      - CrudIndexQueryBuiltEvent - budowanie query dla listy
    - **Eventy operacji (pre/post pattern):**
      - CrudEntityPersistingEvent (pre, stoppable) - przed CREATE
      - CrudEntityPersistedEvent (post) - po CREATE
      - CrudEntityUpdatingEvent (pre, stoppable) - przed UPDATE
      - CrudEntityUpdatedEvent (post) - po UPDATE
      - CrudEntityDeletingEvent (pre, stoppable) - przed DELETE
      - CrudEntityDeletedEvent (post) - po DELETE

**Kontrolery dziedziczące z `AbstractPanelController` (wszystkie mają eventy CRUD):**
- UserCrudController
- UserAccountCrudController
- ServerCrudController
- ProductCrudController
- VoucherCrudController
- CategoryCrudController
- PaymentCrudController
- LogCrudController
- EmailLogCrudController
- ServerProductCrudController
- ServerLogCrudController
- VoucherUsageCrudController
- AbstractSettingCrudController (i wszystkie Settings CRUD)

**Payload eventów CRUD:**
- `entityFqcn` - pełna nazwa klasy encji (np. `App\Core\Entity\User`)
- `entityInstance` - instancja encji
- `user` - zalogowany użytkownik (admin)
- `context` - ip, userAgent, locale

**Zastosowanie dla pluginów:**
Pluginy mogą subskrybować na eventy generyczne (np. `CrudEntityPersistedEvent`) i filtrować po `entityFqcn`:

```php
class MyPluginCrudSubscriber implements EventSubscriberInterface
{
    public function onCrudEntityPersisted(CrudEntityPersistedEvent $event): void
    {
        // Reaguj tylko na tworzenie użytkowników
        if ($event->getEntityFqcn() === User::class) {
            $user = $event->getEntityInstance();
            $this->sendWelcomeEmail($user);
        }
    }
}
```

**Cechy:**
- ✅ **Generyczne** - działają dla wszystkich encji CRUD
- ✅ **Stoppable pre-events** - pluginy mogą zatrzymać operacje (veto)
- ✅ **Audit trail** - automatyczne logowanie operacji (LogActionEnum)
- ✅ **Context** - pełny kontekst requestu (IP, user agent, locale)
- ✅ **Query modification** - pluginy mogą modyfikować query w `CrudIndexQueryBuiltEvent`
- ✅ **UI customization** - pluginy mogą dodawać pola/filtry/akcje

---

## Brakujące Implementacje - Warstwa API

**Lokalizacja:** `src/Core/Controller/API/`

Cała warstwa API **nie emituje eventów EDA**. To są głównie operacje związane z zarządzaniem serwerem przez użytkownika.

### 1. Server Management API

**Plik:** `src/Core/Controller/API/ServerController.php`

#### Endpointy bez eventów:

| Endpoint | Metoda | Akcja |
|----------|--------|-------|
| `/panel/api/server/{id}/details` | GET | Pobieranie szczegółów serwera |
| `/panel/api/server/{id}/websocket` | GET | Generowanie tokenu websocket |
| `/panel/api/server/{id}/accept-eula` | POST | Akceptacja EULA serwera |

#### Proponowane eventy:

```php
// GET /panel/api/server/{id}/details
- ServerDetailsRequestedEvent (pre)
- ServerDetailsLoadedEvent (post)

// GET /panel/api/server/{id}/websocket
- ServerWebsocketTokenRequestedEvent (pre)
- ServerWebsocketTokenGeneratedEvent (post)

// POST /panel/api/server/{id}/accept-eula
- ServerEulaAcceptanceRequestedEvent (pre, stoppable)
- ServerEulaAcceptedEvent (post-commit)
- ServerEulaAcceptanceFailedEvent (error)
```

#### Zastosowanie dla pluginów:
- **Analytics** - tracking dostępu do API serwera
- **Rate limiting** - ograniczanie częstotliwości requestów
- **Audit trail** - logowanie wszystkich operacji API
- **Security** - monitoring podejrzanych aktywności

---

### ~~2. Server Users API~~ ✅ **ZAIMPLEMENTOWANE** (2025-10-22)

**Plik:** `src/Core/Controller/API/ServerUserController.php`

#### ~~Endpointy bez eventów:~~ ✅ Endpointy z eventami:

| Endpoint | Metoda | Akcja | Status |
|----------|--------|-------|--------|
| `/panel/api/server/{id}/users/all` | GET | Lista subuserów | ✅ Read-only (bez eventów) |
| `/panel/api/server/{id}/users/create` | POST | Tworzenie subusera | ✅ Eventy zaimplementowane |
| `/panel/api/server/{id}/users/{userUuid}` | GET | Szczegóły subusera | ✅ Read-only (bez eventów) |
| `/panel/api/server/{id}/users/{userUuid}/permissions` | POST | Aktualizacja uprawnień | ✅ Eventy zaimplementowane |
| `/panel/api/server/{id}/users/{userUuid}/delete` | DELETE | Usuwanie subusera | ✅ Eventy zaimplementowane |

#### Zaimplementowane eventy:

```php
// POST /panel/api/server/{id}/users/create
✅ ServerSubuserCreationRequestedEvent (pre, stoppable) - src/Core/Event/Server/User/
✅ ServerSubuserCreatedEvent (post-commit) - src/Core/Event/Server/User/
✅ ServerSubuserCreationFailedEvent (error) - src/Core/Event/Server/User/

// POST /panel/api/server/{id}/users/{userUuid}/permissions
✅ ServerSubuserPermissionsUpdateRequestedEvent (pre, stoppable) - src/Core/Event/Server/User/
✅ ServerSubuserPermissionsUpdatedEvent (post-commit) - src/Core/Event/Server/User/

// DELETE /panel/api/server/{id}/users/{userUuid}/delete
✅ ServerSubuserDeletionRequestedEvent (pre, stoppable) - src/Core/Event/Server/User/
✅ ServerSubuserDeletedEvent (post-commit) - src/Core/Event/Server/User/
```

**Lokalizacja:**
- Eventy: `src/Core/Event/Server/User/`
- Logika: `src/Core/Service/Server/ServerUserService.php`
- Kontroler: `src/Core/Controller/API/ServerUserController.php` (thin - wywołuje serwis)

**Payload eventów:**
- **Subuser Creation**: userId, serverId, serverPterodactylIdentifier, subuserEmail, permissions, subuserUuid (dla Created), failureReason (dla Failed), context
- **Permissions Update**: userId, serverId, serverPterodactylIdentifier, subuserEmail, subuserUuid, oldPermissions, newPermissions, context
- **Subuser Deletion**: userId, serverId, serverPterodactylIdentifier, subuserEmail, subuserUuid, context

**Flow dla tworzenia subusera:**
```
POST /panel/api/server/{id}/users/create
  → ServerSubuserCreationRequestedEvent (pre, stoppable) - plugin może zablokować
  → Try-catch block
    → Walidacja użytkownika (istnienie, weryfikacja, duplikat)
    → Pterodactyl API createUser()
    → syncServerSubuser() - zapis do lokalnej bazy
    → ServerLogService.logServerAction()
    → ServerSubuserCreatedEvent (post-commit) - po API call
  → Exception catch:
    → ServerSubuserCreationFailedEvent (error) - w przypadku błędu
```

**Flow dla aktualizacji uprawnień:**
```
POST /panel/api/server/{id}/users/{userUuid}/permissions
  → Pobranie starych uprawnień (getSubuser)
  → ServerSubuserPermissionsUpdateRequestedEvent (pre, stoppable) - plugin może zablokować
  → Pterodactyl API updateUserPermissions()
  → syncServerSubuser() - aktualizacja lokalnej bazy
  → ServerLogService.logServerAction()
  → ServerSubuserPermissionsUpdatedEvent (post-commit) - z oldPermissions i newPermissions
```

**Flow dla usuwania subusera:**
```
DELETE /panel/api/server/{id}/users/{userUuid}/delete
  → ServerSubuserDeletionRequestedEvent (pre, stoppable) - plugin może zablokować
  → Pterodactyl API deleteUser()
  → Usunięcie z lokalnej bazy (ServerSubuserRepository.delete)
  → ServerLogService.logServerAction()
  → ServerSubuserDeletedEvent (post-commit) - po API call
```

#### Zastosowanie dla pluginów:
- **Security notifications** - powiadomienia o dodaniu/usunięciu dostępu ✅
- **Audit trail** - pełna historia zmian uprawnień z old/new values ✅
- **Access control** - dodatkowe walidacje (np. limit subuserów, niebezpieczne uprawnienia) ✅
- **Webhooks** - integracje z zewnętrznymi systemami (Discord, Slack) ✅
- **Quota management** - limit subuserów per serwer ✅

---

### ~~3. Server Backups API~~ ✅ **ZAIMPLEMENTOWANE** (2025-10-22)

**Plik:** `src/Core/Controller/API/ServerBackupController.php`

#### ~~Endpointy bez eventów:~~ ✅ Endpointy z eventami:

| Endpoint | Metoda | Akcja | Status |
|----------|--------|-------|--------|
| `/panel/api/server/{id}/backup/create` | POST | Tworzenie backupu | ✅ Eventy zaimplementowane |
| `/panel/api/server/{id}/backup/{backupId}/download` | GET | Pobieranie backupu | ✅ Eventy zaimplementowane |
| `/panel/api/server/{id}/backup/{backupId}/delete` | DELETE | Usuwanie backupu | ✅ Eventy zaimplementowane |
| `/panel/api/server/{id}/backup/{backupId}/restore` | POST | Przywracanie backupu | ✅ Eventy zaimplementowane |

#### Zaimplementowane eventy:

```php
// POST /panel/api/server/{id}/backup/create
✅ ServerBackupCreationRequestedEvent (pre, stoppable) - src/Core/Event/Server/Backup/
✅ ServerBackupCreatedEvent (post-commit) - src/Core/Event/Server/Backup/
✅ ServerBackupCreationFailedEvent (error) - src/Core/Event/Server/Backup/

// GET /panel/api/server/{id}/backup/{backupId}/download
✅ ServerBackupDownloadRequestedEvent (pre) - src/Core/Event/Server/Backup/
✅ ServerBackupDownloadInitiatedEvent (post) - src/Core/Event/Server/Backup/

// DELETE /panel/api/server/{id}/backup/{backupId}/delete
✅ ServerBackupDeletionRequestedEvent (pre, stoppable) - src/Core/Event/Server/Backup/
✅ ServerBackupDeletedEvent (post-commit) - src/Core/Event/Server/Backup/

// POST /panel/api/server/{id}/backup/{backupId}/restore
✅ ServerBackupRestoreRequestedEvent (pre, stoppable) - src/Core/Event/Server/Backup/
✅ ServerBackupRestoreInitiatedEvent (post) - src/Core/Event/Server/Backup/
✅ ServerBackupRestoredEvent (post-commit) - src/Core/Event/Server/Backup/
✅ ServerBackupRestoreFailedEvent (error) - src/Core/Event/Server/Backup/
```

**Lokalizacja:**
- Eventy: `src/Core/Event/Server/Backup/`
- Logika: `src/Core/Service/Server/ServerBackupService.php`
- Kontroler: `src/Core/Controller/API/ServerBackupController.php` (thin - wywołuje serwisy)

**Payload eventów:**
- **Backup Creation**: userId, serverId, serverPterodactylIdentifier, backupName, ignoredFiles, backupId (dla Created), failureReason (dla Failed), context
- **Backup Download**: userId, serverId, serverPterodactylIdentifier, backupId, downloadUrl (dla Initiated), context
- **Backup Deletion**: userId, serverId, serverPterodactylIdentifier, backupId, context
- **Backup Restore**: userId, serverId, serverPterodactylIdentifier, backupId, truncate, failureReason (dla Failed), context

**Flow dla tworzenia backupu:**
```
POST /panel/api/server/{id}/backup/create
  → ServerBackupCreationRequestedEvent (pre, stoppable) - plugin może zablokować
  → Try-catch block
    → Pterodactyl API createBackup()
    → ServerLogService.logServerAction()
    → ServerBackupCreatedEvent (post-commit) - po API call
  → Exception catch:
    → ServerBackupCreationFailedEvent (error) - w przypadku błędu
```

**Flow dla restore backupu:**
```
POST /panel/api/server/{id}/backup/{backupId}/restore
  → ServerBackupRestoreRequestedEvent (pre, stoppable) - plugin może zablokować
  → ServerBackupRestoreInitiatedEvent (post) - po walidacji, przed API
  → Try-catch block
    → Pterodactyl API restoreBackup()
    → ServerLogService.logServerAction()
    → ServerBackupRestoredEvent (post-commit) - po API call
  → Exception catch:
    → ServerBackupRestoreFailedEvent (error) - w przypadku błędu
```

#### Zastosowanie dla pluginów:
- **Quota management** - limit backupów per serwer ✅
- **Billing** - płatność za dodatkowe backupy ✅
- **Notifications** - powiadomienia o zakończeniu backupu/restore ✅
- **Monitoring** - tracking użycia przestrzeni backupów ✅
- **Security** - audit trail dla krytycznych operacji restore ✅

---

### ~~4. Server Databases API~~ ✅ **ZAIMPLEMENTOWANE** (2025-10-22)

**Plik:** `src/Core/Controller/API/ServerDatabaseController.php`

#### ~~Endpointy bez eventów:~~ ✅ Endpointy z eventami:

| Endpoint | Metoda | Akcja | Status |
|----------|--------|-------|--------|
| `/panel/api/server/{id}/database/all` | GET | Lista baz danych | ✅ Read-only (bez eventów) |
| `/panel/api/server/{id}/database/create` | POST | Tworzenie bazy danych | ✅ Eventy zaimplementowane |
| `/panel/api/server/{id}/database/{databaseId}/delete` | DELETE | Usuwanie bazy | ✅ Eventy zaimplementowane |
| `/panel/api/server/{id}/database/{databaseId}/rotate-password` | POST | Zmiana hasła | ✅ Eventy zaimplementowane |

#### Zaimplementowane eventy:

```php
// POST /panel/api/server/{id}/database/create
✅ ServerDatabaseCreationRequestedEvent (pre, stoppable) - src/Core/Event/Server/Database/
✅ ServerDatabaseCreatedEvent (post-commit) - src/Core/Event/Server/Database/
✅ ServerDatabaseCreationFailedEvent (error) - src/Core/Event/Server/Database/

// DELETE /panel/api/server/{id}/database/{databaseId}/delete
✅ ServerDatabaseDeletionRequestedEvent (pre, stoppable) - src/Core/Event/Server/Database/
✅ ServerDatabaseDeletedEvent (post-commit) - src/Core/Event/Server/Database/

// POST /panel/api/server/{id}/database/{databaseId}/rotate-password
✅ ServerDatabasePasswordRotationRequestedEvent (pre, stoppable) - src/Core/Event/Server/Database/
✅ ServerDatabasePasswordRotatedEvent (post-commit) - src/Core/Event/Server/Database/
```

**Lokalizacja:**
- Eventy: `src/Core/Event/Server/Database/`
- Logika: `src/Core/Service/Server/ServerDatabaseService.php`
- Kontroler: `src/Core/Controller/API/ServerDatabaseController.php` (thin - wywołuje serwis)

**Payload eventów:**
- **Database Creation**: userId, serverId, serverPterodactylIdentifier, databaseName, connectionsFrom, failureReason (dla Failed), context
- **Database Deletion**: userId, serverId, serverPterodactylIdentifier, databaseId, context
- **Password Rotation**: userId, serverId, serverPterodactylIdentifier, databaseId, context (bez nowego hasła ze względów bezpieczeństwa)

**Flow dla tworzenia bazy:**
```
POST /panel/api/server/{id}/database/create
  → ServerDatabaseCreationRequestedEvent (pre, stoppable) - plugin może zablokować
  → Try-catch block
    → Pterodactyl API createDatabase()
    → ServerLogService.logServerAction()
    → ServerDatabaseCreatedEvent (post-commit) - po API call
  → Exception catch:
    → ServerDatabaseCreationFailedEvent (error) - w przypadku błędu
```

**Flow dla usuwania bazy:**
```
DELETE /panel/api/server/{id}/database/{databaseId}/delete
  → ServerDatabaseDeletionRequestedEvent (pre, stoppable) - plugin może zablokować
  → Pterodactyl API deleteDatabase()
  → ServerLogService.logServerAction()
  → ServerDatabaseDeletedEvent (post-commit) - po API call
```

**Flow dla rotacji hasła:**
```
POST /panel/api/server/{id}/database/{databaseId}/rotate-password
  → ServerDatabasePasswordRotationRequestedEvent (pre, stoppable) - plugin może zablokować
  → Pterodactyl API rotatePassword()
  → ServerLogService.logServerAction()
  → ServerDatabasePasswordRotatedEvent (post-commit) - po API call
```

#### Zastosowanie dla pluginów:
- **Quota management** - limit baz danych per serwer ✅
- **Security** - audit trail dla operacji na bazach ✅
- **Notifications** - powiadomienia o krytycznych operacjach ✅
- **Backup integration** - automatyczne backupy przed delete/rotate ✅

---

### ~~5. Server Network API~~ ✅ **ZAIMPLEMENTOWANE** (2025-10-22)

**Plik:** `src/Core/Controller/API/ServerNetworkController.php`

#### ~~Endpointy bez eventów:~~ ✅ Endpointy z eventami:

| Endpoint | Metoda | Akcja | Status |
|----------|--------|-------|--------|
| `/panel/api/server/{id}/allocation/create` | POST | Tworzenie alokacji | ✅ Eventy zaimplementowane |
| `/panel/api/server/{id}/allocation/{allocationId}/primary` | POST | Ustawienie jako primary | ✅ Eventy zaimplementowane |
| `/panel/api/server/{id}/allocation/{allocationId}/edit` | POST | Edycja alokacji | ✅ Eventy zaimplementowane |
| `/panel/api/server/{id}/allocation/{allocationId}/delete` | DELETE | Usuwanie alokacji | ✅ Eventy zaimplementowane |

#### Zaimplementowane eventy:

```php
// POST /panel/api/server/{id}/allocation/create
✅ ServerAllocationCreationRequestedEvent (pre, stoppable) - src/Core/Event/Server/Network/
✅ ServerAllocationCreatedEvent (post-commit) - src/Core/Event/Server/Network/
✅ ServerAllocationCreationFailedEvent (error) - src/Core/Event/Server/Network/

// POST /panel/api/server/{id}/allocation/{allocationId}/primary
✅ ServerAllocationPrimaryChangeRequestedEvent (pre, stoppable) - src/Core/Event/Server/Network/
✅ ServerAllocationPrimaryChangedEvent (post-commit) - src/Core/Event/Server/Network/
✅ ServerAllocationPrimaryChangeFailedEvent (error) - src/Core/Event/Server/Network/

// POST /panel/api/server/{id}/allocation/{allocationId}/edit
✅ ServerAllocationEditRequestedEvent (pre, stoppable) - src/Core/Event/Server/Network/
✅ ServerAllocationEditedEvent (post-commit) - src/Core/Event/Server/Network/
✅ ServerAllocationEditFailedEvent (error) - src/Core/Event/Server/Network/

// DELETE /panel/api/server/{id}/allocation/{allocationId}/delete
✅ ServerAllocationDeletionRequestedEvent (pre, stoppable) - src/Core/Event/Server/Network/
✅ ServerAllocationDeletedEvent (post-commit) - src/Core/Event/Server/Network/
✅ ServerAllocationDeletionFailedEvent (error) - src/Core/Event/Server/Network/
```

**Lokalizacja:**
- Eventy: `src/Core/Event/Server/Network/`
- Logika: `src/Core/Service/Server/ServerNetworkService.php`
- Kontroler: `src/Core/Controller/API/ServerNetworkController.php` (thin - wywołuje serwis)

**Payload eventów:**
- **Allocation Creation**: userId, serverId, serverPterodactylIdentifier, failureReason (dla Failed), context
- **Allocation Primary Change**: userId, serverId, serverPterodactylIdentifier, allocationId, failureReason (dla Failed), context
- **Allocation Edit**: userId, serverId, serverPterodactylIdentifier, allocationId, newNotes, failureReason (dla Failed), context
- **Allocation Deletion**: userId, serverId, serverPterodactylIdentifier, allocationId, failureReason (dla Failed), context

**Specyfika implementacji:**
- Wszystkie metody zwracają `ServerAllocationActionResult` zamiast rzucać wyjątki
- Stoppable eventy blokują przez `return ServerAllocationActionResult(success: false, error: reason)`
- Failed eventy emitowane w bloku `if (!empty($errorDetail))` przed zwróceniem DTO z error
- Wszystkie operacje mają Failed event (w przeciwieństwie do oryginalnej dokumentacji)

**Flow dla tworzenia alokacji:**
```
POST /panel/api/server/{id}/allocation/create
  → ServerAllocationCreationRequestedEvent (pre, stoppable) - plugin może zablokować
  → Try-catch block
    → Pterodactyl API assignAllocation()
  → If error:
    → ServerAllocationCreationFailedEvent (error)
    → Return ServerAllocationActionResult(success: false)
  → ServerLogService.logServerAction()
  → ServerAllocationCreatedEvent (post-commit)
  → Return ServerAllocationActionResult(success: true)
```

**Flow dla zmiany primary:**
```
POST /panel/api/server/{id}/allocation/{allocationId}/primary
  → ServerAllocationPrimaryChangeRequestedEvent (pre, stoppable) - plugin może zablokować
  → Try-catch block
    → Pterodactyl API setPrimaryAllocation()
  → If error:
    → ServerAllocationPrimaryChangeFailedEvent (error)
    → Return ServerAllocationActionResult(success: false)
  → ServerLogService.logServerAction()
  → ServerAllocationPrimaryChangedEvent (post-commit)
  → Return ServerAllocationActionResult(success: true)
```

**Flow dla edycji notatek:**
```
POST /panel/api/server/{id}/allocation/{allocationId}/edit
  → ServerAllocationEditRequestedEvent (pre, stoppable) - plugin może zablokować
  → Try-catch block
    → Pterodactyl API updateAllocationNotes()
  → If error:
    → ServerAllocationEditFailedEvent (error)
    → Return ServerAllocationActionResult(success: false)
  → ServerLogService.logServerAction()
  → ServerAllocationEditedEvent (post-commit)
  → Return ServerAllocationActionResult(success: true)
```

**Flow dla usuwania alokacji:**
```
DELETE /panel/api/server/{id}/allocation/{allocationId}/delete
  → ServerAllocationDeletionRequestedEvent (pre, stoppable) - plugin może zablokować
  → Try-catch block
    → Pterodactyl API removeAllocation()
  → If error:
    → ServerAllocationDeletionFailedEvent (error)
    → Return ServerAllocationActionResult(success: false)
  → ServerLogService.logServerAction()
  → ServerAllocationDeletedEvent (post-commit)
  → Return ServerAllocationActionResult(success: true)
```

#### Zastosowanie dla pluginów:
- **Quota management** - limit portów per serwer ✅
- **Billing** - płatność za dodatkowe porty ✅
- **Firewall integration** - automatyczna konfiguracja firewall ✅
- **DDoS protection** - integracja z systemami ochrony ✅
- **Network monitoring** - tracking zmian w alokacjach ✅
- **Security** - audit trail dla operacji sieciowych ✅

---

### ~~6. Server Schedules API~~ ✅ **ZAIMPLEMENTOWANE** (2025-10-23)

**Plik:** `src/Core/Controller/API/ServerScheduleController.php`

#### ~~Endpointy bez eventów:~~ ✅ Endpointy z eventami:

| Endpoint | Metoda | Akcja | Status |
|----------|--------|-------|--------|
| `/panel/api/server/{id}/schedules/create` | POST | Tworzenie harmonogramu | ✅ Eventy zaimplementowane |
| `/panel/api/server/{id}/schedules/{scheduleId}` | PUT | Aktualizacja harmonogramu | ✅ Eventy zaimplementowane |
| `/panel/api/server/{id}/schedules/{scheduleId}/delete` | DELETE | Usuwanie harmonogramu | ✅ Eventy zaimplementowane |
| `/panel/api/server/{id}/schedules/{scheduleId}` | GET | Pobieranie harmonogramu | READ-ONLY |
| `/panel/api/server/{id}/schedules/{scheduleId}/tasks` | POST | Tworzenie zadania | ✅ Eventy zaimplementowane |
| `/panel/api/server/{id}/schedules/{scheduleId}/tasks/{taskId}` | PUT | Aktualizacja zadania | ✅ Eventy zaimplementowane |
| `/panel/api/server/{id}/schedules/{scheduleId}/tasks/{taskId}` | DELETE | Usuwanie zadania | ✅ Eventy zaimplementowane |

#### Zaimplementowane eventy:

```php
// POST /panel/api/server/{id}/schedules/create
✅ ServerScheduleCreationRequestedEvent (pre, stoppable) - src/Core/Event/Server/Schedule/
✅ ServerScheduleCreatedEvent (post-commit) - src/Core/Event/Server/Schedule/
✅ ServerScheduleCreationFailedEvent (error) - src/Core/Event/Server/Schedule/

// PUT /panel/api/server/{id}/schedules/{scheduleId}
✅ ServerScheduleUpdateRequestedEvent (pre, stoppable) - src/Core/Event/Server/Schedule/
✅ ServerScheduleUpdatedEvent (post-commit) - src/Core/Event/Server/Schedule/
✅ ServerScheduleUpdateFailedEvent (error) - src/Core/Event/Server/Schedule/

// DELETE /panel/api/server/{id}/schedules/{scheduleId}/delete
✅ ServerScheduleDeletionRequestedEvent (pre, stoppable) - src/Core/Event/Server/Schedule/
✅ ServerScheduleDeletedEvent (post-commit) - src/Core/Event/Server/Schedule/
✅ ServerScheduleDeletionFailedEvent (error) - src/Core/Event/Server/Schedule/

// POST /panel/api/server/{id}/schedules/{scheduleId}/tasks
✅ ServerScheduleTaskCreationRequestedEvent (pre, stoppable) - src/Core/Event/Server/Schedule/
✅ ServerScheduleTaskCreatedEvent (post-commit) - src/Core/Event/Server/Schedule/
✅ ServerScheduleTaskCreationFailedEvent (error) - src/Core/Event/Server/Schedule/

// PUT /panel/api/server/{id}/schedules/{scheduleId}/tasks/{taskId}
✅ ServerScheduleTaskUpdateRequestedEvent (pre, stoppable) - src/Core/Event/Server/Schedule/
✅ ServerScheduleTaskUpdatedEvent (post-commit) - src/Core/Event/Server/Schedule/
✅ ServerScheduleTaskUpdateFailedEvent (error) - src/Core/Event/Server/Schedule/

// DELETE /panel/api/server/{id}/schedules/{scheduleId}/tasks/{taskId}
✅ ServerScheduleTaskDeletionRequestedEvent (pre, stoppable) - src/Core/Event/Server/Schedule/
✅ ServerScheduleTaskDeletedEvent (post-commit) - src/Core/Event/Server/Schedule/
✅ ServerScheduleTaskDeletionFailedEvent (error) - src/Core/Event/Server/Schedule/
```

**Lokalizacja:**
- Eventy: `src/Core/Event/Server/Schedule/` (18 eventów)
- Logika: `src/Core/Service/Server/ServerScheduleService.php` (6 metod z EDA)
- Kontroler: `src/Core/Controller/API/ServerScheduleController.php` (thin - wywołuje serwis)

**Payload eventów:**

**Schedule Operations:**
- **Creation**: userId, serverId, serverPterodactylIdentifier, scheduleName, cronExpression (array), isActive, onlyWhenOnline, context
- **Created**: userId, serverId, serverPterodactylIdentifier, scheduleId, scheduleName, cronExpression (array), isActive, onlyWhenOnline, context
- **CreationFailed**: userId, serverId, serverPterodactylIdentifier, scheduleName, cronExpression (array), isActive, onlyWhenOnline, failureReason, context
- **Update**: userId, serverId, serverPterodactylIdentifier, scheduleId, scheduleName, cronExpression (array), isActive, onlyWhenOnline, context
- **Updated**: userId, serverId, serverPterodactylIdentifier, scheduleId, scheduleName, cronExpression (array), isActive, onlyWhenOnline, context
- **UpdateFailed**: userId, serverId, serverPterodactylIdentifier, scheduleId, scheduleName, cronExpression (array), isActive, onlyWhenOnline, failureReason, context
- **Deletion**: userId, serverId, serverPterodactylIdentifier, scheduleId, context
- **Deleted**: userId, serverId, serverPterodactylIdentifier, scheduleId, context
- **DeletionFailed**: userId, serverId, serverPterodactylIdentifier, scheduleId, failureReason, context

**Task Operations:**
- **TaskCreation**: userId, serverId, serverPterodactylIdentifier, scheduleId, action, payload, timeOffset, context
- **TaskCreated**: userId, serverId, serverPterodactylIdentifier, scheduleId, taskId, action, payload, timeOffset, context
- **TaskCreationFailed**: userId, serverId, serverPterodactylIdentifier, scheduleId, action, payload, timeOffset, failureReason, context
- **TaskUpdate**: userId, serverId, serverPterodactylIdentifier, scheduleId, taskId, action, payload, timeOffset, context
- **TaskUpdated**: userId, serverId, serverPterodactylIdentifier, scheduleId, taskId, action, payload, timeOffset, context
- **TaskUpdateFailed**: userId, serverId, serverPterodactylIdentifier, scheduleId, taskId, action, payload, timeOffset, failureReason, context
- **TaskDeletion**: userId, serverId, serverPterodactylIdentifier, scheduleId, taskId, context
- **TaskDeleted**: userId, serverId, serverPterodactylIdentifier, scheduleId, taskId, context
- **TaskDeletionFailed**: userId, serverId, serverPterodactylIdentifier, scheduleId, taskId, failureReason, context

**Uwagi implementacyjne:**
- cronExpression przechowywany jako array (np. `['minute' => '0', 'hour' => '*', 'day_of_month' => '*', 'month' => '*', 'day_of_week' => '*']`)
- Quota validation wykonywana PRZED emisją Requested event (hard business rule)
- Wszystkie operacje mają Failed events dla pełnego error trackingu
- Stoppable events pozwalają na blokowanie przed wykonaniem API call

**Flow dla tworzenia harmonogramu:**
```
POST /panel/api/server/{id}/schedules/create
  → Quota validation (hard business rule)
  → ServerScheduleCreationRequestedEvent (pre, stoppable) - plugin może zablokować
  → Pterodactyl API createSchedule()
  → ServerScheduleCreatedEvent (post-commit) - po API call
  → [catch] ServerScheduleCreationFailedEvent (error) - jeśli API error
```

#### Zastosowanie dla pluginów:
- **Quota management** - limit harmonogramów per serwer ✅
- **Analytics** - tracking popularnych schedulów ✅
- **Notifications** - powiadomienia o wykonaniu zadań ✅
- **Monitoring** - tracking błędów w harmonogramach ✅
- **Validation** - dodatkowe walidacje cron expressions ✅
- **Security** - audit trail dla schedulów i tasków ✅

---

### ~~7. Server Configuration API~~ ✅ **ZAIMPLEMENTOWANE** (2025-10-22)

**Plik:** `src/Core/Controller/API/ServerConfigurationController.php`

#### ~~Endpointy bez eventów:~~ ✅ Endpointy z eventami:

| Endpoint | Metoda | Akcja | Status |
|----------|--------|-------|--------|
| `/panel/api/server/{id}/startup/variable` | POST | Zmiana zmiennej startowej | ✅ Eventy zaimplementowane |
| `/panel/api/server/{id}/startup/option` | POST | Zmiana opcji startowej | ✅ Eventy zaimplementowane |
| `/panel/api/server/{id}/details/update` | POST | Aktualizacja szczegółów | ✅ Eventy zaimplementowane |
| `/panel/api/server/{id}/reinstall` | POST | Reinstalacja serwera | ✅ Eventy zaimplementowane |
| `/panel/api/server/{id}/auto-renewal/toggle` | POST | Przełączenie auto-renewal | ✅ Eventy zaimplementowane |

#### Zaimplementowane eventy:

```php
// POST /panel/api/server/{id}/startup/variable
✅ ServerStartupVariableUpdateRequestedEvent (pre, stoppable) - src/Core/Event/Server/Configuration/
✅ ServerStartupVariableUpdatedEvent (post-commit) - src/Core/Event/Server/Configuration/

// POST /panel/api/server/{id}/startup/option
✅ ServerStartupOptionUpdateRequestedEvent (pre, stoppable) - src/Core/Event/Server/Configuration/
✅ ServerStartupOptionUpdatedEvent (post-commit) - src/Core/Event/Server/Configuration/

// POST /panel/api/server/{id}/details/update
✅ ServerDetailsUpdateRequestedEvent (pre, stoppable) - src/Core/Event/Server/Configuration/
✅ ServerDetailsUpdatedEvent (post-commit) - src/Core/Event/Server/Configuration/

// POST /panel/api/server/{id}/reinstall
✅ ServerReinstallRequestedEvent (pre, stoppable) - src/Core/Event/Server/Configuration/
✅ ServerReinstallInitiatedEvent (post) - src/Core/Event/Server/Configuration/
✅ ServerReinstalledEvent (post-commit) - src/Core/Event/Server/Configuration/

// POST /panel/api/server/{id}/auto-renewal/toggle
✅ ServerAutoRenewalToggleRequestedEvent (pre, stoppable) - src/Core/Event/Server/Configuration/
✅ ServerAutoRenewalToggledEvent (post-commit) - src/Core/Event/Server/Configuration/
```

**Lokalizacja:**
- Eventy: `src/Core/Event/Server/Configuration/`
- Logika: `src/Core/Service/Server/ServerConfiguration/` (5 serwisów)
- Kontroler: `src/Core/Controller/API/ServerConfigurationController.php` (thin - wywołuje serwisy)

**Payload eventów:**
- **Startup Variable**: userId, serverId, serverPterodactylIdentifier, variableKey, variableValue, oldValue (dla Updated), context
- **Startup Option**: userId, serverId, serverPterodactylIdentifier, optionKey, optionValue, oldValue (dla Updated), context
- **Server Details**: userId, serverId, serverPterodactylIdentifier, serverName, serverDescription, oldServerName, oldServerDescription (dla Updated), context
- **Server Reinstall**: userId, serverId, serverPterodactylIdentifier, selectedEgg, currentEgg/previousEgg, eggChanged, context
- **Auto Renewal Toggle**: userId, serverId, serverPterodactylIdentifier, newValue, currentValue/previousValue, context

**Flow dla reinstalacji:**
```
POST /panel/api/server/{id}/reinstall
  → ServerReinstallRequestedEvent (pre, stoppable) - plugin może zablokować
  → Walidacja i zmiana egg (jeśli wybrano)
  → ServerReinstallInitiatedEvent (post) - po zmianie egg, przed API
  → Pterodactyl API reinstallServer()
  → ServerReinstalledEvent (post-commit) - po API call
```

#### Zastosowanie dla pluginów:
- **Validation** - dodatkowe walidacje przed reinstalacją ✅
- **Backup automation** - automatyczny backup przed reinstalacją ✅
- **Notifications** - powiadomienia o zmianach konfiguracji ✅
- **Audit trail** - historia zmian konfiguracji ✅
- **Security** - monitoring podejrzanych zmian ✅
- **Rate limiting** - ograniczenia częstotliwości zmian ✅
- **Analytics** - tracking popularnych zmian ✅

---

### ~~8. Server Details API~~ ✅ **ZAIMPLEMENTOWANE** (2025-10-23)

**Plik:** `src/Core/Controller/API/ServerController.php`

#### ~~Endpointy bez eventów:~~ ✅ Endpointy z eventami:

| Endpoint | Metoda | Akcja | Status |
|----------|--------|-------|--------|
| `/panel/api/server/{id}/details` | GET | Pobieranie szczegółów serwera | ✅ Eventy zaimplementowane |
| `/panel/api/server/{id}/websocket` | GET | Pobieranie tokenu WebSocket | ✅ Eventy zaimplementowane |
| `/panel/api/server/{id}/accept-eula` | POST | Akceptacja EULA | ✅ Eventy zaimplementowane |

#### Zaimplementowane eventy:

```php
// GET /panel/api/server/{id}/details
✅ ServerDetailsRequestedEvent (post, non-stoppable) - src/Core/Event/Server/
✅ ServerDetailsLoadedEvent (post, non-stoppable) - src/Core/Event/Server/

// GET /panel/api/server/{id}/websocket
✅ ServerWebsocketTokenRequestedEvent (post, non-stoppable) - src/Core/Event/Server/
✅ ServerWebsocketTokenGeneratedEvent (post, non-stoppable) - src/Core/Event/Server/

// POST /panel/api/server/{id}/accept-eula
✅ ServerEulaAcceptanceRequestedEvent (pre, stoppable) - src/Core/Event/Server/
✅ ServerEulaAcceptedEvent (post-commit) - src/Core/Event/Server/
✅ ServerEulaAcceptanceFailedEvent (error) - src/Core/Event/Server/
```

**Lokalizacja:**
- Eventy: `src/Core/Event/Server/` (7 eventów)
- Kontroler: `src/Core/Controller/API/ServerController.php` (eventy dla GET endpoints)
- Serwis: `src/Core/Service/Pterodactyl/ServerEulaService.php` (full EDA flow dla POST)

**Payload eventów:**

**GET /details (Read-only, metadata only):**
- **ServerDetailsRequested**: userId, serverId, serverPterodactylIdentifier, context
- **ServerDetailsLoaded**: userId, serverId, serverPterodactylIdentifier, currentState, isSuspended, context

**GET /websocket (Read-only, bez tokenu - security):**
- **ServerWebsocketTokenRequested**: userId, serverId, serverPterodactylIdentifier, context
- **ServerWebsocketTokenGenerated**: userId, serverId, serverPterodactylIdentifier, context (BEZ tokenu)

**POST /accept-eula (Write operation):**
- **ServerEulaAcceptanceRequested**: userId, serverId, serverPterodactylIdentifier, context
- **ServerEulaAccepted**: userId, serverId, serverPterodactylIdentifier, context
- **ServerEulaAcceptanceFailed**: userId, serverId, serverPterodactylIdentifier, failureReason, context

**Uwagi implementacyjne:**
- Read-only endpoints (GET) NIE mają Failed events - tylko Request/Loaded pattern
- Websocket token NIE jest w payload ServerWebsocketTokenGeneratedEvent (security)
- ServerDetailsLoadedEvent zawiera tylko metadata (currentState, isSuspended), nie pełny DTO
- EULA acceptance ma pełny flow z Requested (stoppable), Accepted, Failed

**Flow dla EULA acceptance:**
```
POST /panel/api/server/{id}/accept-eula
  → ServerEulaAcceptanceRequestedEvent (pre, stoppable) - plugin może zablokować
  → updateEulaFileContent() - zapis pliku eula.txt
  → sendPowerSignal('restart') - restart serwera
  → ServerEulaAcceptedEvent (post-commit) - po sukcesie
  → [catch] ServerEulaAcceptanceFailedEvent (error) - jeśli błąd
```

#### Zastosowanie dla pluginów:
- **Analytics** - tracking użycia endpointów details i websocket ✅
- **Performance monitoring** - monitoring czasu ładowania danych ✅
- **Security** - monitoring generowania tokenów WebSocket ✅
- **Rate limiting** - ograniczenie częstotliwości requestów ✅
- **EULA validation** - dodatkowe walidacje przed akceptacją ✅
- **Audit trail** - historia akceptacji EULA ✅
- **Notifications** - powiadomienia o akceptacji EULA ✅

---

### ~~9. Voucher API~~ ✅ **ZAIMPLEMENTOWANE** (2025-10-22)

**Plik:** `src/Core/Controller/API/VoucherController.php`

#### ~~Endpointy bez eventów:~~ ✅ Endpoint z eventami:

| Endpoint | Metoda | Akcja | Status |
|----------|--------|-------|--------|
| `/panel/api/voucher/redeem` | POST | Wykorzystanie vouchera | ✅ Eventy zaimplementowane |

#### Zaimplementowane eventy:

```php
// POST /panel/api/voucher/redeem
✅ VoucherRedemptionRequestedEvent (pre, stoppable) - src/Core/Event/Voucher/
✅ VoucherRedeemedEvent (post-commit) - src/Core/Event/Voucher/
✅ VoucherRedemptionFailedEvent (error) - src/Core/Event/Voucher/
```

**Lokalizacja:**
- Eventy: `src/Core/Event/Voucher/`
- Logika: `src/Core/Service/Voucher/VoucherService.php`
- Kontroler: `src/Core/Controller/API/VoucherController.php` (thin - bez zmian)

**Payload eventów:**
- `VoucherRedemptionRequestedEvent`: userId, voucherCode, orderAmount, context + StoppableEventTrait
- `VoucherRedeemedEvent`: userId, voucherId, voucherCode, voucherType, voucherValue, voucherUsageId, balanceAdded, oldBalance, newBalance, context
- `VoucherRedemptionFailedEvent`: userId, voucherCode, failureReason, attemptedVoucherType, attemptedVoucherValue, context

**Flow:**
```
POST /panel/api/voucher/redeem
  → VoucherRedemptionRequestedEvent (pre, stoppable) - plugin może zablokować
  → Walidacje vouchera (expired, max uses, requirements...)
  → Jeśli BALANCE_TOPUP: redeemVoucherForUser() + addBalanceTopup()
  → VoucherRedeemedEvent (post-commit) - z info o dodanym saldzie

CATCH:
  → VoucherRedemptionFailedEvent (error) - z powodem błędu
```

#### Zastosowanie dla pluginów:
- **Fraud detection** - wykrywanie nadużyć ✅
- **Rate limiting** - limity per user/IP ✅
- **Analytics** - tracking wykorzystania voucherów, ROI kampanii ✅
- **Marketing integration** - tracking kampanii, CRM sync ✅
- **Notifications** - powiadomienia o wykorzystaniu ✅
- **Loyalty programs** - punkty za wykorzystanie ✅
- **Security monitoring** - alerting przy nadużyciach ✅

---

### 9. Admin API

**Plik:** `src/Core/Controller/API/Admin/`

#### Endpointy bez eventów:

| Plik | Endpoint | Metoda | Akcja |
|------|----------|--------|-------|
| `VersionController.php` | `/panel/api/check-version` | GET | Sprawdzenie wersji |
| `TemplateController.php` | `/panel/api/template/{templateName}` | GET | Informacje o szablonie |

#### Proponowane eventy:

```php
// GET /panel/api/check-version
- SystemVersionCheckRequestedEvent (pre)
- SystemVersionCheckCompletedEvent (post)

// GET /panel/api/template/{templateName}
- TemplateInfoRequestedEvent (pre)
- TemplateInfoLoadedEvent (post)
```

#### Zastosowanie dla pluginów:
- **Update notifications** - powiadomienia o nowych wersjach
- **Analytics** - tracking wersji systemów klientów
- **Security** - monitoring wersji pod kątem CVE

---

### 10. Eggs API

**Plik:** `src/Core/Controller/API/EggsController.php`

#### Endpointy bez eventów:

| Endpoint | Metoda | Akcja |
|----------|--------|-------|
| `/panel/api/get-eggs/{nestId}` | GET | Pobieranie eggs |

#### Proponowane eventy:

```php
// GET /panel/api/get-eggs/{nestId}
- EggsDataRequestedEvent (pre)
- EggsDataLoadedEvent (post)
```

#### Zastosowanie dla pluginów:
- **Caching** - cache dla często pobieranych eggs
- **Custom eggs** - pluginy mogą dodać własne eggs

---

## ~~Brakujące Implementacje - Panel Admina~~ ✅ JUŻ ZAIMPLEMENTOWANE

**Lokalizacja:** `src/Core/Controller/Panel/`

**Status:** ✅ **WSZYSTKIE kontrolery CRUD mają eventy dzięki `AbstractPanelController`**

### ✅ Eventy CRUD już dostępne dla wszystkich kontrolerów

Wszystkie kontrolery dziedziczące z `AbstractPanelController` automatycznie emitują następujące eventy:

#### Eventy operacji CRUD:
- **CREATE:** `CrudEntityPersistingEvent` (pre, stoppable) → `CrudEntityPersistedEvent` (post)
- **UPDATE:** `CrudEntityUpdatingEvent` (pre, stoppable) → `CrudEntityUpdatedEvent` (post)
- **DELETE:** `CrudEntityDeletingEvent` (pre, stoppable) → `CrudEntityDeletedEvent` (post)

#### Eventy konfiguracyjne:
- `CrudConfiguredEvent` - konfiguracja CRUD
- `CrudActionsConfiguredEvent` - konfiguracja akcji
- `CrudFiltersConfiguredEvent` - konfiguracja filtrów
- `CrudFieldsConfiguredEvent` - konfiguracja pól
- `CrudIndexQueryBuiltEvent` - budowanie query dla listy

**Pełna lista kontrolerów z eventami CRUD:**
- ✅ UserCrudController
- ✅ UserAccountCrudController
- ✅ ServerCrudController
- ✅ ProductCrudController
- ✅ VoucherCrudController
- ✅ CategoryCrudController
- ✅ PaymentCrudController
- ✅ LogCrudController
- ✅ EmailLogCrudController
- ✅ ServerProductCrudController
- ✅ ServerLogCrudController
- ✅ VoucherUsageCrudController
- ✅ GeneralSettingCrudController
- ✅ SecuritySettingCrudController
- ✅ PterodactylSettingCrudController
- ✅ PaymentSettingCrudController
- ✅ ThemeSettingCrudController
- ✅ EmailSettingCrudController

### ✅ Wszystkie eventy w Panel Admina zostały zaimplementowane!

**Status:** ✅ **KOMPLETNE** (Data implementacji: 2025-10-21)

#### ✅ 1. Admin Overview - ZAIMPLEMENTOWANE

**Plik:** `src/Core/Controller/Panel/OverviewController.php`

**Uwaga:** OverviewController **NIE** dziedziczy z `AbstractPanelController`, więc nie ma eventów CRUD, ale ma dedykowane eventy dla overview.

**Route:** `/admin/overview`

**Zaimplementowane eventy:**

```php
// GET /admin/overview
✅ AdminOverviewAccessedEvent (post) - src/Core/Event/Admin/
✅ AdminOverviewDataLoadedEvent (post) - src/Core/Event/Admin/
✅ ViewDataEvent (viewName='admin_overview') - ViewNameEnum::ADMIN_OVERVIEW
```

**Payload eventów:**
- `AdminOverviewAccessedEvent`: userId, roles, context (ip, userAgent, locale)
- `AdminOverviewDataLoadedEvent`: userId, activeServersCount, usersRegisteredLastMonthCount, paymentsCreatedLastMonthCount, pterodactylOnline, context

**Zastosowanie dla pluginów:**
- **Analytics** - tracking odwiedzin strony overview ✅
- **Monitoring** - performance tracking ✅
- **Custom widgets** - pluginy mogą dodać własne statystyki ✅
- **Personalizacja** - customizacja dashboardu admina ✅

---

#### ✅ 2. Operacje specjalne w kontrolerach CRUD - ZAIMPLEMENTOWANE

**ProductCrudController:** `copyProduct()` - kopiowanie produktu

**Zaimplementowane eventy:**
```php
✅ ProductCopyRequestedEvent (pre, stoppable) - src/Core/Event/Product/
✅ ProductCopiedEvent (post-commit) - src/Core/Event/Product/
```

**Lokalizacja:**
- Eventy: `src/Core/Event/Product/`
- Logika: `src/Core/Service/Crud/ProductCopyService.php`
- Kontroler: `src/Core/Controller/Panel/ProductCrudController.php::copyProduct()`

**Payload eventów:**
- `ProductCopyRequestedEvent`: userId, originalProductId, originalProductName, context + StoppableEventTrait
- `ProductCopiedEvent`: userId, originalProductId, copiedProductId, copiedProductName, pricesCount, context

**Flow:**
```
Admin klika "Copy Product"
  → ProductCopyRequestedEvent (pre, stoppable) - plugin może zablokować
  → Kopiowanie produktu + obrazów + cen
  → ProductCopiedEvent (post-commit) - z pricesCount
```

**Zastosowanie dla pluginów:**
- **Validation** - limit liczby kopii produktów ✅
- **Audit trail** - logowanie wszystkich operacji kopiowania ✅
- **Custom post-copy logic** - automatyczne modyfikacje po kopiowaniu ✅
- **Integration** - synchronizacja z zewnętrznymi systemami ✅

**Uwaga:** Standardowe operacje CREATE/UPDATE/DELETE już mają eventy dzięki `AbstractPanelController`.

---

## Brakujące Implementacje - Warstwa CLI

**Lokalizacja:** `src/Core/Command/`

Wszystkie polecenia CLI **nie emitują eventów EDA**.

### Lista komend bez eventów:

#### 1. CreateNewUserCommand ✅ UKOŃCZONA (2025-10-26)

**Komenda:** `app:create-new-user`
**Plik:** `src/Core/Command/CreateNewUserCommand.php`
**Handler:** `src/Core/Handler/CreateNewUserHandler.php`

**Argumenty CLI:**
- `email` (required) - User email
- `password` (required) - User password
- `role` (optional, default: ROLE_USER) - User role (ROLE_USER, ROLE_ADMIN)

**Zaimplementowane eventy (3):**

**Process-level events (3):**

1. **UserCreationProcessStartedEvent** (post)
   - Lokalizacja: `src/Core/Event/Cli/CreateUser/UserCreationProcessStartedEvent.php`
   - Payload: `startedAt`, `email`, `role`, `context`
   - Kiedy: Po walidacji credentials, przed utworzeniem User entity

2. **UserCreationProcessCompletedEvent** (post-commit)
   - Lokalizacja: `src/Core/Event/Cli/CreateUser/UserCreationProcessCompletedEvent.php`
   - Payload: `userId`, `email`, `role`, `hasPterodactylAccount`, `hasApiKey`, `createdWithoutApiKey`, `durationInSeconds`, `completedAt`, `context`
   - Kiedy: Po zapisie użytkownika do bazy (success)
   - Zawiera pełne statystyki utworzonego użytkownika

3. **UserCreationProcessFailedEvent** (error)
   - Lokalizacja: `src/Core/Event/Cli/CreateUser/UserCreationProcessFailedEvent.php`
   - Payload: `failureReason`, `email`, `role`, `failedAt`, `context`
   - Kiedy: W catch() - wszystkie błędy (credentials, Pterodactyl, API key, rollback)

**Flow:**
```
1. Walidacja credentials (email, password)
   → UserCreationProcessFailedEvent jeśli empty
2. UserCreationProcessStartedEvent
3. Utworzenie User entity + hash password
4. Try: Utworzenie Pterodactyl account
   → UserCreationProcessFailedEvent jeśli ValidationException
5. Try: Utworzenie Pterodactyl Client API Key
   → Jeśli błąd i !allowToCreateWithNoPterodactylApiKey:
     - Rollback: delete Pterodactyl account
     - UserCreationProcessFailedEvent
   → Jeśli błąd i allowToCreateWithNoPterodactylApiKey:
     - Continue bez API key (createdWithoutApiKey=true)
6. Save user do bazy
7. UserCreationProcessCompletedEvent (success)
```

**CLI Context:**
```php
[
  'source' => 'cli',
  'command' => 'app:create-new-user',
  'email' => 'user@example.com',
  'role' => 'ROLE_USER',
  'ip' => null,
  'userAgent' => 'Symfony CLI',
  'locale' => 'en',
]
```

**Stats Structure:**
```php
[
  'userId' => int,                      // ID utworzonego użytkownika
  'email' => string,                    // Email użytkownika
  'role' => string,                     // ROLE_USER, ROLE_ADMIN
  'hasPterodactylAccount' => bool,      // Czy utworzono konto Pterodactyl
  'hasApiKey' => bool,                  // Czy utworzono API key
  'createdWithoutApiKey' => bool,       // Czy user wybrał continue bez API key
  'durationInSeconds' => int,           // Czas wykonania
]
```

**Decyzje implementacyjne:**
- ✅ **Basic CLI implementation:** 3 eventy (process-level tylko)
- ✅ **Comprehensive error tracking:** Wszystkie exception types (RuntimeException, ValidationException, CouldNotCreatePterodactylClientApiKeyException)
- ✅ **Rollback tracking:** Dedykowany failureReason jeśli rollback fails
- ✅ **Conditional API key:** Flags `hasApiKey` + `createdWithoutApiKey` w stats
- ✅ **Multi-step tracking:** hasPterodactylAccount + hasApiKey pokazują progress
- ✅ **Admin tool:** Tier 2 (niskie priorytety), basic approach wystarczający

**Error Scenarios:**

1. **Credentials not set:**
   - FailureReason: "User credentials not set"
   - Email/Role: "UNKNOWN" jeśli nie ustawione

2. **Pterodactyl account creation failed:**
   - FailureReason: ValidationException message + errors details
   - Rollback: nie potrzebny (user entity nie zapisana)

3. **API key creation failed (strict mode):**
   - Rollback: delete Pterodactyl account
   - FailureReason: CouldNotCreatePterodactylClientApiKeyException message
   - Jeśli rollback fails: "Could not create API key AND rollback failed: {details}"

4. **API key creation failed (allow mode):**
   - No FailedEvent emitted
   - CompletedEvent z `createdWithoutApiKey=true`, `hasApiKey=false`

**Use Cases:**

✅ **Audit Trail** - Admin operations tracking:
  - Kto i kiedy utworzył użytkownika przez CLI
  - Jaka rola została przypisana
  - Czy utworzono pełne konto (Pterodactyl + API key)
  - Błędy podczas tworzenia kont

✅ **Security & Compliance:**
  - Tracking wszystkich utworzonych kont admin
  - Monitoring failed attempts
  - Rollback tracking dla data integrity
  - Email tracking dla user management

✅ **Monitoring & Alerting:**
  - Alert gdy wiele failed attempts
  - Monitoring success rate tworzenia kont
  - Tracking incomplete accounts (bez API key)
  - Performance tracking (duration)

✅ **Analytics:**
  - Ile kont utworzono przez CLI vs web
  - Distribution of roles (ROLE_USER vs ROLE_ADMIN)
  - API key creation success rate
  - Rollback frequency

---

#### 2. SuspendUnpaidServersCommand ✅ UKOŃCZONA (2025-10-25)

**Komenda:** `app:suspend-unpaid-servers`
**Plik:** `src/Core/Command/SuspendUnpaidServersCommand.php`
**Handler:** `src/Core/Handler/SuspendUnpaidServersHandler.php`

**Zaimplementowane eventy (6):**

**Process-level events:**
1. **SuspendUnpaidServersProcessStartedEvent** - Process start
   - **Ścieżka:** `src/Core/Event/Cli/SuspendUnpaidServers/SuspendUnpaidServersProcessStartedEvent.php`
   - **Payload:** startedAt, context
   - **Kiedy:** Na początku procesu zawieszania

2. **SuspendUnpaidServersProcessCompletedEvent** - Process completion
   - **Ścieżka:** `src/Core/Event/Cli/SuspendUnpaidServers/SuspendUnpaidServersProcessCompletedEvent.php`
   - **Payload:** serversChecked, serversSuspended, serversRenewed, serversFailedToProcess, durationInSeconds, completedAt, context
   - **Kiedy:** Po pomyślnym zakończeniu procesu

3. **SuspendUnpaidServersProcessFailedEvent** - Process failure
   - **Ścieżka:** `src/Core/Event/Cli/SuspendUnpaidServers/SuspendUnpaidServersProcessFailedEvent.php`
   - **Payload:** failureReason, stats (checked/suspended/renewed/failed), failedAt, context
   - **Kiedy:** Gdy proces zakończył się błędem krytycznym

**Per-server events:**
4. **ServerSuspendedForNonPaymentEvent** - Server suspended
   - **Ścieżka:** `src/Core/Event/Cli/SuspendUnpaidServers/ServerSuspendedForNonPaymentEvent.php`
   - **Payload:** userId, serverId, serverPterodactylIdentifier, serverName, suspendedAt, context
   - **Kiedy:** Po pomyślnym zawieszeniu serwera za brak płatności

5. **ServerAutoRenewedEvent** - Auto-renewal success
   - **Ścieżka:** `src/Core/Event/Cli/SuspendUnpaidServers/ServerAutoRenewedEvent.php`
   - **Payload:** userId, serverId, serverPterodactylIdentifier, serverName, renewedAt, renewalCost, context
   - **Kiedy:** Gdy serwer został pomyślnie odnowiony automatycznie (zamiast zawieszenia)

6. **ServerSuspensionFailedEvent** - Per-server failure
   - **Ścieżka:** `src/Core/Event/Cli/SuspendUnpaidServers/ServerSuspensionFailedEvent.php`
   - **Payload:** userId, serverId, serverPterodactylIdentifier, serverName, failureReason, context
   - **Kiedy:** Gdy zawieszenie pojedynczego serwera zakończyło się błędem (proces kontynuuje dla pozostałych)

**Przepływ eventów:**
```
1. Dispatch: SuspendUnpaidServersProcessStartedEvent
   ↓
2. Foreach server:
   - Try auto-renewal
     → Success: Dispatch ServerAutoRenewedEvent
     → Failed: Try suspend
       → Success: Dispatch ServerSuspendedForNonPaymentEvent
       → Failed: Dispatch ServerSuspensionFailedEvent (continue)
   ↓
3. Dispatch: SuspendUnpaidServersProcessCompletedEvent (with stats)
   OR
   Dispatch: SuspendUnpaidServersProcessFailedEvent (on critical error)
```

**CLI Context:**
- `source`: 'cli'
- `command`: 'app:suspend-unpaid-servers'
- `userAgent`: 'CLI'
- `locale`: null
- `ip`: null

**Kluczowe decyzje implementacyjne:**
- Process-level events dla całego procesu + per-server events dla szczegółów
- Proces kontynuuje przetwarzanie pomimo błędów pojedynczych serwerów
- ServerAutoRenewedEvent emitowany gdy auto-renewal kończy się sukcesem
- Szczegółowe statystyki w ProcessCompletedEvent (checked, suspended, renewed, failed, duration)
- CLI-specific context structure (bez Request object)

**Zastosowanie:**
- Notifications - powiadomienia użytkowników o zawieszeniu/odnowieniu
- Analytics - tracking zawieszonych/odnowionych serwerów z metrykami
- Monitoring - alerting przy błędach (per-server i process-level)
- Retry logic - ponowne próby dla failed operations
- Performance monitoring - tracking duration i throughput

---

#### 3. DeleteInactiveServersCommand ✅ UKOŃCZONA (2025-10-25)

**Komenda:** `app:delete-inactive-servers`
**Plik:** `src/Core/Command/DeleteInactiveServersCommand.php`
**Handler:** `src/Core/Handler/DeleteInactiveServersHandler.php`

**Zaimplementowane eventy (6):**

**Process-level events:**
1. **DeleteInactiveServersProcessStartedEvent** - Process start
   - **Ścieżka:** `src/Core/Event/Cli/DeleteInactiveServers/DeleteInactiveServersProcessStartedEvent.php`
   - **Payload:** startedAt, daysAfterExpiration, context
   - **Kiedy:** Na początku procesu usuwania

2. **DeleteInactiveServersProcessCompletedEvent** - Process completion
   - **Ścieżka:** `src/Core/Event/Cli/DeleteInactiveServers/DeleteInactiveServersProcessCompletedEvent.php`
   - **Payload:** serversChecked, serversDeleted, serversSkipped, serversFailed, daysAfterExpiration, durationInSeconds, completedAt, context
   - **Kiedy:** Po pomyślnym zakończeniu procesu

3. **DeleteInactiveServersProcessFailedEvent** - Process failure
   - **Ścieżka:** `src/Core/Event/Cli/DeleteInactiveServers/DeleteInactiveServersProcessFailedEvent.php`
   - **Payload:** failureReason, stats (checked/deleted/skipped/failed), failedAt, context
   - **Kiedy:** Gdy proces zakończył się błędem krytycznym

**Per-server events:**
4. **InactiveServerDeletionRequestedEvent** (stoppable) - Pre-deletion
   - **Ścieżka:** `src/Core/Event/Cli/DeleteInactiveServers/InactiveServerDeletionRequestedEvent.php`
   - **Payload:** userId, serverId, serverPterodactylIdentifier, serverName, expiredAt, daysAfterExpiration, context
   - **Kiedy:** PRZED usunięciem każdego serwera
   - **Stoppable:** TAK - plugin może wykonać backup lub zablokować usunięcie
   - **Trait:** StoppableEventTrait

5. **InactiveServerDeletedEvent** - Server deleted
   - **Ścieżka:** `src/Core/Event/Cli/DeleteInactiveServers/InactiveServerDeletedEvent.php`
   - **Payload:** userId, serverId, serverPterodactylIdentifier, serverName, expiredAt, deletedAt, daysAfterExpiration, context
   - **Kiedy:** Po pomyślnym usunięciu serwera

6. **InactiveServerDeletionFailedEvent** - Per-server failure
   - **Ścieżka:** `src/Core/Event/Cli/DeleteInactiveServers/InactiveServerDeletionFailedEvent.php`
   - **Payload:** userId, serverId, serverPterodactylIdentifier, serverName, expiredAt, failureReason, context
   - **Kiedy:** Gdy usunięcie pojedynczego serwera zakończyło się błędem (proces kontynuuje dla pozostałych)

**Przepływ eventów:**
```
1. Dispatch: DeleteInactiveServersProcessStartedEvent
   ↓
2. Foreach server to delete:
   → Dispatch: InactiveServerDeletionRequestedEvent (stoppable)
     ✓ Not stopped: Proceed with deletion
       → Delete from Pterodactyl
       → Delete from database
       → Success: Dispatch InactiveServerDeletedEvent
       ✗ Error: Dispatch InactiveServerDeletionFailedEvent (continue)
     ✗ Stopped by plugin: Skip deletion (stats['skipped']++)
   ↓
3. Dispatch: DeleteInactiveServersProcessCompletedEvent (with stats)
   OR
   Dispatch: DeleteInactiveServersProcessFailedEvent (on critical error)
```

**Konfiguracja:**
- Dni po wygaśnięciu: Odczytywane z `SettingEnum::DELETE_SUSPENDED_SERVERS_DAYS_AFTER`
- Domyślna wartość: 30 dni

**CLI Context:**
- `source`: 'cli'
- `command`: 'app:delete-inactive-servers'
- `daysAfterExpiration`: konfigurowana wartość
- `userAgent`: 'CLI'
- `locale`: null
- `ip`: null

**Kluczowe decyzje implementacyjne:**
- **Stoppable pre-event** dla backup automation - plugin może zrobić backup lub zablokować usunięcie
- **Pełne dane czasowe** (expiredAt, deletedAt, daysAfterExpiration) dla analytics i audit
- Proces **kontynuuje** przetwarzanie pomimo błędów pojedynczych serwerów
- **Stat 'skipped'** - tracking serwerów zablokowanych przez pluginy
- Szczegółowe statystyki w ProcessCompletedEvent (checked, deleted, skipped, failed, duration)

**Zastosowanie:**
✅ **Backup automation** - Plugin nasłuchuje na `InactiveServerDeletionRequestedEvent`:
  - Wykonuje backup przed usunięciem
  - Może zablokować usunięcie używając `setRejected(true, reason)`

✅ **Notifications** - Plugin może wysłać ostatnie ostrzeżenie w reakcji na `InactiveServerDeletionRequestedEvent`

✅ **Analytics** - Pełne dane czasowe pozwalają na tracking:
  - Ile serwerów jest usuwanych
  - Jak długo po wygaśnięciu następuje usunięcie
  - Success rate usuwania
  - Ile serwerów zostało zablokowanych przez pluginy

✅ **Audit trail** - Wszystkie operacje logowane:
  - Kto (userId), co (serverId), kiedy (timestamps)
  - Powody niepowodzeń
  - Blokady przez pluginy (w tym powody)

---

#### 4. ChangeUserPasswordCommand ✅ UKOŃCZONA (2025-10-26)

**Komenda:** `app:change-user-password`
**Plik:** `src/Core/Command/ChangeUserPasswordCommand.php`
**Handler:** `src/Core/Handler/ChangeUserPasswordHandler.php`

**Argumenty CLI:**
- `email` (required) - User email
- `password` (required) - New password

**Zaimplementowane eventy (3):**

**Process-level events (3):**

1. **PasswordChangeProcessStartedEvent** (post)
   - Lokalizacja: `src/Core/Event/Cli/ChangePassword/PasswordChangeProcessStartedEvent.php`
   - Payload: `startedAt`, `email`, `context`
   - Kiedy: Po walidacji credentials, przed znalezieniem usera

2. **PasswordChangeProcessCompletedEvent** (post-commit)
   - Lokalizacja: `src/Core/Event/Cli/ChangePassword/PasswordChangeProcessCompletedEvent.php`
   - Payload: `userId`, `email`, `passwordChangedInPterodactyl`, `durationInSeconds`, `completedAt`, `context`
   - Kiedy: Po zapisie do bazy (success)
   - Zawiera pełne statystyki zmiany hasła

3. **PasswordChangeProcessFailedEvent** (error)
   - Lokalizacja: `src/Core/Event/Cli/ChangePassword/PasswordChangeProcessFailedEvent.php`
   - Payload: `failureReason`, `email`, `failedAt`, `context`
   - Kiedy: W catch() - wszystkie błędy (credentials, user not found, Pterodactyl)

**Flow:**
```
1. Walidacja credentials (email, password)
   → PasswordChangeProcessFailedEvent jeśli empty
2. PasswordChangeProcessStartedEvent
3. Znalezienie użytkownika w bazie po email
   → PasswordChangeProcessFailedEvent jeśli not found
4. Hash nowego hasła
5. Try: Aktualizacja hasła w Pterodactyl
   → PasswordChangeProcessFailedEvent jeśli ValidationException
6. Save user do bazy
7. PasswordChangeProcessCompletedEvent (success)
```

**CLI Context:**
```php
[
  'source' => 'cli',
  'command' => 'app:change-user-password',
  'email' => 'user@example.com',
  'ip' => null,
  'userAgent' => 'Symfony CLI',
  'locale' => 'en',
]
```

**Stats Structure:**
```php
[
  'userId' => int,                           // ID użytkownika
  'email' => string,                         // Email użytkownika
  'passwordChangedInPterodactyl' => bool,    // Czy zmieniono hasło w Pterodactyl (true w success)
  'durationInSeconds' => int,                // Czas wykonania
]
```

**Decyzje implementacyjne:**
- ✅ **Basic CLI implementation:** 3 eventy (process-level tylko) - zgodnie z wzorcem Tier 2
- ✅ **Security-critical:** Password change tracking dla security notifications
- ✅ **No rollback needed:** Update Pterodactyl i save są atomic operations
- ✅ **Comprehensive error tracking:** 3 różne error scenarios
- ✅ **Simple stats:** passwordChangedInPterodactyl (zawsze true w CompletedEvent)
- ✅ **Admin tool:** Tier 2 (niskie priorytety), basic approach wystarczający

**Error Scenarios:**

1. **Credentials not set:**
   - FailureReason: "User credentials not set"
   - Email: "UNKNOWN" jeśli nie ustawiony
   - FailedEvent emitowany PRZED StartedEvent

2. **User not found:**
   - FailureReason: "User not found"
   - FailedEvent po StartedEvent

3. **Pterodactyl update failed:**
   - FailureReason: ValidationException message + errors details
   - FailedEvent po StartedEvent

**Use Cases:**

✅ **Security Notifications** - Immediate alerts:
  - Email notifications do użytkownika o zmianie hasła
  - SMS/2FA notifications dla security
  - Alert jeśli zmiana z nieznanych lokacji (future enhancement)
  - Tracking wszystkich zmian hasła dla security audit

✅ **Audit Trail** - Admin operations tracking:
  - Kto zmienił hasło przez CLI (admin operations)
  - Kiedy i dla którego użytkownika
  - Success/failure rate
  - Compliance logging dla regulacji

✅ **Monitoring & Alerting:**
  - Alert gdy wiele failed attempts (brute force detection)
  - Monitoring success rate zmian haseł
  - Performance tracking (duration)
  - Pterodactyl integration health check

✅ **Compliance:**
  - GDPR compliance - tracking password changes
  - Security policy enforcement
  - Password change history
  - Admin accountability

---

#### 5. PterodactylMigrateServersCommand

**Komenda:** `app:pterodactyl-migrate-servers`
**Plik:** `src/Core/Command/PterodactylMigrateServersCommand.php`

**Proponowane eventy:**
```php
- ServerMigrationProcessStartedEvent (pre)
- ServerMigratedEvent (per server)
- ServerMigrationProcessCompletedEvent (post)
- ServerMigrationFailedEvent (error, per server)
```

**Zastosowanie:**
- Progress tracking - monitoring postępu migracji
- Error handling - tracking błędów migracji
- Analytics - statystyki migracji
- Notifications - powiadomienia o zakończeniu

---

#### 6. PterocaSyncServersCommand ✅ UKOŃCZONA (2025-10-25)

**Komenda:** `pteroca:sync-servers`
**Plik:** `src/Core/Command/PterocaSyncServersCommand.php`
**Handler:** `src/Core/Handler/SyncServersHandler.php`
**Services:** `PterodactylSyncService.php`, `PterodactylCleanupService.php`

**Opcje CLI:**
- `--limit` (domyślnie 1000) - limit serwerów do sprawdzenia z Pterodactyl
- `--dry-run` - tryb testowy, pokazuje co by zostało zrobione bez wprowadzania zmian
- `--auto` - automatyczny tryb, usuwa osierocone serwery bez pytania (dla crona)

**Zaimplementowane eventy (7):**

**Process-level events:**
1. **ServersSyncProcessStartedEvent** - Process start
   - **Ścieżka:** `src/Core/Event/Cli/SyncServers/ServersSyncProcessStartedEvent.php`
   - **Payload:** startedAt, limit, dryRun, auto, context
   - **Kiedy:** Na początku procesu synchronizacji

2. **ServersSyncProcessCompletedEvent** - Process completion
   - **Ścieżka:** `src/Core/Event/Cli/SyncServers/ServersSyncProcessCompletedEvent.php`
   - **Payload:** pterodactylServersFound, orphanedServersFound, orphanedServersDeleted, orphanedServersSkipped, orphanedServersFailed, limit, dryRun, auto, durationInSeconds, completedAt, context
   - **Kiedy:** Po pomyślnym zakończeniu procesu

3. **ServersSyncProcessFailedEvent** - Process failure
   - **Ścieżka:** `src/Core/Event/Cli/SyncServers/ServersSyncProcessFailedEvent.php`
   - **Payload:** failureReason, stats (częściowe), failedAt, context
   - **Kiedy:** Gdy proces zakończył się błędem krytycznym

**Per-server events:**
4. **OrphanedServerFoundEvent** ⚠️ STOPPABLE - Orphaned server found
   - **Ścieżka:** `src/Core/Event/Cli/SyncServers/OrphanedServerFoundEvent.php`
   - **Payload:** userId, serverId, serverPterodactylServerId, serverPterodactylIdentifier, serverName, context
   - **Kiedy:** Po znalezieniu osieroczonego serwera, PRZED pytaniem użytkownika / usunięciem
   - **Stoppable:** TAK - plugin może zablokować usunięcie lub wykonać backup
   - **Trait:** StoppableEventTrait

5. **OrphanedServerDeletedEvent** - Orphaned server deleted
   - **Ścieżka:** `src/Core/Event/Cli/SyncServers/OrphanedServerDeletedEvent.php`
   - **Payload:** userId, serverId, serverPterodactylServerId, serverPterodactylIdentifier, serverName, deletedAt, context
   - **Kiedy:** Po pomyślnym usunięciu (soft delete) osieroczonego serwera

6. **OrphanedServerSkippedEvent** - Orphaned server skipped
   - **Ścieżka:** `src/Core/Event/Cli/SyncServers/OrphanedServerSkippedEvent.php`
   - **Payload:** userId, serverId, serverPterodactylServerId, serverPterodactylIdentifier, serverName, reason, context
   - **Kiedy:** Gdy serwer został pominięty
   - **Reasons:** "plugin_blocked" (zablokowany przez plugin), "user_declined" (user odmówił), "dry_run" (tryb testowy)

7. **OrphanedServerDeletionFailedEvent** - Per-server failure
   - **Ścieżka:** `src/Core/Event/Cli/SyncServers/OrphanedServerDeletionFailedEvent.php`
   - **Payload:** userId, serverId, serverPterodactylServerId, serverPterodactylIdentifier, serverName, failureReason, context
   - **Kiedy:** Błąd przy usuwaniu osieroczonego serwera (proces kontynuuje)

**Przepływ eventów:**
```
1. Dispatch: ServersSyncProcessStartedEvent
   ↓
2. Fetch servers from Pterodactyl API → stats['pterodactylServersFound']
   ↓
3. Find orphaned servers (in PteroCA but not in Pterodactyl)
   ↓
4. Foreach orphaned server:
   → stats['orphanedServersFound']++
   → Dispatch: OrphanedServerFoundEvent (stoppable)
     ✗ Stopped by plugin:
       → Dispatch OrphanedServerSkippedEvent(reason: "plugin_blocked")
       → stats['orphanedServersSkipped']++
       → continue
     ✓ Not stopped:
       → If !auto: Ask user "delete?"
         ✗ User says no:
           → Dispatch OrphanedServerSkippedEvent(reason: "user_declined")
           → stats['orphanedServersSkipped']++
           → continue
       → If dryRun:
         → Dispatch OrphanedServerSkippedEvent(reason: "dry_run")
         → stats['orphanedServersSkipped']++
         → continue
       → Try delete (soft delete):
         ✓ Success:
           → Dispatch OrphanedServerDeletedEvent
           → stats['orphanedServersDeleted']++
         ✗ Error:
           → Dispatch OrphanedServerDeletionFailedEvent
           → stats['orphanedServersFailed']++
           → continue
   ↓
5. Dispatch: ServersSyncProcessCompletedEvent (with full stats)
   OR (on critical error)
   Dispatch: ServersSyncProcessFailedEvent
```

**CLI Context:**
- `source`: 'cli'
- `command`: 'pteroca:sync-servers'
- `limit`: 1000 (konfigurowalne)
- `dryRun`: true/false
- `auto`: true/false
- `userAgent`: 'CLI'
- `locale`: null
- `ip`: null

**Kluczowe decyzje implementacyjne:**
- **Orphaned cleanup** - komenda usuwa serwery które są w PteroCA ale nie ma ich w Pterodactyl
- **Stoppable pre-event** dla backup automation - plugin może zrobić backup lub zablokować usunięcie
- **Opcje CLI w context** (limit, dryRun, auto) dla pełnej transparentności
- **Dry-run handling** - normalne eventy z flagą w context (nie specjalne "would-be" eventy)
- **User interaction tracking** - stat 'skipped' z trzema powodami (plugin_blocked, user_declined, dry_run)
- Proces **kontynuuje** przetwarzanie pomimo błędów pojedynczych serwerów
- Szczegółowe statystyki w ProcessCompletedEvent (pterodactylServersFound, orphanedServersFound, deleted, skipped, failed, duration)
- **Soft delete** - używa `setDeletedAtValue()` zamiast fizycznego usunięcia

**Zastosowanie:**
✅ **Monitoring** - Process-level events z pełnymi statystykami:
  - Orphan rate (ile % serwerów jest osieroconych)
  - Success rate usuwania
  - Duration i throughput

✅ **Error alerting** - Per-server i process-level failure events:
  - Błędy pojedynczych serwerów
  - Błędy krytyczne procesu

✅ **Analytics** - Szczegółowe tracking:
  - User interaction (ile razy user declined)
  - Plugin interaction (ile razy plugin blocked)
  - Dry-run simulations

✅ **Audit trail** - Pełny tracking:
  - Wszystkie decyzje (user, plugin, system)
  - Powody skipowania serwerów
  - Powody błędów

✅ **Plugin extensibility** - Stoppable pre-event:
  - Plugin może zablokować usunięcie konkretnego serwera
  - Może wykonać backup przed usunięciem
  - Może zmodyfikować flow (np. przenieść serwer zamiast usuwać)

✅ **Dry-run safety** - Eventy emitowane w dry-run mode:
  - Pluginy widzą co by się stało
  - Można testować integracje bez zmian w bazie
  - Eventy mają flagę `dryRun: true` w context

---

#### 7. PterodactylMigrateServersCommand ✅ UKOŃCZONA (2025-10-26)

**Komenda:** `pterodactyl:migrate-servers`
**Plik:** `src/Core/Command/PterodactylMigrateServersCommand.php`
**Handler:** `src/Core/Handler/MigrateServersHandler.php`

**Zaimplementowane eventy (7):**

**Process-level events (3):**
1. **ServerMigrationProcessStartedEvent** (post)
   - Lokalizacja: `src/Core/Event/Cli/MigrateServers/ServerMigrationProcessStartedEvent.php`
   - Payload: `startedAt`, `limit`, `dryRun`, `context`
   - Kiedy: Na początku procesu migracji

2. **ServerMigrationProcessCompletedEvent** (post-commit)
   - Lokalizacja: `src/Core/Event/Cli/MigrateServers/ServerMigrationProcessCompletedEvent.php`
   - Payload: `pterodactylServersFound`, `pterodactylUsersFound`, `serversAlreadyExisting`, `serversMigrated`, `serversSkipped`, `serversFailed`, `limit`, `dryRun`, `durationInSeconds`, `completedAt`, `context`
   - Kiedy: Po zakończeniu całego procesu
   - Zawiera pełne statystyki procesu

3. **ServerMigrationProcessFailedEvent** (error)
   - Lokalizacja: `src/Core/Event/Cli/MigrateServers/ServerMigrationProcessFailedEvent.php`
   - Payload: `failureReason`, `stats` (partial), `failedAt`, `context`
   - Kiedy: W catch() handle() - krytyczny błąd całego procesu

**Per-server events (4):**

4. **ServerMigrationRequestedEvent** (pre, stoppable) ⚠️
   - Lokalizacja: `src/Core/Event/Cli/MigrateServers/ServerMigrationRequestedEvent.php`
   - Payload: `pterodactylServerId`, `pterodactylServerIdentifier`, `serverName`, `ownerEmail`, `pterodactylOwnerId`, `isSuspended`, `context`
   - Kiedy: Po user confirmation, przed askForDuration()
   - **Stoppable:** Plugin może zablokować migrację (backup automation)

5. **ServerMigratedEvent** (post-commit)
   - Lokalizacja: `src/Core/Event/Cli/MigrateServers/ServerMigratedEvent.php`
   - Payload: `userId`, `serverId`, `pterodactylServerId`, `pterodactylServerIdentifier`, `serverName`, `duration`, `price`, `expiresAt`, `migratedAt`, `context`
   - Kiedy: Po utworzeniu wszystkich 3 encji (Server, ServerProduct, ServerProductPrice)
   - Zawiera pełne dane migracji (duration, price)

6. **ServerMigrationSkippedEvent** (post)
   - Lokalizacja: `src/Core/Event/Cli/MigrateServers/ServerMigrationSkippedEvent.php`
   - Payload: `pterodactylServerId`, `pterodactylServerIdentifier`, `serverName`, `reason`, `ownerEmail`, `context`
   - Kiedy: Przy każdym skip (5 scenariuszy)
   - **Reason values:** `already_exists`, `owner_not_found`, `user_declined`, `plugin_blocked`, `dry_run`

7. **ServerMigrationFailedEvent** (error)
   - Lokalizacja: `src/Core/Event/Cli/MigrateServers/ServerMigrationFailedEvent.php`
   - Payload: `pterodactylServerId`, `pterodactylServerIdentifier`, `serverName`, `failureReason`, `ownerEmail`, `context`
   - Kiedy: W catch() podczas migracji pojedynczego serwera
   - **Proces kontynuuje** mimo błędu (fail-safe approach)

**Flow:**
```
1. Pobranie serwerów z Pterodactyl (limit)
2. Pobranie użytkowników z Pterodactyl
3. Pobranie serwerów z PteroCA
4. Pobranie użytkowników z PteroCA
5. ServerMigrationProcessStartedEvent
6. Dla każdego serwera Pterodactyl:
   a. Sprawdzenie czy już istnieje → ServerMigrationSkippedEvent (already_exists)
   b. Sprawdzenie czy owner istnieje → ServerMigrationSkippedEvent (owner_not_found)
   c. Pytanie użytkownika CLI → ServerMigrationSkippedEvent (user_declined)
   d. ServerMigrationRequestedEvent (stoppable)
      → ServerMigrationSkippedEvent (plugin_blocked) jeśli zatrzymano
   e. Pytanie o duration i price
   f. Dry-run check → ServerMigrationSkippedEvent (dry_run)
   g. Migracja: Server → ServerProduct → ServerProductPrice
      → ServerMigratedEvent (success)
      → ServerMigrationFailedEvent (error, continue)
7. ServerMigrationProcessCompletedEvent
```

**CLI Context:**
```php
[
  'source' => 'cli',
  'command' => 'pterodactyl:migrate-servers',
  'limit' => 100,         // Limit serwerów z Pterodactyl
  'dryRun' => false,      // Tryb dry-run
  'ip' => null,
  'userAgent' => 'Symfony CLI',
  'locale' => 'en',
]
```

**Stats Structure:**
```php
[
  'pterodactylServersFound' => int,   // Serwery pobrane z Pterodactyl
  'pterodactylUsersFound' => int,     // Użytkownicy pobrania z Pterodactyl
  'serversAlreadyExisting' => int,    // Serwery już w PteroCA (skip: already_exists)
  'serversMigrated' => int,           // Pomyślnie zmigrowane
  'serversSkipped' => int,            // Wszystkie skip (5 powodów)
  'serversFailed' => int,             // Błędy podczas migracji
]
```

**Skip Reasons (5):**
- `already_exists` - Serwer już istnieje w PteroCA
- `owner_not_found` - Owner nie istnieje w PteroCA
- `user_declined` - Użytkownik CLI powiedział "no"
- `plugin_blocked` - Plugin zatrzymał przez `stopPropagation()`
- `dry_run` - Tryb dry-run (test mode)

**Decyzje implementacyjne:**
- ✅ **User interaction tracking:** Duration i price w `ServerMigratedEvent`
- ✅ **5 skip reasons:** Szczegółowy tracking wszystkich scenariuszy skip
- ✅ **Stoppable pre-event:** `ServerMigrationRequestedEvent` dla backup automation
- ✅ **Fail-safe:** Błąd pojedynczego serwera nie zatrzymuje całego procesu
- ✅ **3 encje:** Server + ServerProduct + ServerProductPrice w jednej transakcji
- ✅ **Dry-run support:** Traktowany jako skip reason (zgodnie z wzorcem)

**Use Cases:**

✅ **Monitoring & Analytics** - Process-level tracking:
  - Ile serwerów pobrano z Pterodactyl
  - Ile z nich już istnieje w PteroCA
  - Success rate migracji
  - Czas trwania procesu

✅ **Per-server Analytics** - Szczegółowe tracking:
  - Które serwery zostały zmigrowane
  - Jakie ceny i duration ustawiono
  - Powody skipowania poszczególnych serwerów
  - Błędy podczas migracji (fail-safe)

✅ **Audit trail** - Pełny tracking:
  - Wszystkie decyzje (user, plugin, system)
  - Powody skipowania serwerów (5 scenariuszy)
  - Powody błędów z exception message
  - User input (duration, price) w eventach

✅ **Plugin extensibility** - Stoppable pre-event:
  - Plugin może zablokować migrację konkretnego serwera
  - Może wykonać backup przed migracją
  - Może zmodyfikować flow (np. custom validation)

✅ **Dry-run safety** - Eventy emitowane w dry-run mode:
  - Pluginy widzą co by się stało
  - Można testować integracje bez zmian w bazie
  - Eventy mają flagę `dryRun: true` w context

---

#### 8. SynchronizeDataCommand ✅ UKOŃCZONA (2025-10-26)

**Komenda:** `app:synchronize-data`
**Plik:** `src/Core/Command/SynchronizeDataCommand.php`
**Handler:** `src/Core/Handler/SynchronizeDataHandler.php`

**Zaimplementowane eventy (6):**

**Process-level events (3):**
1. **DataSyncProcessStartedEvent** (post)
   - Lokalizacja: `src/Core/Event/Cli/SynchronizeData/DataSyncProcessStartedEvent.php`
   - Payload: `startedAt`, `context`
   - Kiedy: Na początku procesu synchronizacji

2. **DataSyncProcessCompletedEvent** (post-commit)
   - Lokalizacja: `src/Core/Event/Cli/SynchronizeData/DataSyncProcessCompletedEvent.php`
   - Payload: `usersWithoutKeys`, `keysCreated`, `keysSkipped`, `keysFailed`, `durationInSeconds`, `completedAt`, `context`
   - Kiedy: Po zakończeniu całego procesu synchronizacji
   - Zawiera pełne statystyki procesu (wszystkie 4 metryki)

3. **DataSyncProcessFailedEvent** (error)
   - Lokalizacja: `src/Core/Event/Cli/SynchronizeData/DataSyncProcessFailedEvent.php`
   - Payload: `failureReason`, `stats` (partial), `failedAt`, `context`
   - Kiedy: W catch() handle() - krytyczny błąd całego procesu

**Per-user events (3):**

4. **UserPterodactylApiKeyCreationRequestedEvent** (pre, stoppable) ⚠️
   - Lokalizacja: `src/Core/Event/Cli/SynchronizeData/UserPterodactylApiKeyCreationRequestedEvent.php`
   - Payload: `userId`, `userEmail`, `userName`, `context`
   - Kiedy: Przed utworzeniem API key dla konkretnego użytkownika
   - **Stoppable:** Plugin może zablokować utworzenie (np. suspended user, security policy)

5. **UserPterodactylApiKeyCreatedEvent** (post-commit)
   - Lokalizacja: `src/Core/Event/Cli/SynchronizeData/UserPterodactylApiKeyCreatedEvent.php`
   - Payload: `userId`, `userEmail`, `userName`, `apiKeyIdentifier`, `createdAt`, `context`
   - Kiedy: Po utworzeniu i zapisaniu Pterodactyl Client API Key
   - Zawiera `apiKeyIdentifier` (bez pełnego klucza dla security)

6. **UserPterodactylApiKeyCreationFailedEvent** (error)
   - Lokalizacja: `src/Core/Event/Cli/SynchronizeData/UserPterodactylApiKeyCreationFailedEvent.php`
   - Payload: `userId`, `userEmail`, `userName`, `failureReason`, `context`
   - Kiedy: W catch() podczas tworzenia klucza dla usera
   - **Fail-safe:** Proces kontynuuje z pozostałymi userami

**Flow:**
```
1. Pobranie użytkowników bez pterodactylUserApiKey (null)
2. DataSyncProcessStartedEvent
3. Dla każdego użytkownika bez API key:
   a. UserPterodactylApiKeyCreationRequestedEvent (stoppable)
      → Skip jeśli plugin zablokuje (keysSkipped++)
   b. Try-catch per user (fail-safe):
      - createClientApiKey() w Pterodactyl
      - setPterodactylUserApiKey() + save()
      - UserPterodactylApiKeyCreatedEvent (success, keysCreated++)
      - UserPterodactylApiKeyCreationFailedEvent (error, keysFailed++, continue)
4. DataSyncProcessCompletedEvent (success)
   → DataSyncProcessFailedEvent (krytyczny błąd całego procesu)
```

**CLI Context:**
```php
[
  'source' => 'cli',
  'command' => 'app:synchronize-data',
  'ip' => null,
  'userAgent' => 'Symfony CLI',
  'locale' => 'en',
]
```

**Stats Structure:**
```php
[
  'usersWithoutKeys' => int,  // Znalezieni użytkownicy bez API keys
  'keysCreated' => int,       // Utworzone klucze API
  'keysSkipped' => int,       // Pominięci (plugin_blocked)
  'keysFailed' => int,        // Błędy podczas tworzenia
  'durationInSeconds' => int, // Czas wykonania
]
```

**Skip Reasons (1):**
- `plugin_blocked` - Plugin zatrzymał przez `stopPropagation()` w UserPterodactylApiKeyCreationRequestedEvent

**Decyzje implementacyjne:**
- ✅ **Pełna implementacja:** 6 eventów (3 process + 3 per-user) dla security-critical API keys
- ✅ **Stoppable pre-event:** Plugin może zablokować utworzenie klucza (security policy, validation)
- ✅ **Fail-safe approach:** Błąd dla jednego usera nie zatrzymuje całego procesu
- ✅ **Security:** `apiKeyIdentifier` w evencie (bez pełnego klucza API)
- ✅ **Per-user tracking:** Pełny audit trail dla każdego utworzonego klucza
- ✅ **Time tracking:** Duration w CompletedEvent

**Use Cases:**

✅ **Security & Audit Trail** - Per-user tracking:
  - Kto i kiedy otrzymał API key
  - Które operacje zostały zablokowane przez plugin
  - Błędy podczas tworzenia kluczy (diagnostyka)
  - Compliance tracking dla security-critical operations

✅ **Monitoring & Analytics** - Process-level tracking:
  - Ile użytkowników potrzebowało API keys
  - Success rate tworzenia kluczy
  - Czas wykonania całego procesu
  - Alerting gdy wiele błędów

✅ **Plugin Extensibility** - Stoppable pre-event:
  - Plugin może zablokować utworzenie klucza dla suspended users
  - Custom security policy enforcement
  - Validation rules (np. tylko verified emails)
  - Pre-creation hooks (logging, notifications)

✅ **Error Handling** - Fail-safe approach:
  - Błąd dla jednego usera nie blokuje pozostałych
  - Szczegółowe tracking błędów (failureReason)
  - Częściowe sukcesy (niektórzy dostają klucze mimo błędów u innych)

---

#### 8. DeleteOldLogsCommand ✅ UKOŃCZONA (2025-10-26)

**Komenda:** `app:delete-old-logs`
**Plik:** `src/Core/Command/DeleteOldLogsCommand.php`

**Zaimplementowane eventy (3):**

**Process-level events (3):**
1. **LogDeletionProcessStartedEvent** (post)
   - Lokalizacja: `src/Core/Event/Cli/DeleteOldLogs/LogDeletionProcessStartedEvent.php`
   - Payload: `startedAt`, `daysAfter`, `cutoffDate`, `context`
   - Kiedy: Na początku procesu usuwania logów

2. **LogDeletionProcessCompletedEvent** (post-commit)
   - Lokalizacja: `src/Core/Event/Cli/DeleteOldLogs/LogDeletionProcessCompletedEvent.php`
   - Payload: `daysAfter`, `cutoffDate`, `deletedLogs`, `deletedServerLogs`, `deletedEmailLogs`, `totalDeleted`, `durationInSeconds`, `completedAt`, `context`
   - Kiedy: Po zakończeniu całego procesu usuwania
   - Zawiera pełne statystyki procesu (wszystkie 3 typy logów)

3. **LogDeletionProcessFailedEvent** (error)
   - Lokalizacja: `src/Core/Event/Cli/DeleteOldLogs/LogDeletionProcessFailedEvent.php`
   - Payload: `failureReason`, `daysAfter` (nullable), `failedAt`, `context`
   - Kiedy: W catch() execute() lub przy invalid setting
   - Zawiera reason dla diagnostyki

**Flow:**
```
1. Odczyt ustawienia LOG_CLEANUP_DAYS_AFTER
2. Walidacja ustawienia
   → LogDeletionProcessFailedEvent (invalid setting) jeśli błędne
3. Obliczenie cutoff date (now - N days)
4. LogDeletionProcessStartedEvent
5. Usuwanie logów z 3 repozytoriów:
   a. LogRepository.deleteOldLogs(cutoffDate)
   b. ServerLogRepository.deleteOldLogs(cutoffDate)
   c. EmailLogRepository.deleteOldLogs(cutoffDate)
6. LogDeletionProcessCompletedEvent (success)
   → LogDeletionProcessFailedEvent (error) w przypadku wyjątku
```

**CLI Context:**
```php
[
  'source' => 'cli',
  'command' => 'app:delete-old-logs',
  'daysAfter' => 90,           // Dni z SettingEnum::LOG_CLEANUP_DAYS_AFTER
  'cutoffDate' => '2024-07-27 12:00:00', // Data graniczna
  'ip' => null,
  'userAgent' => 'Symfony CLI',
  'locale' => 'en',
]
```

**Stats Structure:**
```php
[
  'daysAfter' => int,           // Dni retention z ustawień
  'cutoffDate' => DateTimeImmutable, // Data graniczna
  'deletedLogs' => int,         // Usunięte logi główne
  'deletedServerLogs' => int,   // Usunięte server logs
  'deletedEmailLogs' => int,    // Usunięte email logs
  'totalDeleted' => int,        // Suma wszystkich usuniętych
  'durationInSeconds' => int,   // Czas wykonania
]
```

**Decyzje implementacyjne:**
- ✅ **Basic CLI implementation:** 3 eventy (process-level tylko)
- ✅ **No per-log-type events:** Stats breakdown w CompletedEvent
- ✅ **Setting validation:** Osobny FailedEvent dla invalid setting
- ✅ **3 log types:** logs, server_logs, email_logs w jednej operacji
- ✅ **Time tracking:** Duration w CompletedEvent

**Use Cases:**

✅ **Monitoring & Analytics** - Process-level tracking:
  - Ile logów usunięto (breakdown per type)
  - Czy cleanup działa regularnie
  - Czas wykonania operacji
  - Setting retention period

✅ **Compliance & Audit** - Compliance tracking:
  - Dokładna data cutoff dla każdej operacji
  - Wszystkie operacje czyszczenia w audit log
  - Tracking dla regulacji (GDPR, etc.)

✅ **Storage Management** - Capacity planning:
  - Analytics ile miejsca zwolniono
  - Tracking wzrostu logów
  - Planning retention policy

✅ **Alerting** - Error detection:
  - Monitoring błędów usuwania
  - Alert gdy setting invalid
  - Alert gdy proces trwa za długo

---

#### 9. CronJobScheduleCommand

**Komenda:** `app:cron-job-schedule`
**Plik:** `src/Core/Command/CronJobScheduleCommand.php`

**Proponowane eventy:**
```php
- CronJobScheduleExecutedEvent (per job)
- CronJobScheduleCompletedEvent (post)
- CronJobScheduleFailedEvent (error)
```

**Zastosowanie:**
- Monitoring - tracking wykonywania cron jobs
- Alerting - powiadomienia o błędach
- Analytics - statystyki wykonywania zadań
- Performance tracking

---

#### 10. Inne komendy (Utility)

**Lista:**
- `ConfigureSystemCommand.php` - `app:configure-system`
- `ConfigureDatabaseCommand.php` - `app:configure-database`
- `MakeThemeCommand.php` - `app:make-theme`
- `UpdateSystemCommand.php` - `app:update-system`
- `ShowMissingTranslationsCommand.php` - `app:show-missing-translations`

**Rekomendacja:**
Każda komenda powinna mieć minimum:
- StartedEvent (pre)
- CompletedEvent (post)
- FailedEvent (error)

---

## Brakujące Implementacje - Inne Kontrolery

### ~~1. Server Management Page~~ ✅ **ZAIMPLEMENTOWANE** (2025-10-22)

**Plik:** `src/Core/Controller/ServerController.php`

#### ~~Strona bez eventów:~~ ✅ Strona z eventami:

| Route | Akcja | Status |
|-------|-------|--------|
| `/server?id=XXX` | Strona zarządzania pojedynczym serwerem | ✅ Eventy zaimplementowane |

**Uwaga:** Kontroler ma eventy dla obu endpointów:
- ✅ `/servers` (lista) - ServersListAccessedEvent, ServersListDataLoadedEvent
- ✅ `/server` (szczegóły) - ServerManagementPageAccessedEvent, ServerManagementDataLoadedEvent

#### Zaimplementowane eventy:

```php
// GET /server?id=XXX
✅ ServerManagementPageAccessedEvent (post) - src/Core/Event/Server/
✅ ServerManagementDataLoadedEvent (post) - src/Core/Event/Server/
✅ ViewDataEvent (viewName='server_management') - ViewNameEnum::SERVER_MANAGEMENT
```

**Lokalizacja:**
- Eventy: `src/Core/Event/Server/`
- Kontroler: `src/Core/Controller/ServerController.php::server()`
- ViewNameEnum: `SERVER_MANAGEMENT` case dodany

**Payload eventów:**
- `ServerManagementPageAccessedEvent`: userId, serverId, serverPterodactylIdentifier, serverName, isOwner, isAdminView, context
- `ServerManagementDataLoadedEvent`: userId, serverId, serverPterodactylIdentifier, isInstalling, isSuspended, hasPermissions, loadedDataSections[], context

**Flow:**
```
GET /server?id=XXX
  → ServerManagementPageAccessedEvent (po walidacji serwera)
  → ServerDataService::getServerData() - pobieranie danych
  → ServerManagementDataLoadedEvent (z metadata loadedDataSections)
  → ViewDataEvent (pre-render)
```

**loadedDataSections metadata:**
Lista możliwych sekcji: `pterodactyl_server`, `allocations`, `backups`, `subusers`, `activity_logs`, `schedules`, `server_details`, `server_variables`, `docker_images`, `available_nest_eggs`

#### Zastosowanie dla pluginów:
- **Analytics** - tracking użycia strony zarządzania ✅
- **Performance tracking** - monitoring ładowania danych ✅
- **Custom widgets** - pluginy mogą dodać własne sekcje na podstawie loadedDataSections ✅
- **Personalizacja** - customizacja interfejsu zarządzania ✅
- **Usage analytics** - które funkcje są używane (backups, schedules, databases) ✅

---

### 2. First Configuration

**Plik:** `src/Core/Controller/FirstConfigurationController.php`

#### Strony bez eventów:

| Route | Metoda | Akcja |
|-------|--------|-------|
| `/first-configuration` | GET | Pierwsza konfiguracja systemu |
| `/first-configuration/validate-step` | POST | Walidacja kroku |
| `/first-configuration/finish` | POST | Zakończenie konfiguracji |

#### Proponowane eventy:

```php
// GET /first-configuration
- FirstConfigurationPageAccessedEvent (post)
- FirstConfigurationDataLoadedEvent (post)

// POST /first-configuration/validate-step
- FirstConfigurationStepValidationRequestedEvent (pre)
- FirstConfigurationStepValidatedEvent (post)

// POST /first-configuration/finish
- FirstConfigurationCompletionRequestedEvent (pre, stoppable)
- FirstConfigurationCompletedEvent (post-commit)
- FirstConfigurationFailedEvent (error)
```

#### Zastosowanie dla pluginów:
- **Onboarding tracking** - analytics procesu konfiguracji
- **Custom steps** - pluginy mogą dodać własne kroki
- **Validation** - dodatkowe walidacje przed zakończeniem
- **Integration** - automatyczna konfiguracja pluginów

---

### 3. Default Controller

**Plik:** `src/Core/Controller/DefaultController.php`

#### Route:

| Route | Akcja |
|-------|-------|
| `/` | Redirect do `/login` |

**Rekomendacja:**
Nie wymaga eventów - to tylko prosty redirect.

---

## Priorytetyzacja Implementacji

### 🔴 PRIORYTET 1 - KRYTYCZNY (Najważniejsze dla użytkowników)

#### Dlaczego krytyczne?
Te operacje są **najczęściej wykonywane przez użytkowników** i mają **największy wpływ biznesowy**.

#### Lista:

1. **~~Server Configuration API~~** ✅ **ZAIMPLEMENTOWANE** (2025-10-22)
   - ~~`ServerConfigurationController.php`~~
   - ✅ Auto-renewal toggle - krytyczne dla retention
   - ✅ Reinstall - krytyczna operacja wymagająca audit trail
   - ✅ Startup variables/options - często używane
   - ✅ Server details update - często używane

2. **Server Backups API** (`ServerBackupController.php`)
   - Create/Restore backup - krytyczne operacje bezpieczeństwa
   - Wymaga audit trail i notifications

3. **Server Users API** (`ServerUserController.php`)
   - Dodawanie/usuwanie dostępu - krytyczne dla bezpieczeństwa
   - Wymaga security notifications

4. **~~Server Management Page~~** ✅ **ZAIMPLEMENTOWANE** (2025-10-22)
   - ~~`/server?id=XXX`~~
   - ✅ `ServerManagementPageAccessedEvent`
   - ✅ `ServerManagementDataLoadedEvent`
   - ✅ `ViewDataEvent` (SERVER_MANAGEMENT)

5. **Server Databases API** (`ServerDatabaseController.php`)
   - Create/Delete database - krytyczne operacje
   - Password rotation - operacja bezpieczeństwa

---

### 🟡 PRIORYTET 2 - WYSOKI (Ważne dla operacji)

#### Dlaczego wysokie?
Operacje **często wykonywane** lub **krytyczne dla zarządzania**.

#### Lista:

6. **CLI - Suspend Unpaid Servers** (`SuspendUnpaidServersCommand`)
   - Automatyczne zawieszanie - core business logic
   - Wymaga notifications dla użytkowników

7. **CLI - Delete Inactive Servers** (`DeleteInactiveServersCommand`)
   - Automatyczne czyszczenie - core business logic
   - Wymaga backupów i notifications

8. **CLI - Sync Servers** (`PterocaSyncServersCommand`)
   - Synchronizacja z Pterodactyl - krytyczna dla spójności

9. **~~Server Network API~~** ✅ **ZAIMPLEMENTOWANE** (2025-10-23)
   - ~~Zarządzanie alokacjami - często używane~~

10. **~~Server Schedules API~~** ✅ **ZAIMPLEMENTOWANE** (2025-10-23)
    - ~~Harmonogramy zadań - popularna funkcjonalność~~

---

### ~~🟢 PRIORYTET 3 - ŚREDNI (Strony admina i operacje specjalne)~~ ✅ **UKOŃCZONE**

#### ~~Dlaczego średnie?~~
~~Operacje **wykonywane rzadziej** lub **już częściowo pokryte przez eventy CRUD**.~~

#### ~~Lista:~~

11. **~~Admin Overview~~** ✅ **ZAIMPLEMENTOWANE** (2025-10-21)
    - ~~`OverviewController.php`~~
    - ✅ `AdminOverviewAccessedEvent`
    - ✅ `AdminOverviewDataLoadedEvent`
    - ✅ `ViewDataEvent` (ADMIN_OVERVIEW)

12. **~~Product Copy~~** ✅ **ZAIMPLEMENTOWANE** (2025-10-21)
    - ~~`ProductCrudController::copyProduct()`~~
    - ✅ `ProductCopyRequestedEvent` (stoppable)
    - ✅ `ProductCopiedEvent`

13. **~~Admin CRUD Controllers~~** ✅ **JUŻ ZAIMPLEMENTOWANE**
    - ~~UserCrudController~~ ✅ Ma eventy CRUD
    - ~~ServerCrudController~~ ✅ Ma eventy CRUD
    - ~~ProductCrudController~~ ✅ Ma eventy CRUD
    - ~~VoucherCrudController~~ ✅ Ma eventy CRUD
    - ~~Wszystkie inne CRUD~~ ✅ Mają eventy CRUD przez `AbstractPanelController`

---

### 🔵 PRIORYTET 4 - NISKI (Pozostałe)

#### Dlaczego niskie?
Operacje **rzadko wykonywane** lub **mało krytyczne**.

#### Lista:

16. **~~Server Details API~~** ✅ **ZAIMPLEMENTOWANE** (2025-10-23)
    - ~~Read-only endpoint - niski priorytet~~
    - ✅ `ServerDetailsRequestedEvent`
    - ✅ `ServerDetailsLoadedEvent`
    - ✅ `ServerWebsocketTokenRequestedEvent`
    - ✅ `ServerWebsocketTokenGeneratedEvent`
    - ✅ `ServerEulaAcceptanceRequestedEvent` (stoppable)
    - ✅ `ServerEulaAcceptedEvent`
    - ✅ `ServerEulaAcceptanceFailedEvent`

17. **~~Voucher Redeem API~~** ✅ **ZAIMPLEMENTOWANE** (2025-10-22)
    - ~~`VoucherController.php`~~
    - ✅ `VoucherRedemptionRequestedEvent` (stoppable)
    - ✅ `VoucherRedeemedEvent`
    - ✅ `VoucherRedemptionFailedEvent`

18. **First Configuration** (`FirstConfigurationController.php`)
    - Wykonywane raz podczas instalacji

19. **Admin API** (`VersionController`, `TemplateController`)
    - Utility endpoints

20. **Eggs API** (`EggsController.php`)
    - Read-only utility endpoint

21. **CLI Utility Commands**
    - ConfigureSystemCommand
    - ConfigureDatabaseCommand
    - MakeThemeCommand
    - UpdateSystemCommand
    - ShowMissingTranslationsCommand
    - DeleteOldLogsCommand
    - CronJobScheduleCommand

22. **~~Pozostałe CRUD Controllers~~** ✅ **JUŻ ZAIMPLEMENTOWANE**
    - ~~CategoryCrudController~~ ✅ Ma eventy CRUD
    - ~~PaymentCrudController~~ ✅ Ma eventy CRUD
    - ~~LogCrudController~~ ✅ Ma eventy CRUD
    - ~~EmailLogCrudController~~ ✅ Ma eventy CRUD
    - ~~ServerProductCrudController~~ ✅ Ma eventy CRUD
    - ~~ServerLogCrudController~~ ✅ Ma eventy CRUD
    - ~~VoucherUsageCrudController~~ ✅ Ma eventy CRUD
    - ~~Settings CRUD Controllers~~ ✅ Wszystkie mają eventy CRUD

---

## Rekomendacje Implementacyjne

### 1. Wzorzec Eventów dla API

Dla każdego endpoint API zalecamy **minimum 3 eventy**:

```php
// 1. PRE-EVENT (przed operacją)
- {Operation}RequestedEvent (stoppable)
  - Payload: userId, requestData, context
  - Zastosowanie: Validation, rate limiting, veto

// 2. POST-EVENT (po operacji)
- {Operation}CompletedEvent (post-commit)
  - Payload: userId, result, context
  - Zastosowanie: Notifications, analytics, integrations

// 3. ERROR-EVENT (przy błędzie)
- {Operation}FailedEvent (error)
  - Payload: userId, error, stage, context
  - Zastosowanie: Monitoring, alerting, retry logic
```

**Przykład:**
```php
// POST /panel/api/server/{id}/backup/create
1. ServerBackupCreationRequestedEvent (pre, stoppable)
2. ServerBackupCreatedEvent (post-commit)
3. ServerBackupCreationFailedEvent (error)
```

---

### 2. Wzorzec Eventów dla CRUD

Dla operacji CRUD w panelu admina zalecamy:

```php
// CREATE
- {Entity}CreationRequestedEvent (pre, stoppable)
- {Entity}CreatedEvent (post-commit)

// UPDATE
- {Entity}UpdateRequestedEvent (pre, stoppable)
- {Entity}UpdatedEvent (post-commit)

// DELETE
- {Entity}DeletionRequestedEvent (pre, stoppable)
- {Entity}DeletedEvent (post-commit)
```

**Przykład:**
```php
// UserCrudController
1. AdminUserCreationRequestedEvent
2. AdminUserCreatedEvent
3. AdminUserUpdateRequestedEvent
4. AdminUserUpdatedEvent
5. AdminUserDeletionRequestedEvent
6. AdminUserDeletedEvent
```

---

### 3. Wzorzec Eventów dla CLI

Dla komend CLI zalecamy:

```php
// 1. PROCESS START
- {Command}ProcessStartedEvent
  - Payload: startTime, context
  - Zastosowanie: Monitoring start

// 2. ITEM PROCESSED (dla batch operations)
- {Item}ProcessedEvent (per item)
  - Payload: itemId, result, context
  - Zastosowanie: Progress tracking

// 3. PROCESS COMPLETED
- {Command}ProcessCompletedEvent
  - Payload: totalProcessed, duration, stats
  - Zastosowanie: Monitoring, analytics

// 4. PROCESS FAILED
- {Command}ProcessFailedEvent
  - Payload: error, stage, stats
  - Zastosowanie: Alerting, error tracking
```

**Przykład:**
```php
// SuspendUnpaidServersCommand
1. SuspendUnpaidServersProcessStartedEvent
2. ServerSuspendedForNonPaymentEvent (per server)
3. SuspendUnpaidServersProcessCompletedEvent
4. SuspendUnpaidServersProcessFailedEvent (jeśli błąd)
```

---

### 4. Context w Eventach

Każdy event powinien zawierać **standardowy context**:

```php
[
    'ip' => string,
    'userAgent' => string,
    'locale' => string,
    'userId' => ?int,
    'isAdmin' => bool,
    'source' => string, // 'web', 'api', 'cli'
]
```

---

### 5. Naming Convention

#### Dla API:
```
{Entity}{Action}{Stage}Event

Przykłady:
- ServerBackupCreationRequestedEvent
- ServerDatabasePasswordRotatedEvent
- ServerAllocationDeletedEvent
```

#### Dla CRUD:
```
Admin{Entity}{Action}{Stage}Event

Przykłady:
- AdminUserCreatedEvent
- AdminProductUpdatedEvent
- AdminServerDeletedEvent
```

#### Dla CLI:
```
{Command}{Stage}Event

Przykłady:
- SuspendUnpaidServersProcessStartedEvent
- ServerMigratedEvent
- DataSyncProcessCompletedEvent
```

---

### 6. Event Payload - Best Practices

1. **Używaj immutable properties:**
   ```php
   public function __construct(
       private readonly int $userId,
       private readonly array $data,
   ) {}
   ```

2. **Dodaj metody pomocnicze:**
   ```php
   public function getUserId(): int
   {
       return $this->userId;
   }
   ```

3. **Standardowe pola dla wszystkich eventów:**
   ```php
   - eventId: string (UUID v4)
   - occurredAt: DateTimeImmutable
   - schemaVersion: string (domyślnie 'v1')
   ```

4. **Dla stoppable events:**
   ```php
   use Symfony\Contracts\EventDispatcher\Event;
   use Psr\EventDispatcher\StoppableEventInterface;

   class MyEvent extends Event implements StoppableEventInterface
   {
       use StoppableEventTrait;

       private bool $rejected = false;
       private ?string $rejectionReason = null;

       public function setRejected(bool $rejected, ?string $reason = null): void
       {
           $this->rejected = $rejected;
           $this->rejectionReason = $reason;
           if ($rejected) {
               $this->stopPropagation();
           }
       }
   }
   ```

---

### 7. Delegacja do Serwisów

**Kontrolery powinny emitować tylko "intent events"**, a **serwisy powinny emitować "domain events"**.

#### Przykład z CartController (już zaimplementowane):

**Kontroler:**
```php
// CartController::buy()
$this->dispatchEvent(new CartBuyRequestedEvent(...)); // Intent
CreateServerService::createServer(...); // Delegacja
```

**Serwis:**
```php
// CreateServerService::createServer()
$this->dispatchEvent(new ServerPurchaseValidatedEvent(...));
$this->dispatchEvent(new ServerAboutToBeCreatedEvent(...));
$this->dispatchEvent(new ServerCreatedOnPterodactylEvent(...));
// ... etc
```

**Zalety:**
- ✅ Separation of concerns
- ✅ Reużywalność serwisów
- ✅ Konsystencja eventów (bez względu skąd wywołano serwis)

---

### 8. Testing Events

Każdy event powinien mieć testy:

```php
public function testServerBackupCreationRequestedEventIsDispatchedBeforeBackupCreation(): void
{
    $eventDispatcher = $this->createMock(EventDispatcherInterface::class);
    $eventDispatcher->expects($this->once())
        ->method('dispatch')
        ->with($this->isInstanceOf(ServerBackupCreationRequestedEvent::class));

    // ... test logic
}

public function testServerBackupCreationCanBeVetoed(): void
{
    // Subscriber który zatrzymuje event
    $subscriber = new class implements EventSubscriberInterface {
        public function onBackupCreationRequested(ServerBackupCreationRequestedEvent $event): void
        {
            $event->stopPropagation();
            $event->setRejected(true, 'Test veto');
        }

        public static function getSubscribedEvents(): array
        {
            return [ServerBackupCreationRequestedEvent::class => 'onBackupCreationRequested'];
        }
    };

    // ... test logic, powinien rzucić wyjątek lub zwrócić błąd
}
```

---

### 9. Dokumentacja Eventów

Każdy nowy event powinien być dodany do `EVENT_DRIVEN_ARCHITECTURE.md` z:

1. **Nazwa eventu**
2. **Kiedy jest emitowany**
3. **Payload (pola eventu)**
4. **Zastosowanie** (dla pluginów)
5. **Czy jest stoppable**
6. **Przykład użycia w pluginie**

---

### 10. Migration Path

Sugerowana kolejność implementacji:

#### ~~Faza 1: API - Krytyczne operacje (1-2 tygodnie)~~ ✅ **UKOŃCZONA** (2025-10-22)
- ✅ Server Configuration API (ukończone 2025-10-22)
- ✅ Server Backups API (ukończone 2025-10-22)
- ✅ Server Users API (ukończone 2025-10-22)
- ✅ Server Databases API (ukończone 2025-10-22)

#### ~~Faza 2: API - Pozostałe (1 tydzień)~~ ✅ **UKOŃCZONA** (2025-10-23)
- ✅ Server Network API (ukończone 2025-10-23)
- ✅ Server Schedules API (ukończone 2025-10-23)
- ✅ Server Details API (ukończone 2025-10-23)
- ✅ Voucher API (ukończone 2025-10-22)

#### ~~Faza 3: User-facing pages + Admin operations (2-3 dni)~~ ✅ **UKOŃCZONA** (2025-10-21 - 2025-10-22)
- ✅ Server Management Page (ukończone 2025-10-22)
- ✅ Admin Overview (ukończone 2025-10-21)
- ✅ Product Copy - operacja specjalna (ukończone 2025-10-21)
- ✅ Voucher API (ukończone 2025-10-22)

#### ~~Faza 4: CLI - Critical (1 tydzień)~~ ✅ **UKOŃCZONA** (2025-10-25)
- ✅ SuspendUnpaidServersCommand (ukończone 2025-10-25)
- ✅ DeleteInactiveServersCommand (ukończone 2025-10-25)
- ✅ PterocaSyncServersCommand (ukończone 2025-10-25)

#### ~~Faza 5: Admin CRUD (1 tydzień)~~ ✅ **UKOŃCZONA** (przez AbstractPanelController)
- ~~User CRUD~~ ✅ Eventy CRUD automatyczne
- ~~Server CRUD~~ ✅ Eventy CRUD automatyczne
- ~~Product CRUD~~ ✅ Eventy CRUD automatyczne + Product Copy
- ~~Voucher CRUD~~ ✅ Eventy CRUD automatyczne

#### ~~Faza 6: CLI - Utility~~ ✅ **CAŁKOWICIE UKOŃCZONA** (2025-10-26)

**Tier 1: Wysokie priorytety (produkcyjne operacje)**

1. **PterodactylMigrateServersCommand** (`pterodactyl:migrate-servers`) ⭐⭐⭐⭐⭐
   - Migracja serwerów z Pterodactyl do istniejących kont użytkowników w PteroCA
   - CLI opcje: `--limit`, `--dry-run`
   - Handler: MigrateServersHandler (304 linie, bardzo złożony)
   - Szacowane eventy: ~9-11 (process-level + per-server + optional)
   - Czas: 2-3h

2. **DeleteOldLogsCommand** (`app:delete-old-logs`) ⭐⭐⭐
   - Usuwanie logów starszych niż N dni (logs, server_logs, email_logs)
   - Brak CLI opcji (czyta z SettingEnum::LOG_CLEANUP_DAYS_AFTER)
   - Szacowane eventy: ~3-4
   - Czas: 1h

3. **SynchronizeDataCommand** (`app:synchronize-data`) ⭐⭐
   - Synchronizacja danych (tworzenie Pterodactyl API keys dla użytkowników)
   - Handler: SynchronizeDataHandler (prosty, 34 linie)
   - Szacowane eventy: ~5-6
   - Czas: 1h

**Tier 2: Niskie priorytety (narzędzia admin)**

4. **CreateNewUserCommand** (`app:create-new-user`) ⭐
   - Tworzenie nowego użytkownika (PteroCA + Pterodactyl account + API key)
   - Handler: CreateNewUserHandler
   - Szacowane eventy: ~3-4
   - Czas: 1h

5. **ChangeUserPasswordCommand** (`app:change-user-password`) ⭐
   - Zmiana hasła użytkownika (PteroCA + Pterodactyl)
   - Handler: ChangeUserPasswordHandler
   - Szacowane eventy: ~3
   - Czas: 45min

**Poza zakresem EDA (dev/install tools):**
- UpdateSystemCommand (dev tool - update systemu)
- MakeThemeCommand (dev tool - generator motywów)
- ConfigureSystemCommand (instalator - konfiguracja początkowa)
- ConfigureDatabaseCommand (instalator - setup bazy)
- ShowMissingTranslationsCommand (dev tool - YAML translations)
- CronJobScheduleCommand (wrapper - uruchamia inne komendy)

**Razem Tier 1+2:** ~23-28 eventów, około 6-7h pracy

#### ~~Faza 7: Pozostałe CRUD (1 tydzień)~~ ✅ **UKOŃCZONA** (przez AbstractPanelController)
- ~~Category, Payment, Logs, Settings CRUD~~ ✅ Eventy CRUD automatyczne

#### ~~Faza 8: Nice-to-have~~ ❌ **POZA ZAKRESEM EDA** (2025-10-26)

**Analiza i decyzja:** Wszystkie elementy Fazy 8 są **read-only utility endpoints** bez business value dla EDA.

**Powody wykluczenia:**

1. **First Configuration** (`FirstConfigurationController.php`)
   - ❌ Jednorazowa operacja podczas instalacji
   - ❌ Brak pluginów w tym momencie
   - ❌ Nie powinno być żadnych ingerencji
   - **Decyzja:** Poza zakresem EDA

2. **Admin API** (`VersionController`, `TemplateController`)
   - ❌ Read-only GET endpoints (brak state changes)
   - ❌ Utility functions bez business logic
   - ❌ Overhead > Value (caching lepiej na innym poziomie)
   - ❌ Analytics można robić przez application logs
   - **Decyzja:** Poza zakresem EDA

3. **Eggs API** (`EggsController.php`)
   - ❌ Read-only GET endpoint (lista eggs z Pterodactyl)
   - ❌ Brak side effects, brak state changes
   - ❌ Caching lepiej przez HTTP headers/Redis/service layer
   - ❌ Plugin extensibility value: minimalny
   - **Decyzja:** Poza zakresem EDA

**Konsekwencje:**
- ✅ Oszczędność: ~6 eventów (nie trzeba implementować)
- ✅ Focus na API Controllers z prawdziwą business value
- ✅ Consistency: EDA tylko dla business-critical i state-changing operations

---

## Podsumowanie

### Statystyki:

- **✅ Już zaimplementowane:**
  - **16 obszarów funkcjonalnych** (Rejestracja, Logowanie, Dashboard, Store, Cart, Balance, itp.)
  - **55+ eventów domenowych** dla procesów biznesowych
  - **11 eventów CRUD** dla panelu admina (`AbstractPanelController`)
  - **3 eventy generyczne** (FormBuildEvent, FormSubmitEvent, ViewDataEvent)
  - **✨ 4 nowe eventy (2025-10-21):**
    - ✅ `AdminOverviewAccessedEvent` - Admin Overview
    - ✅ `AdminOverviewDataLoadedEvent` - Admin Overview
    - ✅ `ProductCopyRequestedEvent` - Product Copy (stoppable)
    - ✅ `ProductCopiedEvent` - Product Copy
  - **✨ 3 nowe eventy (2025-10-22 rano):**
    - ✅ `VoucherRedemptionRequestedEvent` - Voucher API (stoppable)
    - ✅ `VoucherRedeemedEvent` - Voucher API
    - ✅ `VoucherRedemptionFailedEvent` - Voucher API
  - **✨ 2 nowe eventy (2025-10-22 popołudnie):**
    - ✅ `ServerManagementPageAccessedEvent` - Server Management Page
    - ✅ `ServerManagementDataLoadedEvent` - Server Management Page
  - **✨ 11 nowych eventów (2025-10-22 wieczór):**
    - ✅ `ServerStartupVariableUpdateRequestedEvent` - Server Configuration API (stoppable)
    - ✅ `ServerStartupVariableUpdatedEvent` - Server Configuration API
    - ✅ `ServerStartupOptionUpdateRequestedEvent` - Server Configuration API (stoppable)
    - ✅ `ServerStartupOptionUpdatedEvent` - Server Configuration API
    - ✅ `ServerDetailsUpdateRequestedEvent` - Server Configuration API (stoppable)
    - ✅ `ServerDetailsUpdatedEvent` - Server Configuration API
    - ✅ `ServerReinstallRequestedEvent` - Server Configuration API (stoppable)
    - ✅ `ServerReinstallInitiatedEvent` - Server Configuration API
    - ✅ `ServerReinstalledEvent` - Server Configuration API
    - ✅ `ServerAutoRenewalToggleRequestedEvent` - Server Configuration API (stoppable)
    - ✅ `ServerAutoRenewalToggledEvent` - Server Configuration API
  - **✨ 6 nowych eventów CLI (2025-10-25 - SuspendUnpaidServers):**
    - ✅ `SuspendUnpaidServersProcessStartedEvent` - CLI SuspendUnpaidServers (process-level)
    - ✅ `SuspendUnpaidServersProcessCompletedEvent` - CLI SuspendUnpaidServers (process-level)
    - ✅ `SuspendUnpaidServersProcessFailedEvent` - CLI SuspendUnpaidServers (process-level)
    - ✅ `ServerSuspendedForNonPaymentEvent` - CLI SuspendUnpaidServers (per-server)
    - ✅ `ServerAutoRenewedEvent` - CLI SuspendUnpaidServers (per-server)
    - ✅ `ServerSuspensionFailedEvent` - CLI SuspendUnpaidServers (per-server)
  - **✨ 6 nowych eventów CLI (2025-10-25 - DeleteInactiveServers):**
    - ✅ `DeleteInactiveServersProcessStartedEvent` - CLI DeleteInactiveServers (process-level)
    - ✅ `DeleteInactiveServersProcessCompletedEvent` - CLI DeleteInactiveServers (process-level)
    - ✅ `DeleteInactiveServersProcessFailedEvent` - CLI DeleteInactiveServers (process-level)
    - ✅ `InactiveServerDeletionRequestedEvent` - CLI DeleteInactiveServers (per-server, stoppable)
    - ✅ `InactiveServerDeletedEvent` - CLI DeleteInactiveServers (per-server)
    - ✅ `InactiveServerDeletionFailedEvent` - CLI DeleteInactiveServers (per-server)
  - **RAZEM:** ~101+ eventów + automatyczne eventy dla 13+ kontrolerów CRUD

- **❌ Do zaimplementowania:**
  - **API Controllers:** 8 kontrolerów (~36+ eventów) ~~9 kontrolerów (~47+ eventów)~~
  - **User Pages:** 1 strona (~3+ eventy) ~~2 strony~~
  - ~~**CLI Commands:**~~ ✅ **UKOŃCZONE** (Faza 4 + Faza 6, wszystkie 8 komend - 41 eventów CLI)
  - ~~**Faza 8 (Nice-to-have):**~~ ❌ **POZA ZAKRESEM EDA** (read-only utilities)
  - ~~**Admin Pages:**~~ ✅ **UKOŃCZONE** (Admin Overview - 2025-10-21)
  - ~~**Operacje specjalne:**~~ ✅ **UKOŃCZONE** (Product Copy - 2025-10-21)
  - ~~**Voucher API:**~~ ✅ **UKOŃCZONE** (Voucher Redeem - 2025-10-22)
  - ~~**Server Management Page:**~~ ✅ **UKOŃCZONE** (Server Management - 2025-10-22)
  - ~~**Server Configuration API:**~~ ✅ **UKOŃCZONE** (Server Configuration - 2025-10-22)
  - ~~**SuspendUnpaidServersCommand CLI:**~~ ✅ **UKOŃCZONE** (SuspendUnpaidServers - 2025-10-25)
  - ~~**DeleteInactiveServersCommand CLI:**~~ ✅ **UKOŃCZONE** (DeleteInactiveServers - 2025-10-25)
  - ~~**PterocaSyncServersCommand CLI:**~~ ✅ **UKOŃCZONE** (PterocaSyncServers - 2025-10-26)
  - ~~**Faza 4 (CLI - Critical):**~~ ✅ **UKOŃCZONE** (3 critical CLI commands - 2025-10-26)
  - ~~**PterodactylMigrateServersCommand CLI:**~~ ✅ **UKOŃCZONE** (MigrateServers - 2025-10-26)
  - ~~**DeleteOldLogsCommand CLI:**~~ ✅ **UKOŃCZONE** (DeleteOldLogs - 2025-10-26)
  - ~~**SynchronizeDataCommand CLI:**~~ ✅ **UKOŃCZONE** (SynchronizeData - 2025-10-26)
  - ~~**CreateNewUserCommand CLI:**~~ ✅ **UKOŃCZONE** (CreateUser - 2025-10-26)
  - ~~**ChangeUserPasswordCommand CLI:**~~ ✅ **UKOŃCZONE** (ChangePassword - 2025-10-26)
  - ~~**Faza 6 (CLI - Utility):**~~ ✅ **CAŁKOWICIE UKOŃCZONA** (Tier 1+2, wszystkie 5 komend - 2025-10-26)
  - ~~**Faza 8 (Nice-to-have):**~~ ❌ **POZA ZAKRESEM EDA** (read-only utilities - 2025-10-26)
  - **RAZEM pozostałych:** ~39 nowych eventów (API Controllers ~36, User Pages ~3)
  - **Oszczędność:** -27 eventów (DeleteOldLogs -3, SynchronizeData -6, CreateNewUser -3, ChangePassword -3, CLI poza zakresem -6, Faza 8 -6)
  - **Ukończono:** +61 eventów od 2025-10-21 (Faza 4: +19, Faza 6: +22, User-facing: +20)

**Zmiana po analizie AbstractPanelController:**
- ~~30+ eventów dla Admin CRUD~~ → ✅ **Już zaimplementowane w AbstractPanelController**
- **Oszczędność:** ~30 eventów nie trzeba implementować!

**Zmiana po implementacji Admin Overview + Product Copy (2025-10-21):**
- ~~Admin Pages + Operacje specjalne~~ → ✅ **Ukończone!**
- **Postęp:** +4 eventy zaimplementowane! 🎉

**Zmiana po implementacji Voucher API (2025-10-22 rano):**
- ~~Voucher Redeem API~~ → ✅ **Ukończone!**
- **Postęp:** +3 eventy zaimplementowane! 🎉
- **Łącznie od 2025-10-21:** +7 nowych eventów!

**Zmiana po implementacji Server Management Page (2025-10-22 popołudnie):**
- ~~Server Management Page~~ → ✅ **Ukończone!**
- **Postęp:** +2 eventy zaimplementowane! 🎉
- **Łącznie od 2025-10-21:** +9 nowych eventów! 🎊

**Zmiana po implementacji Server Configuration API (2025-10-22 wieczór):**
- ~~Server Configuration API~~ → ✅ **Ukończone!**
- **Postęp:** +11 eventów zaimplementowanych (5 endpointów, 11 eventów)! 🎉
- **Łącznie od 2025-10-21:** +20 nowych eventów! 🎊🎊
- **Priorytet 1 (Krytyczny):** Częściowo ukończony! Server Configuration API to jeden z najważniejszych API!

**Zmiana po implementacji SuspendUnpaidServersCommand CLI (2025-10-25):**
- ~~SuspendUnpaidServersCommand CLI~~ → ✅ **Ukończone!**
- **Postęp:** +6 eventów zaimplementowanych (3 process-level + 3 per-server)! 🎉
- **Nowe narzędzia:**
  - ✅ `AbstractDomainEvent` - zaktualizowany z opcjonalnym `eventId`
  - ✅ `EventContextService::buildCliContext()` - CLI context builder
- **Łącznie od 2025-10-21:** +26 nowych eventów! 🎊🎊🎊
- **Faza 4 (CLI - Critical):** Rozpoczęta! Pierwsze CLI command z pełnym EDA! 🚀

**Zmiana po implementacji DeleteInactiveServersCommand CLI (2025-10-25):**
- ~~DeleteInactiveServersCommand CLI~~ → ✅ **Ukończone!**
- **Postęp:** +6 eventów zaimplementowanych (3 process-level + 3 per-server)! 🎉
- **Kluczowe feature:**
  - ✅ Stoppable pre-event dla backup automation (`InactiveServerDeletionRequestedEvent`)
  - ✅ Pełne dane czasowe (expiredAt, deletedAt, daysAfterExpiration)
  - ✅ Stat 'skipped' dla serwerów zablokowanych przez pluginy
- **Łącznie od 2025-10-21:** +32 nowych eventów! 🎊🎊🎊🎊
- **Faza 4 (CLI - Critical):** 2/3 ukończone! 🚀🚀

**Zmiana po implementacji PterocaSyncServersCommand CLI (2025-10-26):**
- ~~PterocaSyncServersCommand CLI~~ → ✅ **Ukończone!**
- **Postęp:** +7 eventów zaimplementowanych (3 process-level + 4 per-server)! 🎉
- **Kluczowe feature:**
  - ✅ Stoppable pre-event dla backup automation (`OrphanedServerFoundEvent`)
  - ✅ Wsparcie dla dry-run mode z oddzielną obsługą w eventach
  - ✅ Wsparcie dla auto mode (brak interakcji użytkownika)
  - ✅ 3 powody skip: `plugin_blocked`, `user_declined`, `dry_run`
  - ✅ Pełne statystyki (5 metryk): pterodactylServersFound, orphanedServersFound, orphanedServersDeleted, orphanedServersSkipped, orphanedServersFailed
- **Łącznie od 2025-10-21:** +39 nowych eventów! 🎊🎊🎊🎊🎊
- **Faza 4 (CLI - Critical):** ✅ **UKOŃCZONA!** Wszystkie 3 critical CLI commands gotowe! 🚀🚀🚀

**Zmiana po implementacji PterodactylMigrateServersCommand CLI (2025-10-26):**
- ~~PterodactylMigrateServersCommand CLI~~ → ✅ **Ukończone!**
- **Postęp:** +7 eventów zaimplementowanych (3 process-level + 4 per-server)! 🎉
- **Kluczowe feature:**
  - ✅ Stoppable pre-event dla backup automation (`ServerMigrationRequestedEvent`)
  - ✅ 5 skip reasons: `already_exists`, `owner_not_found`, `user_declined`, `plugin_blocked`, `dry_run`
  - ✅ User interaction tracking: duration i price w `ServerMigratedEvent`
  - ✅ Fail-safe approach: błąd pojedynczego serwera nie zatrzymuje całego procesu
  - ✅ 3 encje w jednej transakcji: Server + ServerProduct + ServerProductPrice
  - ✅ Pełne statystyki (6 metryk): pterodactylServersFound, pterodactylUsersFound, serversAlreadyExisting, serversMigrated, serversSkipped, serversFailed
- **Łącznie od 2025-10-21:** +46 nowych eventów! 🎊🎊🎊🎊🎊🎊
- **Faza 6 (CLI - Utility):** Rozpoczęta! Pierwsza komenda z Tier 1! 🚀

**Zmiana po implementacji DeleteOldLogsCommand CLI (2025-10-26):**
- ~~DeleteOldLogsCommand CLI~~ → ✅ **Ukończone!**
- **Postęp:** +3 eventy zaimplementowane (3 process-level)! 🎉
- **Kluczowe feature:**
  - ✅ Basic CLI implementation (3 eventy, bez per-log-type events)
  - ✅ Setting validation z osobnym FailedEvent
  - ✅ 3 log types: logs, server_logs, email_logs w jednej operacji
  - ✅ Pełne statystyki (breakdown per type): deletedLogs, deletedServerLogs, deletedEmailLogs, totalDeleted
  - ✅ Time tracking z duration w CompletedEvent
  - ✅ Compliance tracking (GDPR, audit log)
- **Łącznie od 2025-10-21:** +49 nowych eventów! 🎊🎊🎊🎊🎊🎊
- **Faza 6 (CLI - Utility) Tier 1:** Postęp! 2/3 komend Tier 1 ukończone! 🚀

**Zmiana po implementacji SynchronizeDataCommand CLI (2025-10-26):**
- ~~SynchronizeDataCommand CLI~~ → ✅ **Ukończone!**
- **Postęp:** +6 eventów zaimplementowanych (3 process-level + 3 per-user)! 🎉
- **Kluczowe feature:**
  - ✅ Pełna implementacja (6 eventów) dla security-critical API keys
  - ✅ Stoppable pre-event dla security policy enforcement (`UserPterodactylApiKeyCreationRequestedEvent`)
  - ✅ Fail-safe approach: błąd dla jednego usera nie zatrzymuje całego procesu
  - ✅ Per-user tracking: pełny audit trail dla każdego utworzonego klucza
  - ✅ Security: `apiKeyIdentifier` w evencie (bez pełnego klucza API)
  - ✅ Pełne statystyki (4 metryki): usersWithoutKeys, keysCreated, keysSkipped, keysFailed
  - ✅ Skip reason: `plugin_blocked` dla custom validation
- **Łącznie od 2025-10-21:** +55 nowych eventów! 🎊🎊🎊🎊🎊🎊🎊
- **Faza 6 (CLI - Utility) Tier 1:** ✅ **UKOŃCZONA!** Wszystkie 3 komendy Tier 1 gotowe! 🚀🚀🚀

**Zmiana po implementacji CreateNewUserCommand CLI (2025-10-26):**
- ~~CreateNewUserCommand CLI~~ → ✅ **Ukończone!**
- **Postęp:** +3 eventy zaimplementowane (3 process-level)! 🎉
- **Kluczowe feature:**
  - ✅ Basic CLI implementation (3 eventy, admin tool Tier 2)
  - ✅ Comprehensive error tracking: wszystkie exception types (RuntimeException, ValidationException, CouldNotCreatePterodactylClientApiKeyException)
  - ✅ Rollback tracking: dedykowany failureReason jeśli rollback fails
  - ✅ Conditional API key: flags hasApiKey + createdWithoutApiKey
  - ✅ Multi-step tracking: hasPterodactylAccount + hasApiKey pokazują progress
  - ✅ Error scenarios: 4 różne scenariusze błędów z dedykowanym tracking
- **Łącznie od 2025-10-21:** +58 nowych eventów! 🎊🎊🎊🎊🎊🎊🎊🎊
- **Faza 6 (CLI - Utility) Tier 2:** Postęp! 1/2 komend Tier 2 ukończone! 🚀

**Zmiana po implementacji ChangeUserPasswordCommand CLI (2025-10-26):**
- ~~ChangeUserPasswordCommand CLI~~ → ✅ **Ukończone!**
- **Postęp:** +3 eventy zaimplementowane (3 process-level)! 🎉
- **Kluczowe feature:**
  - ✅ Basic CLI implementation (3 eventy, admin tool Tier 2)
  - ✅ Security-critical: password change tracking dla security notifications
  - ✅ Comprehensive error tracking: 3 różne error scenarios (credentials, user not found, Pterodactyl)
  - ✅ No rollback needed: update Pterodactyl i save są atomic operations
  - ✅ Simple stats: passwordChangedInPterodactyl tracking
  - ✅ Use cases: Security Notifications, Audit Trail, Monitoring, Compliance (GDPR)
- **Łącznie od 2025-10-21:** +61 nowych eventów! 🎊🎊🎊🎊🎊🎊🎊🎊🎊
- **Faza 6 (CLI - Utility) Tier 2:** ✅ **UKOŃCZONA!** Wszystkie 2 komendy Tier 2 gotowe! 🚀🚀
- **Faza 6 (CLI - Utility):** ✅ **CAŁKOWICIE UKOŃCZONA!** Wszystkie komendy Tier 1+2 gotowe! 🎉🎉🎉

### Szacowany czas implementacji (zaktualizowany 2025-10-26):

- **Priorytet 1 (Krytyczny):** 2-3 tygodnie (API - Server Management) ⏳ - częściowo ukończony (Server Management Page ✅)
- **Priorytet 2 (Wysoki):** 2 tygodnie (CLI + pozostałe API) ⏳
- ~~**Priorytet 3 (Średni):**~~ ~~3-4 dni (Admin Overview + Product Copy)~~ ✅ **UKOŃCZONE!** (2025-10-21)
- **Priorytet 4 (Niski):** 1-2 tygodnie (Utility endpoints i CLI) ⏳ - częściowo ukończony (Voucher API ✅)

**TOTAL:** ~5-6 tygodni przy pełnym zaangażowaniu (zamiast pierwotnie 8-10!)

**Redukcje czasu:**
- ✅ ~2-3 tygodnie dzięki `AbstractPanelController`! 🎉
- ✅ ~3-4 dni zaoszczędzone przez ukończenie Priorytetu 3! 🎉

---

## Następne Kroki

1. ✅ **Review dokumentacji** - przeczytaj `EVENT_DRIVEN_ARCHITECTURE.md`
2. ✅ **Zapoznaj się z istniejącymi implementacjami** - sprawdź eventy w `RegistrationController`, `CartController`
3. ✅ **Priorytet 3 UKOŃCZONY** - Admin Overview + Product Copy zaimplementowane! (2025-10-21)
4. ✅ **Voucher API UKOŃCZONE** - Voucher Redeem API zaimplementowane! (2025-10-22 rano)
5. ✅ **Server Management Page UKOŃCZONE** - Server Management Page zaimplementowane! (2025-10-22 popołudnie)
6. ✅ **Faza 3 UKOŃCZONA** - Wszystkie user-facing pages + admin operations gotowe!
7. ✅ **Server Configuration API UKOŃCZONE** - 5 endpointów, 11 eventów zaimplementowanych! (2025-10-22 wieczór)
8. ⏳ **Kontynuuj Priorytet 1** - Pozostałe API: Server Backups, Server Users, Server Databases
9. ⏳ **Implementuj systematycznie** - jeden kontroler na raz
10. ⏳ **Testuj** - każdy event z testami
10. ⏳ **Dokumentuj** - aktualizuj `EVENT_DRIVEN_ARCHITECTURE.md`
11. ⏳ **Review** - code review przed merge

---

**Koniec dokumentu**

**Ostatnia aktualizacja:** 2025-10-26
**Status:**
- ✅ Priorytet 3 (Średni): **UKOŃCZONY** - Admin Overview + Product Copy (2025-10-21)
- ✅ Faza 3: **UKOŃCZONA** - User-facing pages + Admin operations (2025-10-21 - 2025-10-22)
- ✅ Priorytet 4 (Niski): **Częściowo ukończony** - Voucher API (2025-10-22 rano)
- ✅ Priorytet 1 (Krytyczny): **Częściowo ukończony** - Server Management Page + Server Configuration API (2025-10-22)
- ✅ Faza 4 (CLI - Critical): **UKOŃCZONA** - SuspendUnpaidServersCommand (✅), DeleteInactiveServersCommand (✅), PterocaSyncServersCommand (✅)
- ✅ Faza 6 (CLI - Utility) Tier 1: **UKOŃCZONA** - PterodactylMigrateServersCommand (✅), DeleteOldLogsCommand (✅), SynchronizeDataCommand (✅)
- ✅ Faza 6 (CLI - Utility) Tier 2: **UKOŃCZONA** - CreateNewUserCommand (✅), ChangeUserPasswordCommand (✅)
- ✅ Faza 6 (CLI - Utility): **CAŁKOWICIE UKOŃCZONA!** Wszystkie Tier 1+2 gotowe! 🎉
- ⏳ Pozostało: API Controllers (8), User Pages (1)
- 🎊🎊🎊🎊🎊🎊🎊🎊🎊 **+61 nowych eventów od 2025-10-21!** (największy przyrost!)
- 📊 **Postęp Priorytetu 1:** Server Configuration API (✅), Server Management Page (✅), pozostałe: Server Backups, Server Users, Server Databases
- 🚀 **Faza 4 (CLI - Critical):** ✅ **UKOŃCZONA!** Wszystkie 3 komendy critical gotowe! (+19 eventów CLI)
- 🎯 **Faza 6 (CLI - Utility):** ✅ **CAŁKOWICIE UKOŃCZONA!** Wszystkie 5 komend Tier 1+2 gotowe! (+22 eventy CLI)
  - Tier 1: PterodactylMigrateServers (+7), DeleteOldLogs (+3), SynchronizeData (+6)
  - Tier 2: CreateNewUser (+3), ChangePassword (+3)
- 💾 **Nowe feature:** Backup automation support + fail-safe migration - stoppable pre-events dla wszystkich CLI commands!
- 🔐 **Security:** API key creation tracking + password change tracking z pełnym audit trail dla compliance
- 🔄 **Rollback tracking:** Comprehensive error handling z rollback scenarios dla data integrity
