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

---

### ✅ Dashboard Użytkownika

**Lokalizacja eventów:** `src/Core/Event/Dashboard/`

**Eventy:**
1. `DashboardAccessedEvent` (post) - wejście na dashboard
2. `DashboardDataLoadedEvent` (post) - po załadowaniu danych

**Subscriber:** Brak - eventy są emitowane tylko dla pluginów

**Flow:**
```
GET /panel
  → DashboardAccessedEvent
  → Pobieranie danych (serwery, logi, MOTD)
  → DashboardDataLoadedEvent
  → Render template
```

**Zastosowanie:**
- Analytics i tracking użycia dashboardu (przez pluginy)
- Monitoring wydajności ładowania (przez pluginy)
- Pluginy mogą dodać custom widgets/notifications
- Personalizacja dashboardu (przez pluginy)
- Performance tracking (przez pluginy)

**Charakterystyka:**
- Minimalistyczne podejście (tylko 2 eventy)
- Read-only view - brak operacji zapisu
- Brak built-in subscriberów - tylko dla pluginów
- Fokus na rozszerzalność przez pluginy

---

---

### ✅ Generyczny System Eventów dla Formularzy i Widoków

**Lokalizacja eventów:** `src/Core/Event/Form/` i `src/Core/Event/View/`

PteroCA wykorzystuje **generyczny system eventów** umożliwiający pluginom modyfikację formularzy i danych widoków w **CAŁEJ aplikacji**.

**Eventy generyczne:**
1. **FormBuildEvent** - dodawanie pól do formularzy
2. **FormSubmitEvent** - walidacja i modyfikacja danych formularza
3. **ViewDataEvent** - modyfikacja danych widoku przed renderem

**Różnica od eventów domenowych:** Eventy generyczne są **uniwersalne** i działają dla wszystkich formularzy/widoków, podczas gdy eventy domenowe (np. `UserRegisteredEvent`) opisują konkretne zdarzenia biznesowe.

---

#### **1. FormBuildEvent - Budowanie Formularzy**

**Lokalizacja:** `src/Core/Event/Form/FormBuildEvent.php`

**Kiedy:** Emitowany w `buildForm()` każdego FormType  
**Typ:** Generyczny event  
**Payload:**
- `FormBuilderInterface $form` (mutable)
- `string $formType` ('registration', 'login', 'profile', etc.)
- `array $context` (ip, userAgent, locale)

**Zastosowanie:**
- Pluginy mogą dodawać własne pola do **dowolnych formularzy**
- Pola mogą być `mapped => false` (nie zapisywane do encji)
- Plugin sam obsługuje zapis swoich pól
- **Cross-cutting:** Plugin może dodać pole do WSZYSTKICH formularzy naraz

**Przykład użycia w FormType:**
```php
public function buildForm(FormBuilderInterface $builder, array $options): void
{
    // Standardowe pola formularza
    $builder->add('email', EmailType::class, [...]);
    
    // Emit FormBuildEvent dla pluginów
    $event = new FormBuildEvent($builder, 'registration', $context);
    $this->eventDispatcher->dispatch($event);
}
```

**Przykład subscribera pluginu:**
```php
class MyPluginFormSubscriber implements EventSubscriberInterface
{
    public function onFormBuild(FormBuildEvent $event): void
    {
        if ($event->getFormType() === 'registration') {
            $event->getForm()->add('newsletter', CheckboxType::class, [
                'label' => 'Subscribe to newsletter',
                'required' => false,
                'mapped' => false,
            ]);
        }
    }
}
```

---

#### **2. FormSubmitEvent - Walidacja i Przetwarzanie Danych**

**Lokalizacja:** `src/Core/Event/Form/FormSubmitEvent.php`

**Kiedy:** Emitowany po `$form->handleRequest()` i `$form->isSubmitted()`, przed zapisem  
**Typ:** Generyczny event  
**Payload:**
- `string $formType` ('registration', 'login', etc.)
- `array $formData` (mutable) - zawiera dane formularza + pola z pluginów
- `array $context` (ip, userAgent, locale)

**Cechy:**
- `StoppableEventTrait` - plugin może zatrzymać proces przez `stopPropagation()`

**Zastosowanie:**
- Pluginy mogą **walidować dane formularza** przed zapisem
- Pluginy mogą **modyfikować dane** (np. normalizacja)
- Pluginy mogą **zatrzymać submit** jeśli walidacja nie przejdzie
- Plugin może **zapisać własne dane** do własnych tabel
- **Cross-cutting:** Plugin może walidować WSZYSTKIE formularze naraz (np. CAPTCHA)

