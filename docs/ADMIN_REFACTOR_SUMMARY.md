# Admin Blade Refactor Summary

## Shared component inventory

Inventory covers all 37 files under `resources/views/components/`. Props marked “required” have no default. Slots are the Blade default slot unless named.

### Admin

- `<x-admin.detail-header>` — props: `title` (required), `subtitle=null`, `crumbs=[]`; named `actions` slot; detail-page heading with optional breadcrumbs and actions.
- `<x-admin.nav-link>` — props: `href` (required), `match=null`, `icon=null`, `badge=null`; default slot; sidebar link with active state and optional icon/badge.
- `<x-admin.page-header>` — props: `label`, `title`, `text` (required), `exportUrl=null`, `exportLabel='Export'`, `confirmTitle='Export this list?'`, `confirmText='The file may contain personal information. Keep it safe and share it only with authorized people.'`; default slot; page title, description, extra actions, and confirmed export link.
- `<x-admin.stat-card>` — props: `label` (required), `subtitle=null`, `value='—'`, `trend=null`, `latest=null`, `latestLabel='Latest'`, `emptyText='No records yet'`, `dateFormat='M d, Y'`, `note=null`, `href=null`, `invert=false`, `mono=false`; named `icon` slot; metric card with optional trend, latest date, and link.

### Feedback

- `<x-feedbacks.form-errors>` — props: `title='Please fix the following:'`, `bag='default'`, `light=true`; no slot; displays a validation error bag.
- `<x-feedbacks.password-requirement>` — prop: `inputId='password'`; no slot; password-strength guidance tied to an input.
- `<x-feedbacks.t-confirmation>` — no props or slots; accessible confirmation dialog exposing `tConfirmation({title, text, confirmLabel})`.
- `<x-feedbacks.toast>` — no props or slots; notification container exposing `showToast(type, message, title)`.

### Forms

- `<x-form.button>` — props: `type='button'`, `variant='primary'`, `color='emerald'`, `hoverColor=null`, `loadingText='Loading...'`, `loading=false`; default slot; themed submit/action button with loading state.
- `<x-form.checkbox>` — props: `name` (required), `value='1'`, `light=true` (accepted but ignored); default slot; themed checkbox with inline label.
- `<x-form.contact-tabs>` — props: `emailName='email'`, `emailLabel='Email Address'`, `emailType='email'`, `emailPlaceholder='you@example.com'`, `emailAutocomplete='email'`; no slot; switches between email and mobile contact inputs.
- `<x-form.input>` — props: `name` (required), `id=null`, `label` (required), `type='text'`, `value=null`, `options=[]`, `hint=null`, `raised=false`, `light=false`, `useOld=true`; default slot; floating-label input, select, or textarea with validation hooks.
- `<x-form.otp>` — props: `name='otp'`, `id=null`, `label=null`, `length=6`, `value=null`, `hint=null`, `numeric=true`, `light=false`, `useOld=true`, `autofocus=false`, `required=false`; no slot; multi-box one-time-password input with `OtpInput` helper.
- `<x-form.stepper>` — props: `steps=[]`, `current=1`, `id='stepper'`; no slot; animated progress steps controlled by `Stepper.set`.
- `<x-form.upload-docu>` — props: `name` (required), `id=null`, `label=null`, `accept='.jpg,.jpeg,.png,.pdf'`, `maxMb=10`, `text='Drag & Drop or click to choose files'`; no slot; drag/drop document input with preview and file-size/type handling.

### Tables

