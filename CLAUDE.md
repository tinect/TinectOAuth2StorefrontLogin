# Plugin development rules

## Configuration changes

New/changed/removed field in `OAuthClientEntity` → always update:

1. `README.md` — add/update field row in **Configuration** table with effect description.
2. Admin snippets — `src/Resources/app/administration/src/module/tinect-oauth-storefront-client/snippet/en-GB.json` and `de-DE.json` — add label + help-text keys.
3. Admin edit template — `…/page/edit/tinect-oauth-storefront-client-edit-page.html.twig` — add `sw-switch-field` or input.
4. Admin edit JS — `…/page/edit/index.js` — init field in `created()` hook.

## Storefront-facing texts

All customer-visible texts (`src/Resources/snippet/`) must be non-technical:

- No jargon: no "OAuth", "authorisation code", "security validation", "provider".
- Plain, friendly language. German: formal "Sie" throughout — never mix with "du".
- Every sentence needs explicit subject (no impersonal passives like "Bitte erneut versuchen.").
- Error messages: tell user what to do next, not what system did wrong.

## Migrations

- Never use `AFTER <column>` in `ALTER TABLE … ADD COLUMN` statements.