**Różnica od eventów domenowych:** `FormSubmitEvent` jest emitowany **przed logiką biznesową**, podczas gdy `UserRegisteredEvent` jest emitowany **po zapisie do bazy**

**Przykład użycia w kontrolerze:**
```php
if ($form->isSubmitted() && $form->isValid()) {
    $formData = [
        'email' => $user->getEmail(),
        // ... inne pola
    ];
    
    // Dodaj pola z pluginów (unmapped)
    foreach ($form->all() as $fieldName => $field) {
        if ($field->getConfig()->getOption('mapped') === false) {
            $formData[$fieldName] = $field->getData();
        }
    }
    
    // Emit FormSubmitEvent
    $submitEvent = new FormSubmitEvent('registration', $formData, $context);
    $this->eventDispatcher->dispatch($submitEvent);
    
    if ($submitEvent->isPropagationStopped()) {
        // Plugin zatrzymał submit
        $errors[] = 'Plugin validation failed';
    } else {
        // Kontynuuj z zmodyfikowanymi danymi
        $this->service->process($submitEvent->getFormData());
    }
}
```

**Przykład subscribera pluginu:**
```php
class MyPluginValidationSubscriber implements EventSubscriberInterface
{
    public function onFormSubmit(FormSubmitEvent $event): void
    {
        if ($event->getFormType() === 'registration') {
            $newsletter = $event->getFormValue('newsletter');
            
            if ($newsletter) {
                // Zapisz subskrypcję
                $this->newsletterService->subscribe($event->getFormValue('email'));
            }
            
            // Opcjonalnie zatrzymaj submit
            if (!$this->customValidation($event->getFormData())) {
                $event->stopPropagation();
            }
        }
    }
}
```

---

#### **3. ViewDataEvent - Modyfikacja Danych Widoku**

**Lokalizacja:** `src/Core/Event/View/ViewDataEvent.php`

**Kiedy:** Emitowany przed `$this->render()` w kontrolerze, po przygotowaniu danych  
**Typ:** Generyczny event  
**Payload:**
- `string $viewName` ('dashboard', 'registration', 'login', etc.)
- `array $viewData` (mutable) - dane przekazywane do template Twig
- `?UserInterface $user` - zalogowany użytkownik (jeśli jest)
- `array $context` (ip, userAgent, locale)

**Zastosowanie:**
- Pluginy mogą **dodawać własne dane** do widoku (widgets, notifications, banners)
- Pluginy mogą **modyfikować istniejące dane** (np. wzbogacić dane o dodatkowe info)
- Pluginy mogą **dodawać tracking scripts** do wszystkich widoków
- Pluginy mogą **personalizować widok** na podstawie użytkownika
- **Cross-cutting:** Plugin może modyfikować WSZYSTKIE widoki naraz

**Różnica od eventów domenowych:** `ViewDataEvent` służy do modyfikacji **UI/presentacji**, podczas gdy eventy domenowe (np. `DashboardAccessedEvent`) służą do **logiki biznesowej/analytics**

**Przykład użycia w kontrolerze:**
```php
public function index(): Response
{
    // Przygotuj dane widoku
    $viewData = [
        'servers' => $servers,
        'user' => $user,
        'logs' => $logs,
    ];
    
    // Emit ViewDataEvent dla pluginów
    $viewEvent = new ViewDataEvent('dashboard', $viewData, $user, $context);
    $this->eventDispatcher->dispatch($viewEvent);
    
    return $this->render('...', $viewEvent->getViewData());
}
```

**Przykład subscribera pluginu:**
```php
class MyPluginDashboardSubscriber implements EventSubscriberInterface
{
    public function onViewData(ViewDataEvent $event): void
    {
        // Dodaj widget tylko do dashboardu
        if ($event->getViewName() === 'dashboard' && $event->getUser()) {
            $event->setViewData('my_plugin_widget', [
                'title' => 'My Plugin Stats',
                'data' => $this->getPluginStats($event->getUser()),
            ]);
        }
        
        // Dodaj tracking do WSZYSTKICH widoków
        $event->setViewData('plugin_tracking_id', $this->generateTrackingId());
    }
}
```

---

#### **Implementacje w Projekcie:**