- `<x-tables.action>` — props: `label` and `icon` (required), `href=null`, `action=null`, `method='POST'`, `variant='default'`, `confirm=null`, `iconOnly=false`; no slot; row link/action button with optional confirmation.
- `<x-tables.badge>` — prop: `variant='neutral'`; default slot; status badge with success, danger, warning, info, and neutral styles.
- `<x-tables.card>` — props: `title=null`, `description=null`, `paginator=null`, `total=null`, `itemLabel='records'`, `searchable=true`, `searchName='search'`, `searchModel=null`, `searchPlaceholder='Search'`, `filters=[]`, `keep=[]`, `addLabel='Add New'`, `addUrl=null`, `addOnclick=null`, `addWire=null`, `perPageOptions=[10, 15, 25, 50]`, `perPageLabel='Lines per page'`, `perPageModel=null`; default slot; table container with optional header, search/filter/add controls, and pager.
- `<x-tables.detail>` — props: `label=null`, `side='left'`, `actions=false`, `always=false`; default slot; responsive detail cell for table rows on mobile.
- `<x-tables.pager>` — props: `paginator=null`, `perPageOptions=[10, 15, 25, 50]`, `perPageModel=null`; no slot; pagination and per-page controls.
- `<x-tables.table>` — props: `heads=[]`, `empty=false`, `selectable=false`, `cards=true`, `message='No records found.'`, `hint='Try changing your search or filters.'`; default slot; responsive table with a built-in empty row that also supports client-side no-match results.
- `<x-tables.toolbar>` — props: `key='tbl'`, `tabs=[]`, `filters=[]`, `search=false`, `searchPlaceholder='Start searching...'`, `searchHint=null`, `exportUrl=null`, `addLabel=null`, `addUrl=null`, `addOnclick=null`; no slot; tabs, client-side search, filters, export, and add action. The rendered toolbar exposes its key as `data-table-key`.
- `<x-tables.user-cell>` — props: `name` (required), `sub=null`, `avatar=null`; no slot; user name, secondary text, and avatar cell.

### Other shared components

- `<x-govern-footer>` — no props or slots; public-facing governance footer.
- `<x-loading-indicator>` — prop: `size='4'`; no slot; compact animated loading indicator, used inside `x-form.button`.
- `<x-navigation.back-nav>` — no props or slots; public-page “back to home” navigation.
- `<x-navigation.section-nav>` — props: `items=[]`, `label='Page sections'`, `bleed=false`; no slot; in-page section navigation with scroll tracking.
- `<x-partials.blueprint>` — props: `src=null`, `base='bg-(--blue-900) dark:bg-(--green-900)'`, `grid=96`, `pos='object-right'`; default slot; decorative blueprint/photo background.
- `<x-partials.c-footer>` — no props or slots; public-facing brand footer.
- `<x-partials.citizen-notice>` — props: `type='blue'`, `title` (required); default slot; themed information notice.
- `<x-partials.journey-panel>` — props: `mode=request()->is('register*') ? 'register' : 'login'`, `active=1`; no slot; photo-backed login/register panel.
- `<x-partials.live-date>` — props: `timezone='Asia/Manila'`, `showTime=true`; no slot; live date/time display.
- `<x-partials.logo>` — no props or slots; logo image with text fallback.
- `<x-partials.section-footer>` — no props or slots; logo, name, and copyright footer.
- `<x-partials.theme-toggle>` — prop: `labels=false`; no slot; light/dark theme switch.
- `<x-partials.two-factor-form>` — prop: `action` (required); no slot; reusable two-factor settings form.
- `<x-tickets.message-sender>` — props: `message` and `ticket` (required); no slot; labels the sender in a ticket conversation.

### Component usage in admin views

- Page headers: audit logs, blockchain ledger, dashboard, document authentication, identity-verification index, notifications, permits index, reports index, smart contracts, ticket inbox, users index, ticket detail, and user detail.
- Detail headers: identity-verification detail and permit review.
- Stat cards: dashboard, document authentication, identity-verification index, blockchain ledger, permits index, and users index.
- Table card/table/user-cell/badge/action: audit logs, blockchain ledger, dashboard, document authentication, identity verification, permits, reports, smart contracts, tickets, and users. The pager is used through `x-tables.card`; users, reports, and smart contracts use `x-tables.toolbar`.
- Form input/button/error components: audit logs, identity-verification detail, notifications, permits, profile, reports actions, smart contracts, ticket detail, and user modals.
- Password requirement: profile, user create, and user edit.
- Citizen notice: document authentication. Two-factor form: profile. Ticket message sender: ticket detail.
- Confirmation toast: included once by the admin layout; exports use the page-header component and row deletes use `x-tables.action`.

