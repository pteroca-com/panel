import { test, expect } from '@playwright/test';
import { LoginPage } from '../../page-objects/LoginPage';
import { DashboardPage } from '../../page-objects/DashboardPage';
import { ServersPage } from '../../page-objects/ServersPage';
import { testConfig } from '../../helpers/test-config';

test.describe('Servers Tests @smoke @ptero', () => {
  
  test.beforeEach(async ({ page }) => {
    // Zaloguj się przed każdym testem
    const loginPage = new LoginPage(page);
    await loginPage.goto();
    await loginPage.login(testConfig.user.email, testConfig.user.password);
    
    const dashboardPage = new DashboardPage(page);
    await dashboardPage.waitForLoad();
  });

  test('REG-040: Display user servers list', async ({ page }) => {
    const dashboardPage = new DashboardPage(page);
    const serversPage = new ServersPage(page);

    // Given: Zalogowany użytkownik ma co najmniej jeden aktywny serwer
    await dashboardPage.clickMyServers();
    await serversPage.waitForLoad();

    // Then: Powinien zobaczyć listę serwerów ze szczegółami
    await expect(serversPage.pageTitle).toBeVisible();
    await expect(serversPage.viewCardsButton).toBeVisible();
    await expect(serversPage.viewListButton).toBeVisible();

    // Sprawdź czy ma serwery lub jest komunikat o braku serwerów
    const hasServers = await serversPage.hasServers();
    
    if (hasServers) {
      // Ma serwery - sprawdź ich wyświetlanie
      expect(await serversPage.getServerCount()).toBeGreaterThan(0);
      
      // Poczekaj na załadowanie danych z API
      await serversPage.waitForServerDataLoad();
      
      // Sprawdź szczegóły pierwszego serwera
      const serverDetails = await serversPage.getServerDetails(0);
      expect(serverDetails.name.length).toBeGreaterThan(0);
      expect(serverDetails.id.length).toBeGreaterThan(0);
      
      // Sprawdź czy przyciski zarządzania są widoczne
      await expect(serversPage.manageServerButtons.first()).toBeVisible();
      await expect(serversPage.extendServerButtons.first()).toBeVisible();
      
    } else {
      // Brak serwerów - sprawdź komunikat
      await expect(serversPage.emptyServersMessage).toBeVisible();
    }
  });

  test('Server list view toggle', async ({ page }) => {
    const dashboardPage = new DashboardPage(page);
    const serversPage = new ServersPage(page);

    // Given: Przejdź na stronę serwerów
    await dashboardPage.clickMyServers();
    await serversPage.waitForLoad();

    // When: Przełącz na widok listy
    await serversPage.clickViewList();
    
    // Then: Widok listy powinien być aktywny
    expect(await serversPage.isListViewActive()).toBeTruthy();
    
    // When: Przełącz z powrotem na widok kart
    await serversPage.clickViewCards();
    
    // Then: Widok kart powinien być aktywny
    expect(await serversPage.isCardsViewActive()).toBeTruthy();
  });

  test('Server management navigation', async ({ page }) => {
    const dashboardPage = new DashboardPage(page);
    const serversPage = new ServersPage(page);

    // Given: Przejdź na stronę serwerów
    await dashboardPage.clickMyServers();
    await serversPage.waitForLoad();

    // Sprawdź czy ma serwery
    const hasServers = await serversPage.hasServers();
    if (!hasServers) {
      test.skip();
      return;
    }

    // When: Kliknij przycisk zarządzania pierwszym serwerem
    await serversPage.clickFirstManageServer();
    
    await page.waitForLoadState('networkidle');

    // Then: Powinno nastąpić przekierowanie do panelu zarządzania serwerem
    const currentUrl = page.url();
    const pageContent = await page.textContent('body');
    
    // Sprawdź czy jesteśmy na stronie zarządzania serwerem lub Pterodactyl panel
    const isValidResponse = currentUrl.match(/(server|pterodactyl|manage)/) || 
                           pageContent?.includes('Server') ||
                           pageContent?.includes('Console') ||
                           pageContent?.includes('Files');
    
    expect(isValidResponse).toBeTruthy();
  });

  test('Server extension navigation', async ({ page }) => {
    const dashboardPage = new DashboardPage(page);
    const serversPage = new ServersPage(page);

    // Given: Przejdź na stronę serwerów
    await dashboardPage.clickMyServers();
    await serversPage.waitForLoad();

    // Sprawdź czy ma serwery
    const hasServers = await serversPage.hasServers();
    if (!hasServers) {
      test.skip();
      return;
    }

    // When: Kliknij przycisk przedłużenia pierwszego serwera
    await serversPage.clickFirstExtendServer();
    
    await page.waitForLoadState('networkidle');

    // Then: Powinno nastąpić przekierowanie do strony przedłużenia/odnowienia
    const currentUrl = page.url();
    const pageContent = await page.textContent('body');
    
    // Sprawdź czy jesteśmy na stronie odnowienia
    const isValidResponse = currentUrl.match(/(renew|extend|cart)/) || 
                           pageContent?.includes('Renew') ||
                           pageContent?.includes('Extend') ||
                           pageContent?.includes('Cart');
    
    expect(isValidResponse).toBeTruthy();
  });

});
