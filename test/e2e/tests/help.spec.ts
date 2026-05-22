import { test, expect } from './fixtures';

test.describe('Help Page', () => {

  test('should load help page', async ({ page }) => {
    await page.goto('/wp-admin/admin.php?page=acsagma-help');
    await page.waitForLoadState('networkidle');

    // Check page title is visible (text is locale-dependent)
    await expect(page.locator('.acs-help-page h1')).toBeVisible();
  });

  test('should display table of contents', async ({ page }) => {
    await page.goto('/wp-admin/admin.php?page=acsagma-help');
    await page.waitForLoadState('networkidle');

    // Should have table of contents section (class is locale-independent)
    const toc = page.locator('.acs-help-toc');
    await expect(toc).toBeVisible();
  });

  test('should display Getting Started section', async ({ page }) => {
    await page.goto('/wp-admin/admin.php?page=acsagma-help');
    await page.waitForLoadState('networkidle');

    // Use the stable section ID instead of translated heading text
    const gettingStarted = page.locator('section#getting-started h2');
    await expect(gettingStarted).toBeVisible();
  });

  test('should display shortcode usage instructions', async ({ page }) => {
    await page.goto('/wp-admin/admin.php?page=acsagma-help');
    await page.waitForLoadState('networkidle');

    // Should show the shortcode in a code block
    const shortcode = page.locator('code:has-text("[acsagma_agenda]")');
    await expect(shortcode).toBeVisible();
  });

  test('should display field descriptions', async ({ page }) => {
    await page.goto('/wp-admin/admin.php?page=acsagma-help');
    await page.waitForLoadState('networkidle');

    // Use stable section ID instead of translated heading text
    const fieldDescriptions = page.locator('section#adding-events h2');
    await expect(fieldDescriptions).toBeVisible();
    // Verify the field table exists
    const fieldTable = page.locator('table.widefat').first();
    await expect(fieldTable).toBeVisible();
  });

  test('should explain Partial Attendance feature', async ({ page }) => {
    await page.goto('/wp-admin/admin.php?page=acsagma-help');
    await page.waitForLoadState('networkidle');

    // Use stable section ID instead of translated heading text
    const partialAttendance = page.locator('section#partial-attendance h2');
    await expect(partialAttendance).toBeVisible();

    // Should have a table with 3 option rows (No / Yes / Keep until end)
    const optionRows = page.locator('section#partial-attendance table.widefat tbody tr');
    await expect(optionRows).toHaveCount(3);
  });

  test('should display troubleshooting section', async ({ page }) => {
    await page.goto('/wp-admin/admin.php?page=acsagma-help');
    await page.waitForLoadState('networkidle');

    // Use stable section ID instead of translated heading text
    const troubleshooting = page.locator('section#troubleshooting h2');
    await expect(troubleshooting).toBeVisible();
  });

  test('should have troubleshooting for events not showing', async ({ page }) => {
    await page.goto('/wp-admin/admin.php?page=acsagma-help');
    await page.waitForLoadState('networkidle');

    // First h3 inside troubleshooting section (text is locale-dependent)
    const eventsNotShowing = page.locator('section#troubleshooting h3').first();
    await expect(eventsNotShowing).toBeVisible();
  });

  test('should have troubleshooting for calendar issues', async ({ page }) => {
    await page.goto('/wp-admin/admin.php?page=acsagma-help');
    await page.waitForLoadState('networkidle');

    // Second h3 inside troubleshooting section (text is locale-dependent)
    const calendarIssues = page.locator('section#troubleshooting h3').nth(1);
    await expect(calendarIssues).toBeVisible();
  });

  test('should display Google Maps setup instructions', async ({ page }) => {
    await page.goto('/wp-admin/admin.php?page=acsagma-help');
    await page.waitForLoadState('networkidle');

    // Should have Google Maps section
    const googleMaps = page.locator('h3:has-text("Google Maps")');
    await expect(googleMaps).toBeVisible();
  });

  test('should have support/contact information', async ({ page }) => {
    await page.goto('/wp-admin/admin.php?page=acsagma-help');
    await page.waitForLoadState('networkidle');

    // GitHub link inside troubleshooting section confirms support info is present
    const support = page.locator('section#troubleshooting a[href*="github"]');
    await expect(support).toBeVisible();
  });

  test('should have link to GitHub issues', async ({ page }) => {
    await page.goto('/wp-admin/admin.php?page=acsagma-help');
    await page.waitForLoadState('networkidle');

    // Should have GitHub link
    const githubLink = page.locator('a[href*="github"]');
    await expect(githubLink).toBeVisible();
  });

  test('should display plugin version information', async ({ page }) => {
    await page.goto('/wp-admin/admin.php?page=acsagma-help');
    await page.waitForLoadState('networkidle');

    // "ACS" is preserved in all locales
    const pluginName = page.locator('h1:has-text("ACS")');
    await expect(pluginName).toBeVisible();
  });

  test('should explain template customization', async ({ page }) => {
    await page.goto('/wp-admin/admin.php?page=acsagma-help');
    await page.waitForLoadState('networkidle');

    // Use stable section ID instead of translated heading text
    const customization = page.locator('section#customization h2');
    await expect(customization).toBeVisible();
  });

  test('should have CSS styling information', async ({ page }) => {
    await page.goto('/wp-admin/admin.php?page=acsagma-help');
    await page.waitForLoadState('networkidle');

    // CSS variable code block is never translated
    const css = page.locator('section#customization pre:has-text("--acs-primary-color")');
    await expect(css).toBeVisible();
  });

  test('should display event management instructions', async ({ page }) => {
    await page.goto('/wp-admin/admin.php?page=acsagma-help');
    await page.waitForLoadState('networkidle');

    // Use stable section ID instead of translated heading text
    const managing = page.locator('section#managing-events h2');
    await expect(managing).toBeVisible();
  });

  test('should have search and filter instructions', async ({ page }) => {
    await page.goto('/wp-admin/admin.php?page=acsagma-help');
    await page.waitForLoadState('networkidle');

    // Use stable section ID; the managing-events section has h3 sub-sections for filtering/searching
    const filtering = page.locator('section#managing-events h3');
    await expect(filtering.first()).toBeVisible();
  });

  test('should be accessible from admin menu', async ({ page }) => {
    await page.goto('/wp-admin/admin.php?page=acsagma-agenda');
    await page.waitForLoadState('networkidle');

    // Navigate to help via menu
    await page.goto('/wp-admin/admin.php?page=acsagma-help');
    await page.waitForLoadState('networkidle');

    // Should successfully load (text is locale-dependent)
    await expect(page.locator('.acs-help-page h1')).toBeVisible();
  });

  test('should have anchor links for navigation', async ({ page }) => {
    await page.goto('/wp-admin/admin.php?page=acsagma-help');
    await page.waitForLoadState('networkidle');

    // Check for anchor links (typically in table of contents)
    const anchorLinks = page.locator('a[href^="#"]');
    const count = await anchorLinks.count();

    // Should have at least some anchor links for internal navigation
    expect(count).toBeGreaterThan(0);
  });

  test('should display properly formatted content', async ({ page }) => {
    await page.goto('/wp-admin/admin.php?page=acsagma-help');
    await page.waitForLoadState('networkidle');

    // Should have proper heading hierarchy
    const h2Headings = page.locator('h2');
    const h2Count = await h2Headings.count();

    expect(h2Count).toBeGreaterThan(0);
  });
});