Components found but not directly used in admin view markup: `<x-admin.nav-link>` is layout navigation; `<x-feedbacks.toast>` and `<x-feedbacks.t-confirmation>` are layout-level; `<x-form.checkbox>` does not fit the existing ID/data-hook-specific checkboxes; contact tabs, OTP, stepper, upload, journey panel, public footers, public navigation, live date, logo, theme toggle, and blueprint are for other flows; `<x-tables.detail>` is unnecessary where the responsive table component already handles the rows. `x-loading-indicator` is used indirectly by `x-form.button`.

### JavaScript and palette

- `tConfirmation({title, text, confirmLabel})` returns a promise; delete, export, and smart-contract pause/resume actions wait for confirmation. The admin layout includes the dialog once.
- Other component helpers include `showToast`, `OtpInput`, `Stepper`, table-toolbar live search, contact-tab switching, upload preview/validation, and the live-date updater. Existing user-modal globals (`window.userRecords`, `window.userInitials`, `openViewUserModal`, `openEditUserModal`, and `openPermissionsModal`) retain their names.
- `resources/css/theme.css` palette: blue 50/100/200/300/400/500/600/700/800/850/900 = `#EEF2FA`, `#D9E2F5`, `#B5C6E8`, `#8CA4D4`, `#5F7FBD`, `#3F5FA3`, `#1F3F7A`, `#173266`, `#112652`, `#070b24`, `#0B1A3A`; green 50/100/200/300/400/500/600/700/800/900 = `#F6F8EC`, `#E9EFD0`, `#D6E0A8`, `#BCCB78`, `#9DB04C`, `#7F9437`, `#667A2B`, `#52631F`, `#414F1A`, `#333F16`; gray 50/100/200/300/400/500/600/700/800/900 = `#F8FAFC`, `#F1F5F9`, `#E2E8F0`, `#CBD5E1`, `#94A3B8`, `#64748B`, `#475569`, `#334155`, `#1E293B`, `#0F172A`; plus white `#FFFFFF`, amber-500 `#F59E0B`, red-500 `#EF4444`, red-600 `#DC2626`, and black-700 `#020018`.

## Files changed

### Controllers and routes

- `app/Http/Controllers/Admin/DashboardController.php` — prepares dashboard greeting, permissions, shortcuts, totals, and workload data outside Blade.
- `app/Http/Controllers/Admin/IdentityReviewController.php` — prepares verification counts, detail fields, image URLs, and presentation labels.
- `app/Http/Controllers/Admin/NotificationController.php` — supplies notification-page data and status grouping previously embedded in the view.
- `app/Http/Controllers/Admin/PermitController.php` — prepares review state, request details, document records, and action-related presentation data.
- `app/Http/Controllers/Admin/ProfileController.php` — filters profile validation errors for the view.
- `app/Http/Controllers/Admin/ReportController.php` — prepares report metrics, comparison labels/classes, sparkline series, export query, and selected PDF permit.
- `app/Http/Controllers/Admin/SmartContractController.php` — prepares contract status flags, shortened addresses, and recent activity row data.
- `app/Http/Controllers/Admin/UserController.php` — prepares tabs, paginators, merged records, stats, modal JSON, status badges, detail fields, and validation-reopen state.
- `routes/web.php` — routes notifications through its controller while preserving the original URI and route name.

### Admin views

