<?php

namespace App\Http\Middleware;

use App\Http\Services\MLSuggestionService;
use App\Http\Services\MarketDataService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\Response;

class RunInlineAutomation
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if (!(bool) config('haarray.inline_automation.enabled', true)) {
            return $response;
        }

        $user = $request->user();
        if (!$user || !$request->isMethod('GET')) {
            return $response;
        }

        if ($this->shouldSkip($request)) {
            return $response;
        }

        $suggestionsEvery = max(60, (int) config('haarray.inline_automation.suggestions_every_seconds', 900));
        $marketEvery = max(300, (int) config('haarray.inline_automation.market_refresh_every_seconds', 3600));

        try {
            if ($this->shouldRunKey('haarray:inline:suggestions:user:' . (int) $user->id, $suggestionsEvery)) {
                app(MLSuggestionService::class)->generateForUser($user);
            }
        } catch (\Throwable) {
            // Never break page flow for inline automation failures.
        }

        try {
            if ($this->shouldRunKey('haarray:inline:market:global', $marketEvery)) {
                app(MarketDataService::class)->forceRefresh();
            }
        } catch (\Throwable) {
            // Never break page flow for inline automation failures.
        }

        return $response;
    }

    private function shouldRunKey(string $cacheKey, int $ttlSeconds): bool
    {
        return Cache::add($cacheKey, (string) now()->timestamp, now()->addSeconds(max(60, $ttlSeconds)));
    }

    private function shouldSkip(Request $request): bool
    {
        $path = trim($request->path(), '/');
        if ($path === '' || $path === 'up') {
            return true;
        }

        if ($request->is('ui/*')) {
            return true;
        }

        if ($request->is('notifications/*')) {
            return true;
        }

        return false;
    }
}

