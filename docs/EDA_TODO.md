# Event-Driven Architecture - TODO Lista

**Data ostatniej aktualizacji:** 2025-10-21
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

### 2. Server Users API

**Plik:** `src/Core/Controller/API/ServerUserController.php`

#### Endpointy bez eventów:

| Endpoint | Metoda | Akcja |
|----------|--------|-------|
| `/panel/api/server/{id}/users/all` | GET | Lista subuserów |
| `/panel/api/server/{id}/users/create` | POST | Tworzenie subusera |
| `/panel/api/server/{id}/users/{userUuid}` | GET | Szczegóły subusera |
| `/panel/api/server/{id}/users/{userUuid}/permissions` | POST | Aktualizacja uprawnień |
| `/panel/api/server/{id}/users/{userUuid}/delete` | DELETE | Usuwanie subusera |

#### Proponowane eventy:

```php
// POST /panel/api/server/{id}/users/create
- ServerSubuserCreationRequestedEvent (pre, stoppable)
- ServerSubuserCreatedEvent (post-commit)
- ServerSubuserCreationFailedEvent (error)

// POST /panel/api/server/{id}/users/{userUuid}/permissions
- ServerSubuserPermissionsUpdateRequestedEvent (pre, stoppable)
- ServerSubuserPermissionsUpdatedEvent (post-commit)

// DELETE /panel/api/server/{id}/users/{userUuid}/delete
- ServerSubuserDeletionRequestedEvent (pre, stoppable)
- ServerSubuserDeletedEvent (post-commit)
```

#### Zastosowanie dla pluginów:
- **Security notifications** - powiadomienia o dodaniu/usunięciu dostępu
- **Audit trail** - pełna historia zmian uprawnień
- **Access control** - dodatkowe walidacje (np. limit subuserów)
- **Webhooks** - integracje z zewnętrznymi systemami (Discord, Slack)

---

### 3. Server Backups API

**Plik:** `src/Core/Controller/API/ServerBackupController.php`

#### Endpointy bez eventów:

| Endpoint | Metoda | Akcja |
|----------|--------|-------|
| `/panel/api/server/{id}/backup/create` | POST | Tworzenie backupu |
| `/panel/api/server/{id}/backup/{backupId}/download` | GET | Pobieranie backupu |
| `/panel/api/server/{id}/backup/{backupId}/delete` | DELETE | Usuwanie backupu |
| `/panel/api/server/{id}/backup/{backupId}/restore` | POST | Przywracanie backupu |

#### Proponowane eventy:

```php
// POST /panel/api/server/{id}/backup/create
- ServerBackupCreationRequestedEvent (pre, stoppable)
- ServerBackupCreatedEvent (post-commit)
- ServerBackupCreationFailedEvent (error)

// GET /panel/api/server/{id}/backup/{backupId}/download
- ServerBackupDownloadRequestedEvent (pre)
- ServerBackupDownloadInitiatedEvent (post)

// DELETE /panel/api/server/{id}/backup/{backupId}/delete
- ServerBackupDeletionRequestedEvent (pre, stoppable)
- ServerBackupDeletedEvent (post-commit)

// POST /panel/api/server/{id}/backup/{backupId}/restore
- ServerBackupRestoreRequestedEvent (pre, stoppable)
- ServerBackupRestoreInitiatedEvent (post)
- ServerBackupRestoredEvent (post-commit)
- ServerBackupRestoreFailedEvent (error)
```

#### Zastosowanie dla pluginów:
- **Quota management** - limit backupów per serwer
- **Billing** - płatność za dodatkowe backupy
- **Notifications** - powiadomienia o zakończeniu backupu/restore
- **Monitoring** - tracking użycia przestrzeni backupów
- **Security** - audit trail dla krytycznych operacji restore

---

### 4. Server Databases API

**Plik:** `src/Core/Controller/API/ServerDatabaseController.php`

#### Endpointy bez eventów:

| Endpoint | Metoda | Akcja |
|----------|--------|-------|
| `/panel/api/server/{id}/database/all` | GET | Lista baz danych |
| `/panel/api/server/{id}/database/create` | POST | Tworzenie bazy danych |
| `/panel/api/server/{id}/database/{databaseId}/delete` | DELETE | Usuwanie bazy |
| `/panel/api/server/{id}/database/{databaseId}/rotate-password` | POST | Zmiana hasła |

#### Proponowane eventy:

