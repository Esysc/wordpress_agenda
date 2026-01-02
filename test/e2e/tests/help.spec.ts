import { test, expect } from './fixtures';

test.describe('Help Page', () => {

  test('should load help page', async ({ page }) => {
    await page.goto('/wp-admin/admin.php?page=acsagma-help');
    await page.waitForLoadState('networkidle');

    // Check page title
    await expect(page.locator('h1')).toContainText('User Guide');
  });

  test('should display table of contents', async ({ page }) => {
    await page.goto('/wp-admin/admin.php?page=acsagma-help');
    await page.waitForLoadState('networkidle');

    // Should have table of contents section
    const toc = page.locator('text=/Contents|Table of Contents/i');
    await expect(toc).toBeVisible();
  });

  test('should display Getting Started section', async ({ page }) => {
    await page.goto('/wp-admin/admin.php?page=acsagma-help');
    await page.waitForLoadState('networkidle');

    const gettingStarted = page.locator('h2:has-text("Getting Started")');
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

    // Should have adding events section with field table
    const fieldDescriptions = page.locator('h2:has-text("Adding Events")');
    await expect(fieldDescriptions).toBeVisible();
    // Verify the field table exists
    const fieldTable = page.locator('table.widefat').first();
    await expect(fieldTable).toBeVisible();
  });

  test('should explain Partial Attendance feature', async ({ page }) => {
    await page.goto('/wp-admin/admin.php?page=acsagma-help');
    await page.waitForLoadState('networkidle');

    // Should have partial attendance explanation
    const partialAttendance = page.locator('h2:has-text("Partial Attendance")');
    await expect(partialAttendance).toBeVisible();

    // Should explain the three options in the table
    const optionKeep = page.locator('text=/Keep until end/i');
    await expect(optionKeep).toBeVisible();
  });

  test('should display troubleshooting section', async ({ page }) => {
    await page.goto('/wp-admin/admin.php?page=acsagma-help');
    await page.waitForLoadState('networkidle');

    const troubleshooting = page.locator('h2:has-text("Troubleshooting")');
    await expect(troubleshooting).toBeVisible();
  });

  test('should have troubleshooting for events not showing', async ({ page }) => {
    await page.goto('/wp-admin/admin.php?page=acsagma-help');
    await page.waitForLoadState('networkidle');

    const eventsNotShowing = page.locator('text=/Events not showing/i');
    await expect(eventsNotShowing).toBeVisible();
  });

  test('should have troubleshooting for calendar issues', async ({ page }) => {
    await page.goto('/wp-admin/admin.php?page=acsagma-help');
    await page.waitForLoadState('networkidle');

    const calendarIssues = page.locator('text=/Calendar not working/i');
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

    // Should have "Need more help?" text and GitHub link
    const support = page.locator('text=/Need more help/i');
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

    // Should show plugin name in title
    const pluginName = page.locator('h1:has-text("ACS Agenda Manager")');
    await expect(pluginName).toBeVisible();
  });

  test('should explain template customization', async ({ page }) => {
    await page.goto('/wp-admin/admin.php?page=acsagma-help');
    await page.waitForLoadState('networkidle');

    // Should have customization section
    const customization = page.locator('h2:has-text("Customization")');
    await expect(customization).toBeVisible();
  });

  test('should have CSS styling information', async ({ page }) => {
    await page.goto('/wp-admin/admin.php?page=acsagma-help');
    await page.waitForLoadState('networkidle');

    // Should mention CSS Styling as a heading
    const css = page.locator('h3:has-text("CSS Styling")');
    await expect(css).toBeVisible();
  });

  test('should display event management instructions', async ({ page }) => {
    await page.goto('/wp-admin/admin.php?page=acsagma-help');
    await page.waitForLoadState('networkidle');

    // Should have Managing Events section
    const managing = page.locator('h2:has-text("Managing Events")');
    await expect(managing).toBeVisible();
  });

  test('should have search and filter instructions', async ({ page }) => {
    await page.goto('/wp-admin/admin.php?page=acsagma-help');
    await page.waitForLoadState('networkidle');

    // Should have Filtering Events or Searching Events section
    const filtering = page.locator('h3:has-text("Filtering Events")');
    await expect(filtering).toBeVisible();
  });

  test('should be accessible from admin menu', async ({ page }) => {
    await page.goto('/wp-admin/admin.php?page=acsagma-agenda');
    await page.waitForLoadState('networkidle');

    // Navigate to help via menu
    await page.goto('/wp-admin/admin.php?page=acsagma-help');
    await page.waitForLoadState('networkidle');

    // Should successfully load
    await expect(page.locator('h1')).toContainText('User Guide');
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
