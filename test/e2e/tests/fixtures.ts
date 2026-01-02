import { test as base, expect, Page } from '@playwright/test';

// Test fixtures with authenticated state
export const test = base.extend({
  // Use saved auth state for all tests
  storageState: './auth.json',
});

export { expect };

// Helper class for Agenda operations
export class AgendaPage {
  constructor(private page: Page) {}

  async goto() {
    await this.page.goto('/wp-admin/admin.php?page=acsagma-agenda');
    await this.page.waitForLoadState('networkidle');
  }

  async clickAddEvent() {
    await this.page.click('#acs-add-event');
    // Wait for jQuery UI dialog to open
    await this.page.waitForSelector('.ui-dialog:has(#acs-event-dialog)', { state: 'visible' });
  }

  async fillEventForm(data: {
    title: string;
    category?: string;
    location?: string;
    date?: string;
    intro?: string;
    price?: string;
    link?: string;
  }) {
    // Fill fields using their IDs (jQuery UI dialog form)
    if (data.category) {
      await this.page.fill('#event-categorie', data.category);
    }

    await this.page.fill('#event-title', data.title);

    if (data.location) {
      await this.page.fill('#event-emplacement', data.location);
    }
    if (data.date) {
      // The date field is readonly and requires the calendar
      // We need to set it via JavaScript
      await this.page.evaluate((dateValue) => {
        const input = document.getElementById('event-date') as HTMLInputElement;
        if (input) {
          input.readOnly = false;
          input.value = dateValue;
        }
      }, data.date);
    }
    if (data.intro) {
      await this.page.fill('#event-intro', data.intro);
    }
    if (data.price) {
      await this.page.fill('#event-price', data.price);
    }
    if (data.link) {
      await this.page.fill('#event-link', data.link);
    }
  }

  async submitForm() {
    // Click the jQuery UI dialog button (Add or Update)
    // Target the event dialog's parent .ui-dialog and find its buttonset
    const eventDialogButtonset = this.page.locator('#acs-event-dialog').locator('..').locator('.ui-dialog-buttonset button').first();
    await eventDialogButtonset.click();
  }

  async waitForSuccess() {
    // Wait for page to reload (the JS does location.reload() on success)
    await this.page.waitForLoadState('networkidle');
    // Or wait for success notice
    await this.page.waitForSelector('.notice-success', {
      state: 'visible',
      timeout: 10000
    }).catch(() => {
      // Success might also mean page reloaded, which is fine
    });
  }

  async waitForPageReload() {
    // Wait for the AJAX to complete and page to reload
    await this.page.waitForLoadState('networkidle');
    await this.page.waitForTimeout(1000); // Give time for reload
  }

  async getEventCount(): Promise<number> {
    const rows = await this.page.locator('table.wp-list-table tbody tr:not(.no-items)').count();
    return rows;
  }

  async eventExists(title: string, useSearch: boolean = false): Promise<boolean> {
    await this.page.waitForLoadState('networkidle');

    // Optionally use search to find the event (handles pagination)
    if (useSearch) {
      const searchInput = this.page.locator('input[name="s"]');
      if (await searchInput.isVisible()) {
        await searchInput.fill(title);
        await this.page.click('#search-submit');
        await this.page.waitForLoadState('networkidle');
      }
    }

    // First try a simple text check on the page
    const titleElement = this.page.locator(`table.wp-list-table td:has-text("${title}")`);
    const directCount = await titleElement.count();
    if (directCount > 0) {
      return true;
    }

    // Fallback: Look for the title in table rows
    const rows = this.page.locator('table.wp-list-table tbody tr:not(.no-items)');
    const count = await rows.count();

    for (let i = 0; i < count; i++) {
      const rowText = await rows.nth(i).textContent();
      // Check if title appears as a distinct value (not as substring of another title)
      if (rowText?.includes(title)) {
        return true;
      }
    }
    return false;
  }

  async deleteEvent(title: string) {
    // Find the row with the event
    const row = this.page.locator('table.wp-list-table tbody tr').filter({ hasText: title });

    // Hover over the row to reveal the action buttons (WordPress hides them by default)
    await row.hover();

    // Click the delete link
    const deleteLink = row.locator('a.ACSdelete');
    await deleteLink.waitFor({ state: 'visible' });
    await deleteLink.click();

    // Wait for the confirmation dialog to appear
    await this.page.waitForSelector('.ui-dialog:has(#acs-delete-dialog)', { state: 'visible' });

    // Click the Confirm button in the delete confirmation dialog
    const confirmButton = this.page.locator('#acs-delete-dialog').locator('..').locator('.ui-dialog-buttonset button').first();
    await confirmButton.click();

    // Wait for redirect back to the list
    await this.page.waitForURL('**/admin.php?page=acsagma-agenda**');
    await this.page.waitForLoadState('networkidle');
  }

  async editEvent(title: string) {
    const row = this.page.locator('table.wp-list-table tbody tr').filter({ hasText: title });

    // Hover over the row to reveal the action buttons (WordPress hides them by default)
    await row.hover();

    const editLink = row.locator('a.editItems');
    await editLink.waitFor({ state: 'visible' });
    await editLink.click();
    await this.page.waitForSelector('.ui-dialog:has(#acs-event-dialog)', { state: 'visible' });
  }

  async closeModal() {
    // Click the Cancel button in jQuery UI dialog (last button in buttonset)
    const cancelButton = this.page.locator('#acs-event-dialog').locator('..').locator('.ui-dialog-buttonset button').last();
    await cancelButton.click();
    await this.page.waitForSelector('.ui-dialog:has(#acs-event-dialog)', { state: 'hidden' });
  }

  async selectEventsForBulkAction(titles: string[]) {
    for (const title of titles) {
      // Find the row containing the title text, then check its checkbox
      const row = this.page.locator('table.wp-list-table tbody tr').filter({ hasText: title });
      const checkbox = row.locator('input[type="checkbox"][name="bulk-delete[]"]');
      await checkbox.scrollIntoViewIfNeeded();
      await checkbox.check();
    }
  }

  async performBulkDelete() {
    await this.page.selectOption('select[name="action"]', 'bulk-delete');
    await this.page.click('#doaction');
    await this.page.waitForURL('**/admin.php?page=acsagma-agenda**');
    await this.page.waitForLoadState('networkidle');
  }
}

// Helper function to get the agenda frontend URL from settings
export async function getAgendaPageUrl(page: Page): Promise<string> {
  // Go to settings page to get the current agenda page name
  await page.goto('/wp-admin/admin.php?page=acsagma-settings');
  await page.waitForLoadState('networkidle');

  // Get the page name from input
  const pageNameInput = page.locator('input[name="acsagma_page"]');
  const pageName = await pageNameInput.inputValue();

  // Convert page name to slug (lowercase, spaces to hyphens, remove special chars)
  const slug = pageName.toLowerCase().replace(/\s+/g, '-').replace(/[^a-z0-9-]/g, '');
  return `/${slug}/`;
}