```php
// POST /panel/api/server/{id}/database/create
- ServerDatabaseCreationRequestedEvent (pre, stoppable)
- ServerDatabaseCreatedEvent (post-commit)
- ServerDatabaseCreationFailedEvent (error)

// DELETE /panel/api/server/{id}/database/{databaseId}/delete
- ServerDatabaseDeletionRequestedEvent (pre, stoppable)
- ServerDatabaseDeletedEvent (post-commit)

// POST /panel/api/server/{id}/database/{databaseId}/rotate-password
- ServerDatabasePasswordRotationRequestedEvent (pre, stoppable)
- ServerDatabasePasswordRotatedEvent (post-commit)
```

#### Zastosowanie dla pluginów:
- **Quota management** - limit baz danych per serwer
- **Security** - audit trail dla operacji na bazach
- **Notifications** - powiadomienia o krytycznych operacjach
- **Backup integration** - automatyczne backupy przed delete/rotate

---

### 5. Server Network API

**Plik:** `src/Core/Controller/API/ServerNetworkController.php`

#### Endpointy bez eventów:

| Endpoint | Metoda | Akcja |
|----------|--------|-------|
| `/panel/api/server/{id}/allocation/create` | POST | Tworzenie alokacji |
| `/panel/api/server/{id}/allocation/{allocationId}/primary` | POST | Ustawienie jako primary |
| `/panel/api/server/{id}/allocation/{allocationId}/edit` | POST | Edycja alokacji |
| `/panel/api/server/{id}/allocation/{allocationId}/delete` | DELETE | Usuwanie alokacji |

#### Proponowane eventy:

```php
// POST /panel/api/server/{id}/allocation/create
- ServerAllocationCreationRequestedEvent (pre, stoppable)
- ServerAllocationCreatedEvent (post-commit)
- ServerAllocationCreationFailedEvent (error)

// POST /panel/api/server/{id}/allocation/{allocationId}/primary
- ServerAllocationPrimaryChangeRequestedEvent (pre, stoppable)
- ServerAllocationPrimaryChangedEvent (post-commit)

// POST /panel/api/server/{id}/allocation/{allocationId}/edit
- ServerAllocationEditRequestedEvent (pre, stoppable)
- ServerAllocationEditedEvent (post-commit)

// DELETE /panel/api/server/{id}/allocation/{allocationId}/delete
- ServerAllocationDeletionRequestedEvent (pre, stoppable)
- ServerAllocationDeletedEvent (post-commit)
```

#### Zastosowanie dla pluginów:
- **Quota management** - limit portów per serwer
- **Billing** - płatność za dodatkowe porty
- **Firewall integration** - automatyczna konfiguracja firewall
- **DDoS protection** - integracja z systemami ochrony

---

### 6. Server Schedules API

**Plik:** `src/Core/Controller/API/ServerScheduleController.php`

#### Endpointy bez eventów:

| Endpoint | Metoda | Akcja |
|----------|--------|-------|
| `/panel/api/server/{id}/schedules/create` | POST | Tworzenie harmonogramu |
| `/panel/api/server/{id}/schedules/{scheduleId}` | PUT | Aktualizacja harmonogramu |
| `/panel/api/server/{id}/schedules/{scheduleId}/delete` | DELETE | Usuwanie harmonogramu |
| `/panel/api/server/{id}/schedules/{scheduleId}` | GET | Pobieranie harmonogramu |
| `/panel/api/server/{id}/schedules/{scheduleId}/tasks` | POST | Tworzenie zadania |
| `/panel/api/server/{id}/schedules/{scheduleId}/tasks/{taskId}` | PUT | Aktualizacja zadania |
| `/panel/api/server/{id}/schedules/{scheduleId}/tasks/{taskId}` | DELETE | Usuwanie zadania |

#### Proponowane eventy:

```php
// POST /panel/api/server/{id}/schedules/create
- ServerScheduleCreationRequestedEvent (pre, stoppable)
- ServerScheduleCreatedEvent (post-commit)

// PUT /panel/api/server/{id}/schedules/{scheduleId}
- ServerScheduleUpdateRequestedEvent (pre, stoppable)
- ServerScheduleUpdatedEvent (post-commit)

// DELETE /panel/api/server/{id}/schedules/{scheduleId}/delete
- ServerScheduleDeletionRequestedEvent (pre, stoppable)
- ServerScheduleDeletedEvent (post-commit)

// POST /panel/api/server/{id}/schedules/{scheduleId}/tasks
- ServerScheduleTaskCreationRequestedEvent (pre, stoppable)
- ServerScheduleTaskCreatedEvent (post-commit)

// PUT /panel/api/server/{id}/schedules/{scheduleId}/tasks/{taskId}
- ServerScheduleTaskUpdateRequestedEvent (pre, stoppable)
- ServerScheduleTaskUpdatedEvent (post-commit)

// DELETE /panel/api/server/{id}/schedules/{scheduleId}/tasks/{taskId}
- ServerScheduleTaskDeletionRequestedEvent (pre, stoppable)
- ServerScheduleTaskDeletedEvent (post-commit)
```

