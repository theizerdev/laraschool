<div class="app-brand demo me-4">
  <a href="{{ url('/') }}" class="app-brand-link">
    <span class="app-brand-logo demo">
      <span class="text-primary">
        <svg width="32" height="18" viewBox="0 0 38 20" fill="none" xmlns="http://www.w3.org/2000/svg">
          <path
            d="M30.0944 2.22569C29.0511 0.444187 26.7508 -0.172113 24.9566 0.849138C23.1623 1.87039 22.5536 4.14247 23.5969 5.92397L30.5368 17.7743C31.5801 19.5558 33.8804 20.1721 35.6746 19.1509C37.4689 18.1296 38.0776 15.8575 37.0343 14.076L30.0944 2.22569Z"
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
    <span class="app-brand-text demo menu-text fw-semibold ms-2">{{ config('app.name', 'Laravel') }}</span>
  </a>
</div>

  <ul class="menu-inner py-1">
     <!-- Dashboard -->
    <li class="menu-item {{ request()->routeIs('admin/dashboard') || request()->routeIs('superadmin/dashboard') ? 'active' : '' }}">
      <a href="{{ url('/') }}" class="menu-link">
        <i class="menu-icon tf-icons ri ri-home-4-line"></i>
        <div>Dashboard</div>
      </a>
    </li>



    @can('access students')
    <!-- Estudiantes -->
    <li class="menu-item {{ request()->routeIs('admin.students.*') || request()->routeIs('admin.access.students') ? 'active open' : '' }}">
      <a href="javascript:void(0);" class="menu-link menu-toggle">
        <i class="menu-icon tf-icons ri ri-user-3-line"></i>
        <div>Estudiantes</div>
      </a>
      <ul class="menu-sub">
        <li class="menu-item {{ request()->routeIs('admin.students.index') ? 'active' : '' }}">
          <a href="{{ route('admin.students.index') }}" class="menu-link">
            <div>Listado</div>
          </a>
        </li>
        @can('create students')
        <li class="menu-item {{ request()->routeIs('admin.students.create') ? 'active' : '' }}">
          <a href="{{ route('admin.students.create') }}" class="menu-link">
            <div>Crear</div>
          </a>
        </li>
        @endcan
        @can('import students')
        <li class="menu-item {{ request()->routeIs('admin.students.import') ? 'active' : '' }}">
          <a href="{{ route('admin.students.import') }}" class="menu-link">
            <div>Importar</div>
          </a>
        </li>
        @endcan
        <li class="menu-item {{ request()->routeIs('admin.access.students') ? 'active' : '' }}">
          <a href="{{ route('admin.access.students') }}" class="menu-link">
            <div>Control de Acceso</div>
          </a>
        </li>
      </ul>
    </li>
    @endcan
    @can('access programas')
      <li class="menu-item {{ request()->routeIs('admin.programas.*') ? 'active' : '' }}">
        <a href="{{ route('admin.programas.index') }}" class="menu-link">
          <div>Programas</div>
        </a>
      </li>
      @endcan
      @can('access subjects')
      <li class="menu-item {{ request()->routeIs('admin.subjects.index') ? 'active' : '' }}">
        <a href="{{ route('admin.subjects.index') }}" class="menu-link">
          <div>Materias</div>
        </a>
      </li>
      @endcan
       @can('access study_plans')
      <li class="menu-item {{ request()->routeIs('admin.study-plans.*') ? 'active' : '' }}">
        <a href="{{ route('admin.study-plans.index') }}" class="menu-link">
          <div>Planes de Estudio</div>
        </a>
      </li>
      @endcan
      @can('access teachers')
      <li class="menu-item {{ request()->routeIs('admin.teachers.*') ? 'active' : '' }}">
        <a href="{{ route('admin.teachers.index') }}" class="menu-link">
          <div>Profesores</div>
        </a>
      </li>
      @endcan
       @can('cambiar cuotas matriculas')
      <li class="menu-item {{ request()->routeIs('admin.matriculas.cambiar-cuotas') ? 'active' : '' }}">
        <a href="{{ route('admin.matriculas.cambiar-cuotas') }}" class="menu-link">
          <div>Cambiar Cuotas</div>
        </a>
      </li>
      @endcan
    @canany(['access matriculas', 'access programas', 'access subjects', 'access study_plans'])
  <!-- Matrículas y Materias -->
  <li class="menu-item {{ request()->routeIs('admin.matriculas.*') ? 'active' : '' }}">
    <a href="javascript:void(0);" class="menu-link menu-toggle">
      <i class="menu-icon tf-icons ri ri-graduation-cap-line"></i>
      <div>Matrículas</div>
    </a>
    <ul class="menu-sub">
     
      
     
      @can('access matriculas')
      <li class="menu-item {{ request()->routeIs('admin.matriculas.index') ? 'active' : '' }}">
        <a href="{{ route('admin.matriculas.index') }}" class="menu-link">
          <div>Listado general</div>
        </a>
      </li>
       @endcan
      @can('access matriculas')
      <li class="menu-item {{ request()->routeIs('admin.matriculas.pendientes') ? 'active' : '' }}">
        <a href="{{ route('admin.matriculas.pendientes') }}" class="menu-link">
          <div>Pendientes</div>
        </a>
      </li>
       @endcan
      @can('access matriculas')
      <li class="menu-item {{ request()->routeIs('admin.matriculas.solventes') ? 'active' : '' }}">
        <a href="{{ route('admin.matriculas.solventes') }}" class="menu-link">
          <div>Solventes</div>
        </a>
      </li>
       @endcan
      @can('create matriculas')
      <li class="menu-item {{ request()->routeIs('admin.matriculas.create') ? 'active' : '' }}">
        <a href="{{ route('admin.matriculas.create') }}" class="menu-link">
          <div>Crear matrícula</div>
        </a>
      </li>
      @endcan
     
     
    </ul>
  </li>
  @endcan

    @canany(['access evaluation periods', 'access evaluation types', 'access evaluations', 'access grades'])
    <!-- Control de Estudios -->
    <li class="menu-item {{ request()->routeIs('admin.evaluation-periods.*') || request()->routeIs('admin.evaluation-types.*') || request()->routeIs('admin.evaluations.*') || request()->routeIs('admin.grades.*') ? 'active open' : '' }}">
      <a href="javascript:void(0);" class="menu-link menu-toggle">
        <i class="menu-icon tf-icons ri ri-file-list-2-line"></i>
        <div>Control de Estudios</div>
      </a>
      <ul class="menu-sub">
        @can('access evaluation periods')
        <li class="menu-item {{ request()->routeIs('admin.evaluation-periods.*') ? 'active' : '' }}">
          <a href="{{ route('admin.evaluation-periods.index') }}" class="menu-link">
            <div>Período de Evaluación</div>
          </a>
        </li>
        @endcan
        @can('access evaluation types')
        <li class="menu-item {{ request()->routeIs('admin.evaluation-types.*') ? 'active' : '' }}">
          <a href="{{ route('admin.evaluation-types.index') }}" class="menu-link">
            <div>Tipos de Evaluación</div>
          </a>
        </li>
        @endcan
        @can('access evaluations')
        <li class="menu-item {{ request()->routeIs('admin.evaluations.*') ? 'active' : '' }}">
          <a href="{{ route('admin.evaluations.index') }}" class="menu-link">
            <div>Evaluaciones</div>
          </a>
        </li>
        @endcan
        @can('access grades')
        <li class="menu-item {{ request()->routeIs('admin.grades.*') ? 'active' : '' }}">
          <a href="{{ route('admin.grades.index') }}" class="menu-link">
            <div>Calificaciones</div>
          </a>
        </li>
        @endcan
      </ul>
    </li>
    @endcan

    @canany(['access academic records', 'access recovery periods', 'access promotion control'])
    <!-- Seguimiento Académico - Fase 3 -->
    <li class="menu-item {{ request()->routeIs('admin.academic-tracking.*') ? 'active open' : '' }}">
      <a href="javascript:void(0);" class="menu-link menu-toggle">
        <i class="menu-icon tf-icons ri ri-line-chart-line"></i>
        <div>Seguimiento Académico</div>
      </a>
      <ul class="menu-sub">
        @can('access academic records')
        <li class="menu-item {{ request()->routeIs('admin.academic-tracking.academic-history') ? 'active' : '' }}">
          <a href="{{ route('admin.academic-tracking.academic-history') }}" class="menu-link">
            <div>Historial Académico</div>
          </a>
        </li>
        @endcan
        @can('access promotion control')
        <li class="menu-item {{ request()->routeIs('admin.academic-tracking.promotion-control') ? 'active' : '' }}">
          <a href="{{ route('admin.academic-tracking.promotion-control') }}" class="menu-link">
            <div>Control de Promoción</div>
          </a>
        </li>
        @endcan
        @can('access recovery periods')
        <li class="menu-item {{ request()->routeIs('admin.academic-tracking.recovery-periods') ? 'active' : '' }}">
          <a href="{{ route('admin.academic-tracking.recovery-periods') }}" class="menu-link">
            <div>Períodos de Recuperación</div>
          </a>
        </li>
        @endcan
      </ul>
    </li>
    @endcan

    @canany(['access classrooms', 'access sections', 'access schedules'])
    <!-- Secciones y Horarios -->
    <li class="menu-item {{ request()->routeIs('admin.classrooms.*') || request()->routeIs('admin.sections.*') || request()->routeIs('admin.schedules.*') ? 'active open' : '' }}">
      <a href="javascript:void(0);" class="menu-link menu-toggle">
        <i class="menu-icon tf-icons ri ri-calendar-schedule-line"></i>
        <div>Secciones y Horarios</div>
      </a>
      <ul class="menu-sub">
        @can('access classrooms')
        <li class="menu-item {{ request()->routeIs('admin.classrooms.*') ? 'active' : '' }}">
          <a href="{{ route('admin.classrooms.index') }}" class="menu-link">
            <div>Aulas</div>
          </a>
        </li>
        @endcan
        @can('access sections')
        <li class="menu-item {{ request()->routeIs('admin.sections.*') ? 'active' : '' }}">
          <a href="{{ route('admin.sections.index') }}" class="menu-link">
            <div>Secciones</div>
          </a>
        </li>
        @endcan
        @can('access schedules')
        <li class="menu-item {{ request()->routeIs('admin.schedules.*') ? 'active' : '' }}">
          <a href="{{ route('admin.schedules.index') }}" class="menu-link">
            <div>Horarios</div>
          </a>
        </li>
        @endcan
      </ul>
    </li>
    @endcan

    @canany(['access pagos', 'access conceptos pago', 'access cajas'])
    <!-- Pagos y Finanzas -->
    <li class="menu-item {{ request()->routeIs('admin.pagos.*') || request()->routeIs('admin.conceptos-pago.*') || request()->routeIs('admin.cajas.*') ? 'active open' : '' }}">
      <a href="javascript:void(0);" class="menu-link menu-toggle">
        <i class="menu-icon tf-icons ri ri-money-dollar-circle-line"></i>
        <div>Pagos y Finanzas</div>
      </a>
      <ul class="menu-sub">
        @can('access pagos')
        <li class="menu-item {{ request()->routeIs('admin.pagos.index') ? 'active' : '' }}">
          <a href="{{ route('admin.pagos.index') }}" class="menu-link">
            <div>Pagos</div>
          </a>
        </li>
        @can('create pagos')
        <li class="menu-item {{ request()->routeIs('admin.pagos.create') ? 'active' : '' }}">
          <a href="{{ route('admin.pagos.create') }}" class="menu-link">
            <div>Registrar Pago</div>
          </a>
        </li>
        @endcan
        @endcan
        @can('access conceptos pago')
        <li class="menu-item {{ request()->routeIs('admin.conceptos-pago.index') ? 'active' : '' }}">
          <a href="{{ route('admin.conceptos-pago.index') }}" class="menu-link">
            <div>Conceptos de Pago</div>
          </a>
        </li>
        @endcan
        @can('access cajas')
        <li class="menu-item {{ request()->routeIs('admin.cajas.index') ? 'active' : '' }}">
          <a href="{{ route('admin.cajas.index') }}" class="menu-link">
            <div>Caja Chica</div>
          </a>
        </li>
        @endcan
        @can('access late payment rules')
        <li class="menu-item {{ request()->routeIs('admin.late-payment-rules.index') ? 'active' : '' }}">
          <a href="{{ route('admin.late-payment-rules.index') }}" class="menu-link">
            <div>Reglas de Morosidad</div>
          </a>
        </li>
        @endcan
      </ul>
    </li>
    @endcan

    @can('access reportes')
    <!-- Reportes -->
    <li class="menu-item {{ request()->routeIs('admin.reportes.*') ? 'active open' : '' }}">
      <a href="javascript:void(0);" class="menu-link menu-toggle">
        <i class="menu-icon tf-icons ri ri-file-chart-line"></i>
        <div>Reportes</div>
      </a>
      <ul class="menu-sub">
        @can('view estado cuentas')
        <li class="menu-item {{ request()->routeIs('admin.reportes.estado-cuentas') ? 'active' : '' }}">
          <a href="{{ route('admin.reportes.estado-cuentas') }}" class="menu-link">
            <div>Estado de Cuentas</div>
          </a>
        </li>
        @endcan
        @can('view resumen pagos')
        <li class="menu-item {{ request()->routeIs('admin.reportes.resumen-pagos') ? 'active' : '' }}">
          <a href="{{ route('admin.reportes.resumen-pagos') }}" class="menu-link">
            <div>Resumen de Pagos</div>
          </a>
        </li>
        @endcan
        @can('view morosidad')
        <li class="menu-item {{ request()->routeIs('admin.reportes.morosidad') ? 'active' : '' }}">
          <a href="{{ route('admin.reportes.morosidad') }}" class="menu-link">
            <div>Morosidad</div>
          </a>
        </li>
        @endcan
        @can('view ingresos totales')
        <li class="menu-item {{ request()->routeIs('admin.reportes.ingresos-totales') ? 'active' : '' }}">
          <a href="{{ route('admin.reportes.ingresos-totales') }}" class="menu-link">
            <div>Ingresos Totales</div>
          </a>
        </li>
        @endcan
        @can('view historico matriculas')
        <li class="menu-item {{ request()->routeIs('admin.reportes.historico-matriculas') ? 'active' : '' }}">
          <a href="{{ route('admin.reportes.historico-matriculas') }}" class="menu-link">
            <div>Histórico Matrículas</div>
          </a>
        </li>
        @endcan
        
        <!-- Reportes Académicos - Fase 1 -->
        @can('view estadisticas calificaciones materia')
        <li class="menu-item {{ request()->routeIs('admin.reportes.estadisticas-calificaciones-materia') ? 'active' : '' }}">
          <a href="{{ route('admin.reportes.estadisticas-calificaciones-materia') }}" class="menu-link">
            <div>Estadísticas Calificaciones</div>
          </a>
        </li>
        @endcan
        @can('view rendimiento estudiantil periodo')
        <li class="menu-item {{ request()->routeIs('admin.reportes.rendimiento-estudiantil-periodo') ? 'active' : '' }}">
          <a href="{{ route('admin.reportes.rendimiento-estudiantil-periodo') }}" class="menu-link">
            <div>Rendimiento Estudiantil</div>
          </a>
        </li>
        @endcan
        @can('view asistencia evaluaciones')
        <li class="menu-item {{ request()->routeIs('admin.reportes.asistencia-evaluaciones') ? 'active' : '' }}">
          <a href="{{ route('admin.reportes.asistencia-evaluaciones') }}" class="menu-link">
            <div>Asistencia y Evaluaciones</div>
          </a>
        </li>
        @endcan
        @can('view boletines calificaciones')
        <li class="menu-item {{ request()->routeIs('admin.reportes.boletines-calificaciones') ? 'active' : '' }}">
          <a href="{{ route('admin.reportes.boletines-calificaciones') }}" class="menu-link">
            <div>Boletines de Calificaciones</div>
          </a>
        </li>
        @endcan
      </ul>
    </li>
    @endcan



    @canany(['access empresas', 'access sucursales', 'access school periods', 'access niveles educativos', 'access turnos'])
    <!-- Configuración Institucional -->
    <li class="menu-item {{ request()->routeIs('admin.empresas.*') || request()->routeIs('admin.sucursales.*') || request()->routeIs('admin.school-periods.*') || request()->routeIs('admin.niveles-educativos.*') || request()->routeIs('admin.turnos.*') ? 'active open' : '' }}">
      <a href="javascript:void(0);" class="menu-link menu-toggle">
        <i class="menu-icon tf-icons ri ri-building-4-line"></i>
        <div>Configuración</div>
      </a>
      <ul class="menu-sub">
        @can('access empresas')
        <li class="menu-item {{ request()->routeIs('admin.empresas.index') ? 'active' : '' }}">
          <a href="{{ route('admin.empresas.index') }}" class="menu-link">
            <div>Empresas</div>
          </a>
        </li>
        @endcan
        @can('access paises')
        <li class="menu-item {{ request()->routeIs('admin.paises.index') ? 'active' : '' }}">
          <a href="{{ route('admin.paises.index') }}" class="menu-link">
            <div>Países</div>
          </a>
        </li>
        @endcan
        @can('access sucursales')
        <li class="menu-item {{ request()->routeIs('admin.sucursales.index') ? 'active' : '' }}">
          <a href="{{ route('admin.sucursales.index') }}" class="menu-link">
            <div>Sucursales</div>
          </a>
        </li>
        @endcan
        @can('access school periods')
        <li class="menu-item {{ request()->routeIs('admin.school-periods.index') ? 'active' : '' }}">
          <a href="{{ route('admin.school-periods.index') }}" class="menu-link">
            <div>Períodos Escolares</div>
          </a>
        </li>
        @endcan
        @can('access niveles educativos')
        <li class="menu-item {{ request()->routeIs('admin.niveles-educativos.index') ? 'active' : '' }}">
          <a href="{{ route('admin.niveles-educativos.index') }}" class="menu-link">
            <div>Niveles Educativos</div>
          </a>
        </li>
        @endcan
        @can('access turnos')
        <li class="menu-item {{ request()->routeIs('admin.turnos.index') ? 'active' : '' }}">
          <a href="{{ route('admin.turnos.index') }}" class="menu-link">
            <div>Turnos</div>
          </a>
        </li>
        @endcan
      </ul>
    </li>
    @endcan

    @can('access series')
    <!-- Series de Documentos -->
    <li class="menu-item {{ request()->routeIs('admin.series.*') ? 'active' : '' }}">
      <a href="{{ route('admin.series.index') }}" class="menu-link">
        <i class="menu-icon tf-icons ri ri-file-list-3-line"></i>
        <div>Series</div>
      </a>
    </li>
    @endcan

    @can('view exchange-rates')
    <!-- Tasas de Cambio -->
    <li class="menu-item {{ request()->routeIs('admin.exchange-rates') ? 'active' : '' }}">
      <a href="{{ route('admin.exchange-rates') }}" class="menu-link">
        <i class="menu-icon tf-icons ri ri-exchange-dollar-line"></i>
        <div>Tasas BCV</div>
      </a>
    </li>
    @endcan

    <!-- Personalización de Plantilla -->
    <li class="menu-item {{ request()->routeIs('admin.template-customization') ? 'active' : '' }}">
      <a href="{{ route('admin.template-customization') }}" class="menu-link">
        <i class="menu-icon tf-icons ri ri-palette-line"></i>
        <div>Personalización</div>
      </a>
    </li>



    @can('access mensajeria')
    <!-- Mensajería -->
    <li class="menu-item {{ request()->routeIs('admin.mensajeria.*') ? 'active' : '' }}">
      <a href="{{ route('admin.mensajeria.index') }}" class="menu-link">
        <i class="menu-icon tf-icons ri ri-mail-line"></i>
        <div>Mensajería</div>
      </a>
    </li>
    @endcan

    @can('access whatsapp')
    <!-- WhatsApp -->
    <li class="menu-item {{ request()->routeIs('admin.whatsapp.*') ? 'active open' : '' }}">
      <a href="javascript:void(0);" class="menu-link menu-toggle">
        <i class="menu-icon tf-icons ri ri-whatsapp-line"></i>
        <div>WhatsApp</div>
      </a>
      <ul class="menu-sub">
        @can('access whatsapp')
        <li class="menu-item {{ request()->routeIs('admin.whatsapp.index') ? 'active' : '' }}">
          <a href="{{ route('admin.whatsapp.index') }}" class="menu-link">
            <div>Panel</div>
          </a>
        </li>
        @endcan
        
      </ul>
    </li>
   @endcan
    @can('access biblioteca')
    <!-- Biblioteca Digital -->
    <li class="menu-item {{ request()->routeIs('admin.biblioteca.*') ? 'active' : '' }}">
      <a href="{{ route('admin.biblioteca.index') }}" class="menu-link">
        <i class="menu-icon tf-icons ri ri-book-2-line"></i>
        <div>Biblioteca</div>
      </a>
    </li>
    @endcan





    @canany(['access users', 'access roles', 'access permissions'])
    <!-- Usuarios y Permisos -->
    <li class="menu-item {{ request()->routeIs('admin.users.*') || request()->routeIs('admin.roles.*') || request()->routeIs('admin.permissions.*') ? 'active open' : '' }}">
      <a href="javascript:void(0);" class="menu-link menu-toggle">
        <i class="menu-icon tf-icons ri ri-group-line"></i>
        <div>Administración</div>
      </a>
      <ul class="menu-sub">
        @can('access users')
        <li class="menu-item {{ request()->routeIs('admin.users.index') ? 'active' : '' }}">
          <a href="{{ route('admin.users.index') }}" class="menu-link">
            <div>Usuarios</div>
          </a>
        </li>
        @endcan
        @can('access roles')
        <li class="menu-item {{ request()->routeIs('admin.roles.index') ? 'active' : '' }}">
          <a href="{{ route('admin.roles.index') }}" class="menu-link">
            <div>Roles</div>
          </a>
        </li>
        @endcan
        @can('access permissions')
        <li class="menu-item {{ request()->routeIs('admin.permissions.index') ? 'active' : '' }}">
          <a href="{{ route('admin.permissions.index') }}" class="menu-link">
            <div>Permisos</div>
          </a>
        </li>
        @endcan
      </ul>
    </li>
    @endcan

    @canany(['view active sessions', 'access activity log', 'access monitoreo'])
    <!-- Sistema y Monitoreo -->
    <li class="menu-item {{ request()->routeIs('admin.active-sessions*') || request()->is('admin/activity-log*') || request()->routeIs('admin.monitoreo.*') ? 'active open' : '' }}">
      <a href="javascript:void(0);" class="menu-link menu-toggle">
        <i class="menu-icon tf-icons ri ri-line-chart-line"></i>
        <div>Monitoreo</div>
      </a>
      <ul class="menu-sub">
        @can('view active sessions')
        <li class="menu-item {{ request()->routeIs('admin.active-sessions*') ? 'active' : '' }}">
          <a href="{{ route('admin.active-sessions.index') }}" class="menu-link">
            <div>Sesiones</div>
          </a>
        </li>
        @endcan
        @can('access activity log')
        <li class="menu-item {{ request()->is('admin/activity-log*') ? 'active' : '' }}">
          <a href="{{ route('admin.activity-log') }}" class="menu-link">
            <div>Actividad</div>
          </a>
        </li>
        @endcan
        @can('access database export')
        <li class="menu-item {{ request()->routeIs('admin.database-export') ? 'active' : '' }}">
          <a href="{{ route('admin.database-export') }}" class="menu-link">
            <div>Exportar Base de Datos</div>
          </a>
        </li>
        @endcan

      </ul>
    </li>
    @endcan
</ul>