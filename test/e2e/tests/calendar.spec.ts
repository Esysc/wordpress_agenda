import { test, expect, AgendaPage } from './fixtures';

test.describe('Calendar Functionality', () => {
  let agendaPage: AgendaPage;

  test.beforeEach(async ({ page }) => {
    agendaPage = new AgendaPage(page);
    await agendaPage.goto();
  });

  test('should open calendar when clicking calendar button', async ({ page }) => {
    await agendaPage.clickAddEvent();

    // Click the calendar button
    await page.click('.acs-open-calendar');

    // Verify datepicker container becomes visible
    await expect(page.locator('#acs-datepicker-container.active')).toBeVisible();

    // Verify jQuery UI datepicker is rendered inside our container
    await expect(page.locator('#acs-datepicker-container .ui-datepicker')).toBeVisible();
  });

  test('should close calendar when clicking close button', async ({ page }) => {
    await agendaPage.clickAddEvent();

    // Open calendar
    await page.click('.acs-open-calendar');
    await expect(page.locator('#acs-datepicker-container.active')).toBeVisible();

    // Wait for close button to appear
    await page.waitForSelector('.acs-datepicker-close', { state: 'visible' });

    // Click close button
    await page.click('.acs-datepicker-close');

    // Verify calendar is closed
    await expect(page.locator('#acs-datepicker-container.active')).toBeHidden();
  });

  test('should toggle calendar when clicking calendar button twice', async ({ page }) => {
    await agendaPage.clickAddEvent();

    // Open calendar
    await page.click('.acs-open-calendar');
    await expect(page.locator('#acs-datepicker-container.active')).toBeVisible();

    // Click again to close
    await page.click('.acs-open-calendar');
    await expect(page.locator('#acs-datepicker-container.active')).toBeHidden();
  });

  test('should select a date from calendar and populate field', async ({ page }) => {
    await agendaPage.clickAddEvent();

    // Open calendar
    await page.click('.acs-open-calendar');
    await page.waitForSelector('.ui-datepicker', { state: 'visible' });

    // Click on a future date (find a selectable day)
    const selectableDay = page.locator('.ui-datepicker-calendar td:not(.ui-datepicker-unselectable):not(.ui-state-disabled) a').first();
    await selectableDay.click();

    // Verify date field is populated
    const dateValue = await page.inputValue('#event-date');
    expect(dateValue).toMatch(/\d{2}\/\d{2}\/\d{2}/);
  });

  test('should select multiple dates from calendar', async ({ page }) => {
    await agendaPage.clickAddEvent();

    // Open calendar
    await page.click('.acs-open-calendar');
    await page.waitForSelector('.ui-datepicker', { state: 'visible' });

    // Get selectable days
    const selectableDays = page.locator('.ui-datepicker-calendar td:not(.ui-datepicker-unselectable):not(.ui-state-disabled) a');
    const count = await selectableDays.count();

    if (count >= 2) {
      // Select first date
      await selectableDays.nth(0).click();
      await page.waitForTimeout(100);

      // Select second date
      await selectableDays.nth(1).click();
      await page.waitForTimeout(100);

      // Verify date field contains two dates
      const dateValue = await page.inputValue('#event-date');
      const dates = dateValue.split(',').map((d: string) => d.trim()).filter((d: string) => d);
      expect(dates.length).toBe(2);
    }
  });

  test('should deselect a date by clicking it again', async ({ page }) => {
    await agendaPage.clickAddEvent();

    // Open calendar
    await page.click('.acs-open-calendar');
    await page.waitForSelector('.ui-datepicker', { state: 'visible' });

    // Select a date
    const selectableDay = page.locator('.ui-datepicker-calendar td:not(.ui-datepicker-unselectable):not(.ui-state-disabled) a').first();
    await selectableDay.click();
    await page.waitForTimeout(100);

    // Verify date is selected
    let dateValue = await page.inputValue('#event-date');
    expect(dateValue).toMatch(/\d{2}\/\d{2}\/\d{2}/);

    // Click the same date again to deselect
    // The calendar refreshes after selection, so we need to find it again
    await page.locator('.ui-datepicker-calendar td.ui-state-highlight a, .ui-datepicker-calendar td a.ui-state-active').first().click();
    await page.waitForTimeout(100);

    // Verify date field is now empty
    dateValue = await page.inputValue('#event-date');
    expect(dateValue).toBe('');
  });

  test('should disable past dates for new events', async ({ page }) => {
    await agendaPage.clickAddEvent();

    // Open calendar
    await page.click('.acs-open-calendar');
    await page.waitForSelector('.ui-datepicker', { state: 'visible' });

    // Check that past dates have the disabled class or unselectable class
    const pastDates = page.locator('.ui-datepicker-calendar td.ui-datepicker-unselectable, .ui-datepicker-calendar td.ui-state-disabled');
    const pastCount = await pastDates.count();

    // Today is December 24, 2025 - there should be at least some past dates visible
    // (days 1-23 of December)
    expect(pastCount).toBeGreaterThan(0);
  });

  test('should navigate to next month', async ({ page }) => {
    await agendaPage.clickAddEvent();

    // Open calendar
    await page.click('.acs-open-calendar');
    await page.waitForSelector('.ui-datepicker', { state: 'visible' });

    // Get current month text
    const currentMonth = await page.locator('.ui-datepicker-month').textContent();

    // Click next month button
    await page.click('.ui-datepicker-next');
    await page.waitForTimeout(200);

    // Get new month text
    const nextMonth = await page.locator('.ui-datepicker-month').textContent();

    // Months should be different
    expect(nextMonth).not.toBe(currentMonth);
  });

  test('should navigate to previous month', async ({ page }) => {
    await agendaPage.clickAddEvent();

    // Open calendar
    await page.click('.acs-open-calendar');
    await page.waitForSelector('.ui-datepicker', { state: 'visible' });

    // First go to next month so we can go back
    await page.click('.ui-datepicker-next');
    await page.waitForTimeout(200);

    const currentMonth = await page.locator('.ui-datepicker-month').textContent();

    // Click previous month button
    await page.click('.ui-datepicker-prev');
    await page.waitForTimeout(200);

    const prevMonth = await page.locator('.ui-datepicker-month').textContent();

    // Months should be different
    expect(prevMonth).not.toBe(currentMonth);
  });

  test('should validate date field format on blur', async ({ page }) => {
    await agendaPage.clickAddEvent();

    const dateInput = page.locator('#event-date');

    // Type an invalid date
    await dateInput.fill('invalid-date');
    await dateInput.blur();

    // Check that field has error class
    await expect(dateInput).toHaveClass(/error/);
  });

  test('should accept valid date format in field', async ({ page }) => {
    await agendaPage.clickAddEvent();

    const dateInput = page.locator('#event-date');

    // Type a valid date
    await dateInput.fill('25/12/25');
    await dateInput.blur();

    // Check that field does NOT have error class
    await expect(dateInput).not.toHaveClass(/error/);

    // Verify the value is normalized
    const value = await dateInput.inputValue();
    expect(value).toBe('25/12/25');
  });

  test('should accept multiple dates in field', async ({ page }) => {
    await agendaPage.clickAddEvent();

    const dateInput = page.locator('#event-date');

    // Type multiple dates
    await dateInput.fill('31/12/25, 25/12/25, 28/12/25');
    await dateInput.blur();

    // Check that field does NOT have error class (multiple dates are valid)
    await expect(dateInput).not.toHaveClass(/error/);

    // Get the value - should have all dates
    const value = await dateInput.inputValue();
    expect(value).toContain('31/12/25');
    expect(value).toContain('25/12/25');
    expect(value).toContain('28/12/25');
  });

  test('should preserve dates when reopening calendar', async ({ page }) => {
    await agendaPage.clickAddEvent();

    // Open calendar and select a date
    await page.click('.acs-open-calendar');
    await page.waitForSelector('.ui-datepicker', { state: 'visible' });

    const selectableDay = page.locator('.ui-datepicker-calendar td:not(.ui-datepicker-unselectable):not(.ui-state-disabled) a').first();
    await selectableDay.click();
    await page.waitForTimeout(100);

    // Get the selected date
    const dateValue = await page.inputValue('#event-date');

    // Close calendar
    await page.click('.acs-datepicker-close');
    await expect(page.locator('#acs-datepicker-container.active')).toBeHidden();

    // Reopen calendar
    await page.click('.acs-open-calendar');
    await page.waitForSelector('.ui-datepicker', { state: 'visible' });

    // Date field should still have the value
    const dateValueAfter = await page.inputValue('#event-date');
    expect(dateValueAfter).toBe(dateValue);

    // And the date should be highlighted in the calendar
    const highlightedDates = page.locator('.ui-datepicker-calendar td.ui-state-highlight, .ui-datepicker-calendar td:has(a.ui-state-active)');
    const highlightCount = await highlightedDates.count();
    expect(highlightCount).toBeGreaterThan(0);
  });

  test('should clear dates when field is manually cleared and calendar reopened', async ({ page }) => {
    await agendaPage.clickAddEvent();

    // First set a date via calendar
    await page.click('.acs-open-calendar');
    await page.waitForSelector('.ui-datepicker', { state: 'visible' });

    const selectableDay = page.locator('.ui-datepicker-calendar td:not(.ui-datepicker-unselectable):not(.ui-state-disabled) a').first();
    await selectableDay.click();
    await page.waitForTimeout(100);

    // Close calendar
    await page.click('.acs-datepicker-close');

    // Manually clear the date field
    await page.fill('#event-date', '');

    // Reopen calendar and select a new date
    await page.click('.acs-open-calendar');
    await page.waitForSelector('.ui-datepicker', { state: 'visible' });

    // Select a date
    const newSelectableDay = page.locator('.ui-datepicker-calendar td:not(.ui-datepicker-unselectable):not(.ui-state-disabled) a').first();
    await newSelectableDay.click();
    await page.waitForTimeout(100);

    // Should only have one date (not the old one + new one)
    const dateValue = await page.inputValue('#event-date');
    const dates = dateValue.split(',').map((d: string) => d.trim()).filter((d: string) => d);
    expect(dates.length).toBe(1);
  });

  test('should create event with date selected from calendar', async ({ page }) => {
    const eventTitle = `Calendar Test Event ${Date.now()}`;

    await agendaPage.clickAddEvent();

    // Fill in basic fields
    await page.fill('#event-categorie', 'Calendar Test');
    await page.fill('#event-title', eventTitle);

    // Select date using calendar
    await page.click('.acs-open-calendar');
    await page.waitForSelector('.ui-datepicker', { state: 'visible' });

    const selectableDay = page.locator('.ui-datepicker-calendar td:not(.ui-datepicker-unselectable):not(.ui-state-disabled) a').first();
    await selectableDay.click();
    await page.waitForTimeout(100);

    // Close calendar
    await page.click('.acs-datepicker-close');

    // Submit form
    await agendaPage.submitForm();
    await agendaPage.waitForPageReload();

    // Verify event was created
    await agendaPage.goto();
    const exists = await agendaPage.eventExists(eventTitle);
    expect(exists).toBeTruthy();
  });
});