- `resources/views/admin/audit-logs/index.blade.php` — uses shared page header, form controls, table card/table, and user cell.
- `resources/views/admin/blockchain-ledger/index.blade.php` — uses shared header, stat cards, table components, and badges.
- `resources/views/admin/dashboard/index.blade.php` — uses shared header, stat cards, table, user cell, and badges; removes Blade preparation.
- `resources/views/admin/document-authentication/index.blade.php` — uses shared header, stat cards, table, badges, and citizen notice.
- `resources/views/admin/identity-verifications/index.blade.php` — uses shared header, stat cards, table, user cell, badge, and row action.
- `resources/views/admin/identity-verifications/show.blade.php` — uses detail header, error, input, and button components.
- `resources/views/admin/notifications/index.blade.php` — uses shared header/form controls and removes inline sample-data preparation.
- `resources/views/admin/permits/index.blade.php` — uses shared header, stat cards, table, user cell, badge, and row action.
- `resources/views/admin/permits/review.blade.php` — uses detail header, form controls, buttons, and badge while preserving action forms.
- `resources/views/admin/profile/show.blade.php` — uses shared header, form controls/buttons, and the two-factor form component.
- `resources/views/admin/reports/index.blade.php` — migrates four analytics tables to shared table cards, badges, user cells, and searchable toolbars.
- `resources/views/admin/reports/pdf.blade.php` — removes inline permit lookup and documents the PDF report sections.
- `resources/views/admin/smart-contracts/index.blade.php` — migrates deployments, officials, and activity tables; adds live search and shared pause confirmation.
- `resources/views/admin/tickets/index.blade.php` — documents the inbox/table sections; retains its shared table components.
- `resources/views/admin/tickets/show.blade.php` — uses shared header, input, error, button, and sender components.
- `resources/views/admin/users/create-user.blade.php` — removes the create-error PHP block; retains shared form components.
- `resources/views/admin/users/edit-user.blade.php` — moves reopen/old-input preparation to the controller and uses shared error/input components.
- `resources/views/admin/users/index.blade.php` — removes local aggregation, mapping, and modal-JSON preparation; retains shared tabs, tables, stats, and row actions.
- `resources/views/admin/users/permissions-user.blade.php` — moves permission list/error state to the controller and uses the shared error component.
- `resources/views/admin/users/view-user.blade.php` — uses the shared header, badge, and button components; field metadata comes from the controller.

### Shared components and tests

- `resources/views/components/partials/two-factor-form.blade.php` — declares its existing `action` input as a component prop.
- `resources/views/components/tables/action.blade.php` — fixes the confirm path to use `tConfirmation` instead of the browser-native `confirm()`.
- `tests/Feature/Admin/SmartContractControllerTest.php` — covers smart-contract rows, empty states, and pause confirmation markup.
- `tests/Feature/Admin/UserControllerTest.php` — covers all user tabs, invalid-tab fallback, and create/edit/permission modal recovery after validation.
- `tests/Feature/Reports/ReportAccessExportTest.php` — asserts report table rows and shared empty states.

`resources/views/admin/users/row-actions.blade.php` was inspected and left unchanged; it already uses the shared row-action component.

## Logic moved out of Blade

- Dashboard calculations, permission flags, greeting, shortcuts, charts, and workload data moved to `DashboardController`.
- Identity-review status mapping/counts and detail presentation values moved to `IdentityReviewController`.
- Notification prototype content and its grouped/status values moved to `NotificationController`; route URI/name are unchanged.
- Permit review state, active issued-document selection, request details, and labels moved to `PermitController`.
- Profile error filtering moved to `ProfileController`.
- Report filter query, metric formatting, comparison display, sparkline selection, latest results, and PDF permit lookup moved to `ReportController`.
- Smart-contract flags, explorer base, shortened addresses, and activity-row timestamp/transaction selection moved to `SmartContractController`.
- User tab/pager selection, merged users, employee summary values, identity variants, modal record JSON, field metadata, and modal old-input/error recovery moved to `UserController`.
- The two-factor form’s `action` remains passed through the existing include/component call; it is now explicitly declared as a prop.

## Admin table audit

- Dashboard, audit logs, blockchain ledger, document authentication, identity verifications, permits, tickets, and users already used shared table components. Their shared table structure remains in place.
- Smart Contracts had three old raw tables (deployments, authorized officials, and contract activity); all three now use `x-tables.card`, `x-tables.table`, and the live-search toolbar. Existing columns, query ordering, permission links, fallback deployment row, and status values remain intact.
- Reports & Analytics had four old raw tables (trend data, permit-type data, recent transactions, and staff performance); all four now use the shared table design, searchable toolbars, badge/user-cell components, and built-in empty states.
- The nine raw tables in `reports/pdf.blade.php` remain print-specific HTML tables. They form the generated PDF report layout rather than an interactive admin list; wrapping them in the responsive, scripted table cards would compromise the print/export format.
- No shared table component needed extension.
- Users, permits, identity-verification requests, the Ticket Messages inbox, audit logs, blockchain ledger, and document authentication use client-side search over rendered rows only. Their pagers remain server-side; the search text is not submitted or passed to exports.

## Verification

