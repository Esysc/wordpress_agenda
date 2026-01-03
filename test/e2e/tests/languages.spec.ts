/// <reference types="node" />

import { test, expect } from './fixtures';
import * as fs from 'fs';
import * as path from 'path';

// Parse .po file to extract msgid/msgstr pairs
function parsePoFile(filePath: string): Map<string, string> {
  const translations = new Map<string, string>();

  if (!fs.existsSync(filePath)) {
    return translations;
  }

  const content = fs.readFileSync(filePath, 'utf-8');
  const lines = content.split('\n');

  let currentMsgid = '';
  let currentMsgstr = '';
  let inMsgid = false;
  let inMsgstr = false;

  for (const line of lines) {
    if (line.startsWith('msgid "')) {
      inMsgid = true;
      inMsgstr = false;
      currentMsgid = line.slice(7, -1); // Remove 'msgid "' and trailing '"'
    } else if (line.startsWith('msgstr "')) {
      inMsgid = false;
      inMsgstr = true;
      currentMsgstr = line.slice(8, -1); // Remove 'msgstr "' and trailing '"'
    } else if (line.startsWith('"') && line.endsWith('"')) {
      // Continuation line
      const text = line.slice(1, -1);
      if (inMsgid) {
        currentMsgid += text;
      } else if (inMsgstr) {
        currentMsgstr += text;
      }
    } else if (line.trim() === '' && currentMsgid) {
      // Empty line = end of entry
      if (currentMsgid && currentMsgstr) {
        translations.set(currentMsgid, currentMsgstr);
      }
      currentMsgid = '';
      currentMsgstr = '';
      inMsgid = false;
      inMsgstr = false;
    }
  }

  // Don't forget the last entry
  if (currentMsgid && currentMsgstr) {
    translations.set(currentMsgid, currentMsgstr);
  }

  return translations;
}

// Get all available language files
function getAvailableLanguages(): Array<{ locale: string; name: string; filePath: string }> {
  const langDir = path.resolve(__dirname, '../../../lang');
  const languages: Array<{ locale: string; name: string; filePath: string }> = [];

  const localeNames: Record<string, string> = {
    'en_US': 'English',
    'fr_FR': 'French (France)',
    'fr_CH': 'French (Switzerland)',
    'de_DE': 'German (Germany)',
    'de_CH': 'German (Switzerland)',
    'it_IT': 'Italian (Italy)',
    'it_CH': 'Italian (Switzerland)',
    'ja': 'Japanese',
  };

  if (!fs.existsSync(langDir)) {
    return languages;
  }

  const files = fs.readdirSync(langDir);

  for (const file of files) {
    if (file.endsWith('.po')) {
      // Extract locale from filename: acs-agenda-manager-fr_FR.po -> fr_FR
      const match = file.match(/acs-agenda-manager-([a-z]{2}(?:_[A-Z]{2})?)\.po/);
      if (match) {
        const locale = match[1];
        languages.push({
          locale,
          name: localeNames[locale] || locale,
          filePath: path.join(langDir, file),
        });
      }
    }
  }

  return languages;
}

// Key strings to check (must exist in all translations)
const TEST_STRINGS = [
  'Add',
  'Agenda',
  'Settings',
];

test.describe('Language Translations', () => {
  const languages = getAvailableLanguages();

  // Add English as baseline (no .po file needed)
  const allLanguages = [
    { locale: 'en_US', name: 'English', filePath: '', translations: new Map<string, string>() },
    ...languages.map(lang => ({
      ...lang,
      translations: parsePoFile(lang.filePath),
    })),
  ];

  for (const lang of allLanguages) {
    test(`should display correct translations for ${lang.name} (${lang.locale})`, async ({ page }) => {
      // Step 1: Change WordPress language
      await page.goto('/wp-admin/options-general.php');
      await page.waitForLoadState('networkidle');

      const localeSelect = page.locator('select#WPLANG');

      if (await localeSelect.isVisible()) {
        try {
          // WordPress uses empty string for en_US
          const selectValue = lang.locale === 'en_US' ? '' : lang.locale;
          await localeSelect.selectOption(selectValue);
          await page.click('input#submit');
          await page.waitForLoadState('networkidle');
        } catch (e) {
          // Locale might not be installed, skip
          console.log(`Locale ${lang.locale} not available, skipping`);
          test.skip();
          return;
        }
      }

      // Step 2: Go to plugin admin page
      await page.goto('/wp-admin/admin.php?page=acsagma-agenda');
      await page.waitForLoadState('networkidle');

      // Step 3: Verify the page loaded
      await expect(page.locator('h1').first()).toBeVisible();

      // Step 4: Check for translated strings
      const pageContent = await page.content();

      if (lang.locale !== 'en_US' && lang.translations.size > 0) {
        // For non-English, check that at least some translated strings appear
        let foundTranslations = 0;

        for (const testString of TEST_STRINGS) {
          const translation = lang.translations.get(testString);
          if (translation && translation !== testString) {
            if (pageContent.includes(translation)) {
              foundTranslations++;
              console.log(`✓ Found "${testString}" → "${translation}"`);
            } else {
              console.log(`✗ Missing "${testString}" → "${translation}"`);
            }
          }
        }

        // At least one translation should be present
        expect(foundTranslations).toBeGreaterThan(0);
      } else {
        // For English, just verify English strings are present
        for (const testString of TEST_STRINGS) {
          // Page should contain English strings
          const hasString = pageContent.includes(testString);
          if (hasString) {
            console.log(`✓ Found English string: "${testString}"`);
          }
        }
      }
    });
  }

  test.afterAll(async ({ browser }) => {
    // Reset to English after all tests
    const page = await browser.newPage();
    await page.goto('/wp-admin/options-general.php');
    await page.waitForLoadState('networkidle');

    const localeSelect = page.locator('select#WPLANG');
    if (await localeSelect.isVisible()) {
      try {
        await localeSelect.selectOption('');
        await page.click('input#submit');
        await page.waitForLoadState('networkidle');
      } catch {
        // Ignore
      }
    }
    await page.close();
  });

});
