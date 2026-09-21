<?php

declare(strict_types=1);

namespace Jeremykenedy\LaravelObservability\Http\Controllers;

use Illuminate\Routing\Controller;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class AssetController extends Controller
{
    public function __invoke(string $asset): BinaryFileResponse
    {
        abort_unless(in_array($asset, ['observability.css', 'observability.js', 'blade.js'], true), 404);

        return response()->file(__DIR__.'/../../../resources/js/shared/'.$asset, [
            'Content-Type' => str_ends_with($asset, '.css') ? 'text/css' : 'text/javascript',
            'Cache-Control' => 'public, no-cache',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }
}
