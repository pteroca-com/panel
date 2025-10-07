# Event-Driven Architecture - Dokumentacja

## Przegląd

PteroCA wykorzystuje Event-Driven Architecture (EDA) do obsługi procesów biznesowych, szczególnie rejestracji użytkowników. To podejście zapewnia rozszerzalność poprzez system pluginów.

## Struktura Eventów

### 1. Domain Events (Synchroniczne)

Eventy domenowe są emitowane przez `EventDispatcherInterface` i przetwarzane synchronicznie w ramach transakcji.

**Lokalizacja:** `src/Core/Event/User/`

#### Eventy Rejestracji

1. **UserRegistrationRequestedEvent** (pre)
   - **Kiedy:** Tuż po rozpoczęciu procesu rejestracji, przed walidacją
   - **Payload:** email, context (ip, userAgent, locale, source)
   - **Zastosowanie:** Walidacje pluginów (blacklisty, rate-limiting, CAPTCHA)
   - **Stoppable:** Tak

2. **UserRegistrationValidatedEvent** (pre)
   - **Kiedy:** Po walidacji formularza i reguł biznesowych, przed persist
   - **Payload:** email, normalizedEmail, roles, context
   - **Zastosowanie:** Ostatnia szansa na veto/zmianę danych (np. modyfikacja ról)
   - **Stoppable:** Tak

3. **UserAboutToBeCreatedEvent** (pre-persist)
   - **Kiedy:** Tuż przed `persist()`, emitowany przez Doctrine Listener
   - **Payload:** UserInterface
   - **Zastosowanie:** Przypisanie domyślnych ról/feature-flags
   - **Stoppable:** Tak

4. **UserCreatedEvent** (post-persist, in-transaction)
   - **Kiedy:** Po `persist()`, ale wciąż w transakcji
   - **Payload:** userId, email, context
   - **Zastosowanie:** Operacje domenowe bez I/O (inicjalizacja profilu, limitów)
   - **Stoppable:** Nie

5. **UserRegisteredEvent** (post-commit)
   - **Kiedy:** Po `flush()`, poza transakcją
   - **Payload:** userId, email, isVerified, context
   - **Zastosowanie:** Efekty uboczne (welcome email, CRM, webhooks)
   - **Stoppable:** Nie

6. **UserEmailVerificationRequestedEvent** (post-commit)
   - **Kiedy:** Po wysłaniu linku weryfikacyjnego
   - **Payload:** userId, email, verificationToken, context
   - **Zastosowanie:** Tracking, integracje
   - **Stoppable:** Nie

7. **UserEmailVerifiedEvent** (post-commit)
   - **Kiedy:** Po kliknięciu linku weryfikacyjnego
   - **Payload:** userId, email, context
   - **Zastosowanie:** Odblokowanie funkcji, bonusy
   - **Stoppable:** Nie

8. **UserRegistrationFailedEvent** (error)
   - **Kiedy:** Wyjątek podczas rejestracji
   - **Payload:** email, reason, stage, context
   - **Zastosowanie:** Logging, monitoring, alerting
   - **Stoppable:** Nie

### 2. Messages (Asynchroniczne)

Wiadomości do Symfony Messenger dla operacji asynchronicznych.

**Lokalizacja:** `src/Core/Message/`

Przykłady:
- `SendEmailMessage` - wysyłka emaili
- Możliwość dodania: `SendWelcomeEmailMessage`, `NotifyCrmMessage`, etc.

## Payload Eventów

Każdy event zawiera standardowe pola:

```php
- eventId: string (UUID v4)
- occurredAt: DateTimeImmutable
- schemaVersion: string (domyślnie 'v1')
```

Dodatkowo dla eventów użytkownika:
- `userId`: int (gdy już istnieje)
- `email`: string
- `context`: array (ip, userAgent, locale, source)

## Transakcyjność

- **Pre-events** (`UserRegistrationRequestedEvent`, `UserRegistrationValidatedEvent`, `UserAboutToBeCreatedEvent`) - wewnątrz transakcji, mogą zatrzymać proces
- **In-transaction events** (`UserCreatedEvent`) - po persist(), ale przed commit()
- **Post-commit events** (`UserRegisteredEvent`, `UserEmailVerifiedEvent`) - po flush(), bezpieczne dla efektów ubocznych

## StopPropagation & Veto

Pre-eventy mogą wykorzystywać `StoppableEventTrait` do zatrzymania procesu:

```php
class MyPluginSubscriber implements EventSubscriberInterface
{
    public function onUserRegistrationRequested(UserRegistrationRequestedEvent $event): void
    {
        if ($this->isBlacklisted($event->getEmail())) {
            $event->stopPropagation();
            $event->setRejected(true, 'Email is blacklisted');
        }
    }
}
```

