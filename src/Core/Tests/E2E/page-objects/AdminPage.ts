import { Page, Locator } from '@playwright/test';

export class AdminPage {
  readonly page: Page;
  readonly pageTitle: Locator;
  readonly statisticsCards: Locator;
  readonly serversCount: Locator;
  readonly usersCount: Locator;
  readonly paymentsCount: Locator;
  readonly systemInfo: Locator;
  readonly backToUserPanelButton: Locator;
  readonly viewAllPaymentsLink: Locator;
  readonly viewAllUsersLink: Locator;

  constructor(page: Page) {
    this.page = page;
    // Selektory oparte na strukturze z admin/overview.html.twig
    this.pageTitle = page.locator('h1, h2').filter({ hasText: 'Overview' });
    this.statisticsCards = page.locator('.card');
    this.serversCount = page.locator('text=/\\d+/').first(); // liczba serwerów
    this.usersCount = page.locator('text=/\\d+/').nth(1); // liczba użytkowników
    this.paymentsCount = page.locator('text=/\\d+/').nth(2); // liczba płatności
    this.systemInfo = page.locator('.card').filter({ hasText: 'System' });
    this.backToUserPanelButton = page.locator('a.btn').filter({ hasText: 'Back' });
    this.viewAllPaymentsLink = page.locator('a[href*="PaymentCrudController"]');
    this.viewAllUsersLink = page.locator('a[href*="UserCrudController"]');
  }

  async goto() {
    // Admin funkcje są dostępne w tym samym panelu dla użytkowników z uprawnieniami
    await this.page.goto('/panel');
  }

  async waitForLoad() {
    // Sprawdź czy użytkownik ma dostęp do admin funkcji przez sprawdzenie obecności elementów admin
    try {
      // Poczekaj na załadowanie dashboardu
      await this.page.waitForLoadState('networkidle');
      // Sprawdź czy są dostępne linki administracyjne w menu
      const adminLinks = this.page.locator('a[href*="ProductCrudController"], a[href*="UserCrudController"], a[href*="PaymentCrudController"]');
      const hasAdminAccess = await adminLinks.count() > 0;
      if (!hasAdminAccess) {
        throw new Error('User does not have admin access');
      }
    } catch {
      throw new Error('User does not have admin access');
    }
  }

  async isLoaded(): Promise<boolean> {
    try {
      await this.pageTitle.waitFor({ timeout: 5000 });
      return true;
    } catch {
      return false;
    }
  }

  async navigateToProducts() {
    // EasyAdmin - przejdź do zarządzania produktami
    await this.page.goto('/panel?crudAction=index&crudControllerFqcn=App%5CCore%5CController%5CPanel%5CProductCrudController');
  }

  async clickNewProduct() {
    // Kliknij przycisk dodawania nowego produktu w EasyAdmin
    const newButton = this.page.locator('a').filter({ hasText: 'New' }).or(
      this.page.locator('a').filter({ hasText: 'Add' })
    ).or(
      this.page.locator('a[href*="new"]')
    );
    await newButton.click();
  }

  async fillProductForm(productData: {
    name: string;
    description?: string;
    memory?: number;
    cpu?: number;
    diskSpace?: number;
    ports?: number;
    backups?: number;
  }) {
    // Wypełnij formularz produktu w EasyAdmin
    const nameField = this.page.locator('input[name*="name"], #product_name');
    await nameField.fill(productData.name);

    if (productData.description) {
      const descField = this.page.locator('textarea[name*="description"], #product_description');
      await descField.fill(productData.description);
    }

    if (productData.memory) {
      const memoryField = this.page.locator('input[name*="memory"], #product_memory');
      await memoryField.fill(productData.memory.toString());
    }

    if (productData.cpu) {
      const cpuField = this.page.locator('input[name*="cpu"], #product_cpu');
      await cpuField.fill(productData.cpu.toString());
    }

    if (productData.diskSpace) {
      const diskField = this.page.locator('input[name*="disk"], #product_diskSpace');
      await diskField.fill(productData.diskSpace.toString());
    }
  }

  async saveProduct() {
    // Zapisz produkt w EasyAdmin
    const saveButton = this.page.locator('button[type="submit"]').or(
      this.page.locator('input[type="submit"]')
    ).or(
      this.page.locator('button').filter({ hasText: 'Save' })
    );
    await saveButton.click();
  }

  async backToUserPanel() {
    await this.backToUserPanelButton.click();
  }
}

export class ProductsAdminPage {
  readonly page: Page;
  readonly pageTitle: Locator;
  readonly newProductButton: Locator;
  readonly productsList: Locator;
  readonly searchField: Locator;

  constructor(page: Page) {
    this.page = page;
    this.pageTitle = page.locator('h1, h2').filter({ hasText: 'Product' });
    this.newProductButton = page.locator('a').filter({ hasText: 'Add product' });
    this.productsList = page.locator('table tbody tr');
    this.searchField = page.locator('input[type="search"]');
  }

  async waitForLoad() {
    await this.pageTitle.waitFor({ state: 'visible' });
  }

  async getProductsCount(): Promise<number> {
    return await this.productsList.count();
  }

  async searchProduct(name: string) {
    if (await this.searchField.isVisible()) {
      await this.searchField.fill(name);
      await this.page.keyboard.press('Enter');
      await this.page.waitForLoadState('networkidle');
    }
  }

  async isProductListed(name: string): Promise<boolean> {
    const productRow = this.page.locator('tr').filter({ hasText: name });
    return await productRow.isVisible();
  }
}
