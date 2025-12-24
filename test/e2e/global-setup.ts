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

    await page.goto(`${baseURL}/wp-login.php`);
    await page.fill('#user_login', 'admin');
    await page.fill('#user_pass', 'admin');
    await page.click('#wp-submit');

    // Wait for dashboard to load
    await page.waitForURL('**/wp-admin/**', { timeout: 30000 });

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