## Doctrine Event Listener

`UserEventListener` automatycznie emituje eventy w odpowiednich momentach cyklu życia encji:

- `prePersist` → `UserAboutToBeCreatedEvent`
- `postPersist` → `UserCreatedEvent`
- `postFlush` → `UserRegisteredEvent`

### Ważne: Zabezpieczenie przed Infinite Loop

`UserEventListener` jest **serwisem singleton**, co oznacza że może być współdzielony między requestami w długotrwałych procesach (Symfony Messenger workers, Supervisor). Aby zapobiec wielokrotnej emisji eventów, listener zawiera zabezpieczenie:

```php
private bool $eventsFiredInCurrentTransaction = false;

public function postFlush(PostFlushEventArgs $args): void
{
    if (empty($this->persistedUsers) || $this->eventsFiredInCurrentTransaction) {
        return;
    }

    $this->eventsFiredInCurrentTransaction = true;
    $usersToProcess = $this->persistedUsers;
    $this->persistedUsers = [];

    foreach ($usersToProcess as $user) {
        $this->eventDispatcher->dispatch(new UserRegisteredEvent(...));
    }

    $this->eventsFiredInCurrentTransaction = false;
}
```

**Problem bez zabezpieczenia:** W środowisku z workerami tablica `$persistedUsers` mogła nie być czyszczona między requestami, co powodowało wielokrotne wysyłanie emaili weryfikacyjnych.

**Rozwiązanie:** Flaga `$eventsFiredInCurrentTransaction` zapewnia, że eventy są emitowane tylko raz per transakcja.

## Event Subscribers

Subscribery słuchają na eventy i reagują na nie. **Ważne:** Subscribery powinny tylko koordynować działania i delegować pracę do dedykowanych serwisów.

**Lokalizacja:** `src/Core/EventSubscriber/User/`

### Przykład: UserRegistrationSubscriber

```php
class UserRegistrationSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private readonly UserEmailService $userEmailService,
        private readonly LoggerInterface $logger,
    ) {}

    public static function getSubscribedEvents(): array
    {
        return [
            UserRegisteredEvent::class => 'onUserRegistered',
            UserRegistrationFailedEvent::class => 'onUserRegistrationFailed',
        ];
    }

    public function onUserRegistered(UserRegisteredEvent $event): void
    {
        // Deleguj wysyłkę emaila do dedykowanego serwisu
        if (!$event->isVerified()) {
            $this->userEmailService->sendVerificationEmail($event->getUserId(), $event->getEmail());
        }
    }

    public function onUserRegistrationFailed(UserRegistrationFailedEvent $event): void
    {
        $this->logger->error('User registration failed', [
            'email' => $event->getEmail(),
            'reason' => $event->getReason(),
            'stage' => $event->getStage(),
            'eventId' => $event->getEventId(),
        ]);
    }
}
```

## Dedykowane Serwisy

Zgodnie z zasadą **Separation of Concerns**, subscribery delegują pracę do dedykowanych serwisów.

**Lokalizacja:** `src/Core/Service/User/`

### Przykład: UserEmailService

```php
class UserEmailService
{
    public function __construct(
        private readonly MessageBusInterface $messageBus,
        private readonly LoggerInterface $logger,
        private readonly TranslatorInterface $translator,
        private readonly SettingService $settingService,
        private readonly EmailVerificationService $emailVerificationService,
        private readonly EmailNotificationService $emailNotificationService,
        private readonly UserRepository $userRepository,
        private readonly EventDispatcherInterface $eventDispatcher,
    ) {}

    public function sendVerificationEmail(int $userId, string $email): void
    {
        // Logika wysyłki emaila weryfikacyjnego
        // ...
    }
}
```

**Zalety:**
- ✅ **Reużywalność** - serwis może być używany w różnych miejscach
- ✅ **Testowanie** - łatwiejsze mockowanie i testowanie
- ✅ **Czytelność** - subscriber jest deklaratywny ("co"), serwis opisuje "jak"
- ✅ **Single Responsibility** - każda klasa ma jedną odpowiedzialność

## Rozszerzalność dla Pluginów

Pluginy mogą:

1. **Rejestrować własne EventSubscribers** słuchające na eventy systemowe
2. **Dispatchować własne Messages** do Messenger
3. **Zatrzymywać proces** przez `stopPropagation()` w pre-eventach
4. **Modyfikować dane** (np. role w `UserRegistrationValidatedEvent`)

### Przykład Pluginu

