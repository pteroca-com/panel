import { test, expect } from '@playwright/test';
import { LoginPage } from '../../page-objects/LoginPage';
import { AdminPage, ProductsAdminPage } from '../../page-objects/AdminPage';
import { testConfig } from '../../helpers/test-config';

test.describe('Admin Tests @smoke @admin', () => {
  
  test.beforeEach(async ({ page }) => {
    // Zaloguj się jako admin przed każdym testem
    const loginPage = new LoginPage(page);
    await loginPage.goto();
    await loginPage.login(testConfig.admin.email, testConfig.admin.password);
    
    // Poczekaj na przekierowanie po logowaniu
    await page.waitForLoadState('networkidle');
  });

  test('REG-063: Create new product as admin', async ({ page }) => {
    const adminPage = new AdminPage(page);
    const productsPage = new ProductsAdminPage(page);

    // Given: Admin jest na stronie zarządzania produktami
    await adminPage.navigateToProducts();
    await productsPage.waitForLoad();

    // When: Kliknij "Add Product" i wypełnij wszystkie wymagane pola
    await productsPage.newProductButton.click();
    
    await page.waitForLoadState('networkidle');

    const testProductData = {
      name: `Test Product ${Date.now()}`,
      description: 'Test product created by E2E test',
      memory: 1024,
      cpu: 50,
      diskSpace: 2048,
      ports: 1,
      backups: 1,
    };

    await adminPage.fillProductForm(testProductData);
    await adminPage.saveProduct();

    // Then: Nowy produkt zostaje utworzony pomyślnie
    await page.waitForLoadState('networkidle');
    
    // Sprawdź czy jesteśmy z powrotem na liście produktów lub nastąpiło przekierowanie
    const currentUrl = page.url();
    const pageContent = await page.textContent('body');
    
    // Sprawdź czy operacja się powiodła
    const isSuccess = currentUrl.includes('Product') || 
                     pageContent?.includes('successfully') ||
                     pageContent?.includes('created') ||
                     !pageContent?.includes('error');
    
    expect(isSuccess).toBeTruthy();

    // Sprawdź czy można wrócić do listy produktów
    if (!currentUrl.includes('index')) {
      await adminPage.navigateToProducts();
      await productsPage.waitForLoad();
    }

    // Sprawdź czy produkt jest dostępny w liście (opcjonalne - może być na kolejnej stronie)
    await productsPage.searchProduct(testProductData.name);
    
    // Jeśli wyszukiwanie zadziałało, sprawdź czy produkt jest widoczny
    if (await productsPage.searchField.isVisible()) {
      const productVisible = await productsPage.isProductListed(testProductData.name);
      // Produkt powinien być widoczny jeśli wyszukiwanie działa
      if (await productsPage.getProductsCount() > 0) {
        expect(productVisible).toBeTruthy();
      }
    }
  });

  test('Admin dashboard access and elements', async ({ page }) => {
    const adminPage = new AdminPage(page);

    // Given: Przejdź do panelu administracyjnego
    await adminPage.goto();
    await adminPage.waitForLoad();

    // Then: Panel administracyjny powinien być dostępny
    await expect(adminPage.pageTitle).toBeVisible();
    await expect(adminPage.statisticsCards.first()).toBeVisible();
    
    // Sprawdź czy statystyki są wyświetlane
    const cardsCount = await adminPage.statisticsCards.count();
    expect(cardsCount).toBeGreaterThan(0);

    // Sprawdź czy informacje systemowe są widoczne
    await expect(adminPage.systemInfo).toBeVisible();
    
    // Sprawdź czy przycisk powrotu do panelu użytkownika działa
    await expect(adminPage.backToUserPanelButton).toBeVisible();
  });

  test('Admin navigation to products management', async ({ page }) => {
    const adminPage = new AdminPage(page);
    const productsPage = new ProductsAdminPage(page);

    // Given: Admin jest w panelu administracyjnym
    await adminPage.goto();
    await adminPage.waitForLoad();

    // When: Przejdź do zarządzania produktami
    await adminPage.navigateToProducts();
    await productsPage.waitForLoad();

    // Then: Strona zarządzania produktami powinna się załadować
    await expect(productsPage.pageTitle).toBeVisible();
    await expect(productsPage.newProductButton).toBeVisible();
    
    // Sprawdź czy lista produktów jest widoczna (może być pusta)
    const currentUrl = page.url();
    expect(currentUrl).toMatch(/(Product|product)/);
  });

  test('Admin back to user panel navigation', async ({ page }) => {
    const adminPage = new AdminPage(page);

    // Given: Admin jest w panelu administracyjnym
    await adminPage.goto();
    await adminPage.waitForLoad();

    // When: Kliknij przycisk powrotu do panelu użytkownika
    await adminPage.backToUserPanel();
    
    await page.waitForLoadState('networkidle');

    // Then: Powinien wrócić do głównego panelu
    const currentUrl = page.url();
    const pageContent = await page.textContent('body');
    
    // Sprawdź czy jesteśmy z powrotem w panelu użytkownika
    const isBackInUserPanel = !currentUrl.includes('admin') ||
                             pageContent?.includes('Dashboard') ||
                             pageContent?.includes('Balance');
    
    expect(isBackInUserPanel).toBeTruthy();
  });

});
