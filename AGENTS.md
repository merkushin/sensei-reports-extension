# AGENTS.md

Reports Graph for Sensei LMS is a WordPress plugin meant for the WordPress.org plugin directory. It supports PHP 7.4+
and the WordPress version in "Requires at least" (plugin header and `readme.txt`).

## Commands

| Command | What it does |
| --- | --- |
| `make check` | Everything CI checks on the sources. **Must pass before you finish.** |
| `make test` / `vendor/bin/phpunit --filter Name` | PHP tests |
| `make lint-php` / `vendor/bin/phpcbf` | WordPress Coding Standards; phpcbf fixes formatting |
| `make phpstan` | Static analysis |
| `make test-js` / `make lint-js` | JavaScript tests (Vitest) and ESLint/Stylelint |
| `make i18n` | Regenerates `languages/reports-graph-for-sensei-lms.pot`; run after changing translatable strings |
| `make dist && make smoke` | Builds the release zip and runs it; run after changing WordPress calls or the build |
| `make plugin-check` | WordPress.org's Plugin Check on the build (Docker) |

## Layout

- `reports-graph-for-sensei-lms.php`: plugin header and bootstrap only. Code goes in `src/`.
- `src/`: PSR-4, namespace `Merkushin\ReportsGraphForSensei\`, one class per file named after the class (`src/Admin/SettingsPage.php`).
  `Plugin` wires hooks; put features in their own classes and register them from `Plugin::init()`.
- `tests/unit/`: PHPUnit 9 tests, namespace `Merkushin\ReportsGraphForSensei\Tests`, mirroring `src/`.
- `tests/smoke/dist.php`: runs the release build with stand-ins for WordPress functions.
- `assets/src/<entry>/index.js` (+ CSS it imports): built by `@wordpress/scripts` to `assets/build/<entry>.js`,
  `.css`, `-rtl.css` and `.asset.php`. Entries are listed in `webpack.config.js` and enqueued by `Plugin::enqueue_entry()`.
  JS tests sit next to the code as `*.test.js`.
- Generated, never edit: `assets/build/`, `build/`, `vendor/`, `vendor-prefixed/`.

## Sensei LMS

The plugin extends [Sensei LMS](https://github.com/Automattic/sensei) (`Requires Plugins: sensei-lms`). It relies on:

- The `sensei_analysis_after_headers` action on the Reports page (`includes/class-sensei-analysis.php` in Sensei).
- The report's query arguments: `page=sensei_reports`, `view`, `course_id`/`lesson_id`/`user_id`, `start_date`,
  `end_date` and `timezone` (`includes/reports/helper/class-sensei-reports-helper-date-range-trait.php`).
- Course progress in either storage: `sensei_course_status` comments (default) or the `sensei_lms_progress`
  table when High-Performance Progress Storage is on (`Sensei\Internal\Services\Progress_Storage_Settings`).

Sensei is not a Composer dependency: check its classes with `class_exists()`/`is_callable()` and keep a fallback.
`make plugin-check` starts WordPress with Sensei in Docker; `wp-env run cli wp ...` seeds data there.

## Calling WordPress

PHP code calls WordPress through [WPAL](https://github.com/merkushin/WPAL) services, so tests can mock them:

```php
$this->options = ServiceFactory::create_options();   // in the constructor
$this->options->get_option( 'reports_graph_for_sensei_lms_settings', [] );
```

- Every public WordPress function is a method with the same name and parameters. To find its service:
  `grep -l "function get_option(" vendor/merkushin/wpal/src/Service/*.php`. The full map is in
  `vendor/merkushin/wpal/docs/services.md`.
- Use the **Service** layer (`Merkushin\Wpal\Service\*`, `ServiceFactory`), not the `Api` layer or `Wpal` class:
  those need PHP 8.4 and the plugin supports 7.4.
- In tests, replace a service with a mock: `ServiceFactory::set_custom_options( $this->createMock( Options::class ) )`.
  See `tests/unit/PluginTest.php`.
- Translation and escaping functions (`__()`, `esc_html()`, ...) and code in `reports-graph-for-sensei-lms.php` and `uninstall.php` call
  WordPress directly; `tests/stubs/` stands in for them, and for `wpdb`, in unit tests.
- `$wpdb` has no WPAL service: classes take it in the constructor, and tests pass `tests/unit/FakeWpdb.php`.
- The release build keeps only the WPAL services `src/` uses. A new service works in tests but must also work in
  `make dist && make smoke`; add stand-ins for new WordPress functions to `tests/smoke/dist.php`.

## Conventions

- PHP 7.4 syntax: no `match`, enums, `readonly`, named arguments, constructor promotion or union types.
  PHPCompatibility in `make lint-php` catches these.
- WordPress Coding Standards with short array syntax. Prefix everything global: options, transients, hooks,
  meta keys, nonces, script handles and global functions with `reports_graph_for_sensei_lms_` (or `reports-graph-for-sensei-lms-` for handles).
- Security, which WordPress.org review checks first:
  - Check capabilities (`current_user_can()`) before doing anything on a user's behalf.
  - Verify a nonce on every form submission and state-changing request; REST routes need a `permission_callback`.
  - Sanitize input on the way in (`sanitize_text_field()`, `absint()`, ...), escape output late (`esc_html()`,
    `esc_attr()`, `esc_url()`, `wp_kses_post()`).
  - Use `$wpdb->prepare()` for any SQL with variables.
- i18n: every user-facing string goes through `__()` / `esc_html__()` / `_n()` with the `reports-graph-for-sensei-lms` text domain and
  literal strings (no variables). Add a `/* translators: */` comment for placeholders. In JavaScript, use
  `@wordpress/i18n`; the plugin loads script translations automatically. Then run `make i18n`.
- Data the plugin stores (options, transients, tables, cron events) must be removed in `uninstall.php`.
- Don't load assets everywhere: return early from the enqueue methods on screens and pages that don't need them.
- Don't call external services or load remote scripts/fonts without the user's consent, and document them in
  `readme.txt` (WordPress.org guideline 7).

## Versions

The version lives in four places: the plugin header, `readme.txt` (Stable tag), `Plugin::VERSION` and `package.json`.
`make version-check` fails when they differ. Add each change to the `== Changelog ==` in `readme.txt`.
