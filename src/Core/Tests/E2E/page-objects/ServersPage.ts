import { Page, Locator } from '@playwright/test';

export class ServersPage {
  readonly page: Page;
  readonly pageTitle: Locator;
  readonly serversDescription: Locator;
  readonly serverCards: Locator;
  readonly viewCardsButton: Locator;
  readonly viewListButton: Locator;
  readonly emptyServersMessage: Locator;
  readonly manageServerButtons: Locator;
  readonly extendServerButtons: Locator;
  readonly serverList: Locator;

  constructor(page: Page) {
    this.page = page;
    // Selektory oparte na strukturze z servers/servers.html.twig
    this.pageTitle = page.locator('h4').filter({ hasText: 'Servers' });
    this.serversDescription = page.locator('p.text-muted');
    this.serverCards = page.locator('.server-card');
    this.viewCardsButton = page.locator('#view-cards');
    this.viewListButton = page.locator('#view-list');
    this.emptyServersMessage = page.locator('text=any servers yet');
    this.manageServerButtons = page.locator('a').filter({ hasText: 'Manage Server' });
    this.extendServerButtons = page.locator('a').filter({ hasText: 'Extend' });
    this.serverList = page.locator('#list-view table');
  }

  async goto() {
    await this.page.goto('/panel?routeName=servers');
  }

  async waitForLoad() {
    await this.pageTitle.waitFor({ state: 'visible' });
  }

  async getServerCount(): Promise<number> {
    return await this.serverCards.count();
  }

  async hasServers(): Promise<boolean> {
    return (await this.getServerCount()) > 0;
  }

  async clickViewCards() {
    await this.viewCardsButton.click();
  }

  async clickViewList() {
    await this.viewListButton.click();
  }

  async clickFirstManageServer() {
    await this.manageServerButtons.first().click();
  }

  async clickFirstExtendServer() {
    await this.extendServerButtons.first().click();
  }

  async getFirstServerName(): Promise<string> {
    const firstCard = this.serverCards.first();
    const nameElement = firstCard.locator('h5.card-title');
    return await nameElement.textContent() || '';
  }

  async getServerDetails(index: number = 0) {
    const serverCard = this.serverCards.nth(index);
    
    return {
      name: await serverCard.locator('h5.card-title').textContent() || '',
      id: await serverCard.locator('[data-server-id]').getAttribute('data-server-id') || '',
      isActive: await serverCard.locator('.badge-success').isVisible().catch(() => false),
      isSuspended: await serverCard.locator('.badge-warning').isVisible().catch(() => false),
    };
  }

  async waitForServerDataLoad() {
    // Czeka aż dane serwerów się załadują (placeholder zniknie)
    await this.page.waitForTimeout(3000); // API call może potrwać
  }

  async isListViewActive(): Promise<boolean> {
    return await this.viewListButton.getAttribute('class').then(cls => cls?.includes('active') || false);
  }

  async isCardsViewActive(): Promise<boolean> {
    return await this.viewCardsButton.getAttribute('class').then(cls => cls?.includes('active') || false);
  }
}
