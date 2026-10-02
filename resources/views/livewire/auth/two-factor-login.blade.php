<div class="position-relative">
  <div class="authentication-wrapper authentication-cover">
    <div class="authentication-inner row m-0 min-vh-100 w-100">

      {{-- PANEL IZQUIERDO --}}
      <div class="d-none d-lg-flex col-lg-7 col-xl-8 flex-column align-items-center justify-content-between auth-left-cover position-relative overflow-hidden">
        <div class="auth-left-bg"></div>
        <div class="auth-bubble auth-bubble-1"></div>
        <div class="auth-bubble auth-bubble-2"></div>

        {{-- Header --}}
        <div class="w-100 px-10 pt-10 position-relative z-1">
          <div class="d-flex align-items-center gap-3">
            <div class="auth-inst-emblem"><i class="icon-base ri ri-building-4-line fs-4 text-white"></i></div>
            <div>
              <div class="text-white fw-bold fs-6 lh-1">INSTITUTO VARGAS II</div>
              <div class="text-white-50 small">Sede El Paraíso, Venezuela</div>
            </div>
            <div class="ms-auto d-flex align-items-center gap-2 auth-status-chip">
              <span class="auth-pulse-dot"></span>
              <span class="text-white-75 small fw-medium">2FA Activo</span>
            </div>
          </div>
        </div>

        {{-- Ilustración --}}
        <div class="auth-illustration-wrapper position-relative z-1 px-10 text-center">
          <div class="mb-4">
            <span class="auth-badge-tag"><i class="icon-base ri ri-shield-keyhole-line me-1"></i>Verificación en Dos Pasos</span>
            <h2 class="text-white fw-bolder mt-3 mb-2 auth-main-headline">
              Acceso con<br><span class="auth-gradient-word">Doble Factor</span>
            </h2>
            <p class="text-white-60 fs-6">
              Una capa adicional de seguridad para<br>proteger tu cuenta institucional.
            </p>
          </div>
          <div class="auth-illustration-img-wrap">
            <img src="/materialize/assets/img/illustrations/auth-two-steps-illustration-light.png" class="auth-illustration-img" alt="2FA" data-app-light-img="illustrations/auth-two-steps-illustration-light.png" data-app-dark-img="illustrations/auth-two-steps-illustration-dark.png" />
            <img src="/materialize/assets/img/illustrations/auth-cover-register-mask-light.png" class="auth-mask-img" alt="mask" data-app-light-img="illustrations/auth-cover-register-mask-light.png" data-app-dark-img="illustrations/auth-cover-register-mask-dark.png" />
          </div>
        </div>

        {{-- Stats --}}
        <div class="w-100 px-10 pb-10 position-relative z-1">
          <div class="row g-3">
            <div class="col-4">
              <div class="auth-stat-card">
                <div class="auth-stat-icon"><i class="icon-base ri ri-smartphone-line"></i></div>
                <div class="text-white fw-semibold small">Authenticator</div>
                <div class="text-white-50" style="font-size:0.7rem">Google / Authy</div>
              </div>
            </div>
            <div class="col-4">
              <div class="auth-stat-card">
                <div class="auth-stat-icon"><i class="icon-base ri ri-timer-2-line"></i></div>
                <div class="text-white fw-semibold small">30 segundos</div>
                <div class="text-white-50" style="font-size:0.7rem">Código TOTP</div>
              </div>
            </div>
            <div class="col-4">
              <div class="auth-stat-card">
                <div class="auth-stat-icon"><i class="icon-base ri ri-lock-password-line"></i></div>
                <div class="text-white fw-semibold small">6 Dígitos</div>
                <div class="text-white-50" style="font-size:0.7rem">Código único</div>
              </div>
            </div>
          </div>
        </div>
      </div>

      {{-- PANEL DERECHO --}}
      <div class="d-flex col-12 col-lg-5 col-xl-4 align-items-center authentication-bg position-relative py-sm-12 px-12 py-6">
        <div class="w-px-400 mx-auto pt-12 pt-lg-0">

          {{-- Logo + Nombre --}}
          <div class="app-brand mb-7">
            <a href="{{ url('/') }}" class="app-brand-link gap-2">
              <span class="app-brand-logo demo">
                <span class="text-primary">
                  <svg width="32" height="20" viewBox="0 0 38 20" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M30.0944 2.22569C29.0511 0.444187 26.7508 -0.172113 24.9566 0.849138C23.1623 1.87039 22.5536 4.14247 23.5969 5.92397L30.5368 17.7743C31.5801 19.5558 33.8804 20.1721 35.6746 19.1509C37.4689 18.1296 38.0776 15.8575 37.0343 14.076L30.0944 2.22569Z" fill="currentColor" />
                    <path d="M22.9676 2.22569C24.0109 0.444187 26.3112 -0.172113 28.1054 0.849138C29.8996 1.87039 30.5084 4.14247 29.4651 5.92397L22.5251 17.7743C21.4818 19.5558 19.1816 20.1721 17.3873 19.1509C15.5931 18.1296 14.9843 15.8575 16.0276 14.076L22.9676 2.22569Z" fill="currentColor" />
                    <path d="M14.9558 2.22569C13.9125 0.444187 11.6122 -0.172113 9.818 0.849138C8.02377 1.87039 7.41502 4.14247 8.45833 5.92397L15.3983 17.7743C16.4416 19.5558 18.7418 20.1721 20.5361 19.1509C22.3303 18.1296 22.9391 15.8575 21.8958 14.076L14.9558 2.22569Z" fill="currentColor" />
                    <path d="M7.82901 2.22569C8.87231 0.444187 11.1726 -0.172113 12.9668 0.849138C14.7611 1.87039 15.3698 4.14247 14.3265 5.92397L7.38656 17.7743C6.34325 19.5558 4.04298 20.1721 2.24875 19.1509C0.454514 18.1296 -0.154233 15.8575 0.88907 14.076L7.82901 2.22569Z" fill="currentColor" />
                  </svg>
                </span>
              </span>
              <span class="app-brand-text demo text-heading fw-bold">Instituto Vargas II</span>
            </a>
          </div>

          {{-- Header --}}
          <h4 class="mb-1">{{ __('auth_ui.two_factor_title') }} 🔐</h4>
          <p class="mb-5 text-muted">{{ __('auth_ui.two_factor_subtitle') }}</p>

          @if (session()->has('error'))
            <div class="alert alert-danger alert-dismissible fade show mb-5" role="alert">
              <div class="d-flex align-items-center gap-2">
                <i class="icon-base ri ri-error-warning-fill"></i>
                <span>{{ session('error') }}</span>
              </div>
              <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
          @endif

          {{-- 2FA Form with 6 OTP inputs --}}
          <form wire:submit.prevent="verifyCode" id="twoFactorForm" class="mb-5">
            <input type="hidden" wire:model="latitude" id="latitude">
            <input type="hidden" wire:model="longitude" id="longitude">

            <p class="text-muted small fw-semibold mb-2">Ingresa tu código de 6 dígitos:</p>

            <div class="mb-3">
              <div class="d-flex align-items-center justify-content-between gap-2" id="otpWrapper">
                @for ($i = 0; $i < 6; $i++)
                  <input type="tel" class="form-control text-center otp-box fw-bold @error('code') border-danger @enderror"
                    maxlength="1" data-index="{{ $i }}" inputmode="numeric" pattern="[0-9]"
                    autocomplete="off" id="otp_{{ $i }}" {{ $i === 0 ? 'autofocus' : '' }} />
                @endfor
                <input type="hidden" name="code" wire:model="code" id="hiddenCode" />
              </div>
            </div>

            @error('code')
              <p class="text-danger small mb-3"><i class="icon-base ri ri-alert-line me-1"></i>{{ $message }}</p>
            @enderror

            <p class="text-muted mb-4" style="font-size:0.78rem">
              <i class="icon-base ri ri-timer-line me-1"></i>El código expira en 30 segundos. Usa el nuevo si expiró.
            </p>

            <button class="btn btn-primary d-grid w-100 mb-4" type="submit" wire:loading.attr="disabled" wire:target="verifyCode" id="twoFaSubmit">
              <span wire:loading.remove wire:target="verifyCode">
                <i class="icon-base ri ri-shield-check-line me-2"></i>{{ __('auth_ui.verify_2fa') }}
              </span>
              <span wire:loading wire:target="verifyCode">
                <span class="spinner-border spinner-border-sm me-2" role="status"></span>Verificando...
              </span>
            </button>
          </form>

          <div class="text-center">
            <a href="{{ route('login') }}" class="d-flex align-items-center justify-content-center">
              <i class="icon-base ri ri-arrow-left-s-line icon-20px me-1"></i>
              {{ __('auth_ui.back_to_login') }}
            </a>
          </div>

          <p class="text-center text-muted mt-5 mb-0" style="font-size:0.75rem">
            &copy; {{ date('Y') }} U.E. Instituto Vargas II &middot; El Para&iacute;so
          </p>
        </div>
      </div>

    </div>
  </div>