```php
class CaptchaPluginSubscriber implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [
            UserRegistrationRequestedEvent::class => ['validateCaptcha', 100],
        ];
    }

    public function validateCaptcha(UserRegistrationRequestedEvent $event): void
    {
        if (!$this->captchaService->verify($event->getContext()['captcha'] ?? '')) {
            $event->stopPropagation();
            $event->setRejected(true, 'Invalid CAPTCHA');
        }
    }
}
```

## Priorytety Subscriberów

Subscribery mogą mieć różne priorytety (wyższy numer = wcześniejsze wykonanie):

```php
public static function getSubscribedEvents(): array
{
    return [
        UserRegistrationRequestedEvent::class => ['onRequested', 100], // priorytet 100
    ];
}
```

## Testowanie

Przy testowaniu procesów z eventami:

1. **Mockuj EventDispatcher** aby sprawdzić emitowane eventy
2. **Testuj subscribery osobno** od logiki biznesowej
3. **Sprawdzaj transakcyjność** - czy eventy są emitowane w odpowiednich momentach

## Procesy Obsługiwane przez EDA

### ✅ Rejestracja Użytkownika

**Lokalizacja eventów:** `src/Core/Event/User/Registration/`

**Eventy:**
1. `UserRegistrationRequestedEvent` (pre) - żądanie rejestracji
2. `UserRegistrationValidatedEvent` (pre) - po walidacji formularza
3. `UserAboutToBeCreatedEvent` (pre-persist) - przed persist
4. `UserCreatedEvent` (post-persist) - po persist, w transakcji
5. `UserRegisteredEvent` (post-commit) - po commit
6. `UserEmailVerificationRequestedEvent` (post-commit) - wysłano link weryfikacyjny
7. `UserEmailVerifiedEvent` (post-commit) - email zweryfikowany
8. `UserRegistrationFailedEvent` (error) - błąd rejestracji

**Subscriber:** `UserRegistrationSubscriber`

---

### ✅ Logowanie Użytkownika

**Lokalizacja eventów:** `src/Core/Event/User/Authentication/`

**Eventy:**
1. `UserLoginRequestedEvent` (pre) - wyświetlenie formularza logowania (GET)
2. `UserLoginAttemptedEvent` (pre) - próba logowania (POST)
3. `UserLoginValidatedEvent` (pre) - po walidacji CAPTCHA i user checks
4. `UserAuthenticationSuccessfulEvent` (post) - po udanej autentykacji
5. `UserLoggedInEvent` (post-commit) - po utworzeniu sesji
6. `UserAuthenticationFailedEvent` (error) - nieudane logowanie
7. `UserLoggedOutEvent` (post) - wylogowanie

**Listener:** `AuthenticationEventListener` - nasłuchuje na Symfony Security events  
**Subscriber:** `UserAuthenticationSubscriber` - logging

**Flow:**
```
GET /login (niezalogowany)
  → UserLoginRequestedEvent

POST /login
  → UserLoginAttemptedEvent
  → CAPTCHA validation
  → User validation (deleted/blocked)
  → UserLoginValidatedEvent
  → Symfony Security sprawdza hasło
  
  Jeśli SUCCESS:
    → UserAuthenticationSuccessfulEvent (via AuthenticationEventListener)
    → Symfony tworzy sesję
    → UserLoggedInEvent (via AuthenticationEventListener)
    
  Jeśli FAILURE:
    → UserAuthenticationFailedEvent (via AuthenticationEventListener)
```

---

### Kolejne Procesy do Migracji

- [ ] Tworzenie serwera
- [ ] Płatności
- [ ] Zarządzanie użytkownikami
- [ ] Zarządzanie serwerami

## Best Practices

1. **Eventy powinny być niezmienne** - używaj `readonly` properties
2. **Payload powinien być minimalny** - tylko niezbędne dane
3. **Nie wykonuj I/O w pre-eventach** - mogą być w transakcji
4. **Loguj wszystkie eventy** - ułatwia debugging i audyt
5. **Używaj schemaVersion** - umożliwia ewolucję eventów
6. **Dokumentuj każdy event** - co, kiedy i po co
7. **Subscribery delegują do serwisów** - nie umieszczaj logiki biznesowej w subscriberach
8. **Uważaj na shared state** - listenery są singleton, mogą powodować wycieki między requestami
9. **Testuj transakcyjność** - upewnij się że eventy są emitowane w odpowiednich momentach
10. **Używaj flag zabezpieczających** - zapobiegaj infinite loops w długotrwałych procesach

## Więcej Informacji

- [Symfony Event Dispatcher](https://symfony.com/doc/current/event_dispatcher.html)
- [Symfony Messenger](https://symfony.com/doc/current/messenger.html)
- [Doctrine Events](https://www.doctrine-project.org/projects/doctrine-orm/en/latest/reference/events.html)
