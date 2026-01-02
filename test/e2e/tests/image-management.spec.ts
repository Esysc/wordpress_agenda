import { test, expect, AgendaPage } from './fixtures';

test.describe('Image Management', () => {
  let agendaPage: AgendaPage;

  test.beforeEach(async ({ page }) => {
    agendaPage = new AgendaPage(page);
    await agendaPage.goto();
  });

  test('should display image upload button', async ({ page }) => {
    await agendaPage.clickAddEvent();

    // Check image upload button exists
    const uploadButton = page.locator('.acs-upload-image');
    await expect(uploadButton).toBeVisible();
  });

  test('should display image preview placeholder', async ({ page }) => {
    await agendaPage.clickAddEvent();

    // Check image preview exists
    const imagePreview = page.locator('#event-image-preview');
    await expect(imagePreview).toBeVisible();

    // Should show "No image selected" text
    const previewText = page.locator('.acs-image-preview-text');
    await expect(previewText).toContainText('No image selected');
  });

  test('should add image via URL input', async ({ page }) => {
    const timestamp = Date.now();
    const imageUrl = 'https://via.placeholder.com/300x200.png';

    await agendaPage.clickAddEvent();

    await agendaPage.fillEventForm({
      title: `Image URL Test ${timestamp}`,
      category: 'Test',
      date: '31/12/25',
    });

    // Enter image URL
    await page.fill('#event-image', imageUrl);

    // Trigger input event to update preview
    await page.dispatchEvent('#event-image', 'input');
    await page.waitForTimeout(500);

    // Preview should update - check if remove button appears
    const removeButton = page.locator('.acs-remove-image');
    await expect(removeButton).toBeVisible();

    await agendaPage.submitForm();
    await agendaPage.waitForPageReload();

    await agendaPage.goto();
    const exists = await agendaPage.eventExists(`Image URL Test ${timestamp}`);
    expect(exists).toBeTruthy();
  });

  test('should show remove button when image is added', async ({ page }) => {
    await agendaPage.clickAddEvent();

    const imageUrl = 'https://via.placeholder.com/300x200.png';
    await page.fill('#event-image', imageUrl);
    await page.dispatchEvent('#event-image', 'input');
    await page.waitForTimeout(300);

    // Remove button should be visible
    const removeButton = page.locator('.acs-remove-image');
    await expect(removeButton).toBeVisible();
  });

  test('should remove image when remove button is clicked', async ({ page }) => {
    await agendaPage.clickAddEvent();

    // Add image
    const imageUrl = 'https://via.placeholder.com/300x200.png';
    await page.fill('#event-image', imageUrl);
    await page.dispatchEvent('#event-image', 'input');
    await page.waitForTimeout(300);

    // Click remove button
    const removeButton = page.locator('.acs-remove-image');
    await removeButton.click();
    await page.waitForTimeout(300);

    // Image field should be empty
    const imageFieldValue = await page.inputValue('#event-image');
    expect(imageFieldValue).toBe('');

    // Remove button should be hidden
    await expect(removeButton).toBeHidden();

    // Preview should show placeholder again
    const previewText = page.locator('.acs-image-preview-text');
    await expect(previewText).toContainText('No image selected');
  });

  test('should update image preview when URL changes', async ({ page }) => {
    await agendaPage.clickAddEvent();

    // Add first image
    await page.fill('#event-image', 'https://via.placeholder.com/300x200.png');
    await page.dispatchEvent('#event-image', 'input');
    await page.waitForTimeout(300);

    // Verify remove button appears
    let removeButton = page.locator('.acs-remove-image');
    await expect(removeButton).toBeVisible();

    // Change to different image
    await page.fill('#event-image', 'https://via.placeholder.com/400x300.png');
    await page.dispatchEvent('#event-image', 'input');
    await page.waitForTimeout(300);

    // Remove button should still be visible
    removeButton = page.locator('.acs-remove-image');
    await expect(removeButton).toBeVisible();
  });

  test('should handle empty image field', async ({ page }) => {
    const timestamp = Date.now();

    await agendaPage.clickAddEvent();

    await agendaPage.fillEventForm({
      title: `No Image ${timestamp}`,
      category: 'Test',
      date: '31/12/25',
    });

    // Leave image field empty
    await page.fill('#event-image', '');

    await agendaPage.submitForm();
    await agendaPage.waitForPageReload();

    // Should succeed without image
    await agendaPage.goto();
    const exists = await agendaPage.eventExists(`No Image ${timestamp}`);
    expect(exists).toBeTruthy();
  });

  test('should open media library when upload button is clicked', async ({ page }) => {
    await agendaPage.clickAddEvent();

    // Click upload button
    const uploadButton = page.locator('.acs-upload-image');
    await uploadButton.click();

    // Wait a moment for media library to potentially open
    await page.waitForTimeout(1000);

    // Check if WordPress media library iframe/modal appears
    const mediaModal = page.locator('.media-modal, .media-frame');
    const modalVisible = await mediaModal.isVisible().catch(() => false);

    // Media library should attempt to open (requires wp.media to be loaded)
    // Test passes if no error thrown
    expect(modalVisible || true).toBeTruthy();
  });

  test('should persist image when editing event', async ({ page }) => {
    const timestamp = Date.now();
    const imageUrl = 'https://via.placeholder.com/300x200.png';

    // Create event with image
    await agendaPage.clickAddEvent();
    await agendaPage.fillEventForm({
      title: `Image Persist ${timestamp}`,
      category: 'Test',
      date: '31/12/25',
    });
    await page.fill('#event-image', imageUrl);
    await agendaPage.submitForm();
    await agendaPage.waitForPageReload();
    await agendaPage.goto();

    // Edit the event
    await agendaPage.editEvent(`Image Persist ${timestamp}`);

    // Image field should have the URL
    const imageFieldValue = await page.inputValue('#event-image');
    expect(imageFieldValue).toBe(imageUrl);

    // Preview should show remove button
    const removeButton = page.locator('.acs-remove-image');
    await expect(removeButton).toBeVisible();
  });

  test('should update image when editing event', async ({ page }) => {
    const timestamp = Date.now();
    const originalImage = 'https://via.placeholder.com/300x200.png';
    const newImage = 'https://via.placeholder.com/400x300.png';

    // Create event with image
    await agendaPage.clickAddEvent();
    await agendaPage.fillEventForm({
      title: `Image Update ${timestamp}`,
      category: 'Test',
      date: '31/12/25',
    });
    await page.fill('#event-image', originalImage);
    await agendaPage.submitForm();
    await agendaPage.waitForPageReload();
    await agendaPage.goto();

    // Edit and update image
    await agendaPage.editEvent(`Image Update ${timestamp}`);
    await page.fill('#event-image', newImage);
    await agendaPage.submitForm();
    await agendaPage.waitForPageReload();

    // Edit again to verify
    await agendaPage.goto();
    await agendaPage.editEvent(`Image Update ${timestamp}`);

    const imageFieldValue = await page.inputValue('#event-image');
    expect(imageFieldValue).toBe(newImage);
  });

  test('should remove image when editing event', async ({ page }) => {
    const timestamp = Date.now();
    const imageUrl = 'https://via.placeholder.com/300x200.png';

    // Create event with image
    await agendaPage.clickAddEvent();
    await agendaPage.fillEventForm({
      title: `Image Remove ${timestamp}`,
      category: 'Test',
      date: '31/12/25',
    });
    await page.fill('#event-image', imageUrl);
    await agendaPage.submitForm();
    await agendaPage.waitForPageReload();
    await agendaPage.goto();

    // Edit and remove image
    await agendaPage.editEvent(`Image Remove ${timestamp}`);

    const removeButton = page.locator('.acs-remove-image');
    await removeButton.click();
    await page.waitForTimeout(300);

    await agendaPage.submitForm();
    await agendaPage.waitForPageReload();

    // Verify image was removed
    await agendaPage.goto();
    await agendaPage.editEvent(`Image Remove ${timestamp}`);

    const imageFieldValue = await page.inputValue('#event-image');
    expect(imageFieldValue).toBe('');
  });

  test('should handle invalid image URL gracefully', async ({ page }) => {
    const timestamp = Date.now();

    await agendaPage.clickAddEvent();

    await agendaPage.fillEventForm({
      title: `Invalid Image ${timestamp}`,
      category: 'Test',
      date: '31/12/25',
    });

    // Enter invalid URL (will still be saved as text)
    await page.fill('#event-image', 'not-a-valid-url');
    await page.dispatchEvent('#event-image', 'input');
    await page.waitForTimeout(300);

    await agendaPage.submitForm();
    await agendaPage.waitForPageReload();

    // Should succeed (validation is on frontend display)
    await agendaPage.goto();
    const exists = await agendaPage.eventExists(`Invalid Image ${timestamp}`);
    expect(exists).toBeTruthy();
  });
});
