# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## What this is

`onlyoffice_docspace` is a Drupal module that connects Drupal to an ONLYOFFICE DocSpace portal. It embeds DocSpace in the Drupal admin, exports Drupal users to DocSpace, and adds a field type that embeds DocSpace rooms and files in content. For the supported Drupal core versions, read `core_version_requirement` in `onlyoffice_docspace.info.yml`.

The repo is the module directory itself. Drupal finds the bundled `.po` files only if the module sits at the path given in the interface translation server pattern in `onlyoffice_docspace.info.yml`. That path is relative to the Drupal root.

ONLYOFFICE also has sibling PHP DocSpace integrations on GitHub, such as `ONLYOFFICE/onlyoffice-docspace-wordpress` and `ONLYOFFICE/moodle-mod_onlyofficedocspace`. Check them for prior art when designing a feature or fixing a bug.

## Commands

Verify changes with these local static checks:

- Lint: `composer phpcs`. Run `composer install` first.
  - The `phpcs` script in `composer.json` sets the coding standards, the file extensions and the ignored paths.
  - Lint a single file: `vendor/bin/phpcs --standard=<standards from the phpcs script> <path>`
  - Auto-fix: `vendor/bin/phpcbf --standard=<standards from the phpcs script> <path>`
- Format CSS with csscomb using `.csscomb.json`: `csscomb css`. Install csscomb globally with npm first (`npm install -g csscomb`), the same way CI does.
- Check the rules in `.editorconfig`: `editorconfig-checker`.

CI runs the linters defined in `.github/workflows/lint.yml`. Read that file for the PHP and Composer versions it uses.

`composer install` also activates `captainhook/plugin-composer`, which installs the git hooks defined in `captainhook.json`. Read that file for what gets checked on commit. If a hook fails, the commit is rejected.

Runtime behaviour depends on a live DocSpace portal and the JS SDK in a browser, so static checks don't show that a change works at runtime.

## Branches and releases

- Commit messages use the format `<type>(<optional scope>): <description>`, with a lowercase type such as `feat`, `fix`, `refactor`, `docs`, `ci`, `style`, `build`, `chore` or `revert`.
- Branch names use the format `<type>/<short-kebab-description>`, with prefixes such as `feature/`, `fix/`, `bugfix/`, `ci/` or `docs/`.

`develop` usually holds the lowest supported Drupal version. If code for different Drupal versions conflicts, support for the latest versions is usually built in a separate branch.

PRs target `develop`. After a PR is merged, `develop` is merged into each branch for a newer Drupal version, if any exist. The merges go from the lowest version to the highest, and each merge includes whatever changes that version needs. These merges are usually done by hand, and lints on those branches are run manually through the `workflow_dispatch` trigger of `.github/workflows/lint.yml`. Changes to workflows that run on push to these branches can go through PRs.

Add changelog entries at the top of `CHANGELOG.md`. New features go under `Added`, and everything else goes under `Changed`. Don't add a version header; the version number is added at release time.

Releases are built by `.github/workflows/release.yml`. Read it for which branch triggers a release and where the version number comes from.

## Architecture

The services in `src/Manager` are registered in `onlyoffice_docspace.services.yml`:
- `RequestManager` is the server-side DocSpace REST client. It authenticates as the configured DocSpace admin and caches the token in the module config. Its methods return result arrays with error codes from `RequestManagerInterface` instead of throwing.
- `SecurityManager` stores Drupal users' DocSpace password hashes in a custom table. `hook_install` creates that table.
- `UtilsManager::buildComponent()` is the shared entry point for every page that embeds DocSpace. It attaches the SDK libraries and passes the settings the JS needs through `drupalSettings`.

### Authentication: password hashes, not passwords
DocSpace authenticates with password hashes. The browser computes them using the DocSpace JS SDK, which loads from the configured portal through `assets/js/docspace-integration-sdk.js`. Before a form submits, its JS writes the hash into a separate hidden field and overwrites the password field with random text, so the plain password is not sent to Drupal. Drupal keeps the admin's hash in the module config and the users' hashes in the `SecurityManager` table. When a page embeds DocSpace, the JS fetches the current user's hash from a module route and logs in to DocSpace with it.

What happens when that login fails depends on the page. The admin DocSpace page and the widget's selector redirect the user to `LoginForm`, where they re-enter their DocSpace password. The formatter logs the user out and falls back to public access instead, the same way it handles anonymous visitors.

### User export
`UsersForm` lists Drupal users along with their DocSpace status, which it gets from `OODSPUserListBuilder`. The "Invite to DocSpace" bulk action is the `ExportUser` action plugin. Its config entity is in `config/install/`.

### Field type
The field type lives in `src/Plugin/Field`:
- `OODSPItem` stores a reference to a DocSpace room or file.
- `OODSPWidget` and its JS let editors pick a room or file in the DocSpace selector.
- `OODSPFormatter` passes one SDK frame config per item to the formatter JS through `drupalSettings`.

With public access, only items saved with a request token (public rooms and files) are shown. Every other item shows an error message instead.

## Coding practices

- Before writing new code, check whether Drupal core already provides what you need through a public API or service. If it does, use it and keep the change small. Avoid `@internal` code.
- Write code for the lowest PHP version the current branch supports. Work it out from the oldest Drupal core version in `core_version_requirement` in `onlyoffice_docspace.info.yml`. Polyfills that ship with Drupal core can be used.
- Every Drupal API you use must still exist in the newest supported core. An API newer than the oldest supported core is allowed only where the oldest core never reaches it, as `onlyoffice_docspace_field_type_category_info_alter()` does. Check the Drupal change records for anything recently added or deprecated.
- Hooks are plain functions in `onlyoffice_docspace.module` and `onlyoffice_docspace.install`. Don't use `#[Hook]` classes; the oldest supported core lacks them.
- Inject services instead of calling `\Drupal::`: controllers, forms and plugins through `create()`, the list builder through `createInstance()`, managers through their arguments in `onlyoffice_docspace.services.yml`. `ManagerBase` and `OODSPUserListBuilder` still use `\Drupal::`; don't copy that.
- Keep module settings in `onlyoffice_docspace.settings` and add each new key to `config/schema/onlyoffice_docspace.schema.yml`.
- Before using a DocSpace JS SDK method or option, check that the SDK version pinned in `assets/js/docspace-integration-sdk.js` supports it.

## Conventions

- Each JS/CSS bundle is a library in `onlyoffice_docspace.libraries.yml`. Attach libraries with `#attached`. The DocSpace SDK is not a library: load it with `DocspaceIntegrationSdk.initScript()` from `assets/js/docspace-integration-sdk.js`.
- JS-side names use the OODSP prefix: `drupalSettings` keys, helpers on `Drupal.OODSP_Utils`, frame ids `oodsp-*-frame`.
- PHP, JS and CSS files carry a GPL license header. Copy it from an existing file onto any new one.
- Translations are in `translations/`. Add every new `t()` or `Drupal.t()` string to each `.po` file and translate it yourself. A human reviewer checks the translations.