**Formularze:**
- ✅ `RegistrationFormType` - emit `FormBuildEvent`
- ✅ `RegistrationController::register()` - emit `FormSubmitEvent` + `ViewDataEvent`
- ✅ `LoginFormType` - emit `FormBuildEvent`
- ✅ `AuthorizationController::login()` - emit `ViewDataEvent`

**Widoki:**
- ✅ `DashboardController::index()` - emit `ViewDataEvent`
- ✅ `RegistrationController::register()` - emit `ViewDataEvent`
- ✅ `AuthorizationController::login()` - emit `ViewDataEvent`

**Form Themes:**
- ✅ `themes/default/form/bootstrap_5_custom.html.twig` - custom rendering dla Bootstrap 5
- ✅ Konfiguracja w `config/packages/twig.yaml`

**Dynamiczne Renderowanie:**
- ✅ `themes/default/panel/registration/register.html.twig` - używa `form_row()` zamiast hardkodu
- ✅ `themes/default/panel/login/login.html.twig` - używa `form_row()` + hardkodowany CSRF token

**CSRF w Formularzach:**
- ✅ **Rejestracja:** CSRF token automatyczny przez `form_end()` 
- ✅ **Logowanie:** CSRF token hardkodowany (`<input type="hidden" name="_csrf_token" value="{{ csrf_token('authenticate') }}">`)
  - `LoginFormType` ma `csrf_protection: false` (UserAuthenticator obsługuje walidację)
  - Hybrydowe podejście: Symfony Forms dla pól + hardkodowany CSRF dla kompatybilności z UserAuthenticator

**Docelowo:**
- Wszystkie FormTypes będą emitowały `FormBuildEvent`
- Wszystkie kontrolery będą emitowały `ViewDataEvent` przed renderem

---

#### **Kiedy Używać Eventów Generycznych vs Domenowych:**

| **Potrzeba Pluginu** | **Użyj Eventu** |
|---|------|
| Dodać pole do formularza rejestracji | `FormBuildEvent` (formType='registration') |
| Walidować dane formularza przed zapisem | `FormSubmitEvent` (formType='registration') |
| Wysłać welcome email po rejestracji | `UserRegisteredEvent` (domenowy) |
| Dodać widget do dashboardu | `ViewDataEvent` (viewName='dashboard') |
| Dodać tracking do wszystkich widoków | `ViewDataEvent` (wszystkie viewName) |
| Nadać bonusowe kredyty po weryfikacji | `UserEmailVerifiedEvent` (domenowy) |
| Dodać CAPTCHA do wszystkich formularzy | `FormBuildEvent` (wszystkie formType) |
| Integracja z CRM po rejestracji | `UserRegisteredEvent` (domenowy) |

**Wniosek:** Eventy **generyczne** służą do modyfikacji **UI i formularzy**, eventy **domenowe** służą do **logiki biznesowej i integracji**.

---

---

### ✅ My Servers (Lista Serwerów Użytkownika)

**Lokalizacja eventów:** `src/Core/Event/Server/`

**Eventy:**
1. `ServersListAccessedEvent` (post) - wejście na stronę `/servers`
2. `ServersListDataLoadedEvent` (post) - po załadowaniu listy serwerów

**Subscriber:** Brak - eventy są emitowane tylko dla pluginów

**Flow:**
```
GET /servers
  → ServersListAccessedEvent
  → Pobieranie serwerów użytkownika (ServerService::getServersWithAccess)
  → Dodawanie ścieżek do obrazków produktów
  → ServersListDataLoadedEvent (payload: servers, serversCount)
  → ViewDataEvent (viewName='servers_list')
  → Render template
```

**Zastosowanie:**
- Analytics i tracking odwiedzin strony serwerów (przez pluginy)
- Monitoring użycia - ile serwerów użytkownik ma (przez pluginy)
- Performance tracking ładowania listy (przez pluginy)
- Pluginy mogą dodać custom widgets/statystyki do widoku
- Pluginy mogą modyfikować dane serwerów przed wyświetleniem

**Charakterystyka:**
- Minimalistyczne podejście (tylko 2 eventy domenowe + ViewDataEvent)
- Read-only view - brak operacji zapisu
- Brak built-in subscriberów - tylko dla pluginów
- Fokus na rozszerzalność przez pluginy
- Spójność z DashboardAccessedEvent/DashboardDataLoadedEvent

---

