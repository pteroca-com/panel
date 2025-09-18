import { defineConfig, devices } from '@playwright/test';

/**
 * Konfiguracja Playwright dla testów E2E PteroCA
 * Obsługuje testy P0 (smoke), P1 (regression) i P2 (edge cases)
 */
export default defineConfig({
  testDir: './tests',
  
  /* Uruchom testy równolegle */
  fullyParallel: true,
  
  /* Nie udajemy żadnych testów w CI */
  forbidOnly: !!process.env.CI,
  
  /* Retry tylko w CI */
  retries: process.env.CI ? 2 : 0,
  
  /* Opt out of parallel tests na CI */
  workers: process.env.CI ? 1 : undefined,
  
  /* Reporter do użycia */
  reporter: [
    ['html'],
    ['junit', { outputFile: 'test-results/results.xml' }],
    ['list']
  ],
  
  /* Globalne opcje dla wszystkich projektów */
  use: {
    /* Czas na akcje */
    actionTimeout: 10000,
    
    /* Base URL dla testów */
    baseURL: process.env.BASE_URL || 'http://localhost:8080',
    
    /* Kolekcja śladów tylko przy niepowodzeniu */
    trace: 'on-first-retry',
    
    /* Screenshots tylko przy niepowodzeniu */
    screenshot: 'only-on-failure',
    
    /* Video tylko przy niepowodzeniu */
    video: 'retain-on-failure',
    
    /* Ignorowanie błędów HTTPS */
    ignoreHTTPSErrors: true,
  },

  /* Konfiguracja różnych projektów testowych */
  projects: [
    {
      name: 'P0-smoke-chromium',
      testDir: './tests/P0-smoke',
      use: { ...devices['Desktop Chrome'] },
      grep: /@smoke/,
    },
    
    {
      name: 'P1-regression-chromium', 
      testDir: './tests/P1-regression',
      use: { ...devices['Desktop Chrome'] },
      grep: /@regression/,
    },
    
    {
      name: 'P2-edge-cases-chromium',
      testDir: './tests/P2-edge-cases', 
      use: { ...devices['Desktop Chrome'] },
      grep: /@edge/,
    },

    /* Testy mobilne - opcjonalne */
    // {
    //   name: 'Mobile Chrome',
    //   use: { ...devices['Pixel 5'] },
    // },
  ],

  /* Nie uruchamiamy serwera - używamy już działającej aplikacji */
  // webServer: {
  //   command: 'docker compose up -d',
  //   url: 'http://localhost:8080',
  //   reuseExistingServer: !process.env.CI,
  //   timeout: 120000,
  // },

  /* Opcjonalne globalne setup i teardown - na razie wyłączone */
  // globalSetup: require.resolve('./helpers/global-setup.ts'),
  // globalTeardown: require.resolve('./helpers/global-teardown.ts'),
  
  /* Timeout dla całego testu */
  timeout: 30000,
  
  /* Timeout dla expect */
  expect: {
    timeout: 5000,
  },
});
