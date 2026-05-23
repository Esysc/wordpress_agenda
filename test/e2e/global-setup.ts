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
    await Promise.all([
      page
        .waitForURL(/wp-(admin|login)\//, { timeout: 45000 })
        .catch(() => null),
      page.click('#wp-submit'),
    ]);

    // Navigate explicitly to admin to avoid relying on redirect timing only.
    await page.goto(`${baseURL}/wp-admin/`, { waitUntil: 'domcontentloaded' });

    // If login failed, WordPress keeps us on wp-login with #login_error.
    if (page.url().includes('/wp-login.php')) {
      const loginError = await page.locator('#login_error').first().textContent().catch(() => null);
      if (loginError) {
        throw new Error(`WordPress login failed: ${loginError.trim()}`);
      }

      throw new Error('WordPress login failed: still on login page after submit.');
    }

    await page.waitForSelector('#wpadminbar', { timeout: 60000 });

    console.log('✅ Login successful');

    // Force admin locale to English so text-based assertions remain stable.
    await page.goto(`${baseURL}/wp-admin/profile.php`, { waitUntil: 'domcontentloaded' });
    const localeSelect = page.locator('#locale');
    if (await localeSelect.count()) {
      const currentLocale = await localeSelect.inputValue();
      const englishLocale = await localeSelect.evaluate((el) => {
        const options = Array.from((el as HTMLSelectElement).options);

        const siteDefault = options.find((opt) => opt.value === '');
        if (siteDefault) {
          return siteDefault.value;
        }

        const exact = options.find((opt) => opt.value === 'en_US');
        if (exact) {
          return exact.value;
        }

        const byLabel = options.find((opt) => /english/i.test(opt.textContent || ''));
        if (byLabel) {
          return byLabel.value;
        }

        const byValue = options.find((opt) => /^en([_-]|$)/i.test(opt.value));
        return byValue ? byValue.value : null;
      });

      if (englishLocale !== null && currentLocale !== englishLocale) {
        await localeSelect.selectOption(englishLocale);
        await page.click('input#submit');
        await page.waitForLoadState('networkidle');
        console.log(`🌐 Locale switched to ${englishLocale || 'Site Default'} for E2E tests`);
      } else if (englishLocale === null) {
        console.log('ℹ️ No English locale option available in profile settings');
      }
    }

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