---

### ✅ Store (Sklep)

**Lokalizacja eventów:** `src/Core/Event/Store/`

**3 przepływy:**
1. **Store Index** (`/store`) - główna strona sklepu z kategoriami i produktami
2. **Store Category** (`/store/category?id=X`) - produkty w kategorii
3. **Store Product** (`/store/product?id=X`) - szczegóły produktu

---

#### **1. Store Index - Główna strona sklepu**

**Eventy:**
- `StoreAccessedEvent` (post) - wejście na `/store`
- `StoreDataLoadedEvent` (post) - po załadowaniu kategorii i produktów

**Flow:**
```
GET /store
  → StoreAccessedEvent (userId może być null - niezalogowany użytkownik)
  → Pobieranie kategorii (StoreService::getCategories)
  → Pobieranie produktów (StoreService::getCategoryProducts)
  → StoreDataLoadedEvent (payload: categories, products, categoriesCount, productsCount)
  → ViewDataEvent (viewName='store_index')
  → Render template
```

**Zastosowanie:**
- Analytics - tracking odwiedzin sklepu (przez pluginy)
- Monitoring użycia - ile kategorii/produktów jest wyświetlanych (przez pluginy)
- Performance tracking (przez pluginy)
- Pluginy mogą dodać custom filtering/sorting produktów
- Pluginy mogą dodać banery/promocje do widoku sklepu

---

#### **2. Store Category - Produkty w kategorii**

**Eventy:**
- `StoreCategoryAccessedEvent` (post) - wejście na kategorię
- `StoreCategoryDataLoadedEvent` (post) - po załadowaniu produktów

**Flow:**
```
GET /store/category?id=X
  → Walidacja istnienia kategorii (404 jeśli brak)
  → StoreCategoryAccessedEvent (payload: userId, categoryId, categoryName)
  → Pobieranie produktów (StoreService::getCategoryProducts)
  → StoreCategoryDataLoadedEvent (payload: userId, categoryId, products, productsCount)
  → ViewDataEvent (viewName='store_category')
  → Render template
```

**Zastosowanie:**
- Analytics - które kategorie są najpopularniejsze (przez pluginy)
- Tracking nawigacji użytkownika (przez pluginy)
- A/B testing kategorii (przez pluginy)
- Pluginy mogą dodać custom sorting/filtering
- Pluginy mogą personalizować produkty w kategorii

---

#### **3. Store Product - Szczegóły produktu**

**Eventy:**
- `StoreProductViewedEvent` (post) - wyświetlenie produktu
- `StoreProductDataLoadedEvent` (post) - po załadowaniu szczegółów

**Flow:**
```
GET /store/product?id=X
  → Walidacja istnienia produktu (404 jeśli brak)
  → StoreService::prepareProduct (dodaje metadata, ścieżki do obrazków)
  → StoreProductViewedEvent (payload: userId, productId, productName, productPrices)
  → Pobieranie eggs (StoreService::getProductEggs)
  → StoreProductDataLoadedEvent (payload: userId, productId, product, eggs, eggsCount)
  → ViewDataEvent (viewName='store_product')
  → Render template
```

**Zastosowanie:**
- Analytics - które produkty są najpopularniejsze (przez pluginy)
- Tracking product views - conversion funnel (przez pluginy)
- Recommendations - "często oglądane razem" (przez pluginy)
- Dynamic pricing - pluginy mogą modyfikować ceny przed wyświetleniem
- Custom upsells/cross-sells (przez pluginy)

---

**Charakterystyka:**
- ✅ **Minimalistyczne** - 2 eventy domenowe per flow + ViewDataEvent
- ✅ **E-commerce specific** - tracking views, prices, recommendations
- ✅ **Może być niezalogowany** - userId może być null (publiczny sklep)
- ✅ **Brak built-in subscriberów** - tylko dla pluginów
- ✅ **Fokus na rozszerzalność** przez pluginy
- ✅ **Spójność** z Dashboard i My Servers

**Uwaga:** `StoreProductViewedEvent` zawiera `Collection` cen (`productPrices`), ponieważ produkt może mieć wiele wariantów cenowych (static, dynamic, slot-based), które użytkownik może konfigurować.

---

### Kolejne Procesy do Migracji

- [ ] Admin Overview (OverviewController)
- [ ] Dodanie FormBuildEvent i ViewDataEvent do pozostałych formularzy i kontrolerów
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
