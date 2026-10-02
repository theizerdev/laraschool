<div class="position-relative">
  <div class="authentication-wrapper authentication-basic container-p-y p-4 p-sm-0">
    <div class="authentication-inner py-6">
      <!-- Login -->
      <div class="card p-md-7 p-1">
        <!-- Logo -->
        <div class="app-brand justify-content-center mt-5">
          <a href="{{ url('/') }}" class="app-brand-link gap-2">
           
            <span class="app-brand-text demo text-heading fw-semibold">{{ config('app.name', 'Laravel') }}</span>
          </a>
        </div>
        <!-- /Logo -->

        <div class="card-body mt-1">
          <h4 class="mb-1">¡{{ __('auth_ui.login_title') }}s! 👋</h4>
          <p class="mb-5">{{ __('auth_ui.login_subtitle') }}</p>

          <form wire:submit.prevent="authenticate" id="loginForm">
            <input type="hidden" wire:model="latitude" id="latitude">
            <input type="hidden" wire:model="longitude" id="longitude">

            <div class="form-floating form-floating-outline mb-5 form-control-validation">
              <input
                type="text"
                class="form-control @if($hasError('email')) is-invalid @endif"
                id="email"
                name="email"
                wire:model="email"
                placeholder="{{ config('app.locale') == 'es' ? 'Ingresa tu nombre de usuario o email' : 'Enter your username or email' }}"
                autofocus />
              <label for="email">
             @if(config('app.locale') == 'es')Nombre de usuario o Email @else Username or Email @endif</label>
              @if($hasError('email'))
                <div class="invalid-feedback d-block">{{ $getError('email') }}</div>
              @endif
            </div>
            <div class="mb-5">
              <div class="form-password-toggle form-control-validation">
                <div class="input-group input-group-merge">
                  <div class="form-floating form-floating-outline">
                    <input
                      type="password"
                      id="password"
                      class="form-control @if($hasError('password')) is-invalid @endif"
                      name="password"
                      wire:model="password"
                      placeholder="&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;"
                      aria-describedby="password" />
                    <label for="password">{{ __('auth_ui.password') }}</label>

                  </div>
                  <span class="input-group-text cursor-pointer"
                    ><i class="icon-base ri ri-eye-off-line icon-20px"></i
                  ></span>
                </div>
                 @if($hasError('password'))
                      <div class="invalid-feedback d-block">{{ $getError('password') }}</div>
                    @endif
              </div>
            </div>
            <div class="mb-5 d-flex justify-content-between mt-5">
              <div class="form-check mt-2">
                <input class="form-check-input" type="checkbox" id="remember-me" wire:model="remember" />
                <label class="form-check-label" for="remember-me"> {{ __('auth_ui.remember_me') }} </label>
              </div>
              <a href="{{ route('password.request') }}" class="float-end mb-1 mt-2">
                <span>{{ __('auth_ui.forgot_password') }}</span>
              </a>
            </div>
            <div class="mb-5">
              <button class="btn btn-primary d-grid w-100" type="submit">{{ __('auth_ui.login_button') }}</button>
            </div>
          </form>

          

          <div class="divider my-5">
            <div class="divider-text">or</div>
          </div>

          <div class="d-flex justify-content-center gap-2">
            <a href="javascript:;" class="btn btn-icon rounded-circle btn-text-facebook">
              <i class="icon-base ri ri-facebook-fill icon-18px"></i>
            </a>

            <a href="javascript:;" class="btn btn-icon rounded-circle btn-text-twitter">
              <i class="icon-base ri ri-twitter-fill icon-18px"></i>
            </a>

            <a href="javascript:;" class="btn btn-icon rounded-circle btn-text-github">
              <i class="icon-base ri ri-github-fill icon-18px"></i>
            </a>

            <a href="javascript:;" class="btn btn-icon btn-lg rounded-pill btn-text-google-plus">
              <i class="icon-base ri ri-google-fill icon-18px"></i>
            </a>
          </div>
        </div>
      </div>
      <!-- /Login -->

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
    });
    </script>
    @endpush