- `php artisan view:clear` ran after each processed admin view; all invocations succeeded.
- `php artisan view:cache` compiled every Blade template successfully, followed by `php artisan view:clear`.
- `php artisan route:list --except-vendor` succeeded and listed 98 application routes; admin route names and paths remain present.
- Latest full test run passed: 241 tests, 239 passed, 2 skipped, 1,384 assertions.
- Focused smart-contract/report tests passed: 12 tests, 118 assertions.
- Focused `php artisan test --compact tests\Feature\Admin\UserControllerTest.php` passed: 5 tests, 24 assertions.
- `vendor/bin/pint --dirty --format agent` passed.
- `npm run build` passed; Vite/Tailwind production build completed.
- `git diff --check` passed.
- Authenticated browser checks covered the Smart Contracts and Reports pages at desktop/mobile sizes and in dark/light modes. Live search filtered and restored rows; the pause confirmation opened and was cancelled without submitting. Other admin table pages were not manually exercised in this follow-up.

## Issues, decisions, and risks

- Initial test assertions needed three corrections: HTML escaping required a raw assertion, request trimming converts an empty name to `null`, and the Smart Contracts activity table displays the issued-document number rather than the service-request reference. The tests now assert the actual rendered behavior; focused and full suites pass.
- The delete component previously used native `confirm()`, inconsistent with the app’s shared confirmation dialog. Its confirm path now waits for `tConfirmation` before submitting; the existing route, method, and button remain unchanged.
- The notifications view previously depended on prototype data with `Route::view`; it now uses `NotificationController@index` with the same route path/name.
- No component was added, and no unrelated component was edited. Two directly related component fixes are documented above.
- The legacy “Smart ContractS Management” capitalization and other existing copy were left as-is rather than changing behavior/copy outside the refactor.
- Search and confirmation interactions were browser-tested on the modified pages. Other page-specific interactions, including the PDF’s print layout and actions outside the changed tables, were not manually retested.
- The data-backed reports page took about 18 seconds to render during browser verification; a concurrent standalone request timed out while the admin layout was issuing periodic refreshes. The page eventually rendered and report feature tests passed, so no unrelated query changes were made; investigate if the delay recurs under normal use.

## Repeated markup suggestion

User modals share a similar shell, sticky header/footer, close controls, and validation placement across create, edit, permissions, and view-user popups. A dedicated modal-shell component could reduce duplication in a future change, but no component was added here as requested.

## Shared admin exports

The admin export flow now uses the throttled `admin.exports.show` endpoint (`GET admin/exports/{module}`) and central `ExportService` for CSV/PDF response generation. CSV is streamed in chunks with a UTF-8 BOM and formula-injection protection; filenames are slugged and timestamped. DomPDF 3.1.2 was already installed and is used for PDF exports. No XLSX package is installed, and no dependency was added.

| Module | Previous export | Shared exporter | Permission | Formats | Preserved filters |
|---|---|---|---|---|---|
| Users | `admin.users.export` CSV | `UsersExport` | Admin role | CSV, PDF | `users_tab` |
| Permits | None | `PermitsExport` | `permits.review` | CSV, PDF | None |
| Audit logs | None | `AuditLogsExport` | `audit.view` | CSV, PDF | `user_id`, `action`, `date` |
| Blockchain ledger | None | `BlockchainLedgerExport` | `blockchain.view` | CSV, PDF | None |
| Identity verifications | None | `IdentityVerificationsExport` | `identity.review` | CSV, PDF | None |
| Tickets | None | `TicketsExport` | `tickets.reply` | CSV, PDF | None |
| Document authentication | None | `DocumentAuthenticationExport` | `documents.authenticate` | CSV, PDF | None |
| Reports | Existing CSV/PDF analytics | `ReportsExport` | Admin role and `reports.view` | CSV, PDF | `from`, `to`, `permit_type`, `document_type`, `grouping` |

The old Users and Reports export route names remain as redirects to the shared endpoint. Reports retain their existing multi-section analytics and PDF layout through the narrow `AdminReportExport` provider contract; the other modules implement the flat `AdminExport` contract. Shared query/filter services keep the exported records aligned with their list/report views.

