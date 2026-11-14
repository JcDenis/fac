# fac

Notes for AI assistants working on the Dotclear plugin fac.

**Read first** the shared conventions of the author's Dotclear modules:
https://raw.githubusercontent.com/JcDenis/gh2da/master/CONVENTIONS.md
(if a web tool refuses the address, use `curl -s` on it from the shell).
The rules below take precedence for this module.

## Module

Add options to the sidebar of post edition page to link en extenal feed to the bottom of an entry.

Configuration page in the plugins manager, no admin page. Public side: behaviors.

Required Dotclear and PHP versions: see `_define.php`.

## Structure

* `src/Backend.php`: admin side: menu entry, favorites, behaviors registration.
* `src/BackendBehaviors.php`: admin behaviors.
* `src/Config.php`: configuration page of the module (plugins manager).
* `src/Format.php`: feed format helper.
* `src/Frontend.php`: public side: behaviors, template tags and URL handlers registration.
* `src/Install.php`: install and update, default settings.
* `src/My.php`: module helpers (`My::id()`, settings, permissions).
* `src/Uninstall.php`: cleaners for the plugin Uninstaller.
* `js/`: `backend.js`.
* Behaviors used: `publicEntryAfterContent`, `adminBlogPreferencesFormV2`, `adminBeforeBlogSettingsUpdate`, `adminPostHeaders`, `adminPostFormItems`, `adminAfterPostCreate`, `adminAfterPostUpdate`, `adminBeforePostDelete`, `adminPostsActions`.

## Pitfalls

* None recorded yet: add here the traps met while working on the module.

## Testing

* Check the configuration page, the admin help, in English and French.
* CI browses the common admin and public pages; extra pages can be listed in `.github/dotclear-test.txt`.
