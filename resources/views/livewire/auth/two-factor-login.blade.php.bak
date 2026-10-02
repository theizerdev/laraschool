<div class="position-relative">
  <div class="authentication-wrapper authentication-basic container-p-y p-4 p-sm-0">
    <div class="authentication-inner py-6">
      <!-- 2FA Verification -->
      <div class="card p-md-7 p-1">
        <!-- Logo -->
        <div class="app-brand justify-content-center mt-5">
          <a href="{{ url('/') }}" class="app-brand-link gap-2">
            <span class="app-brand-logo demo">
              <span class="text-primary">
                <svg width="32" height="18" viewBox="0 0 38 20" fill="none" xmlns="http://www.w3.org/2000/svg">
                  <path
                    d="M30.0944 2.22569C29.0511 0.444187 26.7508 -0.172113 24.9566 0.849138C23.1623 1.87039 22.5536 4.14247 23.5969 5.92397L30.5368 17.7743C31.5801 19.5558 33.957 20.1721 35.7512 19.1509C37.4689 18.1296 38.0776 15.8575 37.0343 14.076L30.0944 2.22569Z"
                    fill="currentColor" />
                  <path
                    d="M30.171 2.22569C29.1277 0.444187 26.8274 -0.172113 25.0332 0.849138C23.2389 1.87039 22.6302 4.14247 23.6735 5.92397L30.6134 17.7743C31.6567 19.5558 33.957 20.1721 35.7512 19.1509C37.5455 18.1296 38.1542 15.8575 37.1109 14.076L30.171 2.22569Z"
                    fill="url(#paint0_linear_2989_100980)"
                    fill-opacity="0.4" />
                  <path
                    d="M22.9676 2.22569C24.0109 0.444187 26.3112 -0.172113 28.1054 0.849138C29.8996 1.87039 30.5084 4.14247 29.4651 5.92397L22.5251 17.7743C21.4818 19.5558 19.1816 20.1721 17.3873 19.1509C15.5931 18.1296 14.9843 15.8575 16.0276 14.076L22.9676 2.22569Z"
                    fill="currentColor" />
                  <path
                    d="M14.9558 2.22569C13.9125 0.444187 11.6122 -0.172113 9.818 0.849138C8.02377 1.87039 7.41502 4.14247 8.45833 5.92397L15.3983 17.7743C16.4416 19.5558 18.7418 20.1721 20.5361 19.1509C22.3303 18.1296 22.9391 15.8575 21.8958 14.076L14.9558 2.22569Z"
                    fill="currentColor" />
                  <path
                    d="M14.9558 2.22569C13.9125 0.444187 11.6122 -0.172113 9.818 0.849138C8.02377 1.87039 7.41502 4.14247 8.45833 5.92397L15.3983 17.7743C16.4416 19.5558 18.7418 20.1721 20.5361 19.1509C22.3303 18.1296 22.9391 15.8575 21.8958 14.076L14.9558 2.22569Z"
                    fill="url(#paint1_linear_2989_100980)"
                    fill-opacity="0.4" />
                  <path
                    d="M7.82901 2.22569C8.87231 0.444187 11.1726 -0.172113 12.9668 0.849138C14.7611 1.87039 15.3698 4.14247 14.3265 5.92397L7.38656 17.7743C6.34325 19.5558 4.04298 20.1721 2.24875 19.1509C0.454514 18.1296 -0.154233 15.8575 0.88907 14.076L7.82901 2.22569Z"
                    fill="currentColor" />
                  <defs>
                    <linearGradient
                      id="paint0_linear_2989_100980"
                      x1="5.36642"
                      y1="0.849138"
                       x2="10.532"
                      y2="24.104"
                       gradientUnits="userSpaceOnUse">
                      <stop offset="0" stop-opacity="1" />
                      <stop offset="1" stop-opacity="0" />
                    </linearGradient>
                    <linearGradient
                      id="paint1_linear_2989_100980"
                      x1="5.19475"
                       y1="0.849139"
                       x2="10.3357"
                       y2="24.1155"
                       gradientUnits="userSpaceOnUse">
                      <stop offset="0" stop-opacity="1" />
                      <stop offset="1" stop-opacity="0" />
                    </linearGradient>
                  </defs>
                </svg>
              </span>
            </span>
            <span class="app-brand-text demo text-heading fw-semibold">{{ config('app.name', 'Laravel') }}</span>
          </a>
        </div>
        <!-- /Logo -->

        <div class="card-body mt-1">
          <h4 class="mb-1">{{ __('auth_ui.two_factor_title') }}</h4>
          <p class="mb-5">{{ __('auth_ui.two_factor_subtitle') }}</p>

          @if (session()->has('error'))
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
              {{ session('error') }}
              <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
          @endif

          <form wire:submit.prevent="verifyCode" id="twoFactorForm">
            <input type="hidden" wire:model="latitude" id="latitude">
            <input type="hidden" wire:model="longitude" id="longitude">

            <div class="mb-5">
              <div class="form-floating form-floating-outline">
                <input
                  type="text"
                  class="form-control"
                  id="code"
                  name="code"
                  wire:model="code"
                  placeholder="{{ __('auth_ui.two_factor_code') }}"
                  autofocus
                  maxlength="6"
                  inputmode="numeric"
                  pattern="[0-9]*" />
                <label for="code">{{ __('auth_ui.two_factor_code') }}</label>
              </div>
              <div class="form-text">{{ __('auth_ui.two_factor_subtitle') }}</div>
            </div>

            <div class="mb-5">
              <button class="btn btn-primary d-grid w-100" type="submit">{{ __('auth_ui.verify_2fa') }}</button>
            </div>
          </form>

          <div class="text-center">
            <a href="{{ route('login') }}">{{ __('auth_ui.back_to_login') }}</a>
          </div>
        </div>
      </div>
      <!-- /2FA Verification -->
    </div>
  </div>
</div>

@push('scripts')
  <script>
    document.addEventListener('livewire:initialized', () => {
      // Al hacer clic en el botón
      if (navigator.geolocation) {
        navigator.geolocation.getCurrentPosition(
          (position) => {
            // Si tiene éxito, emite un evento con las coordenadas
            @this.latitude = position.coords.latitude;
            @this.longitude = position.coords.longitude;
          },
          (error) => {
            // Si hay un error, emite un evento con el mensaje de error
            @this.dispatch('setError', {
              error: error.message
            });
          }
        );
      } else {
        // El navegador no soporta la geolocalización
        @this.dispatch('setError', {
          error: "Geolocalización no es soportada por este navegador."
        });
      }

      // Manejar entrada de código con auto-focus y auto-submit
      const codeInput = document.getElementById('code');
      if (codeInput) {
        codeInput.addEventListener('input', function(e) {
          // Solo permitir números
          this.value = this.value.replace(/[^0-9]/g, '');
          
          // Si se ingresan 6 dígitos, enviar automáticamente
          if (this.value.length === 6) {
            @this.verifyCode();
          }
        });

        // Auto-focus en el primer campo vacío
        if (!codeInput.value) {
          codeInput.focus();
        }
      }
    });
  </script>
@endpush