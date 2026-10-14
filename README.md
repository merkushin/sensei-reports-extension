# Reports Graph for Sensei LMS

Adds a chart of course completions over time to the Sensei LMS Students report
(**Sensei LMS → Reports → Students**). See [readme.txt](readme.txt) for what users get and the
`reports_graph_for_sensei_lms_datasets` filter for adding lines to the chart.

## How it works

- `Plugin` hooks the chart into Sensei's `sensei_analysis_after_headers` action and loads the script only
  where the chart is shown.
- `ChartPanel` shows the chart on the Students report to users who can `manage_sensei_grades`; users who
  can't `manage_sensei` (teachers) get their own courses only.
- `ReportRequest` reads the report's query arguments (`start_date`, `end_date`, `timezone`) the way Sensei
  does, and `DateRange` turns them into days or months in the viewer's timezone.
- `Completions\CommentsCompletionsQuery` and `Completions\TablesCompletionsQuery` count completed courses in
  Sensei's two progress storages, in 15-minute UTC buckets that PHP adds up into local days, so the counts
  stay right in every timezone and across DST changes.
- `CompletionsChart` labels the periods and applies the datasets filter; `assets/src/admin/chart.js` draws
  the result with a bundled Chart.js.

## Development

Requirements: PHP 7.4+, Composer, Node.js 22.22+
and Docker for running Plugin Check locally.

```bash
make install     # composer install && npm ci
make check       # coding standards, PHPStan, PHP and JS tests, POT and version checks
make playground  # WordPress in the browser with Sensei LMS and this plugin active
npm start        # rebuild JavaScript and CSS on change
```

| Where | What |
| --- | --- |
| `reports-graph-for-sensei-lms.php` | Plugin header and bootstrap |
| `src/` | PHP code, PSR-4 namespace `Merkushin\ReportsGraphForSensei\` |
| `tests/unit/` | PHPUnit tests; WordPress calls are mocked through WPAL |
| `assets/src/admin/` | The chart's JavaScript and CSS, built to `assets/build/` |
| `languages/` | Translation template, `make i18n` |
| `uninstall.php` | Cleanup when the plugin is deleted |
| `readme.txt` | The WordPress.org plugin page |
| `.wordpress-org/` | WordPress.org banners, icons, screenshots and Live Preview blueprint |

WordPress functions are called through [WPAL](https://github.com/merkushin/WPAL) services, so the
code can be unit-tested without WordPress. [AGENTS.md](AGENTS.md) has the conventions.

## Release

```bash
make dist          # builds reports-graph-for-sensei-lms.zip
make smoke         # loads the built plugin and runs its hooks
make plugin-check  # WordPress.org's Plugin Check on the build
```

The first release goes through WordPress.org review: see
[docs/FIRST-RELEASE.md](docs/FIRST-RELEASE.md). After approval, pushing a version tag publishes
the plugin: see `.github/workflows/release.yml`.