Created the export contracts, eight exporter classes, `ExportController`, `ExportService`, report data/filter services, export registry, generic table-PDF view, endpoint tests, and focused unit tests for the seven flat exporters. Updated the eight list-page export links, affected list controllers and report filter request, `routes/web.php`, and the existing Reports export tests. Per-controller file-generation code was removed; legacy Users and Reports route names now redirect to the shared endpoint.

Export rows include only declared headings. Excluded sensitive/internal values include passwords, tokens, 2FA secrets, private keys, identity document/selfie paths and ID-number hashes, document UUIDs/paths/hashes, and audit IP addresses, user agents, and raw values. Existing Users DOB and full-address fields remain for compatibility.

### Export verification

- Focused admin search/pager and export tests passed: 28 tests, 94 assertions.
- The default-memory full-suite run hit the PHP 128 MB limit in an existing DomPDF permit workflow test. Rerunning with `php -d memory_limit=512M artisan test --compact` passed: 241 tests, 239 passed, 2 skipped, 1,384 assertions.
- `php artisan route:list --except-vendor` listed all 98 application routes, including the shared endpoint and retained legacy route names.
- `php artisan view:clear` and `php artisan view:cache` succeeded.
- `vendor/bin/pint --dirty --format agent`, `npm run build`, and `git diff --check` succeeded.
- Authenticated browser checks were not available for export flows. Manually verify the confirmation dialog, CSV/PDF downloads, filter carry-over, and file opening for each module.

The initial focused run exposed a mislabeled audit subject class and a fixture that bypassed Eloquent's JSON casts; both were corrected. A CSV header assertion was also made tolerant of valid CSV quoting. No unresolved test/build failures remain.

## Client-only admin table search

The shared toolbar now filters only the table rows already rendered in the browser. Search inputs are not form controls, do not update the URL, and are not passed to list queries or exports. The toolbar scopes filtering to its containing table card; the table's built-in empty row is reused when there are no client-side matches, and the pager strips any legacy `search` query parameter.

| Page / table | Previous design | Result | Paginated |
|---|---|---|---|
| Users / Accounts | Toolbar was client-side, but forwarded its text into the export URL and restored `?search=` on load | Kept client-side; removed URL/export coupling | Yes; current page only |
| Permits / Permit Applications | GET search box and export query search | Converted to keyed client toolbar; query search removed | Yes; current page only |
| Identity verifications / Requests | GET search box and export query search | Converted to keyed client toolbar; query search removed | Yes; current page only |
| Ticket Messages / Citizen Messages | GET search box and export query search | Converted to keyed client toolbar; query search removed | Yes; current page only |
| Audit logs / System Activity History | GET search input and export query search | Converted to keyed client toolbar; actor/action/date filters remain server-side | Yes; current page only |
| Blockchain ledger / Transaction History | GET search box and export query search | Converted to keyed client toolbar; query search removed | Yes; current page only |
| Document authentication / Authentication Records | GET search box and export query search | Converted to keyed client toolbar; query search removed | Yes; current page only |
| Reports / four analytics tables | Client-side toolbar | Verified and retained | No; all view data is rendered (recent blockchain rows are capped by the report query) |
| Smart Contracts / deployments, officials, activity | Client-side toolbar | Verified and retained | No; activity is limited to the latest eight records |

The Notifications inbox search filters its rendered conversation cards, not a table, and remains local to that view. No new component was created. `x-tables.toolbar`, `x-tables.table`, and `x-tables.pager` were updated to expose toolbar keys/hints, reuse the empty state for no matches, and avoid carrying legacy search parameters through pagination.

The focused affected feature suite passed: 42 tests, 276 assertions. `view:clear`, `view:cache`, `route:list --except-vendor` (98 routes), Pint, and `npm run build` passed. The full PHPUnit run with a 512 MB limit completed 242 tests: 238 passed, 2 skipped, and 2 failed in pre-existing privacy/terms copy assertions. Those assertions expect wording absent from the already-modified legal views and are unrelated to the table-search changes. `artisan test --compact` also hit the default 128 MB limit in the existing DomPDF permit workflow; running PHPUnit directly with the increased limit completed the suite.

Authenticated browser verification remains outstanding because Playwright could not launch: the local Chromium executable is not installed. Manually check instant filtering and clearing, no network request or URL change while typing, the no-match empty state, mobile card filtering, and light/dark styling.
