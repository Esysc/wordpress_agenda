import { chromium, FullConfig } from '@playwright/test';

async function globalSetup(config: FullConfig) {
  const baseURL = config.projects[0].use.baseURL || 'http://localhost:8080';

  console.log('🔧 Running global setup...');
  console.log(`📍 WordPress URL: ${baseURL}`);

  const browser = await chromium.launch();
  const context = await browser.newContext();
  const page = await context.newPage();

  try {
    // Login to WordPress and save auth state
    console.log('🔐 Logging into WordPress admin...');

    await page.goto(`${baseURL}/wp-login.php`, { waitUntil: 'domcontentloaded' });
    await page.fill('#user_login', 'admin');
    await page.fill('#user_pass', 'admin');
    await page.click('#wp-submit');

    // Wait for admin redirect using URL matcher first, then fall back to admin marker.
    const loginReachedAdmin = await page
      .waitForURL('**/wp-admin/**', { timeout: 45000 })
      .then(() => true)
      .catch(() => false);

    if (!loginReachedAdmin) {
      const loginError = await page.locator('#login_error').first().textContent().catch(() => null);
      if (loginError) {
        throw new Error(`WordPress login failed: ${loginError.trim()}`);
      }

      // Some local setups take longer before the toolbar appears even after auth succeeds.
      await page.waitForSelector('#wpadminbar', { timeout: 45000 });
    }

    await page.waitForSelector('#wpadminbar', { timeout: 30000 });

    console.log('✅ Login successful');

    // Save authentication state
    await context.storageState({ path: './auth.json' });
    console.log('💾 Auth state saved');

  } catch (error) {
    console.error('❌ Global setup failed:', error);
    throw error;
  } finally {
    await browser.close();
  }
}

export default globalSetup;
