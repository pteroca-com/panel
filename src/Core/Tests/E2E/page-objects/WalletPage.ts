import { Page, Locator } from '@playwright/test';

export class WalletPage {
  readonly page: Page;
  readonly pageTitle: Locator;
  readonly amountInput: Locator;
  readonly currencySpan: Locator;
  readonly rechargeButton: Locator;
  readonly useVoucherButton: Locator;
  readonly transactionHistoryLink: Locator;
  readonly balanceCard: Locator;
  readonly errorMessage: Locator;

  constructor(page: Page) {
    this.page = page;
    // Selektory oparte na strukturze z recharge.html.twig i recharge_balance_card.html.twig
    this.pageTitle = page.locator('h4').filter({ hasText: 'Recharge Balance' });
    this.amountInput = page.locator('#amount');
    this.currencySpan = page.locator('.input-group-text');
    this.rechargeButton = page.locator('button[type="submit"]').filter({ hasText: 'Recharge' });
    this.useVoucherButton = page.locator('button[data-bs-target="#useVoucherModal"]');
    this.transactionHistoryLink = page.locator('a.text-decoration-none[href*="UserPaymentCrudController"]');
    this.balanceCard = page.locator('.card').filter({ hasText: 'Balance' });
    this.errorMessage = page.locator('.alert-danger, .invalid-feedback');
  }

  async goto() {
    await this.page.goto('/panel?routeName=recharge_balance');
  }

  async waitForLoad() {
    await this.pageTitle.waitFor({ state: 'visible' });
  }

  async enterAmount(amount: string) {
    await this.amountInput.fill(amount);
  }

  async clickRecharge() {
    await this.rechargeButton.click();
  }

  async clickUseVoucher() {
    await this.useVoucherButton.click();
  }

  async clickTransactionHistory() {
    await this.transactionHistoryLink.click();
  }

  async getAmountValue(): Promise<string> {
    return await this.amountInput.inputValue();
  }

  async getCurrency(): Promise<string> {
    return await this.currencySpan.textContent() || '';
  }

  async isRechargeButtonEnabled(): Promise<boolean> {
    return await this.rechargeButton.isEnabled();
  }

  async waitForError() {
    await this.errorMessage.waitFor({ state: 'visible' });
  }

  async getErrorMessage(): Promise<string> {
    return await this.errorMessage.textContent() || '';
  }
}
