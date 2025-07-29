<?php

namespace App\Http\Middleware;

use App\Constants\Status;
use App\Models\Language;
use Closure;
use Illuminate\Http\Request;

class LanguageMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  Request  $request
     * @param  Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        $lang = $this->determineLanguage($request);

        // Set application locale
        app()->setLocale($lang);

        // Store in session for future requests
        session()->put('lang', $lang);

        return $next($request);
    }

    /**
     * Determine the language to use
     */
    protected function determineLanguage(Request $request): string
    {
        // 2. Check custom header (X-Language)
        if ($request->hasHeader('X-Language')) {
            return $this->validateLanguage($request->header('X-Language'));
        }
        // 3. Check session
        if (session()->has('lang')) {
            return $this->validateLanguage(session('lang'));
        }
        // 1. Check Accept-Language header first
        // $headerLang = $request->getPreferredLanguage(config('app.available_locales'));
        $headerLang = "ar";

        if ($headerLang) {
            return $this->validateLanguage($headerLang);
        }

        // 4. Fallback to database default
        $language = Language::where('is_default', Status::ENABLE)->first();

        // 5. Ultimate fallback
        return $language ? $language->code : config('app.fallback_locale');
    }

    /**
     * Validate language code against available languages
     */
    protected function validateLanguage(string $code): string
    {
        $availableLanguages = Language::pluck('code')
            ->toArray();

        return in_array($code, $availableLanguages) ? $code : $this->getDefaultLanguageCode();
    }

    /**
     * Get default language code
     */
    protected function getDefaultLanguageCode(): string
    {
        $language = Language::where('is_default', Status::ENABLE)->first();
        return $language ? $language->code : 'en';
    }
}
