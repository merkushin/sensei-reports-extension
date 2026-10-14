---
name: release
description: Prepare a new version of the plugin that is already on WordPress.org - bump the version, update the changelog and check the build. Use when the user asks to release, bump the version or ship a new version.
---

1. Find the current version in the plugin header (`reports-graph-for-sensei-lms.php`) and the changes since the last tag:
   `git describe --tags --abbrev=0`, then `git log <tag>..HEAD --oneline`.
2. Propose the next version (semantic versioning: fixes bump the patch, new features the minor) and confirm it
   with the user unless they gave one.
3. Set the version in the plugin header, `readme.txt` (`Stable tag`), `Plugin::VERSION` in `src/Plugin.php` and
   `package.json` (with `npm version <version> --no-git-tag-version`, which also updates `package-lock.json`).
4. Add a `= <version> =` entry at the top of `== Changelog ==` in `readme.txt`, written for users: what changed
   for them, not commit messages. Add an `== Upgrade Notice ==` entry if users must know something before updating.
5. Check `Tested up to` against the current WordPress version (`https://api.wordpress.org/core/version-check/1.7/`).
   Only raise it if the user has tested the plugin on that version.
6. Run `make check`, then `make dist` and `make smoke`.
7. Commit the change. Don't create or push the tag yourself: tell the user to merge and run
   `git tag v<version> && git push origin v<version>`, which starts `.github/workflows/release.yml`.