#### Zastosowanie dla pluginów:
- **Quota management** - limit harmonogramów per serwer
- **Analytics** - tracking popularnych schedulów
- **Notifications** - powiadomienia o wykonaniu zadań
- **Monitoring** - tracking błędów w harmonogramach

---

### 7. Server Configuration API

**Plik:** `src/Core/Controller/API/ServerConfigurationController.php`

#### Endpointy bez eventów:

| Endpoint | Metoda | Akcja |
|----------|--------|-------|
| `/panel/api/server/{id}/startup/variable` | POST | Zmiana zmiennej startowej |
| `/panel/api/server/{id}/startup/option` | POST | Zmiana opcji startowej |
| `/panel/api/server/{id}/details/update` | POST | Aktualizacja szczegółów |
| `/panel/api/server/{id}/reinstall` | POST | Reinstalacja serwera |
| `/panel/api/server/{id}/auto-renewal/toggle` | POST | Przełączenie auto-renewal |

#### Proponowane eventy:

```php
// POST /panel/api/server/{id}/startup/variable
- ServerStartupVariableUpdateRequestedEvent (pre, stoppable)
- ServerStartupVariableUpdatedEvent (post-commit)

// POST /panel/api/server/{id}/startup/option
- ServerStartupOptionUpdateRequestedEvent (pre, stoppable)
- ServerStartupOptionUpdatedEvent (post-commit)

// POST /panel/api/server/{id}/details/update
- ServerDetailsUpdateRequestedEvent (pre, stoppable)
- ServerDetailsUpdatedEvent (post-commit)

// POST /panel/api/server/{id}/reinstall
- ServerReinstallRequestedEvent (pre, stoppable)
- ServerReinstallInitiatedEvent (post)
- ServerReinstalledEvent (post-commit)

// POST /panel/api/server/{id}/auto-renewal/toggle
- ServerAutoRenewalToggleRequestedEvent (pre, stoppable)
- ServerAutoRenewalToggledEvent (post-commit)
```

#### Zastosowanie dla pluginów:
- **Validation** - dodatkowe walidacje przed reinstalacją
- **Backup automation** - automatyczny backup przed reinstalacją
- **Notifications** - powiadomienia o zmianach konfiguracji
- **Audit trail** - historia zmian konfiguracji
- **Security** - monitoring podejrzanych zmian

---

### ~~8. Voucher API~~ ✅ **ZAIMPLEMENTOWANE** (2025-10-22)

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

#### 1. CreateNewUserCommand

**Komenda:** `app:create-new-user`
**Plik:** `src/Core/Command/CreateNewUserCommand.php`

**Proponowane eventy:**
```php
- UserCreationViaCLIRequestedEvent (pre)
- UserCreatedViaCLIEvent (post-commit)
- UserCreationViaCLIFailedEvent (error)
```

**Zastosowanie:**
- Audit trail dla kont tworzonych przez CLI
- Notifications dla nowych użytkowników
- Analytics

---

#### 2. SuspendUnpaidServersCommand

**Komenda:** `app:suspend-unpaid-servers`
**Plik:** `src/Core/Command/SuspendUnpaidServersCommand.php`

**Proponowane eventy:**
```php
- SuspendUnpaidServersProcessStartedEvent (pre)
- ServerSuspendedForNonPaymentEvent (per server)
- SuspendUnpaidServersProcessCompletedEvent (post)
```

**Zastosowanie:**
- Notifications - powiadomienia użytkowników o zawieszeniu
- Analytics - tracking zawieszonych serwerów
- Monitoring - alerting przy błędach
- Retry logic - ponowne próby dla failed operations

---

#### 3. DeleteInactiveServersCommand

**Komenda:** `app:delete-inactive-servers`
**Plik:** `src/Core/Command/DeleteInactiveServersCommand.php`

**Proponowane eventy:**
```php
- DeleteInactiveServersProcessStartedEvent (pre)
- InactiveServerDeletedEvent (per server)
- DeleteInactiveServersProcessCompletedEvent (post)
```

**Zastosowanie:**
- Backup automation - backupy przed usunięciem
- Notifications - ostatnie ostrzeżenia dla użytkowników
- Analytics - tracking usuniętych serwerów
- Audit trail

