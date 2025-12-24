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
    await page.waitForSelector('#acs-modal', { state: 'visible' });

    await page.fill('input[name="title"]', eventTitle);
    await page.fill('input[name="categorie"]', 'Frontend Test');
    await page.fill('input[name="date"]', '2025-12-31');
    await page.fill('textarea[name="intro"]', 'Test event for frontend display');

    await page.click('#acs-save-event');
    await page.waitForSelector('.notice-success, #acs-admin-notices .notice-success', {
      state: 'visible',
      timeout: 10000
    });

    // Now check the frontend
    await page.goto('/agenda/');
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
