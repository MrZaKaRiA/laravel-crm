# Help / Support Menu — Design Spec

**Date:** 2026-06-05
**Status:** Approved
**Scope:** Add a "Help & Resources" page reachable from a new bottom-of-sidebar menu item in the core Krayin Admin package.

## Summary

Add a static **Help & Resources** hub to the admin panel, surfaced by a new **Help** menu item pinned to the bottom of the sidebar. The page links out to Krayin services/documentation and shows a static placeholder "Recent Support Tickets" table. It matches the provided design (`design/screen.png`, `design/code.html`) but is re-skinned to Krayin's actual admin theme. This is a **core contribution** — it lives inside `packages/Webkul/Admin`, following the established menu/ACL/route/controller/view conventions. No database, model, repository, or ticket backend is built.

## Decisions

| Topic | Decision |
|---|---|
| Tickets table | **Static placeholder** — sample rows, clearly non-functional. No DB/model/CRUD. |
| Location | Core `Webkul/Admin` package (not a separate package). |
| Visibility / ACL | Add a `help` entry to `acl.php`. Full-access (`permission_type = 'all'`) roles see it automatically; custom roles can be granted it under Settings → Roles. Required because admin menus are bouncer-filtered (see Mechanism note). |
| Content source | Data-driven from a new `Config/support.php` array + lang keys. Card URLs hardcoded to real krayin.com pages in the config. |
| Menu icon | New `.icon-help` CSS rule using an inline SVG mask, added to `app.css`. No icomoon font regeneration (no `selection.json` is checked in). |
| Menu position | Bottom of sidebar, `sort => 10` (after Configuration `9`). |
| View structure | **Single view file** `help/index.blade.php` (Approach A). Card grids loop the config array. Partial-split (Approach B) rejected as over-engineering for a static page. |
| Naming | Technical key/route/files use **`help`** (matches the design's "Help" sidebar label and "Help & Resources" page title, and the agreed ACL key). |
| Search bar | The mockup's "Search resources…" input is **omitted** (non-functional; omitted for honesty). |
| Card icons | Inline SVGs in the blade (the icomoon font lacks cloud/puzzle/code/etc. glyphs). |

## Mechanism note (why the ACL entry is required)

`Webkul/Core/src/Menu.php::getItems('admin')` filters every admin menu item through `bouncer()->hasPermission($item['key'])`. `Bouncer::hasPermission` returns `true` for `permission_type = 'all'` roles, but for custom roles it is `in_array($key, $role->permissions)` — and a key only enters `$role->permissions` if it exists in `acl.php`. A menu item without a matching `acl.php` key is therefore invisible to all custom roles. Adding the `help` ACL key is the core-clean way to make it visible (default-on for full-access roles, grantable for custom roles).

## Files

### Modified (core)
- `packages/Webkul/Admin/src/Config/menu.php` — add `help` item (`sort => 10`, `icon-class => 'icon-help'`, `route => 'admin.help.index'`).
- `packages/Webkul/Admin/src/Config/acl.php` — add `help` permission entry (`sort => 10`).
- `packages/Webkul/Admin/src/Routes/Admin/web.php` — `require 'help-routes.php'`.
- `packages/Webkul/Admin/src/Resources/assets/css/app.css` — add `.icon-help` rule (SVG mask glyph).
- `packages/Webkul/Admin/src/Resources/lang/en/app.php` — add `layouts.help` label and a new `help` content section (hero, section headings, card titles/descriptions, ticket table headers, CTA text, footer links).
- `packages/Webkul/Admin/src/Providers/AdminServiceProvider.php` — `mergeConfigFrom(dirname(__DIR__).'/Config/support.php', 'support')`.

### Added
- `packages/Webkul/Admin/src/Config/support.php` — data: `services` cards, `resources` cards, `community` cards. Each card: `{ icon, title (lang key), description (lang key), url, url_label }`.
- `packages/Webkul/Admin/src/Routes/Admin/help-routes.php` — single `GET help` → `admin.help.index`.
- `packages/Webkul/Admin/src/Http/Controllers/HelpController.php` — `index()` returns `admin::help.index` with the support config.
- `packages/Webkul/Admin/src/Resources/views/help/index.blade.php` — the page.

## Controller & route

```php
// help-routes.php
Route::controller(HelpController::class)->prefix('help')->group(function () {
    Route::get('', 'index')->name('admin.help.index');
});

// HelpController
public function index(): View
{
    return view('admin::help.index', ['support' => config('support')]);
}
```

## Page composition (top to bottom)

Wrapped in `<x-admin::layouts>` with `<x-slot:title>`. Uses Krayin theme tokens (`bg-brandColor`, `text-gray-*`, `dark:*`, `x-admin::button`) instead of the mockup's raw hex / Material classes. The mockup's purple `#4a2ac9` already matches Krayin's `brandColor` (`var(--brand-color)`).

1. **Hero** — "Help & Resources" heading + intro paragraph.
2. **Services** grid — 3 cards (Cloud Hosting, Support & Maintenance, Paid Services), external krayin.com links, looped from `config('support.services')`.
3. **Resources & Documentation** grid — 3 cards (Extensions, Dev Docs & Blogs, API Docs), looped from `config('support.resources')`.
4. **Recent Support Tickets** — static placeholder table (sample rows), Krayin table styling, clearly non-functional.
5. **"Still need a hand?"** CTA banner — Contact us button → krayin.com/contact.
6. **Community Forums + Video Tutorials** — two-card row from `config('support.community')`.

Responsive grids (`md:grid-cols-2 lg:grid-cols-3`), full dark-mode support.

## Out of scope (explicitly not built)

- Ticket database, model, repository, DataGrid, or any CRUD.
- Functional resource search (mockup search input omitted).
- Admin-configurable URLs via core_config / Configuration screen.
- icomoon font regeneration / new font glyphs.

## Acceptance criteria

1. A **Help** item appears at the bottom of the admin sidebar with the `.icon-help` icon.
2. Full-access roles see it without any change; a custom role can be granted/denied it via Settings → Roles.
3. Clicking it opens `/admin/help` rendering the Help & Resources page matching the design, in Krayin's theme, with working light/dark mode and responsive layout.
4. All card text is translatable (lang keys) and all URLs come from `Config/support.php`.
5. No new database tables/migrations; no functional ticket backend.
6. Existing admin pages and menus are unaffected.
