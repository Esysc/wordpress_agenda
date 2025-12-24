import { test, expect, AgendaPage } from './fixtures';

test.describe('Search and Filter', () => {
  let agendaPage: AgendaPage;

  test.beforeEach(async ({ page }) => {
    agendaPage = new AgendaPage(page);
  });

  test('should search for events by title', async ({ page }) => {
    // Create a uniquely named event
    const uniqueTitle = `UniqueSearchTest${Date.now()}`;

    await agendaPage.goto();
    await agendaPage.clickAddEvent();
    await agendaPage.fillEventForm({
      title: uniqueTitle,
      category: 'Search Test',
      date: '2025-12-31',
    });
    await agendaPage.submitForm();
    await agendaPage.waitForSuccess();

    // Search for the event
    await agendaPage.goto();
    await page.fill('input[name="s"]', uniqueTitle);
    await page.click('#search-submit');
    await page.waitForLoadState('networkidle');

    // Should find the event
    const exists = await agendaPage.eventExists(uniqueTitle);
    expect(exists).toBeTruthy();
  });

  test('should return no results for non-existent search', async ({ page }) => {
    await agendaPage.goto();

    // Search for something that definitely doesn't exist
    await page.fill('input[name="s"]', 'NonExistentEvent12345XYZ');
    await page.click('#search-submit');
    await page.waitForLoadState('networkidle');

    // Should show "no items" message
    const noItems = page.locator('tr.no-items, .no-items');
    await expect(noItems).toBeVisible();
  });

  test('should filter events by category', async ({ page }) => {
    const category = `FilterCategory${Date.now()}`;
    const eventTitle = `Filter Test ${Date.now()}`;

    // Create an event with a specific category
    await agendaPage.goto();
    await agendaPage.clickAddEvent();
    await agendaPage.fillEventForm({
      title: eventTitle,
      category: category,
      date: '2025-12-31',
    });
    await agendaPage.submitForm();
    await agendaPage.waitForSuccess();

    // Reload and check if filter option exists
    await agendaPage.goto();

    const filterSelect = page.locator('select[name="event-filter"]');
    if (await filterSelect.isVisible()) {
      // Select the category filter
      await filterSelect.selectOption({ label: category });
      await page.click('#post-query-submit, button[type="submit"]');
      await page.waitForLoadState('networkidle');

      // The filtered event should be visible
      const exists = await agendaPage.eventExists(eventTitle);
      expect(exists).toBeTruthy();
    }
  });

  test('should paginate events', async ({ page }) => {
    await agendaPage.goto();

    // Check if pagination exists (only if there are enough events)
    const paginationLinks = page.locator('.tablenav-pages a');
    const count = await paginationLinks.count();

    if (count > 0) {
      // Click on page 2 if available
      const page2Link = page.locator('.tablenav-pages a:has-text("2")');
      if (await page2Link.isVisible()) {
        await page2Link.click();
        await page.waitForLoadState('networkidle');

        // Should still be on agenda page
        await expect(page).toHaveURL(/page=agenda/);
      }
    }
  });

  test('should sort events by column', async ({ page }) => {
    await agendaPage.goto();

    // Click on a sortable column header (e.g., Title)
    const titleHeader = page.locator('th.column-title a, th#title a');

    if (await titleHeader.isVisible()) {
      await titleHeader.click();
      await page.waitForLoadState('networkidle');

      // URL should include orderby parameter
      expect(page.url()).toMatch(/orderby=/);
    }
  });
});
