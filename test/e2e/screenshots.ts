import { chromium, Browser, Page } from '@playwright/test';
import * as path from 'path';
import * as fs from 'fs';

/**
 * Automated Screenshot Generator for WordPress.org Plugin Assets
 *
 * This script captures screenshots for the plugin's WordPress.org listing.
 * Run with: npx playwright test screenshots.ts --project=chromium
 * Or: npx ts-node screenshots.ts
 */

const ASSETS_DIR = path.resolve(__dirname, '../../.wordpress-org');
const BASE_URL = process.env.WP_URL || 'http://localhost:8080';

// Ensure assets directory exists
if (!fs.existsSync(ASSETS_DIR)) {
  fs.mkdirSync(ASSETS_DIR, { recursive: true });
}

interface ScreenshotConfig {
  name: string;
  description: string;
  path: string;
  width: number;
  height: number;
  action: (page: Page) => Promise<void>;
}

const screenshots: ScreenshotConfig[] = [
  {
    name: 'Admin Dashboard',
    description: 'The main Agenda Manager admin interface',
    path: 'screenshot-1.png',
    width: 1280,
    height: 800,
    action: async (page) => {
      await page.goto(`${BASE_URL}/wp-admin/admin.php?page=agenda`, { timeout: 30000 });
      await page.waitForLoadState('networkidle');
      // Wait for the table to be visible
      await page.waitForSelector('#acs-add-event', { state: 'visible', timeout: 10000 });
    }
  },
  {
    name: 'Add Event Dialog',
    description: 'The event creation dialog with form fields',
    path: 'screenshot-2.png',
    width: 1280,
    height: 900,
    action: async (page) => {
      await page.goto(`${BASE_URL}/wp-admin/admin.php?page=agenda`, { timeout: 30000 });
      await page.waitForLoadState('networkidle');
      await page.click('#acs-add-event');
      await page.waitForSelector('.ui-dialog:has(#acs-event-dialog)', { state: 'visible', timeout: 10000 });
      // Fill some sample data for better screenshot
      await page.fill('#event-title', 'Yoga Workshop');
      await page.fill('#event-categorie', 'Wellness');
      await page.fill('#event-emplacement', 'Community Center');
      await page.fill('#event-intro', 'Join us for a relaxing yoga session suitable for all levels.');
      await page.fill('#event-price', 'CHF 25.-');
    }
  },
  {
    name: 'Calendar Date Picker',
    description: 'Multi-date selection calendar',
    path: 'screenshot-3.png',
    width: 1280,
    height: 900,
    action: async (page) => {
      await page.goto(`${BASE_URL}/wp-admin/admin.php?page=agenda`, { timeout: 30000 });
      await page.waitForLoadState('networkidle');
      await page.click('#acs-add-event');
      await page.waitForSelector('.ui-dialog:has(#acs-event-dialog)', { state: 'visible', timeout: 10000 });
      await page.click('.acs-open-calendar');
      await page.waitForSelector('#acs-datepicker-container.active', { state: 'visible', timeout: 10000 });
    }
  },
  {
    name: 'Frontend Agenda Display',
    description: 'How the agenda looks on the public website',
    path: 'screenshot-4.png',
    width: 1280,
    height: 800,
    action: async (page) => {
      await page.goto(`${BASE_URL}/agenda/`, { timeout: 30000 });
      await page.waitForLoadState('networkidle');
    }
  },
  {
    name: 'Settings Page',
    description: 'Plugin settings and configuration options',
    path: 'screenshot-5.png',
    width: 1280,
    height: 800,
    action: async (page) => {
      await page.goto(`${BASE_URL}/wp-admin/admin.php?page=agenda-settings`, { timeout: 30000 });
      await page.waitForLoadState('networkidle');
    }
  }
];

async function authenticate(page: Page): Promise<void> {
  console.log('🔐 Authenticating...');

  try {
    // Go to login page
    console.log('   → Loading login page...');
    await page.goto(`${BASE_URL}/wp-login.php`, { timeout: 15000, waitUntil: 'networkidle' });

    // Check if login form exists (means we need to log in)
    const loginForm = await page.$('#loginform');
    if (!loginForm) {
      console.log('   ✅ Already logged in');
      return;
    }

    // Fill login form
    console.log('   → Filling credentials...');
    await page.fill('#user_login', process.env.WP_USER || 'admin');
    await page.fill('#user_pass', process.env.WP_PASS || 'admin');

    // Submit and wait for navigation
    console.log('   → Submitting...');
    await Promise.all([
      page.waitForNavigation({ timeout: 15000, waitUntil: 'networkidle' }),
      page.click('#wp-submit')
    ]);

    console.log('   ✅ Logged in successfully');
  } catch (error) {
    console.log(`   ⚠ Auth error: ${error}`);
    throw error; // Don't continue if auth fails
  }
}

