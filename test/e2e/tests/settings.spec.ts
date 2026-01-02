import { test, expect } from './fixtures';

test.describe('Settings Page', () => {

  test('should load settings page', async ({ page }) => {
    await page.goto('/wp-admin/admin.php?page=acsagma-settings');
    await page.waitForLoadState('networkidle');

    // Check page title
    await expect(page.locator('h1')).toContainText('Agenda Settings');

    // Check form exists
    await expect(page.locator('form')).toBeVisible();
  });

  test('should display current agenda page name', async ({ page }) => {
    await page.goto('/wp-admin/admin.php?page=acsagma-settings');
    await page.waitForLoadState('networkidle');

    // Page name input should be visible and have a value
    const pageNameInput = page.locator('input[name="acsagma_page"]');
    await expect(pageNameInput).toBeVisible();

    const value = await pageNameInput.inputValue();
    expect(value.length).toBeGreaterThan(0);
  });

  test('should display Google Maps API key field', async ({ page }) => {
    await page.goto('/wp-admin/admin.php?page=acsagma-settings');
    await page.waitForLoadState('networkidle');

    // API key input should be visible
    const apiKeyInput = page.locator('input[name="acsagma_google_maps_api_key"]');
    await expect(apiKeyInput).toBeVisible();
  });

  test('should save Google Maps API key', async ({ page }) => {
    await page.goto('/wp-admin/admin.php?page=acsagma-settings');
    await page.waitForLoadState('networkidle');

    const testApiKey = 'AIzaSyTest123456789';

    // Fill API key
    await page.fill('input[name="acsagma_google_maps_api_key"]', testApiKey);

    // Submit form
    await page.click('input[type="submit"]');
    await page.waitForLoadState('networkidle');

    // Should show success message
    const successNotice = page.locator('.notice-success, .updated');
    await expect(successNotice).toBeVisible({ timeout: 5000 });

    // Verify the value was saved
    const apiKeyInput = page.locator('input[name="acsagma_google_maps_api_key"]');
    await expect(apiKeyInput).toHaveValue(testApiKey);
  });

  test('should update agenda page name', async ({ page }) => {
    await page.goto('/wp-admin/admin.php?page=acsagma-settings');
    await page.waitForLoadState('networkidle');

    // Save original page name to restore later
    const pageNameInput = page.locator('input[name="acsagma_page"]');
    const originalPageName = await pageNameInput.inputValue();

    const timestamp = Date.now();
    const newPageName = `Test Agenda ${timestamp}`;

    // Fill new page name
    await page.fill('input[name="acsagma_page"]', newPageName);

    // Submit form
    await page.click('input[type="submit"]');
    await page.waitForLoadState('networkidle');

    // Should show success message
    const successNotice = page.locator('.notice-success, .updated');
    await expect(successNotice).toBeVisible({ timeout: 5000 });

    // Verify the value was saved
    await expect(pageNameInput).toHaveValue(newPageName);

    // Restore original page name
    await page.fill('input[name="acsagma_page"]', originalPageName);
    await page.click('input[type="submit"]');
    await page.waitForLoadState('networkidle');

    // Verify original is restored
    await expect(pageNameInput).toHaveValue(originalPageName);
  });

  test('should have link to view current agenda page', async ({ page }) => {
    await page.goto('/wp-admin/admin.php?page=acsagma-settings');
    await page.waitForLoadState('networkidle');

    // Should have a link to view the agenda page
    const viewAgendaLink = page.locator('a[href*="agenda"]').first();
    await expect(viewAgendaLink).toBeVisible();
  });

  test('should display usage instructions', async ({ page }) => {
    await page.goto('/wp-admin/admin.php?page=acsagma-settings');
    await page.waitForLoadState('networkidle');

    // Should show shortcode in a code element
    const shortcodeText = page.locator('code:has-text("[acsagma_agenda]")');
    await expect(shortcodeText).toBeVisible();
  });

  test('should display Google Maps setup instructions', async ({ page }) => {
    await page.goto('/wp-admin/admin.php?page=acsagma-settings');
    await page.waitForLoadState('networkidle');

    // Should show Google Maps API key field label
    const apiInstructions = page.locator('label:has-text("Google Maps API Key")');
    await expect(apiInstructions).toBeVisible();
  });

  test('should handle empty API key gracefully', async ({ page }) => {
    await page.goto('/wp-admin/admin.php?page=acsagma-settings');
    await page.waitForLoadState('networkidle');

    // Clear API key
    await page.fill('input[name="acsagma_google_maps_api_key"]', '');

    // Submit form
    await page.click('input[type="submit"]');
    await page.waitForLoadState('networkidle');

    // Should still show success (empty is valid)
    const successNotice = page.locator('.notice-success, .updated');
    await expect(successNotice).toBeVisible({ timeout: 5000 });
  });

  test('should sanitize page name input', async ({ page }) => {
    await page.goto('/wp-admin/admin.php?page=acsagma-settings');
    await page.waitForLoadState('networkidle');

    // Save original page name to restore later
    const pageNameInput = page.locator('input[name="acsagma_page"]');
    const originalPageName = await pageNameInput.inputValue();

    // Try to input page name with special characters
    const unsafeName = 'Test<script>alert("xss")</script>Page';

    await page.fill('input[name="acsagma_page"]', unsafeName);
    await page.click('input[type="submit"]');
    await page.waitForLoadState('networkidle');

    // Should be sanitized
    const savedValue = await pageNameInput.inputValue();

    // Should not contain script tags
    expect(savedValue).not.toContain('<script>');
    expect(savedValue).not.toContain('</script>');

    // Restore original page name
    await page.fill('input[name="acsagma_page"]', originalPageName);
    await page.click('input[type="submit"]');
    await page.waitForLoadState('networkidle');

    // Verify original is restored
    await expect(pageNameInput).toHaveValue(originalPageName);
  });
});
