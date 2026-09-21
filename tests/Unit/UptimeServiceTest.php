<?php

declare(strict_types=1);

use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Jeremykenedy\LaravelObservability\Services\UptimeService;

it('posts UptimeRobot credentials as form data and returns monitors', function () {
    config(['observability.uptime.uptimerobot.api_key' => 'test-token']);
    Http::fake(['api.uptimerobot.com/*' => Http::response(['monitors' => [['id' => 12, 'status' => 2]]])]);
    expect($this->app->make(UptimeService::class)->getUptimeRobotStatus())->toBe([['id' => 12, 'status' => 2]]);
    Http::assertSent(fn (Request $request) => $request->hasHeader('Content-Type', 'application/x-www-form-urlencoded') && $request['api_key'] === 'test-token');
});

it('uses bearer authentication for StatusCake', function () {
    config(['observability.uptime.statuscake.api_key' => 'test-token']);
    Http::fake(['api.statuscake.com/*' => Http::response(['data' => [['id' => '42']]])]);
    expect($this->app->make(UptimeService::class)->getStatusCakeStatus())->toBe([['id' => '42']]);
    Http::assertSent(fn (Request $request) => $request->hasHeader('Authorization', 'Bearer test-token'));
});

it('returns null when the uptime service is unavailable', function (string $provider, string $method, string $url) {
    config(['observability.uptime.'.$provider.'.api_key' => 'test-token']);
    Http::fake([$url => Http::sequence()->push([], 503)->push([], 503)]);
    expect($this->app->make(UptimeService::class)->$method())->toBeNull();
    Http::assertSentCount(2);
})->with([
    ['uptimerobot', 'getUptimeRobotStatus', 'api.uptimerobot.com/*'],
    ['statuscake', 'getStatusCakeStatus', 'api.statuscake.com/*'],
]);

it('handles malformed upstream payloads', function () {
    config(['observability.uptime.statuscake.api_key' => 'test-token']);
    Http::fake(['api.statuscake.com/*' => Http::response(['data' => 'invalid'])]);
    expect($this->app->make(UptimeService::class)->getStatusCakeStatus())->toBeNull();
});

it('makes no requests without credentials', function () {
    Http::fake();
    $service = $this->app->make(UptimeService::class);
    expect($service->getStatusCakeStatus())->toBeNull()->and($service->getUptimeRobotStatus())->toBeNull();
    Http::assertNothingSent();
});
