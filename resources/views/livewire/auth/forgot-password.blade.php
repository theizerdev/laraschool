<div class="position-relative">
  <div class="authentication-wrapper authentication-cover">
    <div class="authentication-inner row m-0 min-vh-100 w-100">

      {{-- ══════════════════════════════════════════════════════════
           PANEL IZQUIERDO — Gradiente Institucional + Ilustración
      ══════════════════════════════════════════════════════════ --}}
      <div class="d-none d-lg-flex col-lg-7 col-xl-8 flex-column align-items-center justify-content-between auth-left-cover position-relative overflow-hidden">

        <div class="auth-left-bg"></div>
        <div class="auth-bubble auth-bubble-1"></div>
        <div class="auth-bubble auth-bubble-2"></div>
        <div class="auth-bubble auth-bubble-3"></div>

        {{-- Header institucional --}}
        <div class="w-100 px-10 pt-10 position-relative z-1">
          <div class="d-flex align-items-center gap-3">
            <div class="auth-inst-emblem">
              <i class="icon-base ri ri-building-4-line fs-4 text-white"></i>
            </div>
            <div>
              <div class="text-white fw-bold fs-6 lh-1">INSTITUTO VARGAS II</div>
              <div class="text-white-50 small">Sede El Paraíso, Venezuela</div>
            </div>
            <div class="ms-auto d-flex align-items-center gap-2 auth-status-chip">
              <span class="auth-pulse-dot"></span>
              <span class="text-white-75 small fw-medium">Recuperación</span>
            </div>
          </div>
        </div>

        {{-- Ilustración central --}}
        <div class="auth-illustration-wrapper position-relative z-1 px-10 text-center">
          <div class="mb-4">
            <span class="auth-badge-tag">
              <i class="icon-base ri ri-key-2-line me-1"></i>
              Restablecimiento de Contraseña
            </span>
            <h2 class="text-white fw-bolder mt-3 mb-2 auth-main-headline">
              ¿Olvidaste tu<br>
              <span class="auth-gradient-word">Contraseña?</span>
            </h2>
            <p class="text-white-60 fs-6">
              No te preocupes. Enviaremos un enlace seguro<br>
              a tu correo para restablecer tu acceso.
            </p>
          </div>
          <div class="auth-illustration-img-wrap">
            <img
              src="/materialize/assets/img/illustrations/auth-forgot-password-illustration-light.png"
              class="auth-illustration-img"
              alt="Recuperar contraseña"
              data-app-light-img="illustrations/auth-forgot-password-illustration-light.png"
              data-app-dark-img="illustrations/auth-forgot-password-illustration-dark.png"
            />
            <img
              src="/materialize/assets/img/illustrations/auth-cover-forgot-password-mask-light.png"
              class="auth-mask-img"
              alt="mask"
              data-app-light-img="illustrations/auth-cover-forgot-password-mask-light.png"
              data-app-dark-img="illustrations/auth-cover-forgot-password-mask-dark.png"
            />
          </div>
        </div>

        {{-- Info cards --}}
        <div class="w-100 px-10 pb-10 position-relative z-1">
          <div class="row g-3">
            <div class="col-4">
              <div class="auth-stat-card">
                <div class="auth-stat-icon"><i class="icon-base ri ri-mail-send-line"></i></div>
                <div class="text-white fw-semibold small">Enlace Seguro</div>
                <div class="text-white-50" style="font-size:0.7rem">Firmado y único</div>
              </div>
            </div>
            <div class="col-4">
              <div class="auth-stat-card">
                <div class="auth-stat-icon"><i class="icon-base ri ri-timer-flash-line"></i></div>
                <div class="text-white fw-semibold small">60 Minutos</div>
                <div class="text-white-50" style="font-size:0.7rem">Enlace válido</div>
              </div>
            </div>
            <div class="col-4">
              <div class="auth-stat-card">
                <div class="auth-stat-icon"><i class="icon-base ri ri-shield-check-line"></i></div>
                <div class="text-white fw-semibold small">Protegido</div>
                <div class="text-white-50" style="font-size:0.7rem">SSL cifrado</div>
              </div>
            </div>
          </div>
        </div>

      </div>
      {{-- /Panel Izquierdo --}}

      {{-- ══════════════════════════════════════════════════════════
           PANEL DERECHO — Formulario
      ══════════════════════════════════════════════════════════ --}}
      {{-- PANEL DERECHO — Formulario --}}
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

          {{-- Heading --}}
          <h4 class="mb-1">{{ __('auth_ui.forgot_password_title') }} 🔒</h4>
          <p class="mb-5 text-muted">{{ __('auth_ui.forgot_password_subtitle') }}</p>

          {{-- Alerts --}}
          @if (session()->has('status'))
            <div class="alert alert-success alert-dismissible fade show mb-5" role="alert">
              <div class="d-flex align-items-center gap-2">
                <i class="icon-base ri ri-checkbox-circle-fill"></i>
                <span>{{ session('status') }}</span>
              </div>
              <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
          @endif
          @if ($successMessage)
            <div class="alert alert-success alert-dismissible fade show mb-5" role="alert">
              <div class="d-flex align-items-center gap-2">
                <i class="icon-base ri ri-checkbox-circle-fill"></i>
                <span>{{ $successMessage }}</span>
              </div>
              <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
          @endif

          {{-- Form --}}
          <form wire:submit="sendResetLink" class="mb-5">
            <div class="form-floating form-floating-outline mb-5 form-control-validation">
              <input
                type="text"
                class="form-control @if($hasError('email')) is-invalid @endif"
                id="email"
                name="email"
                wire:model="email"
                placeholder="correo@institutovargas.edu"
                autocomplete="email"
                autofocus />
              <label for="email">{{ __('auth_ui.email') }}</label>
              @if($hasError('email'))
                <span class="invalid-feedback d-block">{{ $getError('email') }}</span>
              @endif
            </div>

            <button class="btn btn-primary d-grid w-100 mb-5" type="submit" wire:loading.attr="disabled" wire:target="sendResetLink">
              <span wire:loading.remove wire:target="sendResetLink">
                <i class="icon-base ri ri-mail-send-line me-1"></i>{{ __('auth_ui.send_reset_link') }}
              </span>
              <span wire:loading wire:target="sendResetLink">
                <span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>Enviando enlace...
              </span>
            </button>
          </form>

          {{-- Back to login --}}
          <div class="text-center">
            <a href="{{ route('login') }}" class="d-flex align-items-center justify-content-center">
              <i class="icon-base ri ri-arrow-left-s-line icon-20px me-1"></i>
              {{ __('auth_ui.back_to_login') }}
            </a>
          </div>

          {{-- Footer --}}
          <p class="text-center text-muted mt-5 mb-0" style="font-size:0.75rem">
            &copy; {{ date('Y') }} U.E. Instituto Vargas II &middot; El Para&iacute;so
          </p>

        </div>
      </div>
      {{-- /Panel Derecho --}}

    </div>
  </div>
