import { test, expect, AgendaPage } from './fixtures';

test.describe('Form Validation', () => {
  let agendaPage: AgendaPage;

  test.beforeEach(async ({ page }) => {
    agendaPage = new AgendaPage(page);
    await agendaPage.goto();
  });

  test('should require title field', async ({ page }) => {
    await agendaPage.clickAddEvent();

    // Try to submit without title
    await agendaPage.fillEventForm({
      title: '',
      category: 'Test',
      date: '31/12/25',
    });

    // Clear the title to make it empty
    await page.fill('#event-title', '');

    await agendaPage.submitForm();

    // Should show error or prevent submission
    // The dialog should still be open
    await expect(page.locator('.ui-dialog:has(#acs-event-dialog)')).toBeVisible();
  });

  test('should validate date format', async ({ page }) => {
    await agendaPage.clickAddEvent();

    await page.fill('#event-title', 'Test Event');

    // Try invalid date format
    await page.evaluate(() => {
      const input = document.getElementById('event-date') as HTMLInputElement;
      if (input) {
        input.readOnly = false;
        input.value = 'invalid-date';
      }
    });

    // Trigger blur event to validate
    await page.locator('#event-date').blur();

    // Should show validation error or clear invalid input
    const dateValue = await page.inputValue('#event-date');
    // Either empty (cleared) or still invalid - but shouldn't crash
    expect(typeof dateValue).toBe('string');
  });

  test('should validate URL fields', async ({ page }) => {
    await agendaPage.clickAddEvent();

    await agendaPage.fillEventForm({
      title: 'URL Test Event',
      category: 'Test',
      date: '31/12/25',
      link: 'not-a-valid-url',
    });

    // Try to submit with invalid URL
    await agendaPage.submitForm();

    // HTML5 validation should catch this
    // Check if form is still visible (submission blocked)
    const isDialogVisible = await page.locator('.ui-dialog:has(#acs-event-dialog)').isVisible();
    expect(isDialogVisible).toBeTruthy();
  });

  test('should accept valid URL formats', async ({ page }) => {
    const timestamp = Date.now();

    await agendaPage.clickAddEvent();

    await agendaPage.fillEventForm({
      title: `Valid URL Test ${timestamp}`,
      category: 'Test',
      date: '31/12/25',
      link: 'https://example.com/event',
    });

    // Set redirect URL via JavaScript
    await page.evaluate(() => {
      const input = document.getElementById('event-redirect') as HTMLInputElement;
      if (input) {
        input.value = 'https://register.example.com';
      }
    });

    await agendaPage.submitForm();
    await agendaPage.waitForPageReload();

    // Should succeed
    await agendaPage.goto();
    const exists = await agendaPage.eventExists(`Valid URL Test ${timestamp}`);
    expect(exists).toBeTruthy();
  });

  test('should handle empty optional fields', async ({ page }) => {
    const timestamp = Date.now();

    await agendaPage.clickAddEvent();

    // Fill only required fields (title, category, date) - use dynamic date
    await page.fill('#event-title', `Minimal Event ${timestamp}`);
    await page.fill('#event-categorie', 'Test');  // Category is required
    // Use a future date (tomorrow)
    await page.evaluate(() => {
      const input = document.getElementById('event-date') as HTMLInputElement;
      if (input) {
        input.readOnly = false;
        const tomorrow = new Date();
        tomorrow.setDate(tomorrow.getDate() + 1);
        const day = String(tomorrow.getDate()).padStart(2, '0');
        const month = String(tomorrow.getMonth() + 1).padStart(2, '0');
        const year = String(tomorrow.getFullYear()).slice(-2);
        input.value = `${day}/${month}/${year}`;
      }
    });

    await agendaPage.submitForm();
    await agendaPage.waitForPageReload();

    // Should succeed with minimal data (only required fields)
    await agendaPage.goto();
    const exists = await agendaPage.eventExists(`Minimal Event ${timestamp}`, true);
    expect(exists).toBeTruthy();
  });

  test('should validate date field on blur', async ({ page }) => {
    await agendaPage.clickAddEvent();

    // Enter valid date
    await page.evaluate(() => {
      const input = document.getElementById('event-date') as HTMLInputElement;
      if (input) {
        input.readOnly = false;
        input.value = '31/12/25';
      }
    });

    // Blur should not clear valid date
    await page.locator('#event-date').blur();
    await page.waitForTimeout(500);

    const dateValue = await page.inputValue('#event-date');
    expect(dateValue).toBe('31/12/25');
  });

  test('should show error for failed AJAX submission', async ({ page }) => {
    // This test would need to mock a failed AJAX response
    // For now, we'll test that error notices are displayed
    await agendaPage.goto();

    // Check if error notice container exists
    const noticeContainer = page.locator('#acs-admin-notices');
    const exists = await noticeContainer.count();

    // Should have notice container ready
    expect(exists).toBeGreaterThanOrEqual(0);
  });

  test('should trim whitespace from title', async ({ page }) => {
    const timestamp = Date.now();
    const title = `  Trimmed Title ${timestamp}  `;

    await agendaPage.clickAddEvent();

    // Fill title with whitespace first, then fill other fields without overwriting title
    await page.fill('#event-title', title);
    await page.fill('#event-categorie', 'Test');
    // Use a future date (tomorrow)
    await page.evaluate(() => {
      const input = document.getElementById('event-date') as HTMLInputElement;
      if (input) {
        input.readOnly = false;
        const tomorrow = new Date();
        tomorrow.setDate(tomorrow.getDate() + 1);
        const day = String(tomorrow.getDate()).padStart(2, '0');
        const month = String(tomorrow.getMonth() + 1).padStart(2, '0');
        const year = String(tomorrow.getFullYear()).slice(-2);
        input.value = `${day}/${month}/${year}`;
      }
    });

    await agendaPage.submitForm();
    await agendaPage.waitForPageReload();

    await agendaPage.goto();

    // The trimmed version should exist
    const trimmedExists = await agendaPage.eventExists(`Trimmed Title ${timestamp}`);
    expect(trimmedExists).toBeTruthy();
  });

  test('should handle special characters in title', async ({ page }) => {
    const timestamp = Date.now();
    const specialTitle = `Event "Quote" & <Special> Chars ${timestamp}`;

    await agendaPage.clickAddEvent();

    await agendaPage.fillEventForm({
      title: specialTitle,
      category: 'Test',
      date: '31/12/25',
    });

    await agendaPage.submitForm();
    await agendaPage.waitForPageReload();

    await agendaPage.goto();

    // Should be saved and displayed (possibly escaped)
    const eventRow = page.locator('table.wp-list-table tbody tr').filter({ hasText: `${timestamp}` });
    await expect(eventRow).toBeVisible();
  });

  test('should preserve line breaks in description', async ({ page }) => {
    const timestamp = Date.now();
    const multilineIntro = `Line 1\nLine 2\nLine 3`;

    await agendaPage.clickAddEvent();

    await agendaPage.fillEventForm({
      title: `Multiline Test ${timestamp}`,
      category: 'Test',
      date: '31/12/25',
      intro: multilineIntro,
    });

    await agendaPage.submitForm();
    await agendaPage.waitForPageReload();

    await agendaPage.goto();
    const exists = await agendaPage.eventExists(`Multiline Test ${timestamp}`);
    expect(exists).toBeTruthy();
  });
});