---

#### 4. ChangeUserPasswordCommand

**Komenda:** `app:change-user-password`
**Plik:** `src/Core/Command/ChangeUserPasswordCommand.php`

**Proponowane eventy:**
```php
- PasswordChangeViaCLIRequestedEvent (pre)
- PasswordChangedViaCLIEvent (post-commit)
- PasswordChangeViaCLIFailedEvent (error)
```

**Zastosowanie:**
- Security notifications - powiadomienia o zmianie hasła
- Audit trail - kto zmienił hasło przez CLI
- Compliance logging

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

#### 6. PterocaSyncServersCommand

**Komenda:** `app:pteroca-sync-servers`
**Plik:** `src/Core/Command/PterocaSyncServersCommand.php`

**Proponowane eventy:**
```php
- ServersSyncProcessStartedEvent (pre)
- ServerSyncedEvent (per server)
- ServersSyncProcessCompletedEvent (post)
- ServerSyncFailedEvent (error, per server)
```

**Zastosowanie:**
- Monitoring - tracking synchronizacji
- Error alerting - powiadomienia o błędach
- Analytics - statystyki sync
- Performance tracking

---

#### 7. SynchronizeDataCommand

**Komenda:** `app:synchronize-data`
**Plik:** `src/Core/Command/SynchronizeDataCommand.php`

**Proponowane eventy:**
```php
- DataSyncProcessStartedEvent (pre)
- DataSyncProcessCompletedEvent (post)
- DataSyncFailedEvent (error)
```

**Zastosowanie:**
- Monitoring - tracking synchronizacji danych
- Alerting - powiadomienia o błędach
- Performance tracking

---

#### 8. DeleteOldLogsCommand

**Komenda:** `app:delete-old-logs`
**Plik:** `src/Core/Command/DeleteOldLogsCommand.php`

**Proponowane eventy:**
```php
- LogDeletionProcessStartedEvent (pre)
- LogDeletionProcessCompletedEvent (post, z informacją ile usunięto)
```

**Zastosowanie:**
- Monitoring - tracking czyszczenia logów
- Analytics - statystyki przestrzeni zwolnionej
- Compliance - logging operacji czyszczenia

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

### 1. Server Management Page

**Plik:** `src/Core/Controller/ServerController.php`

#### Strona bez eventów:

| Route | Akcja |
|-------|-------|
| `/server?id=XXX` | Strona zarządzania pojedynczym serwerem |

**Uwaga:** Kontroler ma eventy dla `/servers` (lista), ale **nie ma** dla `/server` (szczegóły).

#### Proponowane eventy:

```php
// GET /server?id=XXX
- ServerManagementPageAccessedEvent (post)
- ServerManagementDataLoadedEvent (post)
- ViewDataEvent (viewName='server_management')
```

#### Zastosowanie dla pluginów:
- **Analytics** - tracking użycia strony zarządzania
- **Performance tracking** - monitoring ładowania danych
- **Custom widgets** - pluginy mogą dodać własne sekcje
- **Personalizacja** - customizacja interfejsu zarządzania

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

1. **Server Configuration API** (`ServerConfigurationController.php`)
   - Auto-renewal toggle - krytyczne dla retention
   - Reinstall - krytyczna operacja wymagająca audit trail
   - Startup variables - często używane

2. **Server Backups API** (`ServerBackupController.php`)
   - Create/Restore backup - krytyczne operacje bezpieczeństwa
   - Wymaga audit trail i notifications

3. **Server Users API** (`ServerUserController.php`)
   - Dodawanie/usuwanie dostępu - krytyczne dla bezpieczeństwa
   - Wymaga security notifications

4. **Server Management Page** (`/server?id=XXX`)
   - Główny interfejs zarządzania serwerem
   - Brak eventów blokuje rozszerzalność pluginów

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

9. **Server Network API** (`ServerNetworkController.php`)
   - Zarządzanie alokacjami - często używane

10. **Server Schedules API** (`ServerScheduleController.php`)
    - Harmonogramy zadań - popularna funkcjonalność

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

16. **Server Details API** (`ServerController.php` - `/api/server/{id}/details`)
    - Read-only endpoint - niski priorytet

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

#### Faza 1: API - Krytyczne operacje (1-2 tygodnie)
- Server Configuration API
- Server Backups API
- Server Users API
- Server Databases API

#### Faza 2: API - Pozostałe (1 tydzień)
- Server Network API
- Server Schedules API
- Server Details API
- Voucher API

