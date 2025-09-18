import { Page, Locator } from '@playwright/test';

export class DashboardPage {
  readonly page: Page;
  readonly dashboardTitle: Locator;
  readonly welcomeMessage: Locator;
  readonly quickActionsSection: Locator;
  readonly browseStoreButton: Locator;
  readonly myServersButton: Locator;
  readonly rechargeBalanceButton: Locator;
  readonly balanceCard: Locator;
  readonly serversSection: Locator;
  readonly logoutButton: Locator;

  constructor(page: Page) {
    this.page = page;
    // Selektory oparte na strukturze z dashboard.html.twig
    this.dashboardTitle = page.locator('h4').filter({ hasText: 'Dashboard' });
    this.welcomeMessage = page.locator('p.text-muted');
    this.quickActionsSection = page.locator('.card-header:has-text("Quick Actions")').locator('..');
    this.browseStoreButton = page.locator('#main-menu a[href*="store"]').first();
    this.myServersButton = page.locator('#main-menu a[href*="servers"]').first();
    this.rechargeBalanceButton = page.locator('#main-menu a[href*="recharge_balance"]').first();
    this.balanceCard = page.locator('.card').filter({ hasText: 'Balance' });
    this.serversSection = page.locator('.card').filter({ hasText: 'Servers' });
    this.logoutButton = page.locator('a[href="/logout"].menu-item-contents').first();
  }

  async goto() {
    await this.page.goto('/dashboard');
  }

  async waitForLoad() {
    await this.dashboardTitle.waitFor({ state: 'visible' });
  }

  async isLoaded(): Promise<boolean> {
    try {
      await this.dashboardTitle.waitFor({ timeout: 5000 });
      return true;
    } catch {
      return false;
    }
  }

  async clickBrowseStore() {
    await this.browseStoreButton.click();
  }

  async clickMyServers() {
    await this.myServersButton.click();
  }

  async clickRechargeBalance() {
    await this.rechargeBalanceButton.click();
  }

  async logout() {
    await this.logoutButton.click();
  }

  async getBalance(): Promise<string> {
    const balanceText = await this.balanceCard.textContent();
    return balanceText?.match(/\$?[\d,]+\.?\d*/)?.[0] || '0';
  }
}
