import { test, expect } from '@playwright/test';

test.describe('RBDB Report Lifecycle', () => {
    test('should login, create a manual execution, and verify status', async ({ page }) => {
        // 1. Initial Access & Login
        await page.goto('/login');
        await expect(page).toHaveTitle(/RBDB/);

        await page.getByLabel('Institutional Email').fill('admin@rbdb.local');
        await page.getByLabel('Security Access Token').fill('password');
        await page.getByRole('button', { name: 'Authenticate Identity' }).click();

        // 2. Navigation to Dashboard
        await expect(page).toHaveURL(/.*dashboard/);

        // 3. Navigate to Reports
        // Assuming sidebar has a 'Reports' link
        await page.getByRole('link', { name: 'Reports' }).click();
        await expect(page).toHaveURL(/.*reports/);

        // 4. Trigger Execution (Manual Pulse)
        // We'll target the first report in the list for simplicity in this smoke test
        const firstReportRow = page.locator('table tbody tr').first();
        await firstReportRow.getByRole('button', { name: /Execute/i }).click();

        // 5. Verify Modal and Confirm
        await expect(page.locator('text=Execute Report')).toBeVisible();
        await page.getByRole('button', { name: 'Confirm Execution' }).click();

        // 6. Navigate to Executions and check status
        await page.getByRole('link', { name: 'Executions' }).click();
        await expect(page).toHaveURL(/.*executions/);

        // Initial status should be pending or processing
        const firstExecutionStatus = page.locator('table tbody tr').first().locator('td').nth(3);
        await expect(firstExecutionStatus).toContainText(/(pending|processing|completed)/i);
    });
});