</div>

@push('styles')
<style>
/* PANEL IZQUIERDO */
.auth-left-cover { min-height: 100vh; position: relative; }
.auth-left-bg {
  position: absolute; inset: 0;
  background: linear-gradient(145deg, #1a1363 0%, #2d2aa5 25%, #4338ca 50%, #1e40af 75%, #0f172a 100%);
  z-index: 0;
}
.auth-bubble {
  position: absolute; border-radius: 50%; opacity: 0.12;
  animation: floatBubble 8s ease-in-out infinite; z-index: 0;
}
.auth-bubble-1 { width: 350px; height: 350px; top: -80px; right: -60px; background: radial-gradient(circle, #a5b4fc, transparent); animation-delay: 0s; }
.auth-bubble-2 { width: 200px; height: 200px; bottom: 120px; left: -40px; background: radial-gradient(circle, #818cf8, transparent); animation-delay: 3s; opacity: 0.08; }
.auth-bubble-3 { width: 150px; height: 150px; top: 50%; left: 20%; background: radial-gradient(circle, #c4b5fd, transparent); animation-delay: 5s; opacity: 0.10; }
@keyframes floatBubble {
  0%, 100% { transform: translateY(0) scale(1); }
  50% { transform: translateY(-20px) scale(1.05); }
}
.z-1 { z-index: 1; }
.auth-inst-emblem {
  width: 42px; height: 42px;
  background: rgba(255,255,255,0.15); border: 1px solid rgba(255,255,255,0.25);
  border-radius: 12px; display: flex; align-items: center; justify-content: center;
  backdrop-filter: blur(8px);
}
.auth-status-chip {
  background: rgba(113,221,55,0.15); border: 1px solid rgba(113,221,55,0.3);
  border-radius: 50rem; padding: 0.25rem 0.75rem; backdrop-filter: blur(8px);
}
.auth-pulse-dot {
  width: 7px; height: 7px; background: #71dd37; border-radius: 50%;
  box-shadow: 0 0 8px #71dd37; animation: pulseGlow 2s ease-in-out infinite;
}
@keyframes pulseGlow {
  0%, 100% { opacity: 1; transform: scale(1); }
  50% { opacity: 0.6; transform: scale(1.35); }
}
.text-white-50 { color: rgba(255,255,255,0.5) !important; }
.text-white-60 { color: rgba(255,255,255,0.65) !important; }
.text-white-75 { color: rgba(255,255,255,0.8) !important; }
.auth-badge-tag {
  display: inline-flex; align-items: center;
  background: rgba(255,255,255,0.12); border: 1px solid rgba(255,255,255,0.2);
  color: rgba(255,255,255,0.9); padding: 0.35rem 1rem; border-radius: 50rem;
  font-size: 0.8rem; font-weight: 500; backdrop-filter: blur(8px);
}
.auth-main-headline { font-size: 1.9rem; line-height: 1.25; letter-spacing: -0.02em; }
.auth-gradient-word {
  background: linear-gradient(90deg, #a5b4fc, #f9a8d4);
  -webkit-background-clip: text; -webkit-text-fill-color: transparent;
}
.auth-illustration-wrapper { width: 100%; max-width: 580px; }
.auth-illustration-img-wrap { position: relative; display: inline-block; width: 100%; }
.auth-illustration-img {
  position: relative; z-index: 2; width: 100%; max-width: 360px;
  filter: drop-shadow(0 20px 40px rgba(0,0,0,0.3));
}
.auth-mask-img { position: absolute; inset: auto 0 0 0; z-index: 1; width: 100%; opacity: 0.5; }
.auth-stat-card {
  background: rgba(255,255,255,0.08); border: 1px solid rgba(255,255,255,0.15);
  border-radius: 14px; padding: 1rem; text-align: center;
  backdrop-filter: blur(10px); transition: all 0.3s ease;
}
.auth-stat-card:hover { background: rgba(255,255,255,0.14); transform: translateY(-2px); }
.auth-stat-icon {
  width: 34px; height: 34px; background: rgba(255,255,255,0.15);
  border-radius: 8px; display: flex; align-items: center; justify-content: center;
  margin: 0 auto 0.5rem; font-size: 1.1rem; color: #a5b4fc;
}
</style>
@endpush