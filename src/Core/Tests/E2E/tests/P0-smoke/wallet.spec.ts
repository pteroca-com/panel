import { test, expect } from '@playwright/test';
import { LoginPage } from '../../page-objects/LoginPage';
import { DashboardPage } from '../../page-objects/DashboardPage';
import { WalletPage } from '../../page-objects/WalletPage';
import { testConfig } from '../../helpers/test-config';

test.describe('Wallet Tests @smoke @billing', () => {
  
  test.beforeEach(async ({ page }) => {
    // Zaloguj się przed każdym testem
    const loginPage = new LoginPage(page);
    await loginPage.goto();
    await loginPage.login(testConfig.user.email, testConfig.user.password);
    
    const dashboardPage = new DashboardPage(page);
    await dashboardPage.waitForLoad();
  });

  test('REG-020: Wallet top-up with valid amount', async ({ page }) => {
    const dashboardPage = new DashboardPage(page);
    const walletPage = new WalletPage(page);

    // Given: Zalogowany użytkownik na stronie Wallet
    await dashboardPage.clickRechargeBalance();
    await walletPage.waitForLoad();

    // When: Wprowadź poprawną kwotę doładowania (np. $10) i zainicjuj doładowanie
    await walletPage.enterAmount('10.00');
    
    // Sprawdź czy przycisk jest aktywny
    expect(await walletPage.isRechargeButtonEnabled()).toBeTruthy();
    
    await walletPage.clickRecharge();

    // Then: System przekierowuje do koszyka/płatności Stripe
    // W trybie MOCK sprawdzamy czy nastąpiło przekierowanie do odpowiedniej strony
    await page.waitForLoadState('networkidle');
    
    // Sprawdź czy zostaliśmy przekierowani do strony płatności lub koszyka
    expect(page.url()).toMatch(/(cart|payment|checkout|topup)/);
    
    // Sprawdź czy kwota została zachowana w URL lub na stronie
    const currentUrl = page.url();
    const pageContent = await page.textContent('body');
    
    // Sprawdź czy kwota 10.00 jest widoczna na stronie
    expect(pageContent).toContain('10');
  });

  test('REG-022: Invalid amount validation', async ({ page }) => {
    const dashboardPage = new DashboardPage(page);
    const walletPage = new WalletPage(page);

    // Given: Strona doładowania portfela
    await dashboardPage.clickRechargeBalance();
    await walletPage.waitForLoad();

    // When: Wprowadź niepoprawną kwotę (zero)
    await walletPage.enterAmount('0');
    
    // Then: Formularz powinien pokazać błąd walidacji
    await walletPage.clickRecharge();
    
    // Sprawdź czy wystąpił błąd walidacji lub przycisk pozostał nieaktywny
    try {
      await walletPage.waitForError();
      const errorMessage = await walletPage.getErrorMessage();
      expect(errorMessage.length).toBeGreaterThan(0);
    } catch {
      // Jeśli nie ma błędu, sprawdź czy nie nastąpiło przekierowanie
      expect(page.url()).toMatch(/recharge_balance/);
    }

    // Test z kwotą ujemną
    await walletPage.enterAmount('-5');
    await walletPage.clickRecharge();
    
    // Sprawdź czy nadal jesteśmy na stronie doładowania
    expect(page.url()).toMatch(/recharge_balance/);
  });

  test('Wallet page elements verification', async ({ page }) => {
    const dashboardPage = new DashboardPage(page);
    const walletPage = new WalletPage(page);

    // Given: Przejdź na stronę doładowania
    await dashboardPage.clickRechargeBalance();
    await walletPage.waitForLoad();

    // Then: Sprawdź czy wszystkie elementy są widoczne
    await expect(walletPage.pageTitle).toBeVisible();
    await expect(walletPage.amountInput).toBeVisible();
    await expect(walletPage.rechargeButton).toBeVisible();
    await expect(walletPage.useVoucherButton).toBeVisible();
    await expect(walletPage.transactionHistoryLink).toBeVisible();
    
    // Sprawdź czy waluta jest wyświetlana
    const currency = await walletPage.getCurrency();
    expect(currency.length).toBeGreaterThan(0);
  });

});
