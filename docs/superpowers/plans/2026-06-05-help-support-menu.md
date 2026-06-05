# Help / Support Menu Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Add a "Help & Resources" static page reachable from a new bottom-of-sidebar "Help" menu item in the core Krayin Admin package.

**Architecture:** A thin controller returns a single Blade view driven by a `Config/support.php` data array (service/resource/community cards) with all text in lang files. The menu item is registered in `menu.php` (sort 10) with a new `.icon-help` CSS-mask glyph, and an `acl.php` entry makes it visible to full-access roles and grantable to custom roles. No database, model, or ticket backend — the "Recent Support Tickets" table is a static placeholder.

**Tech Stack:** Laravel 12, Blade, Tailwind (Krayin admin theme), icomoon icon font + inline heroicons SVGs, Pest 3 for tests, Vite for assets.

---

## Reference: design + conventions

- Design source: `design/screen.png`, `design/code.html` (re-skinned to Krayin's theme — do NOT copy the mockup's raw hex/Material classes).
- Spec: `docs/superpowers/specs/2026-06-05-help-support-menu-design.md`.
- Menu rendering: `packages/Webkul/Core/src/Menu.php` filters admin items via `bouncer()->hasPermission($item['key'])` — this is why the `acl.php` entry is mandatory.
- Layout component: `<x-admin::layouts>` with `<x-slot:title>` (see `packages/Webkul/Admin/src/Resources/views/dashboard/index.blade.php`).
- Brand color token: `brandColor` = `var(--brand-color)` (already the mockup's purple).

## File structure

**Modified:**
- `packages/Webkul/Admin/src/Config/menu.php` — add `help` item (sort 10).
- `packages/Webkul/Admin/src/Config/acl.php` — add `help` permission (sort 10).
- `packages/Webkul/Admin/src/Routes/Admin/web.php` — require `help-routes.php`.
- `packages/Webkul/Admin/src/Providers/AdminServiceProvider.php` — merge `support.php` config.
- `packages/Webkul/Admin/src/Resources/assets/css/app.css` — add `.icon-help` rule.
- `packages/Webkul/Admin/src/Resources/lang/en/app.php` — add `layouts.help` + `help` content section.

**Created:**
- `packages/Webkul/Admin/src/Config/support.php`
- `packages/Webkul/Admin/src/Routes/Admin/help-routes.php`
- `packages/Webkul/Admin/src/Http/Controllers/HelpController.php`
- `packages/Webkul/Admin/src/Resources/views/help/index.blade.php`
- `tests/Feature/HelpTest.php`

---

## Task 1: Walking skeleton — route, controller, menu, ACL, skeleton view

**Files:**
- Test: `tests/Feature/HelpTest.php`
- Create: `packages/Webkul/Admin/src/Http/Controllers/HelpController.php`
- Create: `packages/Webkul/Admin/src/Routes/Admin/help-routes.php`
- Create: `packages/Webkul/Admin/src/Resources/views/help/index.blade.php`
- Modify: `packages/Webkul/Admin/src/Routes/Admin/web.php`
- Modify: `packages/Webkul/Admin/src/Config/menu.php`
- Modify: `packages/Webkul/Admin/src/Config/acl.php`
- Modify: `packages/Webkul/Admin/src/Resources/lang/en/app.php`

- [ ] **Step 1: Write the failing feature test**

Create `tests/Feature/HelpTest.php`:

```php
<?php

it('shows the help page to an authenticated admin', function () {
    $admin = getDefaultAdmin();

    test()->actingAs($admin)
        ->get(route('admin.help.index'))
        ->assertOk()
        ->assertSee('Help & Resources');
});
```

- [ ] **Step 2: Run the test to verify it fails**

Run: `./vendor/bin/pest --filter=HelpTest`
Expected: FAIL — `Route [admin.help.index] not defined.`

- [ ] **Step 3: Create the controller**

Create `packages/Webkul/Admin/src/Http/Controllers/HelpController.php`:

```php
<?php

namespace Webkul\Admin\Http\Controllers;

use Illuminate\View\View;

class HelpController extends Controller
{
    /**
     * Display the help & resources page.
     */
    public function index(): View
    {
        return view('admin::help.index', [
            'support' => config('support'),
        ]);
    }
}
```

- [ ] **Step 4: Create the route file**

Create `packages/Webkul/Admin/src/Routes/Admin/help-routes.php`:

```php
<?php

use Illuminate\Support\Facades\Route;
use Webkul\Admin\Http\Controllers\HelpController;

Route::controller(HelpController::class)->prefix('help')->group(function () {
    Route::get('', 'index')->name('admin.help.index');
});
```

- [ ] **Step 5: Register the route file**

In `packages/Webkul/Admin/src/Routes/Admin/web.php`, add after the existing `require` block for configuration routes (anywhere among the requires is fine):

```php
/**
 * Help routes.
 */
require 'help-routes.php';
```

- [ ] **Step 6: Create the skeleton view**

Create `packages/Webkul/Admin/src/Resources/views/help/index.blade.php`:

```blade
<x-admin::layouts>
    <x-slot:title>
        @lang('admin::app.help.index.title')
    </x-slot>

    <div class="flex flex-col gap-4">
        <p class="text-2xl font-semibold dark:text-white">
            @lang('admin::app.help.index.title')
        </p>
    </div>
</x-admin::layouts>
```

- [ ] **Step 7: Add the menu label + minimal lang section**

In `packages/Webkul/Admin/src/Resources/lang/en/app.php`, inside the `'layouts' => [ ... ]` array (around line 2117), add after `'dashboard' => 'Dashboard',`:

```php
        'help' => 'Help',
```

Then add a top-level `'help'` section. Place it immediately before the existing top-level `'layouts' => [` key (so it sits with the other top-level page sections like `'dashboard' => [`):

```php
    'help' => [
        'index' => [
            'title'       => 'Help & Resources',
            'description' => 'Everything you need to get the most out of Krayin Admin — hosting, support and professional services, plus extensions and developer documentation.',
        ],
    ],
```

- [ ] **Step 8: Register the ACL entry**

In `packages/Webkul/Admin/src/Config/acl.php`, add as the final element of the returned array, after the `configuration` entry:

```php
    ], [
        'key' => 'help',
        'name' => 'admin::app.acl.help',
        'route' => 'admin.help.index',
        'sort' => 10,
    ],
```

Then add the ACL label in `packages/Webkul/Admin/src/Resources/lang/en/app.php`. Find the `'acl' => [` array and add:

```php
        'help' => 'Help',
```

- [ ] **Step 9: Register the menu item**

In `packages/Webkul/Admin/src/Config/menu.php`, add as the final element of the returned array, after the `configuration` entry:

```php
    ],

    /**
     * Help.
     */
    [
        'key' => 'help',
        'name' => 'admin::app.layouts.help',
        'route' => 'admin.help.index',
        'sort' => 10,
        'icon-class' => 'icon-help',
    ],
```

- [ ] **Step 10: Run the test to verify it passes**

Run: `./vendor/bin/pest --filter=HelpTest`
Expected: PASS (1 passed).

- [ ] **Step 11: Commit**

```bash
git add tests/Feature/HelpTest.php \
  packages/Webkul/Admin/src/Http/Controllers/HelpController.php \
  packages/Webkul/Admin/src/Routes/Admin/help-routes.php \
  packages/Webkul/Admin/src/Routes/Admin/web.php \
  packages/Webkul/Admin/src/Resources/views/help/index.blade.php \
  packages/Webkul/Admin/src/Config/menu.php \
  packages/Webkul/Admin/src/Config/acl.php \
  packages/Webkul/Admin/src/Resources/lang/en/app.php
git commit -m "feat(admin): add Help menu item, route and page skeleton"
```

---

## Task 2: Menu icon — `.icon-help` CSS mask glyph

**Files:**
- Modify: `packages/Webkul/Admin/src/Resources/assets/css/app.css`

- [ ] **Step 1: Add the `.icon-help` rule**

In `packages/Webkul/Admin/src/Resources/assets/css/app.css`, add this rule immediately after the `[class^="icon-"], [class*=" icon-"] { ... }` block (i.e. right before `@layer components {` near line 30). The base64 below is a heroicons "question-mark-circle" outline SVG:

```css
.icon-help {
    display: inline-block;
    width: 1em;
    height: 1em;
    background-color: currentColor;
    -webkit-mask: url("data:image/svg+xml;base64,PHN2ZyB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIGZpbGw9Im5vbmUiIHZpZXdCb3g9IjAgMCAyNCAyNCIgc3Ryb2tlLXdpZHRoPSIxLjUiIHN0cm9rZT0iIzAwMCI+PHBhdGggc3Ryb2tlLWxpbmVjYXA9InJvdW5kIiBzdHJva2UtbGluZWpvaW49InJvdW5kIiBkPSJNOS44NzkgNy41MTljMS4xNzEtMS4wMjUgMy4wNzEtMS4wMjUgNC4yNDIgMCAxLjE3MiAxLjAyNSAxLjE3MiAyLjY4NyAwIDMuNzEyLS4yMDMuMTc5LS40My4zMjYtLjY3LjQ0Mi0uNzQ1LjM2MS0xLjQ1Ljk5OS0xLjQ1IDEuODI3di43NU0yMSAxMmE5IDkgMCAxIDEtMTggMCA5IDkgMCAwIDEgMTggMFptLTkgNS4yNWguMDA4di4wMDhIMTJ2LS4wMDhaIi8+PC9zdmc+") no-repeat center / contain;
    mask: url("data:image/svg+xml;base64,PHN2ZyB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIGZpbGw9Im5vbmUiIHZpZXdCb3g9IjAgMCAyNCAyNCIgc3Ryb2tlLXdpZHRoPSIxLjUiIHN0cm9rZT0iIzAwMCI+PHBhdGggc3Ryb2tlLWxpbmVjYXA9InJvdW5kIiBzdHJva2UtbGluZWpvaW49InJvdW5kIiBkPSJNOS44NzkgNy41MTljMS4xNzEtMS4wMjUgMy4wNzEtMS4wMjUgNC4yNDIgMCAxLjE3MiAxLjAyNSAxLjE3MiAyLjY4NyAwIDMuNzEyLS4yMDMuMTc5LS40My4zMjYtLjY3LjQ0Mi0uNzQ1LjM2MS0xLjQ1Ljk5OS0xLjQ1IDEuODI3di43NU0yMSAxMmE5IDkgMCAxIDEtMTggMCA5IDkgMCAwIDEgMTggMFptLTkgNS4yNWguMDA4di4wMDhIMTJ2LS4wMDhaIi8+PC9zdmc+") no-repeat center / contain;
}
```

- [ ] **Step 2: Rebuild admin assets**

Run:
```bash
cd packages/Webkul/Admin && npm run build && cd -
```
Expected: Vite build completes, writes to `public/admin/build`. (Alternatively, if a dev server is running via `npm run dev`, the change hot-reloads.)

- [ ] **Step 3: Manually verify the menu icon**

Log into the admin panel as the default admin. Confirm a "Help" item appears at the very bottom of the left sidebar (below Configuration) with a question-mark-in-circle icon, that the icon is the same gray as the other menu icons, and turns white when the Help item is active. Confirm light/dark mode both render the icon.

Expected: Help menu item visible at the bottom with a correctly themed icon.

- [ ] **Step 4: Commit**

```bash
git add packages/Webkul/Admin/src/Resources/assets/css/app.css public/admin/build
git commit -m "feat(admin): add icon-help glyph for the Help menu item"
```

---

## Task 3: Content config + provider merge + content lang keys

**Files:**
- Create: `packages/Webkul/Admin/src/Config/support.php`
- Modify: `packages/Webkul/Admin/src/Providers/AdminServiceProvider.php`
- Modify: `packages/Webkul/Admin/src/Resources/lang/en/app.php`

> URLs below are sensible Krayin defaults; adjust to the final marketing URLs if they differ — they are isolated in this one config file.

- [ ] **Step 1: Create the support config**

Create `packages/Webkul/Admin/src/Config/support.php`:

```php
<?php

return [
    'services' => [
        [
            'icon'        => 'cloud',
            'title'       => 'admin::app.help.index.services.cloud-hosting.title',
            'description' => 'admin::app.help.index.services.cloud-hosting.description',
            'url'         => 'https://krayincrm.com/cloud-hosting',
            'url_label'   => 'krayincrm.com/cloud-hosting',
        ],
        [
            'icon'        => 'support',
            'title'       => 'admin::app.help.index.services.support.title',
            'description' => 'admin::app.help.index.services.support.description',
            'url'         => 'https://krayincrm.com/support',
            'url_label'   => 'krayincrm.com/support',
        ],
        [
            'icon'        => 'services',
            'title'       => 'admin::app.help.index.services.paid-services.title',
            'description' => 'admin::app.help.index.services.paid-services.description',
            'url'         => 'https://krayincrm.com/services',
            'url_label'   => 'krayincrm.com/services',
        ],
    ],

    'resources' => [
        [
            'icon'        => 'extensions',
            'title'       => 'admin::app.help.index.resources.extensions.title',
            'description' => 'admin::app.help.index.resources.extensions.description',
            'url'         => 'https://krayincrm.com/extensions',
            'url_label'   => 'krayincrm.com/extensions',
        ],
        [
            'icon'        => 'docs',
            'title'       => 'admin::app.help.index.resources.dev-docs.title',
            'description' => 'admin::app.help.index.resources.dev-docs.description',
            'url'         => 'https://devdocs.krayincrm.com',
            'url_label'   => 'devdocs.krayincrm.com',
        ],
        [
            'icon'        => 'api',
            'title'       => 'admin::app.help.index.resources.api-docs.title',
            'description' => 'admin::app.help.index.resources.api-docs.description',
            'url'         => 'https://devdocs.krayincrm.com',
            'url_label'   => 'devdocs.krayincrm.com/api',
        ],
    ],

    'community' => [
        [
            'icon'        => 'community',
            'title'       => 'admin::app.help.index.community.forums.title',
            'description' => 'admin::app.help.index.community.forums.description',
            'url'         => 'https://forums.krayincrm.com',
            'link_label'  => 'admin::app.help.index.community.forums.link',
        ],
        [
            'icon'        => 'video',
            'title'       => 'admin::app.help.index.community.tutorials.title',
            'description' => 'admin::app.help.index.community.tutorials.description',
            'url'         => 'https://www.youtube.com/@krayincrm',
            'link_label'  => 'admin::app.help.index.community.tutorials.link',
        ],
    ],

    'contact_url' => 'https://krayincrm.com/contact',
];
```

- [ ] **Step 2: Merge the config in the provider**

In `packages/Webkul/Admin/src/Providers/AdminServiceProvider.php`, inside `registerConfig()`, add after the `attribute_entity_types` merge line:

```php
        $this->mergeConfigFrom(dirname(__DIR__).'/Config/support.php', 'support');
```

- [ ] **Step 3: Add all content lang keys**

In `packages/Webkul/Admin/src/Resources/lang/en/app.php`, replace the `'help' => [ 'index' => [ ... ] ]` block added in Task 1 with the full version:

```php
    'help' => [
        'index' => [
            'title'                       => 'Help & Resources',
            'description'                 => 'Everything you need to get the most out of Krayin Admin — hosting, support and professional services, plus extensions and developer documentation.',
            'contact-us'                  => 'Contact us',
            'view-all'                    => 'View all',
            'recent-tickets'              => 'Recent Support Tickets',
            'still-need-help-title'       => 'Still need a hand?',
            'still-need-help-description' => 'Talk to the Krayin team about hosting, custom development or anything else.',

            'services' => [
                'title'         => 'Services',
                'cloud-hosting' => [
                    'title'       => 'Cloud Hosting',
                    'description' => 'Cost-effective, managed cloud hosting — try and launch Krayin on the cloud in minutes, fully optimised and scalable.',
                ],
                'support' => [
                    'title'       => 'Support & Maintenance',
                    'description' => 'Dedicated technical support and ongoing maintenance plans to keep your CRM secure, updated and running smoothly.',
                ],
                'paid-services' => [
                    'title'       => 'Paid Services',
                    'description' => 'Expert help for module integration, customisation, data migration, version upgrades and bespoke development.',
                ],
            ],

            'resources' => [
                'title'      => 'Resources & Documentation',
                'extensions' => [
                    'title'       => 'Extensions',
                    'description' => 'Browse official and community add-ons to extend Krayin with new connectors, channels and features.',
                ],
                'dev-docs' => [
                    'title'       => 'Dev Docs & Blogs',
                    'description' => 'Developer guides, tutorials and the latest articles to help you build, configure and stay up to date.',
                ],
                'api-docs' => [
                    'title'       => 'API Docs',
                    'description' => 'Full REST API reference with endpoints, authentication and examples to integrate Krayin with your stack.',
                ],
            ],

            'community' => [
                'forums' => [
                    'title'       => 'Community Forums',
                    'description' => 'Connect with thousands of other Krayin users and developers to share tips and solve problems.',
                    'link'        => 'Join the community',
                ],
                'tutorials' => [
                    'title'       => 'Video Tutorials',
                    'description' => 'Watch step-by-step video guides on setting up your CRM, configuring pipelines and managing users.',
                    'link'        => 'Browse YouTube channel',
                ],
            ],

            'tickets' => [
                'id'         => 'ID',
                'subject'    => 'Subject',
                'category'   => 'Category',
                'created-at' => 'Created At',
                'status'     => 'Status',
                'actions'    => 'Actions',
            ],
        ],
    ],
```

- [ ] **Step 4: Verify config + lang load without errors**

Run: `php artisan config:clear && ./vendor/bin/pest --filter=HelpTest`
Expected: PASS (page still renders; config merge introduced no errors).

- [ ] **Step 5: Commit**

```bash
git add packages/Webkul/Admin/src/Config/support.php \
  packages/Webkul/Admin/src/Providers/AdminServiceProvider.php \
  packages/Webkul/Admin/src/Resources/lang/en/app.php
git commit -m "feat(admin): add help page content config and lang strings"
```

---

## Task 4: Full view — hero + services grid + resources grid

**Files:**
- Modify: `packages/Webkul/Admin/src/Resources/views/help/index.blade.php`

- [ ] **Step 1: Replace the view with the icon map, hero, and two card grids**

Overwrite `packages/Webkul/Admin/src/Resources/views/help/index.blade.php` with:

```blade
@php
    $icons = [
        'cloud'      => '<path stroke-linecap="round" stroke-linejoin="round" d="M2.25 15a4.5 4.5 0 0 0 4.5 4.5H18a3.75 3.75 0 0 0 1.332-7.257 3 3 0 0 0-3.758-3.848 5.25 5.25 0 0 0-10.233 2.33A4.502 4.502 0 0 0 2.25 15Z"/>',
        'support'    => '<path stroke-linecap="round" stroke-linejoin="round" d="M16.712 4.33a9.027 9.027 0 0 1 1.652 1.306c.51.51.944 1.064 1.306 1.652M16.712 4.33l-3.448 4.138m3.448-4.138a9.014 9.014 0 0 0-9.424 0M19.67 7.288l-4.138 3.448m4.138-3.448a9.014 9.014 0 0 1 0 9.424m-4.138-5.976a3.736 3.736 0 0 0-.88-1.388 3.737 3.737 0 0 0-1.388-.88m2.268 2.268a3.765 3.765 0 0 1 0 2.528m-2.268-4.796a3.765 3.765 0 0 0-2.528 0m4.796 4.796c-.181.506-.475.982-.88 1.388a3.736 3.736 0 0 1-1.388.88m2.268-2.268 4.138 3.448m0 0a9.027 9.027 0 0 1-1.306 1.652c-.51.51-1.064.944-1.652 1.306m0 0-3.448-4.138m3.448 4.138a9.014 9.014 0 0 1-9.424 0m5.976-4.138a3.765 3.765 0 0 1-2.528 0m0 0a3.736 3.736 0 0 1-1.388-.88 3.737 3.737 0 0 1-.88-1.388m2.268 2.268L7.288 19.67m0 0a9.024 9.024 0 0 1-1.652-1.306 9.027 9.027 0 0 1-1.306-1.652m0 0 4.138-3.448M4.33 16.712a9.014 9.014 0 0 1 0-9.424m4.138 5.976a3.765 3.765 0 0 1 0-2.528m0 0c.181-.506.475-.982.88-1.388a3.736 3.736 0 0 1 1.388-.88m-2.268 2.268L4.33 7.288m6.406 1.18L7.288 4.33m0 0a9.024 9.024 0 0 0-1.652 1.306A9.025 9.025 0 0 0 4.33 7.288"/>',
        'services'   => '<path stroke-linecap="round" stroke-linejoin="round" d="M11.42 15.17 17.25 21A2.652 2.652 0 0 0 21 17.25l-5.877-5.877M11.42 15.17l2.496-3.03c.317-.384.74-.626 1.208-.766M11.42 15.17l-4.655 5.653a2.548 2.548 0 1 1-3.586-3.586l6.837-5.63m5.108-.233c.55-.164 1.163-.188 1.743-.14a4.5 4.5 0 0 0 4.486-6.336l-3.276 3.277a3.004 3.004 0 0 1-2.25-2.25l3.276-3.276a4.5 4.5 0 0 0-6.336 4.486c.091 1.076-.071 2.264-.904 2.95l-.102.085m-1.745 1.437L5.909 7.5H4.5L2.25 3.75l1.5-1.5L7.5 4.5v1.409l4.26 4.26m-1.745 1.437 1.745-1.437m6.615 8.206L15.75 15.75M4.867 19.125h.008v.008h-.008v-.008Z"/>',
        'extensions' => '<path stroke-linecap="round" stroke-linejoin="round" d="M14.25 6.087c0-.355.186-.676.401-.959.221-.29.349-.634.349-1.003 0-1.036-1.007-1.875-2.25-1.875s-2.25.84-2.25 1.875c0 .369.128.713.349 1.003.215.283.401.604.401.959v0a.64.64 0 0 1-.657.643 48.39 48.39 0 0 1-4.163-.3c.186 1.613.293 3.25.315 4.907a.656.656 0 0 1-.658.663v0c-.355 0-.676-.186-.959-.401a1.647 1.647 0 0 0-1.003-.349c-1.036 0-1.875 1.007-1.875 2.25s.84 2.25 1.875 2.25c.369 0 .713-.128 1.003-.349.283-.215.604-.401.959-.401v0c.31 0 .555.26.532.57a48.039 48.039 0 0 1-.642 5.056c1.518.19 3.058.309 4.616.354a.64.64 0 0 0 .657-.643v0c0-.355-.186-.676-.401-.959a1.647 1.647 0 0 1-.349-1.003c0-1.035 1.008-1.875 2.25-1.875 1.243 0 2.25.84 2.25 1.875 0 .369-.128.713-.349 1.003-.215.283-.4.604-.4.959v0c0 .333.277.599.61.58a48.1 48.1 0 0 0 5.427-.63 48.05 48.05 0 0 0 .582-4.717.532.532 0 0 0-.533-.57v0c-.355 0-.676.186-.959.401-.29.221-.634.349-1.003.349-1.035 0-1.875-1.007-1.875-2.25s.84-2.25 1.875-2.25c.37 0 .713.128 1.003.349.283.215.604.401.96.401v0a.656.656 0 0 0 .658-.663 48.422 48.422 0 0 0-.37-5.36c-1.886.342-3.81.574-5.766.689a.578.578 0 0 1-.61-.58v0Z"/>',
        'docs'       => '<path stroke-linecap="round" stroke-linejoin="round" d="M12 6.042A8.967 8.967 0 0 0 6 3.75c-1.052 0-2.062.18-3 .512v14.25A8.987 8.987 0 0 1 6 18c2.305 0 4.408.867 6 2.292m0-14.25a8.966 8.966 0 0 1 6-2.292c1.052 0 2.062.18 3 .512v14.25A8.987 8.987 0 0 0 18 18a8.967 8.967 0 0 0-6 2.292m0-14.25v14.25"/>',
        'api'        => '<path stroke-linecap="round" stroke-linejoin="round" d="M17.25 6.75 22.5 12l-5.25 5.25m-10.5 0L1.5 12l5.25-5.25m7.5-3-4.5 16.5"/>',
        'community'  => '<path stroke-linecap="round" stroke-linejoin="round" d="M18 18.72a9.094 9.094 0 0 0 3.741-.479 3 3 0 0 0-4.682-2.72m.94 3.198.001.031c0 .225-.012.447-.037.666A11.944 11.944 0 0 1 12 21c-2.17 0-4.207-.576-5.963-1.584A6.062 6.062 0 0 1 6 18.719m12 0a5.971 5.971 0 0 0-.941-3.197m0 0A5.995 5.995 0 0 0 12 12.75a5.995 5.995 0 0 0-5.058 2.772m0 0a3 3 0 0 0-4.681 2.72 8.986 8.986 0 0 0 3.74.477m.94-3.197a5.971 5.971 0 0 0-.94 3.197M15 6.75a3 3 0 1 1-6 0 3 3 0 0 1 6 0Zm6 3a2.25 2.25 0 1 1-4.5 0 2.25 2.25 0 0 1 4.5 0Zm-13.5 0a2.25 2.25 0 1 1-4.5 0 2.25 2.25 0 0 1 4.5 0Z"/>',
        'video'      => '<path stroke-linecap="round" stroke-linejoin="round" d="M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/><path stroke-linecap="round" stroke-linejoin="round" d="M15.91 11.672a.375.375 0 0 1 0 .656l-5.603 3.113a.375.375 0 0 1-.557-.328V8.887c0-.286.307-.466.557-.327l5.603 3.112Z"/>',
    ];
@endphp

<x-admin::layouts>
    <x-slot:title>
        @lang('admin::app.help.index.title')
    </x-slot>

    <div class="flex flex-col gap-8">
        <!-- Hero -->
        <div class="flex flex-wrap items-center justify-between gap-4">
            <div class="grid max-w-3xl gap-2">
                <p class="text-2xl font-bold text-gray-800 dark:text-white">
                    @lang('admin::app.help.index.title')
                </p>

                <p class="text-gray-600 dark:text-gray-300">
                    @lang('admin::app.help.index.description')
                </p>
            </div>

            <a
                href="{{ $support['contact_url'] }}"
                target="_blank"
                class="primary-button"
            >
                @lang('admin::app.help.index.contact-us')
            </a>
        </div>

        <!-- Services -->
        <div class="flex flex-col gap-4">
            <p class="text-sm font-semibold uppercase tracking-wider text-gray-400 dark:text-gray-400">
                @lang('admin::app.help.index.services.title')
            </p>

            <div class="grid grid-cols-1 gap-5 md:grid-cols-2 lg:grid-cols-3">
                @foreach ($support['services'] as $card)
                    @include('admin::help.card', ['card' => $card, 'icons' => $icons])
                @endforeach
            </div>
        </div>

        <!-- Resources -->
        <div class="flex flex-col gap-4">
            <p class="text-sm font-semibold uppercase tracking-wider text-gray-400 dark:text-gray-400">
                @lang('admin::app.help.index.resources.title')
            </p>

            <div class="grid grid-cols-1 gap-5 md:grid-cols-2 lg:grid-cols-3">
                @foreach ($support['resources'] as $card)
                    @include('admin::help.card', ['card' => $card, 'icons' => $icons])
                @endforeach
            </div>
        </div>
    </div>
</x-admin::layouts>
```

- [ ] **Step 2: Create the reusable card partial**

Create `packages/Webkul/Admin/src/Resources/views/help/card.blade.php`:

```blade
<a
    href="{{ $card['url'] }}"
    target="_blank"
    class="group flex flex-col rounded-xl border border-gray-200 bg-white p-5 transition-all hover:shadow-lg dark:border-gray-800 dark:bg-gray-900"
>
    <div class="mb-5 flex items-start justify-between">
        <span class="flex h-12 w-12 items-center justify-center rounded-lg bg-violet-50 text-brandColor dark:bg-gray-800">
            <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                {!! $icons[$card['icon']] !!}
            </svg>
        </span>

        <i class="icon-arrow-up-right text-xl text-gray-300 transition-colors group-hover:text-brandColor"></i>
    </div>

    <p class="mb-2 text-lg font-semibold text-gray-800 dark:text-white">
        @lang($card['title'])
    </p>

    <p class="mb-5 text-sm text-gray-600 dark:text-gray-300">
        @lang($card['description'])
    </p>

    <span class="mt-auto text-sm text-gray-400 transition-colors group-hover:text-brandColor">
        {{ $card['url_label'] }}
    </span>
</a>
```

> Note: `icon-arrow-up-right` may not exist in the icomoon font. If it renders blank, replace that `<i>` with an inline SVG arrow or remove it — verify in Step 4.

- [ ] **Step 3: Add the assertion to the feature test**

In `tests/Feature/HelpTest.php`, extend the existing test body (before its closing `});`) with:

```php
    test()->actingAs($admin)
        ->get(route('admin.help.index'))
        ->assertOk()
        ->assertSee('Cloud Hosting')
        ->assertSee('Extensions')
        ->assertSee('krayincrm.com/cloud-hosting');
```

- [ ] **Step 4: Run the test + visually verify**

Run: `./vendor/bin/pest --filter=HelpTest`
Expected: PASS.

Then load `/admin/help` in the browser. Confirm the hero, "Contact us" button, and both 3-card grids render in Krayin's theme with hover shadows, card icons visible, and correct light/dark colors. If the `icon-arrow-up-right` glyph is blank, apply the fallback from Step 2's note.

- [ ] **Step 5: Commit**

```bash
git add packages/Webkul/Admin/src/Resources/views/help/index.blade.php \
  packages/Webkul/Admin/src/Resources/views/help/card.blade.php \
  tests/Feature/HelpTest.php
git commit -m "feat(admin): build help page hero and service/resource card grids"
```

---

## Task 5: Static tickets table + CTA banner + community section

**Files:**
- Modify: `packages/Webkul/Admin/src/Resources/views/help/index.blade.php`

- [ ] **Step 1: Add the three sections before the closing wrapper `</div>`**

In `packages/Webkul/Admin/src/Resources/views/help/index.blade.php`, insert the following immediately after the closing `</div>` of the Resources block and before the final `</div>` that closes `<div class="flex flex-col gap-8">`:

```blade
        <!-- Recent Support Tickets (static placeholder) -->
        <div class="overflow-hidden rounded-xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-gray-900">
            <div class="flex items-center justify-between border-b border-gray-200 p-5 dark:border-gray-800">
                <p class="text-lg font-semibold text-gray-800 dark:text-white">
                    @lang('admin::app.help.index.recent-tickets')
                </p>

                <a href="{{ $support['contact_url'] }}" target="_blank" class="text-sm font-semibold text-brandColor hover:underline">
                    @lang('admin::app.help.index.view-all')
                </a>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left">
                    <thead class="bg-gray-50 text-sm font-semibold text-gray-600 dark:bg-gray-950 dark:text-gray-300">
                        <tr>
                            <th class="px-6 py-4">@lang('admin::app.help.index.tickets.id')</th>
                            <th class="px-6 py-4">@lang('admin::app.help.index.tickets.subject')</th>
                            <th class="px-6 py-4">@lang('admin::app.help.index.tickets.category')</th>
                            <th class="px-6 py-4">@lang('admin::app.help.index.tickets.created-at')</th>
                            <th class="px-6 py-4">@lang('admin::app.help.index.tickets.status')</th>
                        </tr>
                    </thead>

                    <tbody class="divide-y divide-gray-200 text-sm text-gray-600 dark:divide-gray-800 dark:text-gray-300">
                        <tr>
                            <td class="px-6 py-4 font-semibold">#TK-8492</td>
                            <td class="px-6 py-4">SSL certificate renewal issue on production</td>
                            <td class="px-6 py-4">Hosting</td>
                            <td class="px-6 py-4">22 Mar 2025, 02:30 PM</td>
                            <td class="px-6 py-4"><span class="rounded-full bg-violet-50 px-2 py-1 text-xs font-semibold uppercase text-brandColor">In Progress</span></td>
                        </tr>
                        <tr>
                            <td class="px-6 py-4 font-semibold">#TK-8488</td>
                            <td class="px-6 py-4">Custom API endpoint returning 403 Forbidden</td>
                            <td class="px-6 py-4">Development</td>
                            <td class="px-6 py-4">21 Mar 2025, 11:15 AM</td>
                            <td class="px-6 py-4"><span class="rounded-full bg-gray-100 px-2 py-1 text-xs font-semibold uppercase text-gray-600 dark:bg-gray-800 dark:text-gray-300">Pending</span></td>
                        </tr>
                        <tr>
                            <td class="px-6 py-4 font-semibold">#TK-8475</td>
                            <td class="px-6 py-4">Bulk data import failure from CSV</td>
                            <td class="px-6 py-4">Data Management</td>
                            <td class="px-6 py-4">20 Mar 2025, 09:45 AM</td>
                            <td class="px-6 py-4"><span class="rounded-full bg-green-100 px-2 py-1 text-xs font-semibold uppercase text-green-700 dark:bg-green-900 dark:text-green-300">Resolved</span></td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Still need a hand CTA -->
        <div class="flex flex-col items-center justify-between gap-6 rounded-2xl bg-brandColor p-8 md:flex-row">
            <div class="flex items-center gap-6">
                <span class="flex h-16 w-16 shrink-0 items-center justify-center rounded-full bg-white/20 text-white">
                    <svg class="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M8.625 9.75a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm0 0H8.25m4.125 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm0 0H12m4.125 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm0 0h-.375M21 12c0 4.556-4.03 8.25-9 8.25a9.764 9.764 0 0 1-2.555-.337A5.972 5.972 0 0 1 5.41 20.97a5.969 5.969 0 0 1-.474-.065 4.48 4.48 0 0 0 .978-2.025c.09-.457-.133-.901-.467-1.226C3.93 16.178 3 14.189 3 12c0-4.556 4.03-8.25 9-8.25s9 3.694 9 8.25Z"/>
                    </svg>
                </span>

                <div class="text-white">
                    <p class="text-xl font-bold">@lang('admin::app.help.index.still-need-help-title')</p>
                    <p class="opacity-90">@lang('admin::app.help.index.still-need-help-description')</p>
                </div>
            </div>

            <a href="{{ $support['contact_url'] }}" target="_blank" class="shrink-0 rounded-lg bg-white px-6 py-3 font-semibold text-brandColor transition-opacity hover:opacity-90">
                @lang('admin::app.help.index.contact-us')
            </a>
        </div>

        <!-- Community & Tutorials -->
        <div class="grid grid-cols-1 gap-5 md:grid-cols-2">
            @foreach ($support['community'] as $card)
                <div class="flex items-start gap-5 rounded-xl border border-gray-200 bg-white p-6 dark:border-gray-800 dark:bg-gray-900">
                    <span class="flex h-12 w-12 shrink-0 items-center justify-center rounded-lg bg-violet-50 text-brandColor dark:bg-gray-800">
                        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            {!! $icons[$card['icon']] !!}
                        </svg>
                    </span>

                    <div>
                        <p class="mb-2 text-lg font-semibold text-gray-800 dark:text-white">@lang($card['title'])</p>
                        <p class="mb-4 text-sm text-gray-600 dark:text-gray-300">@lang($card['description'])</p>
                        <a href="{{ $card['url'] }}" target="_blank" class="text-sm font-semibold text-brandColor hover:underline">
                            @lang($card['link_label'])
                        </a>
                    </div>
                </div>
            @endforeach
        </div>
```

- [ ] **Step 2: Extend the feature test**

In `tests/Feature/HelpTest.php`, add these assertions to the chained `get(...)` call from Task 4:

```php
        ->assertSee('Recent Support Tickets')
        ->assertSee('Still need a hand?')
        ->assertSee('Community Forums')
        ->assertSee('Video Tutorials')
```

(Insert them into the existing fluent chain before the final `;`.)

- [ ] **Step 3: Run the test + visually verify**

Run: `./vendor/bin/pest --filter=HelpTest`
Expected: PASS.

Load `/admin/help`. Confirm the placeholder tickets table, the purple "Still need a hand?" CTA banner, and the two community cards render correctly and responsively, with dark mode intact.

- [ ] **Step 4: Commit**

```bash
git add packages/Webkul/Admin/src/Resources/views/help/index.blade.php \
  tests/Feature/HelpTest.php
git commit -m "feat(admin): add static tickets table, contact CTA and community cards"
```

---

## Task 6: Final verification

**Files:** none (verification only)

- [ ] **Step 1: Run the full Feature suite**

Run: `./vendor/bin/pest --testsuite=Feature`
Expected: all tests pass, including `HelpTest`. Investigate and fix any regression before continuing.

- [ ] **Step 2: Lint PHP with Pint (changed files only)**

Run: `./vendor/bin/pint packages/Webkul/Admin/src tests/Feature/HelpTest.php`
Expected: no style violations (Pint auto-fixes; review and amend if it changes files).

- [ ] **Step 3: Verify ACL toggling**

In the admin panel: Settings → Roles → create or edit a role with `permission_type = custom`. Confirm a "Help" permission checkbox is present. With it unchecked, log in as a user with that role and confirm the Help menu item is hidden; with it checked, confirm it appears.

Expected: Help visibility follows the ACL permission for custom roles, and is always present for full-access roles.

- [ ] **Step 4: Final build + smoke test**

Run: `cd packages/Webkul/Admin && npm run build && cd -`
Then load `/admin/help` once more (hard refresh) to confirm the production CSS includes `.icon-help` and the page is pixel-consistent with the design in both light and dark mode.

- [ ] **Step 5: Commit any Pint/build changes**

```bash
git add -A
git commit -m "chore(admin): lint and rebuild assets for help page" || echo "nothing to commit"
```

---

## Self-review notes

- **Spec coverage:** menu item (Task 1/2), ACL entry (Task 1, verified Task 6), static help hub page with hero/services/resources/CTA/community (Tasks 4–5), static placeholder tickets table (Task 5), config-driven content + lang (Task 3), custom `.icon-help` (Task 2), search bar omitted (not built), no DB/model/ticket backend (none added). All spec sections map to a task.
- **Type/name consistency:** the view uses `$support['services'|'resources'|'community'|'contact_url']` exactly as defined in `Config/support.php` (Task 3); card keys `icon/title/description/url/url_label` (services/resources) and `icon/title/description/url/link_label` (community) match the partial and community loop; lang keys under `admin::app.help.index.*` referenced in config/views all exist in Task 3's lang block; the `$icons` map keys (`cloud/support/services/extensions/docs/api/community/video`) match every card's `icon` value.
- **Open risk flagged inline:** `icon-arrow-up-right` glyph may be absent from icomoon (fallback noted in Task 4, Step 2).
```
