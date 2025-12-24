import { test, expect } from './fixtures';

test.describe('Frontend Agenda Display', () => {

  test('should display the agenda page', async ({ page }) => {
    await page.goto('/agenda/');

    // The page should load successfully
    await expect(page).not.toHaveURL(/error|404/i);

    // Check for agenda container
    const agendaContainer = page.locator('.acs-agenda, #acs-agenda, [class*="agenda"]');
    await expect(agendaContainer.first()).toBeVisible({ timeout: 10000 });
  });

  test('should display events on the frontend', async ({ page }) => {
    // First create an event via admin
    await page.goto('/wp-admin/admin.php?page=agenda');
    await page.waitForLoadState('networkidle');

    const eventTitle = `Frontend Test ${Date.now()}`;

    await page.click('#acs-add-event');
    // Wait for jQuery UI dialog to open
    await page.waitForSelector('.ui-dialog:has(#acs-event-dialog)', { state: 'visible' });

    // Fill the form using IDs inside the dialog
    await page.fill('#event-title', eventTitle);
    await page.fill('#event-categorie', 'Frontend Test');
    // Set date via JavaScript since field is readonly
    await page.evaluate(() => {
      const input = document.getElementById('event-date') as HTMLInputElement;
      if (input) {
        input.readOnly = false;
        input.value = '2025-12-31';
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
    await page.goto('/agenda/', { waitUntil: 'domcontentloaded' });
    await page.waitForLoadState('networkidle');

    // The event should be visible on the frontend
    const eventElement = page.locator(`text=${eventTitle}`);
    await expect(eventElement).toBeVisible({ timeout: 10000 });
  });

  test('should handle empty agenda gracefully', async ({ page }) => {
    await page.goto('/agenda/');

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
