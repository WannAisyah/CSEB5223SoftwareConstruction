@if(config('services.turnstile.site_key'))
    <div class="cf-turnstile" data-sitekey="{{ config('services.turnstile.site_key') }}"></div>
    @error('cf-turnstile-response')<div class="error">{{ $message }}</div>@enderror
    @pushOnce('scripts')
        <script src="https://challenges.cloudflare.com/turnstile/v0/api.js" async defer></script>
    @endPushOnce
@endif
