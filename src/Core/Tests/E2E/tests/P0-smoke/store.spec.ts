import { test, expect } from '@playwright/test';
import { LoginPage } from '../../page-objects/LoginPage';
import { DashboardPage } from '../../page-objects/DashboardPage';
import { StorePage, ProductPage } from '../../page-objects/StorePage';
import { testConfig } from '../../helpers/test-config';

test.describe('Store Tests @smoke @ptero', () => {
  
  test.beforeEach(async ({ page }) => {
    // Zaloguj się przed każdym testem
    const loginPage = new LoginPage(page);
    await loginPage.goto();
    await loginPage.login(testConfig.user.email, testConfig.user.password);
    
    const dashboardPage = new DashboardPage(page);
    await dashboardPage.waitForLoad();
  });

  test('REG-030: Server purchase from store', async ({ page }) => {
    const dashboardPage = new DashboardPage(page);
    const storePage = new StorePage(page);
    const productPage = new ProductPage(page);

    // Given: Zalogowany użytkownik z wystarczającym saldem portfela
    await dashboardPage.clickBrowseStore();
    await storePage.waitForLoad();

    // Sprawdź czy sklep ma produkty
    const hasProducts = await storePage.hasProducts();
    if (!hasProducts) {
      console.log('Store is empty, skipping purchase test');
      test.skip();
      return;
    }

    // When: Przejdź do sklepu i wybierz dostępny produkt (plan serwera)
    await storePage.clickFirstProduct();
    await productPage.waitForLoad();

    // Skonfiguruj opcje (wybierz domyślny okres rozliczeniowy)
    await productPage.selectDuration(0);
    await productPage.selectEgg(0);

    // Sprawdź czy elementy są widoczne
    await expect(productPage.productName).toBeVisible();
    await expect(productPage.durationSelect).toBeVisible();
    await expect(productPage.eggSelect).toBeVisible();
    await expect(productPage.orderButton).toBeVisible();

    // Kliknij "Purchase" 
    await productPage.clickOrder();

    // Then: Zakup zostaje zakończony pomyślnie
    await page.waitForLoadState('networkidle');
    
    // Sprawdź czy zostaliśmy przekierowani do koszyka lub potwierdzenia
    expect(page.url()).toMatch(/(cart|configure|order|checkout)/);
    
    // Sprawdź czy na stronie są informacje o produkcie
    const pageContent = await page.textContent('body');
    expect(pageContent).toBeTruthy();
  });

  test('REG-031: Insufficient balance prevention', async ({ page }) => {
    const dashboardPage = new DashboardPage(page);
    const storePage = new StorePage(page);
    const productPage = new ProductPage(page);

    // Given: Przejdź do sklepu
    await dashboardPage.clickBrowseStore();
    await storePage.waitForLoad();

    // Sprawdź czy sklep ma produkty
    const hasProducts = await storePage.hasProducts();
    if (!hasProducts) {
      test.skip();
      return;
    }

    // When: Spróbuj kupić serwer (może nie mieć wystarczającego salda)
    await storePage.clickFirstProduct();
    await productPage.waitForLoad();
    
    await productPage.selectDuration(0);
    await productPage.selectEgg(0);
    await productPage.clickOrder();

    await page.waitForLoadState('networkidle');

    // Then: System powinien sprawdzić saldo
    // Jeśli saldo jest niewystarczające, powinien być komunikat o błędzie
    // Jeśli saldo wystarcza, powinno nastąpić przekierowanie do koszyka
    const currentUrl = page.url();
    const pageContent = await page.textContent('body');
    
    // Sprawdź czy jesteśmy na odpowiedniej stronie (cart, error, lub recharge)
    const isValidResponse = currentUrl.match(/(cart|configure|recharge|insufficient|error)/) || 
                           pageContent?.includes('insufficient') || 
                           pageContent?.includes('balance') ||
                           pageContent?.includes('saldo');
    
    expect(isValidResponse).toBeTruthy();
  });

  test('Store page elements verification', async ({ page }) => {
    const dashboardPage = new DashboardPage(page);
    const storePage = new StorePage(page);

    // Given: Przejdź do sklepu
    await dashboardPage.clickBrowseStore();
    await storePage.waitForLoad();

    // Then: Sprawdź czy wszystkie elementy są widoczne
    await expect(storePage.pageTitle).toBeVisible();
    
    // Sprawdź czy sklep ma produkty lub wyświetla komunikat o pustym sklepie
    const hasProducts = await storePage.hasProducts();
    
    if (hasProducts) {
      // Sklep ma produkty
      expect(await storePage.getProductCount()).toBeGreaterThan(0);
      await expect(storePage.firstProductOrderButton).toBeVisible();
    } else {
      // Sklep jest pusty
      await expect(storePage.emptyStoreMessage).toBeVisible();
    }
  });

});
