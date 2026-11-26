import { test, expect } from './fixtures';

test.describe('Read-more access control', () => {
  test('does not expose draft post content through the public AJAX endpoint', async ({ page, playwright, baseURL }) => {
    const rootURL = baseURL || 'http://localhost:8080';

    await page.goto('/wp-admin/admin.php?page=acsagma-agenda');
    const restNonce = await page.evaluate(() => (window as any).wpApiSettings?.nonce || '');
    expect(restNonce).not.toBe('');

    const secret = `AUDIT_SECRET_${Date.now()}`;
    const createResponse = await page.request.post(`${rootURL}/wp-json/wp/v2/posts`, {
      headers: { 'X-WP-Nonce': restNonce },
      data: {
        title: 'Audit Draft',
        status: 'draft',
        content: secret,
      },
    });
    expect(createResponse.ok()).toBeTruthy();
    const draft = await createResponse.json();

    try {
      // A logged-out visitor only has the public frontend nonce.
      const anonymous = await playwright.request.newContext({ baseURL: rootURL });
      const agendaHtml = await (await anonymous.get('/agenda/')).text();
      const nonceMatch = agendaHtml.match(/"nonce":"([a-f0-9]+)"/);
      expect(nonceMatch).not.toBeNull();

      const response = await anonymous.post('/wp-admin/admin-ajax.php', {
        form: {
          action: 'acsagma_read_more',
          nonce: nonceMatch![1],
          postid: String(draft.id),
          event_title: 'Audit',
          event_dates: '01/01/30',
          event_intro: '',
          dialog_mode: 'readmore',
        },
      });

      const body = await response.text();
      expect(body).not.toContain(secret);
      expect(body).toContain('"success":false');
    } finally {
      await page.request.delete(`${rootURL}/wp-json/wp/v2/posts/${draft.id}?force=true`, {
        headers: { 'X-WP-Nonce': restNonce },
      });
    }
  });

  test('still exposes published post content to anonymous visitors', async ({ page, playwright, baseURL }) => {
    const rootURL = baseURL || 'http://localhost:8080';

    await page.goto('/wp-admin/admin.php?page=acsagma-agenda');
    const restNonce = await page.evaluate(() => (window as any).wpApiSettings?.nonce || '');
    expect(restNonce).not.toBe('');

    const secret = `PUBLISHED_SECRET_${Date.now()}`;
    const createResponse = await page.request.post(`${rootURL}/wp-json/wp/v2/posts`, {
      headers: { 'X-WP-Nonce': restNonce },
      data: {
        title: 'Audit Published',
        status: 'publish',
        content: secret,
      },
    });
    expect(createResponse.ok()).toBeTruthy();
    const published = await createResponse.json();

    try {
      const anonymous = await playwright.request.newContext({ baseURL: rootURL });
      const agendaHtml = await (await anonymous.get('/agenda/')).text();
      const nonceMatch = agendaHtml.match(/"nonce":"([a-f0-9]+)"/);
      expect(nonceMatch).not.toBeNull();

      const response = await anonymous.post('/wp-admin/admin-ajax.php', {
        form: {
          action: 'acsagma_read_more',
          nonce: nonceMatch![1],
          postid: String(published.id),
          event_title: 'Audit',
          event_dates: '01/01/30',
          event_intro: '',
          dialog_mode: 'readmore',
        },
      });

      const body = await response.text();
      expect(body).toContain(secret);
    } finally {
      await page.request.delete(`${rootURL}/wp-json/wp/v2/posts/${published.id}?force=true`, {
        headers: { 'X-WP-Nonce': restNonce },
      });
    }
  });
});
