---
name: first-release
description: Prepare the plugin for its first submission to the WordPress.org plugin directory. Use when the user wants to submit, publish or release the plugin on WordPress.org for the first time, or asks whether it is ready for review.
---

Walk through `docs/FIRST-RELEASE.md` with the user, sections 1 to 5. Do the checks yourself and report results;
don't submit anything or create accounts.

1. Read `docs/FIRST-RELEASE.md`, `readme.txt`, the plugin header in `reports-graph-for-sensei-lms.php` and `AGENTS.md`.
2. Section 1: check the name and slug for restricted terms and trademarks. Check whether the slug is taken by fetching
   `https://api.wordpress.org/plugins/info/1.2/?action=plugin_information&slug=<slug>` (an error means it's free).
3. Section 2: run `make check`. Review `src/` for the security items in `AGENTS.md` (capabilities, nonces,
   sanitizing, escaping, `$wpdb->prepare()`), untranslated strings, data `uninstall.php` doesn't remove,
   external requests, and leftover template code. Search for `TODO`.
4. Section 3: compare `readme.txt` with the checklist. Flag template text that is still there and check that
   `Tested up to` is the current WordPress version (`https://api.wordpress.org/core/version-check/1.7/`).
   Offer to draft the description, FAQ and changelog from the code, and let the user edit them.
5. Section 4: run `make dist`, `make smoke` and `make plugin-check` (needs Docker; if it isn't running, say so and
   rely on the Plugin Check step in CI). Fix what you can and explain any remaining warnings.
6. Section 5: list which files in `.wordpress-org/` are missing.

Finish with a checklist of what passed, what you fixed, and what the user still has to do by hand (screenshots,
banners, the upload itself in section 6, and the steps after approval in section 7).
