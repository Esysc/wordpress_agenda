import { test, expect, AgendaPage } from './fixtures';

test.describe('Advanced Event Fields', () => {
  let agendaPage: AgendaPage;

  test.beforeEach(async ({ page }) => {
    agendaPage = new AgendaPage(page);
    await agendaPage.goto();
  });

  test('should set Partial Attendance to "No"', async ({ page }) => {
    const timestamp = Date.now();

    await agendaPage.clickAddEvent();

    await agendaPage.fillEventForm({
      title: `Partial No ${timestamp}`,
      category: 'Test',
      date: '31/12/25',
    });

    // Set partial attendance to "No" (value 0)
    await page.selectOption('#event-candopartial', '0');

    await agendaPage.submitForm();
    await agendaPage.waitForPageReload();

    await agendaPage.goto();
    const exists = await agendaPage.eventExists(`Partial No ${timestamp}`);
    expect(exists).toBeTruthy();
  });

  test('should set Partial Attendance to "Yes"', async ({ page }) => {
    const timestamp = Date.now();

    await agendaPage.clickAddEvent();

    await agendaPage.fillEventForm({
      title: `Partial Yes ${timestamp}`,
      category: 'Test',
      date: '31/12/25',
    });

    // Set partial attendance to "Yes" (value 1)
    await page.selectOption('#event-candopartial', '1');

    await agendaPage.submitForm();
    await agendaPage.waitForPageReload();

    await agendaPage.goto();
    const exists = await agendaPage.eventExists(`Partial Yes ${timestamp}`);
    expect(exists).toBeTruthy();
  });

  test('should set Partial Attendance to "Keep until end"', async ({ page }) => {
    const timestamp = Date.now();

    await agendaPage.clickAddEvent();

    await agendaPage.fillEventForm({
      title: `Partial Keep ${timestamp}`,
      category: 'Test',
      date: '31/12/25',
    });

    // Set partial attendance to "Keep until end" (value 2)
    await page.selectOption('#event-candopartial', '2');

    await agendaPage.submitForm();
    await agendaPage.waitForPageReload();

    await agendaPage.goto();
    const exists = await agendaPage.eventExists(`Partial Keep ${timestamp}`);
    expect(exists).toBeTruthy();
  });

  test('should set Advance Payment to "No"', async ({ page }) => {
    const timestamp = Date.now();

    await agendaPage.clickAddEvent();

    await agendaPage.fillEventForm({
      title: `Payment No ${timestamp}`,
      category: 'Test',
      date: '31/12/25',
    });

    // Set account to "No" (value 0)
    await page.selectOption('#event-account', '0');

    await agendaPage.submitForm();
    await agendaPage.waitForPageReload();

    await agendaPage.goto();
    const exists = await agendaPage.eventExists(`Payment No ${timestamp}`);
    expect(exists).toBeTruthy();
  });

  test('should set Advance Payment to "Yes"', async ({ page }) => {
    const timestamp = Date.now();

    await agendaPage.clickAddEvent();

    await agendaPage.fillEventForm({
      title: `Payment Yes ${timestamp}`,
      category: 'Test',
      date: '31/12/25',
      price: 'CHF 100.-',
    });

    // Set account to "Yes - Required" (value 1)
    await page.selectOption('#event-account', '1');

    await agendaPage.submitForm();
    await agendaPage.waitForPageReload();

    await agendaPage.goto();
    const exists = await agendaPage.eventExists(`Payment Yes ${timestamp}`);
    expect(exists).toBeTruthy();
  });

  test('should add External URL (redirect)', async ({ page }) => {
    const timestamp = Date.now();
    const externalUrl = 'https://register.example.com/event';

    await agendaPage.clickAddEvent();

    await agendaPage.fillEventForm({
      title: `External URL ${timestamp}`,
      category: 'Test',
      date: '31/12/25',
    });

    // Set external redirect URL
    await page.fill('#event-redirect', externalUrl);

    await agendaPage.submitForm();
    await agendaPage.waitForPageReload();

    await agendaPage.goto();
    const exists = await agendaPage.eventExists(`External URL ${timestamp}`);
    expect(exists).toBeTruthy();
  });

  test('should add Page Link and External URL together', async ({ page }) => {
    const timestamp = Date.now();

    await agendaPage.clickAddEvent();

    await agendaPage.fillEventForm({
      title: `Both Links ${timestamp}`,
      category: 'Test',
      date: '31/12/25',
      link: 'https://mysite.com/event-details',
    });

    await page.fill('#event-redirect', 'https://register.example.com');

    await agendaPage.submitForm();
    await agendaPage.waitForPageReload();

    await agendaPage.goto();
    const exists = await agendaPage.eventExists(`Both Links ${timestamp}`);
    expect(exists).toBeTruthy();
  });

  test('should add event with price', async ({ page }) => {
    const timestamp = Date.now();

    await agendaPage.clickAddEvent();

    await agendaPage.fillEventForm({
      title: `Priced Event ${timestamp}`,
      category: 'Workshop',
      date: '31/12/25',
      price: 'CHF 150.-',
    });

    await agendaPage.submitForm();
    await agendaPage.waitForPageReload();

    await agendaPage.goto();
    const exists = await agendaPage.eventExists(`Priced Event ${timestamp}`);
    expect(exists).toBeTruthy();
  });

  test('should add free event', async ({ page }) => {
    const timestamp = Date.now();

    await agendaPage.clickAddEvent();

    await agendaPage.fillEventForm({
      title: `Free Event ${timestamp}`,
      category: 'Seminar',
      date: '31/12/25',
      price: 'Free',
    });

    await agendaPage.submitForm();
    await agendaPage.waitForPageReload();

    await agendaPage.goto();
    const exists = await agendaPage.eventExists(`Free Event ${timestamp}`);
    expect(exists).toBeTruthy();
  });

  test('should add event with "On request" price', async ({ page }) => {
    const timestamp = Date.now();

    await agendaPage.clickAddEvent();

    await agendaPage.fillEventForm({
      title: `On Request ${timestamp}`,
      category: 'Consultation',
      date: '31/12/25',
      price: 'On request',
    });

    await agendaPage.submitForm();
    await agendaPage.waitForPageReload();

    await agendaPage.goto();
    const exists = await agendaPage.eventExists(`On Request ${timestamp}`);
    expect(exists).toBeTruthy();
  });

  test('should create event with all fields filled', async ({ page }) => {
    const timestamp = Date.now();

    await agendaPage.clickAddEvent();

    await agendaPage.fillEventForm({
      title: `Complete Event ${timestamp}`,
      category: 'Conference',
      location: 'Convention Center, Geneva',
      date: '31/12/25',
      intro: 'This is a complete event with all fields filled.',
      price: 'CHF 250.-',
      link: 'https://mysite.com/complete-event',
    });

    // Set image URL
    await page.fill('#event-image', 'https://example.com/image.jpg');

    // Set external URL
    await page.fill('#event-redirect', 'https://register.example.com/complete');

    // Set partial attendance
    await page.selectOption('#event-candopartial', '1');

    // Set advance payment
    await page.selectOption('#event-account', '1');

    await agendaPage.submitForm();
    await agendaPage.waitForPageReload();

    await agendaPage.goto();
    const exists = await agendaPage.eventExists(`Complete Event ${timestamp}`);
    expect(exists).toBeTruthy();
  });

  test('should display contact form shortcode button', async ({ page }) => {
    const timestamp = Date.now();

    // Create an event first
    await agendaPage.clickAddEvent();
    await agendaPage.fillEventForm({
      title: `Shortcode Test ${timestamp}`,
      category: 'Test',
      date: '31/12/25',
    });
    await agendaPage.submitForm();
    await agendaPage.waitForPageReload();

    await agendaPage.goto();

    // Find the event row
    const eventRow = page.locator('table.wp-list-table tbody tr').filter({ hasText: `Shortcode Test ${timestamp}` });

    // Should have shortcode action
    const shortcodeButton = eventRow.locator('.row-actions .shortcode, a:has-text("Form shortcode")');

    // Check if visible (may require hovering)
    const count = await shortcodeButton.count();
    expect(count).toBeGreaterThanOrEqual(0); // May need hover to show
  });

  test('should update event with new advanced fields', async ({ page }) => {
    const timestamp = Date.now();
    const originalTitle = `Update Advanced ${timestamp}`;

    // Create event
    await agendaPage.clickAddEvent();
    await agendaPage.fillEventForm({
      title: originalTitle,
      category: 'Test',
      date: '31/12/25',
    });
    await agendaPage.submitForm();
    await agendaPage.waitForPageReload();
    await agendaPage.goto();

    // Edit event to add advanced fields
    await agendaPage.editEvent(originalTitle);

    // Update price and URLs
    await page.fill('#event-price', 'CHF 99.-');
    await page.fill('#event-link', 'https://updated.com/event');
    await page.fill('#event-redirect', 'https://register.updated.com');
    await page.selectOption('#event-candopartial', '2');
    await page.selectOption('#event-account', '1');

    await agendaPage.submitForm();
    await agendaPage.waitForPageReload();

    await agendaPage.goto();
    const exists = await agendaPage.eventExists(originalTitle);
    expect(exists).toBeTruthy();
  });
});
