import { test, expect, AgendaPage } from './fixtures';

test.describe('Event Management', () => {
  let agendaPage: AgendaPage;

  test.beforeEach(async ({ page }) => {
    agendaPage = new AgendaPage(page);
    await agendaPage.goto();
  });

  test('should load the agenda admin page', async ({ page }) => {
    await expect(page.locator('h1')).toContainText('Agenda Manager');
    await expect(page.locator('#acs-add-event')).toBeVisible();
  });

  test('should open the add event dialog', async ({ page }) => {
    await agendaPage.clickAddEvent();

    // Check jQuery UI dialog is visible
    await expect(page.locator('.ui-dialog:has(#acs-event-dialog)')).toBeVisible();

    // Check form fields exist
    await expect(page.locator('#event-title')).toBeVisible();
    await expect(page.locator('#event-categorie')).toBeVisible();
    await expect(page.locator('#event-date')).toBeVisible();
  });

  test('should create a new event', async ({ page }) => {
    const eventTitle = `Test Event ${Date.now()}`;

    await agendaPage.clickAddEvent();

    await agendaPage.fillEventForm({
      title: eventTitle,
      category: 'E2E Test',
      location: 'Test Location',
      date: '2025-12-31',
      intro: 'This is a test event created by E2E tests',
      price: 'Free',
    });

    await agendaPage.submitForm();
    await agendaPage.waitForPageReload();

    // Reload and verify event exists
    await agendaPage.goto();
    const exists = await agendaPage.eventExists(eventTitle);
    expect(exists).toBeTruthy();
  });

  test('should edit an existing event', async ({ page }) => {
    // First create an event to edit - use unique prefix to avoid substring matching issues
    const timestamp = Date.now();
    const originalTitle = `OriginalEvent ${timestamp}`;
    const updatedTitle = `ModifiedEvent ${timestamp}`;

    await agendaPage.clickAddEvent();
    await agendaPage.fillEventForm({
      title: originalTitle,
      category: 'E2E Test',
      date: '2025-12-31',
    });
    await agendaPage.submitForm();
    await agendaPage.waitForPageReload();
    await agendaPage.goto();

    // Now edit the event
    await agendaPage.editEvent(originalTitle);

    await page.fill('#event-title', updatedTitle);
    await agendaPage.submitForm();
    await agendaPage.waitForPageReload();

    // Verify the update
    await agendaPage.goto();
    const originalExists = await agendaPage.eventExists(originalTitle);
    const updatedExists = await agendaPage.eventExists(updatedTitle);

    expect(originalExists).toBeFalsy();
    expect(updatedExists).toBeTruthy();
  });

  test('should delete a single event', async ({ page }) => {
    // Create an event to delete
    const eventTitle = `Delete Test ${Date.now()}`;

    await agendaPage.clickAddEvent();
    await agendaPage.fillEventForm({
      title: eventTitle,
      category: 'E2E Delete Test',
      date: '2025-12-31',
    });
    await agendaPage.submitForm();
    await agendaPage.waitForPageReload();
    await agendaPage.goto();

    // Verify event exists
    let exists = await agendaPage.eventExists(eventTitle);
    expect(exists).toBeTruthy();

    // Delete the event
    await agendaPage.deleteEvent(eventTitle);

    // Verify URL contains deleted parameter or notice is visible
    const url = page.url();
    const hasDeletedParam = url.includes('deleted=');
    const noticeVisible = await page.locator('.notice-success').isVisible().catch(() => false);
    expect(hasDeletedParam || noticeVisible).toBeTruthy();

    // Verify event is gone
    exists = await agendaPage.eventExists(eventTitle);
    expect(exists).toBeFalsy();
  });

  test('should bulk delete multiple events', async ({ page }) => {
    // Create multiple events
    const timestamp = Date.now();
    const events = [
      `Bulk Delete 1 - ${timestamp}`,
      `Bulk Delete 2 - ${timestamp}`,
    ];

    for (const title of events) {
      await agendaPage.clickAddEvent();
      await agendaPage.fillEventForm({
        title,
        category: 'Bulk Delete Test',
        date: '2025-12-31',
      });
      await agendaPage.submitForm();
      await agendaPage.waitForPageReload();
      await agendaPage.goto();
    }

    // Verify all events exist
    for (const title of events) {
      const exists = await agendaPage.eventExists(title);
      expect(exists).toBeTruthy();
    }

    // Select and bulk delete
    await agendaPage.selectEventsForBulkAction(events);
    await agendaPage.performBulkDelete();

    // Verify URL contains deleted parameter or notice is visible
    const url = page.url();
    const hasDeletedParam = url.includes('deleted=');
    const noticeVisible = await page.locator('.notice-success').isVisible().catch(() => false);
    expect(hasDeletedParam || noticeVisible).toBeTruthy();

    // Verify all events are gone
    for (const title of events) {
      const exists = await agendaPage.eventExists(title);
      expect(exists).toBeFalsy();
    }
  });

  test('should cancel adding an event', async ({ page }) => {
    const initialCount = await agendaPage.getEventCount();

    await agendaPage.clickAddEvent();
    await agendaPage.fillEventForm({
      title: 'Should Not Be Saved',
      category: 'Cancel Test',
      date: '2025-12-31',
    });

    // Close dialog without saving
    await agendaPage.closeModal();

    // Verify no new event was added
    await agendaPage.goto();
    const finalCount = await agendaPage.getEventCount();
    expect(finalCount).toBe(initialCount);
  });
});
