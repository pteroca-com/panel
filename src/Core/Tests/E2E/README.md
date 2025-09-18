# Testy E2E dla PteroCA

Struktura testów End-to-End z Playwright dla aplikacji PteroCA v0.5.8

## Wymagania

- Node.js 18+
- Działająca lokalnie aplikacja PteroCA na `http://localhost:8080`
- Konta testowe (admin i user) skonfigurowane w aplikacji

## Konfiguracja

1. **Zmienne środowiskowe**: Edytuj plik `.env.test` i dostosuj dane logowania:
   ```
   BASE_URL=http://localhost:8080
   ADMIN_EMAIL=admin@example.com
   ADMIN_PASSWORD=password123
   USER_EMAIL=user@example.com
   USER_PASSWORD=password123
   ```

2. **Instalacja zależności**:
   ```bash
   cd src/Core/Tests/E2E
   npm install
   npx playwright install
   ```

## Uruchamianie testów

### Testy P0 (krytyczne smoke testy)
```bash
npm run test:P0
# lub
npm run test:smoke
```

### Wszystkie testy
```bash
npm test
```

### Tryb debugowania
```bash
npm run test:debug
```

### Tryb UI (interfejs graficzny)
```bash
npm run test:ui
```

### Tryb z widoczną przeglądarką
```bash
npm run test:headed
```

## Struktura testów P0

Zaimplementowano 3 z 8 planowanych testów P0:

### ✅ Zaimplementowane:
- **REG-001**: Pomyślne logowanie użytkownika
- **REG-002**: Logowanie z niepoprawnym hasłem  
- **REG-003**: Wylogowanie użytkownika

### 🔄 Do zaimplementowania:
- **REG-020**: Doładowanie portfela przez Stripe (MOCK)
- **REG-030**: Zakup serwera ze sklepu
- **REG-040**: Wyświetlanie listy serwerów użytkownika
- **REG-063**: Tworzenie nowego produktu przez admina

## Struktura folderów

```
src/Core/Tests/E2E/
├── .env.test                 # Zmienne środowiskowe
├── playwright.config.ts      # Konfiguracja Playwright
├── tsconfig.json            # Konfiguracja TypeScript
├── helpers/                 # Funkcje pomocnicze
│   └── test-config.ts       # Konfiguracja testów
├── page-objects/            # Page Object Model
│   ├── LoginPage.ts
│   └── DashboardPage.ts
└── tests/                   # Testy
    └── P0-smoke/            # Testy krytyczne
        └── auth.spec.ts     # Testy autoryzacji
```

## Uwagi

- Testy są dostosowane do rzeczywistej struktury widoków Twig
- Używają lokalnie uruchomionej aplikacji (nie uruchamiają dockera)
- Wszystkie selektory bazują na rzeczywistych elementach HTML z aplikacji
- Testy są oznaczone tagami (@smoke, @auth) dla łatwego filtrowania

## Następne kroki

Aby dokończyć implementację, należy:
1. Przeanalizować widoki wallet, store, servers i admin
2. Utworzyć Page Object Models dla tych sekcji
3. Zaimplementować pozostałe 5 testów P0
4. Dodać testy P1 i P2 w przyszłości
