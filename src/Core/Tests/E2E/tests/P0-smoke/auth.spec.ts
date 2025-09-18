import { test, expect } from '@playwright/test';
import { LoginPage } from '../../page-objects/LoginPage';
import { DashboardPage } from '../../page-objects/DashboardPage';
import { testConfig } from '../../helpers/test-config';

test.describe('Authentication Tests @smoke @auth', () => {
  
  test('REG-001: Successful user login', async ({ page }) => {
    const loginPage = new LoginPage(page);
    const dashboardPage = new DashboardPage(page);

    // Given: Przejdź na stronę logowania
    await loginPage.goto();

    // When: Wprowadź poprawne dane logowania i kliknij "Sign in"
    await loginPage.login(testConfig.user.email, testConfig.user.password);

    // Then: Użytkownik zostaje uwierzytelniony i przekierowany do Dashboard
    await dashboardPage.waitForLoad();
    expect(await dashboardPage.isLoaded()).toBeTruthy();
    
    // Sprawdź czy URL zawiera dashboard lub panel
    expect(page.url()).toMatch(/(dashboard|panel)/);
  });

  test('REG-003: User logout', async ({ page }) => {
    const loginPage = new LoginPage(page);
    const dashboardPage = new DashboardPage(page);

    // Given: Zalogowany użytkownik
    await loginPage.goto();
    await loginPage.login(testConfig.user.email, testConfig.user.password);
    await dashboardPage.waitForLoad();

    // When: Kliknij opcję "Logout"
    await dashboardPage.logout();

    // Then: Użytkownik zostaje wylogowany i przekierowany na stronę logowania
    await expect(page).toHaveURL(/\/login/);
    
    // Sprawdź czy można ponownie zalogować (brak sesji)
    await loginPage.login(testConfig.user.email, testConfig.user.password);
    await dashboardPage.waitForLoad();
    expect(await dashboardPage.isLoaded()).toBeTruthy();
  });

  test('REG-002: Login with incorrect password', async ({ page }) => {
    const loginPage = new LoginPage(page);

    // Given: Strona logowania jest wyświetlona
    await loginPage.goto();

    // When: Wprowadź niepoprawne hasło dla poprawnego konta użytkownika
    await loginPage.login(testConfig.user.email, 'wrongpassword');

    // Then: Próba logowania kończy się niepowodzeniem z odpowiednim komunikatem błędu
    await loginPage.waitForLoginError();
    const errorMessage = await loginPage.getErrorMessage();
    expect(errorMessage).toContain('Invalid credentials');
    
    // Użytkownik pozostaje na stronie logowania
    expect(page.url()).toMatch(/\/login/);
  });

});
