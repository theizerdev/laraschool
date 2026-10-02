@props(['title' => 'Auth'])

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}"
  class="layout-wide customizer-hide"
  dir="ltr"
  data-skin="default"
  data-bs-theme="light"
  data-assets-path="/materialize/assets/"
  data-template="vertical-menu-template">
  <head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title ?? config('app.name') }}</title>
    <meta name="description" content="{{ config('app.name') }} - {{ config('app.description') }}">
    <meta name="keywords" content="admin dashboard, admin template, administration, analytics, bootstrap, bootstrap 5, bootstrap admin template, charts, crm, laravel, laravel admin panel, laravel template, performance, php, responsive, saas, sass">
    <meta name="author" content="ThemeSelection">
    <meta name="robots" content="noindex, nofollow" />

    <!-- Favicon -->
    <link rel="icon" type="image/x-icon" href="/materialize/assets/img/favicon/favicon.ico" />

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link
      href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&ampdisplay=swap"
      rel="stylesheet" />

    <link rel="stylesheet" href="/materialize/assets/vendor/fonts/iconify-icons.css" />

    <!-- Core CSS -->
    <!-- build:css assets/vendor/css/theme.css -->

    <link rel="stylesheet" href="/materialize/assets/vendor/libs/node-waves/node-waves.css" />

    <script src="/materialize/assets/vendor/libs/@algolia/autocomplete-js.js"></script>

    <link rel="stylesheet" href="/materialize/assets/vendor/libs/pickr/pickr-themes.css" />

    <link rel="stylesheet" href="/materialize/assets/vendor/css/core.css" />
    <link rel="stylesheet" href="/materialize/assets/css/demo.css" />

    <!-- Vendors CSS -->

    <link rel="stylesheet" href="/materialize/assets/vendor/libs/perfect-scrollbar/perfect-scrollbar.css" />

    <!-- endbuild -->

    <!-- Vendor -->
    <link rel="stylesheet" href="/materialize/assets/vendor/libs/@form-validation/form-validation.css" />

    <!-- Page CSS -->
    <!-- Page -->
    <link rel="stylesheet" href="/materialize/assets/vendor/css/pages/page-auth.css" />

    <!-- Helpers -->
    <script src="/materialize/assets/vendor/js/helpers.js"></script>
    <!--! Template customizer & Theme config files MUST be included after core stylesheets and helpers.js in the <head> section -->

    <!--? Template customizer: To hide customizer set displayCustomizer value false in config.js. -->
    <script src="/materialize/assets/vendor/js/template-customizer.js"></script>

    <!--? Config: Mandatory theme config file contain global vars & default theme options, Set your preferred theme option in this file. -->

    <script src="/materialize/assets/js/config.js"></script>

    @stack('styles')
    @livewireStyles
  </head>

  <body>
    <!-- Content -->
    {{ $slot }}

    <!-- Core JS -->
    <script src="/materialize/assets/vendor/libs/jquery/jquery.js"></script>

    <script src="/materialize/assets/vendor/libs/popper/popper.js"></script>
    <script src="/materialize/assets/vendor/js/bootstrap.js"></script>
    <script src="/materialize/assets/vendor/libs/node-waves/node-waves.js"></script>

    <script src="/materialize/assets/vendor/libs/@algolia/autocomplete-js.js"></script>

    <script src="/materialize/assets/vendor/libs/pickr/pickr.js"></script>

    <script src="/materialize/assets/vendor/libs/perfect-scrollbar/perfect-scrollbar.js"></script>

    <script src="/materialize/assets/vendor/libs/hammer/hammer.js"></script>

    <script src="/materialize/assets/vendor/libs/i18n/i18n.js"></script>

    <script src="/materialize/assets/vendor/js/menu.js"></script>

    <!-- endbuild -->

    <!-- Vendors JS -->
    <script src="/materialize/assets/vendor/libs/@form-validation/popular.js"></script>
    <script src="/materialize/assets/vendor/libs/@form-validation/bootstrap5.js"></script>
    <script src="/materialize/assets/vendor/libs/@form-validation/auto-focus.js"></script>

    <!-- Main JS -->

    <script src="/materialize/assets/js/main.js"></script>

    <!-- Page JS -->
    <script src="/materialize/assets/js/pages-auth.js"></script>

    <!-- Custom Scripts -->
    @stack('scripts')

    <!-- Livewire -->
    @livewireScripts
  </body>
</html>
