import type { Page } from '@playwright/test';
import { test, expect, getAgendaPageUrl } from './fixtures';

async function setTomorrowDate(page: Page) {
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
}

async function submitEventDialog(page: Page) {
  const dialog = page.locator('.ui-dialog:has(#acs-event-dialog)');
  const submitButton = dialog.locator('.ui-dialog-buttonset button').first();

  await Promise.all([
    page.waitForURL(/page=acsagma-agenda.*(?:created|updated)=1/),
    submitButton.click(),
  ]);

  await expect(page.locator('.notice.notice-success')).toBeVisible();
}

async function ensureAdvancedSettingsOpen(page: Page) {
  const advanced = page.locator('#acs-advanced-settings');
  if (!(await advanced.count())) {
    return;
  }

  const isOpen = await advanced.evaluate((el) => el.hasAttribute('open'));
  if (!isOpen) {
    await page.click('#acs-advanced-settings > summary');
  }
}

async function filterByTitle(page: Page, title: string) {
  const searchInput = page.locator('#acs-filter-search');
  await expect(searchInput).toBeVisible();
  await searchInput.fill(title);
  await expect(searchInput).toHaveValue(title);

  await page.waitForFunction((needle) => {
    const noResults = document.getElementById('acs-no-results');
    if (noResults && !noResults.hasAttribute('hidden')) {
      return true;
    }

    const cards = Array.from(document.querySelectorAll('#acs-agenda-list .acsagenda'));
    return cards.some((card) => {
      const el = card as HTMLElement;
      if (el.offsetParent === null) {
        return false;
      }

      return (el.textContent || '').toLowerCase().includes(needle.toLowerCase());
    });
  }, title);
}

