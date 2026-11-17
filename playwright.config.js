import {defineConfig} from '@playwright/test';

export default defineConfig({
  testDir: './tests',
  testMatch: 'native.spec.js',
  workers: 1,
  timeout: 90000,
  use: {channel: 'chrome', viewport: {width: 1440, height: 1050}},
  outputDir: './artifacts/native-test-results',
  reporter: 'list',
});
