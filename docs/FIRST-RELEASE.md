# First release on WordPress.org

New plugins are reviewed by people on the WordPress.org Plugin Review Team before they are
published. The review checks the code against the
[plugin guidelines](https://developer.wordpress.org/plugins/wordpress-org/detailed-plugin-guidelines/)
and usually takes from a few days to a few weeks. Each round of fixes adds time, so it pays to get
everything right before submitting.

Work through this list from top to bottom. A coding agent can do it with you: ask it to
"prepare the first release" (Claude Code: the `first-release` skill).

## 1. Name and slug

- [ ] The name doesn't start with or contain someone else's trademark ("WordPress", "Woo",
      "Gutenberg", "Facebook", ...). "for WooCommerce" at the end is fine; "Woo Widgets" is not.
- [ ] The slug (directory name, text domain, `reports-graph-for-sensei-lms.php`) is free: https://wordpress.org/plugins/reports-graph-for-sensei-lms/
      should be "not found". WordPress.org derives the slug from `Plugin Name` in the header when you submit,
      so keep the name and the slug matching.
- [ ] The slug can be changed during review (ask in your reply to the review email), never after approval.
      To rename the project itself, create it again with `composer create-project` in a directory with
      the new name and move your code over.

## 2. The plugin

- [ ] `make check` passes.
- [ ] Every string the user sees is translatable (`make i18n` regenerates the POT).
- [ ] Capability checks, nonces, sanitizing and escaping are in place: see "Security" in `AGENTS.md`.
- [ ] `uninstall.php` removes every option, transient, table, meta key and cron event the plugin creates.
- [ ] The plugin doesn't call external services, track users or load remote assets without consent.
      If it uses a third-party service, `readme.txt` names it and links its terms and privacy policy.
- [ ] No leftover template code: delete the enqueue methods for areas you don't use, and assets entries
      you don't need. Make the remaining enqueue methods return early where the plugin has nothing to show.
- [ ] `grep -rn TODO --exclude-dir=vendor --exclude-dir=node_modules .` finds nothing you meant to fill in.

## 3. readme.txt and the plugin header

- [ ] Plugin header: `Plugin Name`, `Description`, `Author`, `Plugin URI` (or remove it) are real.
- [ ] `Contributors` are WordPress.org usernames, including yours.
- [ ] Up to five `Tags` that describe the plugin; no competitor names.
- [ ] `Requires at least` and `Requires PHP` match what you test on, and match `minimum_wp_version` and
      `testVersion` in `phpcs.xml.dist`.
- [ ] `Tested up to` is the current WordPress major version.
- [ ] The short description (the line under the header) is under 150 characters.
- [ ] Description, Installation, FAQ, Screenshots and Changelog are written for users. Remove the template text.
- [ ] The "Source code" section links the public repository: WordPress.org requires the uncompiled
      JavaScript and CSS to be available.
- [ ] Preview the result with the [readme validator](https://wordpress.org/plugins/developers/readme-validator/).

## 4. Build and check

```bash
make dist           # builds reports-graph-for-sensei-lms.zip
make smoke          # loads the build and runs its hooks
make plugin-check   # Plugin Check, the same tool the review team runs
```

- [ ] `make plugin-check` reports no errors. Fix warnings too, or be ready to explain them.
- [ ] Install `reports-graph-for-sensei-lms.zip` on a clean WordPress (`make playground` or any test site) with `WP_DEBUG` on:
      activate, use every feature, deactivate and delete. No notices, warnings or leftover data.
- [ ] CI on GitHub is green, including the Plugin Check step.

## 5. Listing assets

These are not part of the zip; they are uploaded separately after approval, from `.wordpress-org/`.
See the [assets handbook](https://developer.wordpress.org/plugins/wordpress-org/plugin-assets/).

The template ships a default icon and banner (a plug on a violet gradient), so the listing never looks
empty. They work as they are; replace them when you have your own artwork.

- [ ] Icon: `icon.svg`, plus `icon-128x128.png` and `icon-256x256.png` for places that can't show SVG.
- [ ] Banner: `banner-772x250.png` and `banner-1544x500.png`. WordPress.org writes the plugin name over the
      bottom left, so keep that area calm.
- [ ] `screenshot-1.png`, `screenshot-2.png`, ... matching the `== Screenshots ==` captions in `readme.txt`.
- [ ] `blueprints/blueprint.json` installs the plugin for Live Preview; adjust `landingPage` to the screen
      that shows the plugin best. Enable Live Preview in the plugin's Advanced settings after approval.

## 6. Submit

1. Make sure your WordPress.org account email is one you read: all review communication goes there.
   Enable two-factor authentication on the account; it's required to commit.
2. Upload `reports-graph-for-sensei-lms.zip` at https://wordpress.org/plugins/developers/add/.
3. Watch your email. If the review team asks for changes, fix them, run section 4 again, reply to the
   email and upload the new zip from the link in the email. Reply to every point they raise.

## 7. After approval

1. Set an SVN password at https://profiles.wordpress.org/me/profile/edit/group/3/?screen=svn-password.
2. In the GitHub repository settings, add the secrets `SVN_USERNAME` (your WordPress.org username) and
   `SVN_PASSWORD`.
3. Tag the version from the plugin header and push the tag:

   ```bash
   git tag v1.0.0
   git push origin v1.0.0
   ```

   `.github/workflows/release.yml` checks that the versions match the tag, builds and checks the zip,
   commits it and `.wordpress-org/` to WordPress.org SVN and creates a GitHub release.
4. The plugin page appears within minutes; search results can take longer.

## Later releases

1. Bump the version in the plugin header, `readme.txt` (Stable tag), `Plugin::VERSION` and `package.json`.
2. Add a `== Changelog ==` entry, and an `== Upgrade Notice ==` when users should know something before updating.
3. When a new WordPress version comes out, test the plugin on it and update `Tested up to` even without a
   code release: change `readme.txt` on `main`, then run the "WordPress.org readme and assets" workflow
   in the Actions tab. It also publishes changes to `.wordpress-org/`.
4. `make check`, merge, tag, push.
