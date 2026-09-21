# Upgrading existing applications

Run `composer update jeremykenedy/laravel-observability` as part of your normal dependency update process. No package files are automatically published and no framework selections are changed.

## Preserved behavior

- PHP 8.2 and Laravel 10 remain allowed by Composer.
- Tailwind and Blade remain the defaults when no framework has been configured.
- Existing UI Kit settings remain fallbacks until you choose package-specific settings.
- The `health`, `health.providers`, `health.uptime`, and `health.dashboard` route names and their default paths remain available.
- The `observability::dashboard` view namespace, `health-dashboard` Livewire component, and `@observabilityScripts` directive remain available.
- Published configuration, Blade views, and SPA components are never overwritten by install, update, or switch commands, including install with `--force`.

## Adopting the updated dashboard

Unpublished Blade views use the new dashboard after updating. Published custom views continue to use your existing files. Compare them against the package's `resources/views` directory before making changes.

SPA components import `../../shared/observability.js` and `../../shared/observability.css`. The commands publish these helpers alongside the selected frontend, preserving the existing `resources/js/Pages/Observability/HealthDashboard` path. If you already published a component, compare it with the new source and merge the changes, then rebuild your application assets.

The Blade dashboard still extends `layouts.app` and uses its `content` section. Its JavaScript no longer depends on the layout yielding `footer_scripts`, and it does not require Alpine.js. The dashboard's appearance choice is scoped to the component.

## Intentional corrections

- Install and update preserve published config instead of resetting it. Use explicit `vendor:publish --force` only when you intend to replace a reviewed file.
- Invalid framework flags and missing or unwritable environment files cause command failure before publishing files.
- Framework commands save `OBSERVABILITY_CSS` and `OBSERVABILITY_FRONTEND`, leaving UI Kit settings for other packages alone.
- Provider lists are JSON arrays even when some entries are disabled. Providers with type `both` appear in both backend and frontend groups.
- Health probes use unique cache and file names and verify storage writes, reads, and cleanup. Failed writes now correctly produce HTTP 503.
- Health failures keep the existing status and message fields but no longer include raw exception text such as database credentials.
- `observability.enabled=false` now disables package routes.
- Uptime requests have bounded connection and request timeouts and handle failed or malformed responses.

Laravel 10 and 11 compatibility jobs allow older dependencies with known advisories. This exception is limited to CI and does not alter dependency policy in consuming applications. Keep production applications on a maintained framework release where possible.