</div>

@push('styles')
<style>
.auth-left-cover { min-height: 100vh; position: relative; }
.auth-left-bg { position: absolute; inset: 0; background: linear-gradient(145deg, #1a1363 0%, #2d2aa5 25%, #4338ca 50%, #1e40af 75%, #0f172a 100%); z-index: 0; }
.auth-bubble { position: absolute; border-radius: 50%; animation: floatBubble 8s ease-in-out infinite; z-index: 0; }
.auth-bubble-1 { width: 350px; height: 350px; top: -80px; right: -60px; background: radial-gradient(circle, #a5b4fc, transparent); opacity: 0.12; }
.auth-bubble-2 { width: 200px; height: 200px; bottom: 120px; left: -40px; background: radial-gradient(circle, #818cf8, transparent); opacity: 0.08; animation-delay: 3s; }
@keyframes floatBubble { 0%, 100% { transform: translateY(0) scale(1); } 50% { transform: translateY(-20px) scale(1.05); } }
.z-1 { z-index: 1; }
.auth-inst-emblem { width: 42px; height: 42px; background: rgba(255,255,255,0.15); border: 1px solid rgba(255,255,255,0.25); border-radius: 12px; display: flex; align-items: center; justify-content: center; backdrop-filter: blur(8px); }
.auth-status-chip { background: rgba(113,221,55,0.15); border: 1px solid rgba(113,221,55,0.3); border-radius: 50rem; padding: 0.25rem 0.75rem; backdrop-filter: blur(8px); }
.auth-pulse-dot { width: 7px; height: 7px; background: #71dd37; border-radius: 50%; box-shadow: 0 0 8px #71dd37; animation: pulseGlow 2s ease-in-out infinite; }
@keyframes pulseGlow { 0%, 100% { opacity: 1; transform: scale(1); } 50% { opacity: 0.6; transform: scale(1.35); } }
.text-white-50 { color: rgba(255,255,255,0.5) !important; }
.text-white-60 { color: rgba(255,255,255,0.65) !important; }
.text-white-75 { color: rgba(255,255,255,0.8) !important; }
.auth-badge-tag { display: inline-flex; align-items: center; background: rgba(255,255,255,0.12); border: 1px solid rgba(255,255,255,0.2); color: rgba(255,255,255,0.9); padding: 0.35rem 1rem; border-radius: 50rem; font-size: 0.8rem; font-weight: 500; backdrop-filter: blur(8px); }
.auth-main-headline { font-size: 1.9rem; line-height: 1.25; letter-spacing: -0.02em; }
.auth-gradient-word { background: linear-gradient(90deg, #a5b4fc, #f9a8d4); -webkit-background-clip: text; -webkit-text-fill-color: transparent; }
.auth-illustration-wrapper { width: 100%; max-width: 580px; }
.auth-illustration-img-wrap { position: relative; display: inline-block; width: 100%; }
.auth-illustration-img { position: relative; z-index: 2; width: 100%; max-width: 400px; filter: drop-shadow(0 20px 40px rgba(0,0,0,0.3)); }
.auth-mask-img { position: absolute; inset: auto 0 0 0; z-index: 1; width: 100%; opacity: 0.5; }
.auth-stat-card { background: rgba(255,255,255,0.08); border: 1px solid rgba(255,255,255,0.15); border-radius: 14px; padding: 1rem; text-align: center; backdrop-filter: blur(10px); transition: all 0.3s ease; }
.auth-stat-card:hover { background: rgba(255,255,255,0.14); transform: translateY(-2px); }
.auth-stat-icon { width: 34px; height: 34px; background: rgba(255,255,255,0.15); border-radius: 8px; display: flex; align-items: center; justify-content: center; margin: 0 auto 0.5rem; font-size: 1.1rem; color: #a5b4fc; }
/* Right Panel */
.auth-form-wrapper { max-width: 400px; }
.auth-logo-box { width: 44px; height: 44px; background: rgba(105,108,255,0.1); border: 1px solid rgba(105,108,255,0.2); border-radius: 12px; display: flex; align-items: center; justify-content: center; box-shadow: 0 2px 12px rgba(105,108,255,0.15); }
.fs-sm { font-size: 0.875rem; }
/* OTP boxes */
.otp-box {
  flex: 1; max-width: 52px; min-width: 38px;
  height: 58px !important; padding: 0 !important;
  border-radius: 10px !important; font-size: 1.5rem !important;
  font-weight: 800 !important; border: 2px solid var(--bs-border-color);
  transition: all 0.2s ease; caret-color: #696cff;
}
.otp-box:focus { border-color: #696cff !important; box-shadow: 0 0 0 3px rgba(105,108,255,0.15) !important; outline: none; }
</style>
@endpush

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
  const wrap = document.getElementById('otpWrapper');
  const hidden = document.getElementById('hiddenCode');
  const submit = document.getElementById('twoFaSubmit');
  const inputs = wrap ? Array.from(wrap.querySelectorAll('.otp-box')) : [];

  function sync() {
    const val = inputs.map(i => i.value).join('');
    if (hidden) hidden.value = val;
    inputs.forEach(i => i.classList.toggle('filled', i.value.length > 0));
    if (window.Livewire) {
      const id = document.querySelector('[wire\\:id]')?.getAttribute('wire:id');
      if (id) { const c = Livewire.find(id); if (c) c.set('code', val); }
    }
    return val;
  }

  inputs.forEach((inp, idx) => {
    inp.addEventListener('keydown', e => {
      if (e.key === 'Backspace') {
        if (!inp.value && idx > 0) { inputs[idx-1].value=''; inputs[idx-1].focus(); }
        else inp.value = '';
        sync(); e.preventDefault();
      } else if (e.key === 'ArrowLeft' && idx > 0) { inputs[idx-1].focus(); e.preventDefault(); }
      else if (e.key === 'ArrowRight' && idx < inputs.length-1) { inputs[idx+1].focus(); e.preventDefault(); }
    });
    inp.addEventListener('input', function() {
      this.value = this.value.replace(/[^0-9]/g,'').slice(-1);
      const v = sync();
      if (this.value && idx < inputs.length-1) inputs[idx+1].focus();
      if (v.length === 6) setTimeout(() => submit.click(), 200);
    });
    inp.addEventListener('paste', e => {
      e.preventDefault();
      const p = (e.clipboardData||window.clipboardData).getData('text').replace(/[^0-9]/g,'').slice(0,6);
      p.split('').forEach((c,i) => { if(inputs[i]) inputs[i].value=c; });
      sync();
      const nxt = inputs.findIndex(i=>!i.value);
      (nxt !== -1 ? inputs[nxt] : inputs[5]).focus();
    });
  });
});

document.addEventListener('livewire:initialized', () => {
  if (navigator.geolocation) {
    navigator.geolocation.getCurrentPosition(pos => {
      @this.latitude = pos.coords.latitude;
      @this.longitude = pos.coords.longitude;
    }, () => {});
  }
});
</script>
@endpush