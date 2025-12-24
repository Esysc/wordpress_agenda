# ACS Agenda Manager - E2E Tests

End-to-end tests for the ACS Agenda Manager WordPress plugin using Playwright.

## Prerequisites

- Node.js 18+ installed
- The test Docker environment running (see `../README.md`)

## Setup

```bash
# Navigate to the e2e directory
cd test/e2e

# Install dependencies
npm install

# Install Playwright browsers
npx playwright install chromium
```

## Running Tests

Make sure the Docker test environment is running first:

```bash
# From the test directory
cd ..
./start.sh
```

Then run the tests:

```bash
# Run all tests
npm test

# Run tests with UI (interactive mode)
npm run test:ui

# Run tests in headed mode (see the browser)
npm run test:headed

# Debug tests step by step
npm run test:debug

# View test report
npm run report
```

## Test Structure

```
e2e/
├── tests/
│   ├── fixtures.ts         # Test fixtures and helper classes
│   ├── events.spec.ts      # Event CRUD tests
│   ├── frontend.spec.ts    # Frontend display tests
│   └── search-filter.spec.ts # Search and filter tests
├── playwright.config.ts    # Playwright configuration
├── global-setup.ts         # Login and auth state setup
└── auth.json              # Saved auth state (generated)
```

## Test Categories

### Event Management (`events.spec.ts`)
- Load agenda admin page
- Open add event modal
- Create new event
- Edit existing event
- Delete single event
- Bulk delete multiple events
- Validate required fields
- Cancel adding event

### Frontend Display (`frontend.spec.ts`)
- Display agenda page
- Show events on frontend
- Handle empty agenda gracefully

### Search & Filter (`search-filter.spec.ts`)
- Search events by title
- Handle no search results
- Filter events by category
- Pagination
- Column sorting

## Configuration

You can configure the WordPress URL via environment variable:

```bash
WP_URL=http://localhost:8080 npm test
```

## Tips

- Tests run sequentially to maintain WordPress state consistency
- Auth state is saved after first login to speed up subsequent tests
- Screenshots and videos are captured on test failure
- Use `npm run test:debug` to step through tests interactively

## Troubleshooting

### Tests fail with "Login failed"
Make sure the Docker environment is running and WordPress is accessible at http://localhost:8080

### Tests timeout
Increase the timeout in `playwright.config.ts` if your machine is slow.

### Auth state issues
Delete `auth.json` to force a fresh login on next test run.