#### ~~Faza 3: User-facing pages + Admin operations (2-3 dni)~~ ✅ **CZĘŚCIOWO UKOŃCZONA** (2025-10-21)
- ⏳ Server Management Page (do zrobienia)
- ✅ Admin Overview (ukończone 2025-10-21)
- ✅ Product Copy - operacja specjalna (ukończone 2025-10-21)

#### Faza 4: CLI - Critical (1 tydzień)
- SuspendUnpaidServersCommand
- DeleteInactiveServersCommand
- PterocaSyncServersCommand

#### ~~Faza 5: Admin CRUD (1 tydzień)~~ ✅ **UKOŃCZONA** (przez AbstractPanelController)
- ~~User CRUD~~ ✅ Eventy CRUD automatyczne
- ~~Server CRUD~~ ✅ Eventy CRUD automatyczne
- ~~Product CRUD~~ ✅ Eventy CRUD automatyczne + Product Copy
- ~~Voucher CRUD~~ ✅ Eventy CRUD automatyczne

#### Faza 6: CLI - Utility (3-4 dni)
- Pozostałe komendy CLI

#### ~~Faza 7: Pozostałe CRUD (1 tydzień)~~ ✅ **UKOŃCZONA** (przez AbstractPanelController)
- ~~Category, Payment, Logs, Settings CRUD~~ ✅ Eventy CRUD automatyczne

#### Faza 8: Nice-to-have (opcjonalne)
- First Configuration
- Admin API
- Eggs API

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
  - **✨ 3 nowe eventy (2025-10-22):**
    - ✅ `VoucherRedemptionRequestedEvent` - Voucher API (stoppable)
    - ✅ `VoucherRedeemedEvent` - Voucher API
    - ✅ `VoucherRedemptionFailedEvent` - Voucher API
  - **RAZEM:** ~76+ eventów + automatyczne eventy dla 13+ kontrolerów CRUD

- **❌ Do zaimplementowania:**
  - **API Controllers:** 9 kontrolerów (~47+ eventów) ~~10 kontrolerów~~
  - **CLI Commands:** 14 komend (~40+ eventów)
  - **User Pages:** 2 strony (~6+ eventów)
  - ~~**Admin Pages:**~~ ✅ **UKOŃCZONE** (Admin Overview - 2025-10-21)
  - ~~**Operacje specjalne:**~~ ✅ **UKOŃCZONE** (Product Copy - 2025-10-21)
  - ~~**Voucher API:**~~ ✅ **UKOŃCZONE** (Voucher Redeem - 2025-10-22)
  - **RAZEM:** ~93 nowych eventów (zamiast pierwotnie 101)

**Zmiana po analizie AbstractPanelController:**
- ~~30+ eventów dla Admin CRUD~~ → ✅ **Już zaimplementowane w AbstractPanelController**
- **Oszczędność:** ~30 eventów nie trzeba implementować!

**Zmiana po implementacji Admin Overview + Product Copy (2025-10-21):**
- ~~Admin Pages + Operacje specjalne~~ → ✅ **Ukończone!**
- **Postęp:** +4 eventy zaimplementowane! 🎉

**Zmiana po implementacji Voucher API (2025-10-22):**
- ~~Voucher Redeem API~~ → ✅ **Ukończone!**
- **Postęp:** +3 eventy zaimplementowane! 🎉
- **Łącznie od 2025-10-21:** +7 nowych eventów!

### Szacowany czas implementacji (zaktualizowany 2025-10-22):

- **Priorytet 1 (Krytyczny):** 2-3 tygodnie (API - Server Management) ⏳
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
4. ✅ **Voucher API UKOŃCZONE** - Voucher Redeem API zaimplementowane! (2025-10-22)
5. ⏳ **Wybierz kolejny priorytet** - Priorytet 1 (API - Server Management) lub Priorytet 2 (CLI)
6. ⏳ **Implementuj systematycznie** - jeden kontroler na raz
7. ⏳ **Testuj** - każdy event z testami
8. ⏳ **Dokumentuj** - aktualizuj `EVENT_DRIVEN_ARCHITECTURE.md`
9. ⏳ **Review** - code review przed merge

---

**Koniec dokumentu**

**Ostatnia aktualizacja:** 2025-10-22
**Status:**
- ✅ Priorytet 3 (Średni): **UKOŃCZONY** - Admin Overview + Product Copy (2025-10-21)
- ✅ Priorytet 4 (Niski): **Częściowo ukończony** - Voucher API (2025-10-22)
- ⏳ Pozostało: API Controllers (9), CLI Commands (14), User Pages (2)