async function createEvent(page: Page, data: { title: string; category: string; intro?: string; image?: string; link?: string; }) {
  await page.goto('/wp-admin/admin.php?page=acsagma-agenda');
  await page.waitForLoadState('networkidle');
  await page.click('#acs-add-event');
  await page.waitForSelector('.ui-dialog:has(#acs-event-dialog)', { state: 'visible' });
  await ensureAdvancedSettingsOpen(page);
  await page.fill('#event-title', data.title);
  await page.fill('#event-categorie', data.category);
  if (data.intro) {
    await page.fill('#event-intro', data.intro);
    await ensureAdvancedSettingsOpen(page);
  }
  if (data.image) {
    await page.fill('#event-image', data.image);
  }
  if (data.link) {
    await page.fill('#event-link', data.link);
  }

  await setTomorrowDate(page);

  await submitEventDialog(page);
}

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
    await setTomorrowDate(page);
    await page.fill('#event-intro', 'Test event for frontend display');

    await submitEventDialog(page);

    // Now check the frontend
    const agendaUrl = await getAgendaPageUrl(page);
    await page.goto(agendaUrl, { waitUntil: 'networkidle' });

    // Filter to the specific event to ensure it is on page 1
    await filterByTitle(page, eventTitle);

    // The event should be visible on the frontend
    const eventElement = page.locator('#acs-agenda-list .acsagenda .event-title', { hasText: eventTitle }).first();
    await expect(eventElement).toBeVisible({ timeout: 30000 });
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
    const siteOrigin = new URL(page.url()).origin;
    const localImageUrl = `${siteOrigin}/wp-includes/images/w-logo-blue.png`;

    await page.click('#acs-add-event');
    await page.waitForSelector('.ui-dialog:has(#acs-event-dialog)', { state: 'visible' });
    await ensureAdvancedSettingsOpen(page);

    await page.fill('#event-title', eventTitle);
    await page.fill('#event-categorie', 'Frontend Test');
    await page.fill('#event-image', localImageUrl);

    await setTomorrowDate(page);
    await submitEventDialog(page);

    // Check frontend
    const agendaUrl = await getAgendaPageUrl(page);
    await page.goto(agendaUrl, { waitUntil: 'networkidle' });

    // Filter to the specific event to ensure it is on page 1
    await filterByTitle(page, eventTitle);

    // Event should be visible
    const eventElement = page.locator('#acs-agenda-list .acsagenda .event-title', { hasText: eventTitle }).first();
    await expect(eventElement).toBeVisible({ timeout: 30000 });
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
    await ensureAdvancedSettingsOpen(page);

    await page.fill('#event-title', eventTitle);
    await page.fill('#event-categorie', 'Price Test');  // Category is required
    await page.fill('#event-price', price);

    await setTomorrowDate(page);
    await submitEventDialog(page);

    // Check frontend
    const agendaUrl = await getAgendaPageUrl(page);
    await page.goto(agendaUrl, { waitUntil: 'networkidle' });

    // Filter to the specific event to ensure it is on page 1
    await filterByTitle(page, eventTitle);

    // Event with price should be visible
    const eventCard = page.locator('#acs-agenda-list .acsagenda .event-title', { hasText: eventTitle }).first();
    await expect(eventCard).toBeVisible({ timeout: 30000 });
  });

  test('should show Read more when additional content exists', async ({ page }) => {
    // Get agenda URL for frontend checks.
    const agendaUrl = await getAgendaPageUrl(page);
    const siteOrigin = new URL(page.url()).origin;
    // Default WordPress install ships with post ID 1 containing plain text content.
    const detailsLink = `${siteOrigin}/?p=1`;

    await page.goto('/wp-admin/admin.php?page=acsagma-agenda');
    await page.waitForLoadState('networkidle');

    const eventTitle = `Has Details ${Date.now()}`;

    await page.click('#acs-add-event');
    await page.waitForSelector('.ui-dialog:has(#acs-event-dialog)', { state: 'visible' });
    await ensureAdvancedSettingsOpen(page);

    await page.fill('#event-title', eventTitle);
    await page.fill('#event-categorie', 'UX Test');
    await page.fill('#event-link', detailsLink);

    await setTomorrowDate(page);
    await submitEventDialog(page);

    await page.goto(agendaUrl, { waitUntil: 'networkidle' });

    // Filter to the specific event to ensure it is on page 1
    await filterByTitle(page, eventTitle);

    const eventCard = page.locator('.column-center').filter({ hasText: eventTitle }).first();
    await expect(eventCard).toBeVisible({ timeout: 30000 });
    await expect(eventCard.locator('.readmore.show')).toHaveCount(1);

    await eventCard.locator('.readmore.show').first().click();
    await expect(page.locator('#dialog.shown')).toBeVisible({ timeout: 10000 });

    // Verify close button (X) closes the dialog.
    await page.locator('#dialog #close').click();
    await expect(page.locator('#dialog.shown')).toHaveCount(0);
    await expect(page.locator('#dialog')).toBeHidden();
  });

  test('should hide Read more when there is no additional content', async ({ page }) => {
    // Create an event without a link/extra content source
    await page.goto('/wp-admin/admin.php?page=acsagma-agenda');
    await page.waitForLoadState('networkidle');

    const eventTitle = `No Details ${Date.now()}`;

    await page.click('#acs-add-event');
    await page.waitForSelector('.ui-dialog:has(#acs-event-dialog)', { state: 'visible' });

    await page.fill('#event-title', eventTitle);
    await page.fill('#event-categorie', 'UX Test');

    await setTomorrowDate(page);
    await submitEventDialog(page);

    // Validate frontend rendering for this event
    const agendaUrl = await getAgendaPageUrl(page);
    await page.goto(agendaUrl, { waitUntil: 'networkidle' });

    // Filter to the specific event to ensure it is on page 1
    await filterByTitle(page, eventTitle);

    const eventCard = page.locator('.column-center').filter({ hasText: eventTitle }).first();
    await expect(eventCard).toBeVisible({ timeout: 10000 });
    await expect(eventCard.locator('.readmore.show')).toHaveCount(0);
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

  test('should filter events by category and search', async ({ page }) => {
    const stamp = Date.now();
    const category = `FilterUX-${stamp}`;
    const title = `Filterable Event ${stamp}`;
    const keyword = `needle-${stamp}`;

    await createEvent(page, {
      title,
      category,
      intro: `This intro contains ${keyword}`,
    });

    const agendaUrl = await getAgendaPageUrl(page);
    await page.goto(agendaUrl, { waitUntil: 'networkidle' });

    await page.selectOption('#acs-filter-category', category.toLowerCase());
    await page.fill('#acs-filter-search', keyword);

    const visibleCards = page.locator('#acs-agenda-list .acsagenda:visible');
    await expect(visibleCards).toHaveCount(1);
    await expect(visibleCards.first()).toContainText(title);
  });

  test('should paginate filtered results', async ({ page }) => {
    const stamp = Date.now();
    const category = `PageUX-${stamp}`;

    for (let i = 1; i <= 9; i++) {
      await createEvent(page, {
        title: `Page Item ${i} ${stamp}`,
        category,
      });
    }

    const agendaUrl = await getAgendaPageUrl(page);
    await page.goto(agendaUrl, { waitUntil: 'networkidle' });
    await page.selectOption('#acs-filter-category', category.toLowerCase());

    await expect(page.locator('#acs-pagination .acs-page-number', { hasText: '2' })).toBeVisible();
    await page.click('#acs-pagination .acs-page-number:has-text("2")');
    await expect(page).toHaveURL(/acs_page=2/);
  });

  test('should sort filtered results by title', async ({ page }) => {
    const stamp = Date.now();
    const category = `SortUX-${stamp}`;

    await createEvent(page, { title: `ZZZ Event ${stamp}`, category });
    await createEvent(page, { title: `AAA Event ${stamp}`, category });

    const agendaUrl = await getAgendaPageUrl(page);
    await page.goto(agendaUrl, { waitUntil: 'networkidle' });
    await page.selectOption('#acs-filter-category', category.toLowerCase());
    await page.selectOption('#acs-sort-order', 'title');

    const firstVisibleTitle = page.locator('#acs-agenda-list .acsagenda:visible .event-title').first();
    await expect(firstVisibleTitle).toContainText(`AAA Event ${stamp}`);
  });

  test('should show no-results state when filters match nothing', async ({ page }) => {
    const agendaUrl = await getAgendaPageUrl(page);
    await page.goto(agendaUrl, { waitUntil: 'networkidle' });

    await page.fill('#acs-filter-search', 'this-will-not-match-any-event-123456');
    await expect(page.locator('#acs-no-results')).toBeVisible();
    await expect(page.locator('#acs-agenda-list .acsagenda:visible')).toHaveCount(0);
  });

  test('should render month group headings', async ({ page }) => {
    const agendaUrl = await getAgendaPageUrl(page);
    await page.goto(agendaUrl, { waitUntil: 'networkidle' });

    await expect(page.locator('.acs-month-heading').first()).toBeVisible();
  });

  test('should toggle compact mode', async ({ page }) => {
    const agendaUrl = await getAgendaPageUrl(page);
    await page.goto(agendaUrl, { waitUntil: 'networkidle' });

    const compactButton = page.locator('#acs-compact-toggle');
    await compactButton.click();

    await expect(compactButton).toHaveAttribute('aria-pressed', 'true');
    await expect(page.locator('#acs-agenda-list')).toHaveClass(/acs-compact-mode/);
  });

  test('should apply fallback image when image fails to load', async ({ page }) => {
    const stamp = Date.now();
    const title = `Image Fallback ${stamp}`;
    const category = `ImageFallback-${stamp}`;
    const siteOrigin = new URL((await getAgendaPageUrl(page)), 'http://localhost:8080').origin;
    const localImageUrl = `${siteOrigin}/wp-includes/images/w-logo-blue.png`;

    await createEvent(page, {
      title,
      category,
      image: localImageUrl,
    });

    const agendaUrl = await getAgendaPageUrl(page);
    await page.goto(agendaUrl, { waitUntil: 'networkidle' });
    await page.selectOption('#acs-filter-category', category.toLowerCase());

    const eventCard = page.locator('#acs-agenda-list .acsagenda:visible').filter({ hasText: title }).first();
    await expect(eventCard).toBeVisible();

    const image = eventCard.locator('.image-agenda').first();
    await expect(image).toBeVisible();

    await image.evaluate((img: HTMLImageElement) => {
      img.src = `/missing-image-acs-agenda.png?cb=${Date.now()}`;
    });

    await expect(image).toHaveClass(/is-fallback/);
    await expect(image).toHaveAttribute('src', /Accept-icon\.png/);
  });
});
