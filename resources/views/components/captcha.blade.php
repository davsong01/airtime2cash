@php
    $captchaSettings = getSettings()?->captcha_settings ?? [];
@endphp

@if(isset($captchaSettings['captcha_settings_status']) && $captchaSettings['captcha_settings_status'] == 'yes')
    @if(in_array($captchaSettings['captcha_settings_provider'], ['all','google']))
        @php
            $sitekey = $captchaSettings['google']['RECAPTCHA_SITE_KEY'] ?? '';
        @endphp
        @section('CAPTCHA_SITEKEY', $sitekey )
        <input type="hidden" id="g-recaptcha-response" name="g-recaptcha-response">
        <script>
            grecaptcha.ready(function() {
                grecaptcha.execute('{{ $sitekey }}', {action: 'submit'}).then(function(token) {
                    document.getElementById('g-recaptcha-response').value = token;
                });
            });
        </script>
    @endif
    @if(in_array($captchaSettings['captcha_settings_provider'], ['all','simple']))
        <div class="auth-captcha {{ $groupClass ?? '' }}">
            <div>
                <span class="auth-captcha-label">Security check</span>
                <span class="auth-captcha-question">{{ getCaptchaQuestion() }}</span>
            </div>
            <input class="form-control auth-captcha-input" name="_answer" type="number" inputmode="numeric" aria-label="Captcha answer" required>
        </div>
    @endif
@endif
