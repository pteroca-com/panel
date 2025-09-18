import { Page, Locator } from '@playwright/test';

export class StorePage {
  readonly page: Page;
  readonly pageTitle: Locator;
  readonly storeDescription: Locator;
  readonly productCards: Locator;
  readonly categoryCards: Locator;
  readonly emptyStoreMessage: Locator;
  readonly firstProductOrderButton: Locator;

  constructor(page: Page) {
    this.page = page;
    // Selektory oparte na strukturze z store/index.html.twig
    this.pageTitle = page.locator('h4').filter({ hasText: 'Store' });
    this.storeDescription = page.locator('p.text-muted');
    this.productCards = page.locator('.card').filter({ hasText: 'Order' });
    this.categoryCards = page.locator('.card').filter({ hasText: 'Category' });
    this.emptyStoreMessage = page.locator('text=Shop is empty');
    this.firstProductOrderButton = page.locator('a[href*="store_product"]').first();
  }

  async goto() {
    await this.page.goto('/panel?routeName=store');
  }

  async waitForLoad() {
    await this.pageTitle.waitFor({ state: 'visible' });
  }

  async getProductCount(): Promise<number> {
    return await this.productCards.count();
  }

  async clickFirstProduct() {
    await this.firstProductOrderButton.click();
  }

  async hasProducts(): Promise<boolean> {
    return (await this.getProductCount()) > 0;
  }

  async getFirstProductName(): Promise<string> {
    const firstProduct = this.productCards.first();
    const nameElement = firstProduct.locator('h5.card-title');
    return await nameElement.textContent() || '';
  }
}

export class ProductPage {
  readonly page: Page;
  readonly productName: Locator;
  readonly durationSelect: Locator;
  readonly eggSelect: Locator;
  readonly orderButton: Locator;
  readonly priceCalculation: Locator;
  readonly totalPrice: Locator;
  readonly ramSpec: Locator;
  readonly cpuSpec: Locator;
  readonly diskSpec: Locator;

  constructor(page: Page) {
    this.page = page;
    // Selektory oparte na strukturze z store/product.html.twig
    this.productName = page.locator('h4').first();
    this.durationSelect = page.locator('#duration');
    this.eggSelect = page.locator('#egg');
    this.orderButton = page.locator('#order-submit');
    this.priceCalculation = page.locator('[data-type="totalPrice"]');
    this.totalPrice = page.locator('[data-type="totalPrice"] .fw-bold.text-success.fs-5');
    this.ramSpec = page.locator('text=/RAM.*MB/');
    this.cpuSpec = page.locator('text=/CPU.*%/');
    this.diskSpec = page.locator('text=/Disk.*MB/');
  }

  async waitForLoad() {
    await this.productName.waitFor({ state: 'visible' });
    await this.orderButton.waitFor({ state: 'visible' });
  }

  async selectDuration(index: number = 0) {
    await this.durationSelect.selectOption({ index });
  }

  async selectEgg(index: number = 0) {
    await this.eggSelect.selectOption({ index });
  }

  async clickOrder() {
    await this.orderButton.click();
  }

  async getSelectedDuration(): Promise<string> {
    return await this.durationSelect.inputValue();
  }

  async getSelectedEgg(): Promise<string> {
    return await this.eggSelect.inputValue();
  }

  async getProductName(): Promise<string> {
    return await this.productName.textContent() || '';
  }

  async waitForPriceCalculation() {
    await this.priceCalculation.waitFor({ state: 'visible' });
  }
}
