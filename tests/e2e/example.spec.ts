import { test, expect } from '@playwright/test';

// Cần điền baseURL trong playwright.config.ts trước khi chạy test này.
test('trang chủ hiển thị đúng tiêu đề', async ({ page }) => {
  await page.goto('/');
  await expect(page).toHaveTitle(/.+/);
});