async function createSampleEvents(page: Page): Promise<void> {
  console.log('📅 Checking for existing events...');

  try {
    await page.goto(`${BASE_URL}/wp-admin/admin.php?page=agenda`);
    await page.waitForLoadState('networkidle');
    await page.waitForSelector('#acs-add-event', { state: 'visible', timeout: 10000 });

    // Check if events already exist (from WP-CLI setup)
    const eventRows = await page.$$('table.wp-list-table tbody tr');
    if (eventRows.length > 0) {
      console.log(`   ✅ Found ${eventRows.length} existing events`);
      return;
    }

    console.log('   → No events found, creating sample events...');

    const sampleEvents = [
      { title: 'Yoga Workshop', category: 'Wellness', location: 'Community Center', price: 'CHF 25.-' },
      { title: 'Photography Course', category: 'Art', location: 'Studio 42', price: 'CHF 150.-' },
      { title: 'Cooking Class: Italian', category: 'Culinary', location: 'Chef\'s Kitchen', price: 'CHF 80.-' },
      { title: 'Web Development Bootcamp', category: 'Technology', location: 'Tech Hub', price: 'CHF 350.-' },
    ];

    for (const event of sampleEvents) {
      try {
        console.log(`   → Creating: ${event.title}...`);
        await page.click('#acs-add-event');
        await page.waitForSelector('.ui-dialog:has(#acs-event-dialog)', { state: 'visible', timeout: 5000 });

        await page.fill('#event-title', event.title);
        await page.fill('#event-categorie', event.category);
        await page.fill('#event-emplacement', event.location);
        await page.fill('#event-price', event.price);

        // Set a future date
        await page.evaluate(() => {
          const input = document.getElementById('event-date') as HTMLInputElement;
          if (input) {
            const futureDate = new Date();
            futureDate.setDate(futureDate.getDate() + 7 + Math.floor(Math.random() * 30));
            input.readOnly = false;
            input.value = futureDate.toISOString().split('T')[0];
          }
        });

        const submitButton = page.locator('#acs-event-dialog').locator('..').locator('.ui-dialog-buttonset button').first();
        await submitButton.click({ timeout: 5000 });
        await page.waitForLoadState('networkidle');

        console.log(`   ✓ Created: ${event.title}`);
      } catch (e) {
        console.log(`   ⚠ Skipped: ${event.title}`);
        await page.keyboard.press('Escape');
        await page.waitForTimeout(500);
      }
    }
  } catch (error) {
    console.log(`   ⚠ Could not create sample events: ${error}`);
  }
}

async function captureScreenshots(): Promise<void> {
  console.log('');
  console.log('================================================');
  console.log('  WordPress.org Screenshot Generator');
  console.log('================================================');
  console.log('');
  console.log(`📍 WordPress URL: ${BASE_URL}`);

  const browser: Browser = await chromium.launch({
    headless: true
  });

  try {
    // Check for saved auth state (created by e2e tests)
    const authFile = path.resolve(__dirname, './auth.json');
    const hasAuthState = fs.existsSync(authFile);

    if (hasAuthState) {
      console.log('🔐 Using saved auth state from auth.json');
    } else {
      console.log('⚠ No auth.json found. Run e2e tests first: npx playwright test');
    }

    const context = await browser.newContext({
      viewport: { width: 1280, height: 800 },
      baseURL: BASE_URL,
      ...(hasAuthState ? { storageState: authFile } : {}),
    });
    const page = await context.newPage();

    // If no auth state, try to authenticate
    if (!hasAuthState) {
      await authenticate(page);
    }

    // Create sample events for better screenshots
    await createSampleEvents(page);

    console.log('');
    console.log('📸 Capturing screenshots...');
    console.log('');

    for (const screenshot of screenshots) {
      try {
        await page.setViewportSize({
          width: screenshot.width,
          height: screenshot.height
        });

        await screenshot.action(page);
        await page.waitForTimeout(500); // Let animations settle

        const outputPath = path.join(ASSETS_DIR, screenshot.path);
        await page.screenshot({
          path: outputPath,
          fullPage: false
        });

        console.log(`   ✓ ${screenshot.path} - ${screenshot.name}`);
      } catch (error) {
        console.log(`   ✗ ${screenshot.path} - Failed: ${error}`);
      }
    }

    await context.close();

  } finally {
    await browser.close();
  }

  console.log('');
  console.log('================================================');
  console.log('  Screenshots saved to .wordpress-org/');
  console.log('================================================');
  console.log('');

  // List captured files
  const files = fs.readdirSync(ASSETS_DIR).filter(f => f.endsWith('.png'));
  files.forEach(f => console.log(`  • ${f}`));
  console.log('');
}

// Run if executed directly
captureScreenshots().catch(console.error);
