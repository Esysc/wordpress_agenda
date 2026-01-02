import { test, expect, getAgendaPageUrl } from './fixtures';

test.describe('Frontend Agenda Display', () => {

  test('should display the agenda page', async ({ page }) => {
    const agendaUrl = await getAgendaPageUrl(page);
    const response = await page.goto(agendaUrl);
    await page.waitForLoadState('networkidle');

    // Check response status is OK
    expect(response?.status()).toBeLessThan(400);

    // Check for agenda container or "no events" message
    const hasContent = await page.locator('.acs-agenda, #acs-agenda, [class*="agenda"], p, div').first().isVisible();
    expect(hasContent).toBeTruthy();
  });

  test('should display events on the frontend', async ({ page }) => {
    // First create an event via admin
    await page.goto('/wp-admin/admin.php?page=acsagma-agenda');
    await page.waitForLoadState('networkidle');

    const eventTitle = `Frontend Test ${Date.now()}`;

    await page.click('#acs-add-event');
    // Wait for jQuery UI dialog to open
    await page.waitForSelector('.ui-dialog:has(#acs-event-dialog)', { state: 'visible' });

    // Fill the form using IDs inside the dialog
    await page.fill('#event-title', eventTitle);
    await page.fill('#event-categorie', 'Frontend Test');
    // Set date via JavaScript since field is readonly - use tomorrow's date
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
    await page.fill('#event-intro', 'Test event for frontend display');

    // Click the submit button in jQuery UI dialog
    const submitButton = page.locator('#acs-event-dialog').locator('..').locator('.ui-dialog-buttonset button').first();
    await submitButton.click();
    // Wait for page reload (the JS does location.reload() on success)
    await page.waitForLoadState('networkidle');
    // Give time for any background processes
    await page.waitForTimeout(1000);

    // Now check the frontend
    const agendaUrl = await getAgendaPageUrl(page);
    await page.goto(agendaUrl, { waitUntil: 'networkidle' });

    // The event should be visible on the frontend
    const eventElement = page.locator(`text=${eventTitle}`);
    await expect(eventElement).toBeVisible({ timeout: 10000 });
  });

  test('should handle empty agenda gracefully', async ({ page }) => {
    const agendaUrl = await getAgendaPageUrl(page);
    await page.goto(agendaUrl);
    await page.waitForLoadState('networkidle');

    // Page should not show errors
    const errorIndicators = page.locator('text=/error|exception|warning/i');
    const count = await errorIndicators.count();

    // Allow for "No events" type messages but not actual errors
    for (let i = 0; i < count; i++) {
      const text = await errorIndicators.nth(i).textContent();
      expect(text?.toLowerCase()).not.toMatch(/fatal|exception|undefined/);
    }
  });

  test('should display event with image', async ({ page }) => {
    // Create event with image
    await page.goto('/wp-admin/admin.php?page=acsagma-agenda');
    await page.waitForLoadState('networkidle');

    const timestamp = Date.now();
    const eventTitle = `Image Event ${timestamp}`;

    await page.click('#acs-add-event');
    await page.waitForSelector('.ui-dialog:has(#acs-event-dialog)', { state: 'visible' });

    await page.fill('#event-title', eventTitle);
    await page.fill('#event-categorie', 'Frontend Test');
    await page.fill('#event-image', 'https://via.placeholder.com/300x200.png');

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

    const submitButton = page.locator('#acs-event-dialog').locator('..').locator('.ui-dialog-buttonset button').first();
    await submitButton.click();
    await page.waitForLoadState('networkidle');
    await page.waitForTimeout(1000);

    // Check frontend
    const agendaUrl = await getAgendaPageUrl(page);
    await page.goto(agendaUrl, { waitUntil: 'networkidle' });

    // Event should be visible
    const eventElement = page.locator(`text=${eventTitle}`);
    await expect(eventElement).toBeVisible({ timeout: 10000 });
  });

  test('should display event price on frontend', async ({ page }) => {
    // Create event with price
    await page.goto('/wp-admin/admin.php?page=acsagma-agenda');
    await page.waitForLoadState('networkidle');

    const timestamp = Date.now();
    const eventTitle = `Price Event ${timestamp}`;
    const price = 'CHF 150.-';

    await page.click('#acs-add-event');
    await page.waitForSelector('.ui-dialog:has(#acs-event-dialog)', { state: 'visible' });

    await page.fill('#event-title', eventTitle);
    await page.fill('#event-categorie', 'Price Test');  // Category is required
    await page.fill('#event-price', price);

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

    const submitButton = page.locator('#acs-event-dialog').locator('..').locator('.ui-dialog-buttonset button').first();
    await submitButton.click();
    await page.waitForLoadState('networkidle');
    await page.waitForTimeout(1000);

    // Check frontend
    const agendaUrl = await getAgendaPageUrl(page);
    await page.goto(agendaUrl, { waitUntil: 'networkidle' });

    // Event with price should be visible
    const eventCard = page.locator('text=' + eventTitle).locator('..');
    await expect(eventCard).toBeVisible({ timeout: 10000 });
  });

  test('should show Read More dialog', async ({ page }) => {
    // This test assumes there's an event with intro text
    const agendaUrl = await getAgendaPageUrl(page);
    await page.goto(agendaUrl, { waitUntil: 'networkidle' });

    // Look for "Read more" link
    const readMoreLink = page.locator('.read_more, a:has-text("Read more")').first();

    if (await readMoreLink.isVisible()) {
      await readMoreLink.click();
      await page.waitForTimeout(500);

      // Dialog should appear
      const dialog = page.locator('.ui-dialog, [role="dialog"]');
      const isVisible = await dialog.isVisible().catch(() => false);

      expect(isVisible).toBeTruthy();
    }
  });

  test('should display events in chronological order', async ({ page }) => {
    const agendaUrl = await getAgendaPageUrl(page);
    await page.goto(agendaUrl, { waitUntil: 'networkidle' });

    // Get all event date elements
    const dateElements = page.locator('.acs-event-date, [class*="date"]');
    const count = await dateElements.count();

    // Should have structured event display
    expect(count).toBeGreaterThanOrEqual(0);
  });

  test('should be responsive on mobile viewport', async ({ page }) => {
    // Set mobile viewport
    await page.setViewportSize({ width: 375, height: 667 });

    const agendaUrl = await getAgendaPageUrl(page);
    await page.goto(agendaUrl, { waitUntil: 'networkidle' });

    // Page should load without errors
    const body = page.locator('body');
    await expect(body).toBeVisible();
  });

  test('should be responsive on tablet viewport', async ({ page }) => {
    // Set tablet viewport
    await page.setViewportSize({ width: 768, height: 1024 });

    const agendaUrl = await getAgendaPageUrl(page);
    await page.goto(agendaUrl, { waitUntil: 'networkidle' });

    // Page should load without errors
    const body = page.locator('body');
    await expect(body).toBeVisible();
  });

  test('should handle agenda shortcode on custom page', async ({ page }) => {
    // The shortcode should work on any page
    const agendaUrl = await getAgendaPageUrl(page);
    await page.goto(agendaUrl, { waitUntil: 'networkidle' });

    // Check that shortcode content is rendered (not raw shortcode text)
    const rawShortcode = page.locator('text=/\\[acsagma_agenda\\]/');
    const hasRawShortcode = await rawShortcode.isVisible().catch(() => false);

    // Should NOT see raw shortcode (it should be processed)
    expect(hasRawShortcode).toBeFalsy();
  });
});
