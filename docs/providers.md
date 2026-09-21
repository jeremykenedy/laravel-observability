# Provider reference

These entries describe integrations supported by the configuration. Install and configure each service's own SDK or agent using its documentation. Enabling an entry controls discovery and display; this package does not install server agents, create external monitors, run browser audits, or collect APM metrics itself.

| Provider | Type | Website | Documentation | API Reference |
| :--- | :--- | :--- | :--- | :--- |
| [Sentry](https://sentry.io/) | Backend | [sentry.io](https://sentry.io/) | [Laravel Guide](https://docs.sentry.io/platforms/php/guides/laravel/) | [API Docs](https://docs.sentry.io/api/) |
| [Bugsnag](https://www.bugsnag.com/) | Backend | [bugsnag.com](https://www.bugsnag.com/) | [Laravel Guide](https://docs.bugsnag.com/platforms/php/laravel/) | [API Docs](https://docs.bugsnag.com/) |
| [Flare](https://flareapp.io/) | Backend | [flareapp.io](https://flareapp.io/) | [Setup Guide](https://flareapp.io/docs/general/projects) | -- |
| [Rollbar](https://rollbar.com/) | Both | [rollbar.com](https://rollbar.com/) | [Laravel Guide](https://docs.rollbar.com/docs/laravel) | [API Docs](https://docs.rollbar.com/reference) |
| [Honeybadger](https://www.honeybadger.io/) | Both | [honeybadger.io](https://www.honeybadger.io/) | [Laravel Guide](https://docs.honeybadger.io/lib/php/integration/laravel/) | [API Docs](https://docs.honeybadger.io/api/) |
| [Airbrake](https://airbrake.io/) | Backend | [airbrake.io](https://airbrake.io/) | [Laravel Guide](https://docs.airbrake.io/docs/platforms/framework/php/laravel/) | [API Docs](https://airbrake.io/docs/devops-tools/api/) |
| [Raygun](https://raygun.com/) | Both | [raygun.com](https://raygun.com/) | [Laravel Guide](https://raygun.com/documentation/language-guides/php/crash-reporting/laravel/) | [API Docs](https://raygun.com/documentation/product-guides/crash-reporting/api/) |
| [Laravel Exception Notifier](https://github.com/jeremykenedy/laravel-exception-notifier) | Backend | [GitHub](https://github.com/jeremykenedy/laravel-exception-notifier) | [README](https://github.com/jeremykenedy/laravel-exception-notifier#readme) | -- |
| [New Relic](https://newrelic.com/) | Backend | [newrelic.com](https://newrelic.com/) | [PHP Agent Docs](https://docs.newrelic.com/docs/apm/agents/php-agent/) | [API Docs](https://docs.newrelic.com/docs/apis/rest-api-v2/) |
| [Datadog](https://www.datadoghq.com/) | Both | [datadoghq.com](https://www.datadoghq.com/) | [PHP Tracing](https://docs.datadoghq.com/tracing/trace_collection/dd_libraries/php/) | [API Docs](https://docs.datadoghq.com/api/latest/) |
| [AppSignal](https://www.appsignal.com/) | Backend | [appsignal.com](https://www.appsignal.com/) | [PHP Docs](https://docs.appsignal.com/php/) | [API Docs](https://docs.appsignal.com/api/) |
| [Loggly](https://www.loggly.com/) | Backend | [loggly.com](https://www.loggly.com/) | [PHP Logging](https://documentation.solarwinds.com/en/success_center/loggly/content/admin/php-logging.htm) | [API Docs](https://documentation.solarwinds.com/en/success_center/loggly/content/admin/api-overview.htm) |
| [LogRocket](https://logrocket.com/) | Frontend | [logrocket.com](https://logrocket.com/) | [Quickstart](https://docs.logrocket.com/docs/quickstart) | [API Docs](https://docs.logrocket.com/reference/) |
| [Instabug](https://www.instabug.com/) | Frontend | [instabug.com](https://www.instabug.com/) | [Web Integration](https://docs.instabug.com/docs/web-integration) | [API Docs](https://docs.instabug.com/reference/) |
| [Gleap](https://gleap.io/) | Frontend | [gleap.io](https://gleap.io/) | [JavaScript SDK](https://docs.gleap.io/docs/javascript-sdk) | [API Docs](https://docs.gleap.io/reference/) |
| [Firebase Crashlytics](https://firebase.google.com/) | Frontend | [firebase.google.com](https://firebase.google.com/) | [Crashlytics Docs](https://firebase.google.com/docs/crashlytics) | [REST API](https://firebase.google.com/docs/reference/rest/) |
| [Memfault](https://memfault.com/) | Frontend | [memfault.com](https://memfault.com/) | [Docs](https://docs.memfault.com/) | [REST API](https://docs.memfault.com/docs/cloud/rest-api/) |
| [Ghost Inspector](https://ghostinspector.com/) | Testing | [ghostinspector.com](https://ghostinspector.com/) | [Docs](https://ghostinspector.com/docs/) | [API Docs](https://ghostinspector.com/docs/api/) |
| [Google Lighthouse](https://developer.chrome.com/docs/lighthouse/overview/) | Testing | [Chrome DevTools](https://developer.chrome.com/docs/lighthouse/overview/) | [GitHub](https://github.com/GoogleChrome/lighthouse) | [PageSpeed API](https://developers.google.com/speed/docs/insights/v5/get-started) |
| [Spatie Link Checker](https://github.com/spatie/laravel-link-checker) | Testing | [GitHub](https://github.com/spatie/laravel-link-checker) | [Usage Guide](https://github.com/spatie/laravel-link-checker#usage) | -- |
| [SSL Labs](https://www.ssllabs.com/ssltest/) | Testing | [ssllabs.com](https://www.ssllabs.com/ssltest/) | [About](https://www.ssllabs.com/ssltest/) | [API Docs](https://github.com/ssllabs/ssllabs-scan/blob/master/ssllabs-api-docs-v3.md) |
| [Buddy.Works](https://buddy.works/) | Testing | [buddy.works](https://buddy.works/) | [Docs](https://buddy.works/docs/) | [API Docs](https://buddy.works/docs/api/getting-started/) |
| [UptimeRobot](https://uptimerobot.com/) | Uptime | [uptimerobot.com](https://uptimerobot.com/) | [API Docs](https://uptimerobot.com/api/) | [API Docs](https://uptimerobot.com/api/) |
| [StatusCake](https://www.statuscake.com/) | Uptime | [statuscake.com](https://www.statuscake.com/) | [Knowledge Base](https://www.statuscake.com/kb/) | [API v1](https://www.statuscake.com/api/v1/) |

## Credentials

Set the relevant enabled flag and credentials in your environment, then clear cached configuration:

```dotenv
SENTRY_ENABLED=true
SENTRY_LARAVEL_DSN=https://your-public-dsn
UPTIMEROBOT_ENABLED=true
UPTIMEROBOT_API_KEY=your-key
```

```bash
php artisan config:clear
```

See `config/observability.php` for each provider's keys. The installer offers credential prompts with hidden input. Use `observability:update` to change existing credentials or enable and disable providers.

## Browser SDKs

Install and initialize browser SDKs through your application's bundler following the provider's documentation. Never expose a private server API key in browser code.

The existing `@observabilityScripts` directive remains available for configured `js_snippet` values. String placeholders are escaped before insertion. It does not download SDKs or resolve bare npm imports: the default LogRocket import belongs in your application's bundled JavaScript, and Gleap requires its SDK to be loaded first. Custom snippets are executable code and should come only from trusted application configuration.

## Uptime status

The authenticated `/health/uptime` endpoint returns enabled UptimeRobot and StatusCake results. A missing key, failed request, or malformed response returns `null` for that provider. Requests have a three-second connection timeout, a ten-second request timeout, and at most one retry.

Uptime monitors must be created in the service's own dashboard. The package does not expose uptime webhook endpoints. The existing `monitor_ids` setting is retained for compatibility but is not currently applied to requests.
