import {defineConfig} from '@playwright/test';

// Dedicated local fixture accounts and demonstration laws are required.
export default defineConfig({
  testDir: './tests', testMatch: 'security.spec.js', workers: 1, timeout: 45000,
  use: {channel: 'chrome', baseURL: 'https://127.0.0.1:8447', ignoreHTTPSErrors: true, actionTimeout: 10000},
  outputDir: './artifacts/security-test-results', reporter: 'list',
});
