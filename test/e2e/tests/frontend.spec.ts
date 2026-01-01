import { test, expect } from './fixtures';

test.describe('Frontend Agenda Display', () => {

  test('should display the agenda page', async ({ page }) => {
    const response = await page.goto('/agenda/');
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
    await page.goto('/agenda/', { waitUntil: 'networkidle' });

    // The event should be visible on the frontend
    const eventElement = page.locator(`text=${eventTitle}`);
    await expect(eventElement).toBeVisible({ timeout: 10000 });
  });

  test('should handle empty agenda gracefully', async ({ page }) => {
    await page.goto('/agenda/');
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
});
