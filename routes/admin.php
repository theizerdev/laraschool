<?php

use Illuminate\Support\Facades\Route;
use App\Livewire\Dashboard;
use App\Livewire\Admin\Empresas\Index as EmpresasIndex;
use App\Livewire\Admin\Empresas\Create as EmpresasCreate;
use App\Livewire\Admin\Empresas\Edit as EmpresasEdit;
use App\Livewire\Admin\Sucursales\Index as SucursalesIndex;
use App\Livewire\Admin\Sucursales\Create as SucursalesCreate;
use App\Livewire\Admin\Sucursales\Edit as SucursalesEdit;
use App\Livewire\Admin\Sucursales\Show as SucursalesShow;
use App\Livewire\Admin\Users\Index as UsersIndex;
use App\Livewire\Admin\Users\Create as UsersCreate;
use App\Livewire\Admin\Users\Edit as UsersEdit;
use App\Livewire\Admin\Roles\Index as RolesIndex;
use App\Livewire\Admin\Roles\Create as RolesCreate;
use App\Livewire\Admin\Roles\Edit as RolesEdit;
use App\Livewire\Admin\Roles\Show as RolesShow;
use App\Livewire\Admin\Permissions\Index as PermissionsIndex;
use App\Livewire\Admin\Permissions\Create as PermissionsCreate;
use App\Livewire\Admin\Permissions\Edit as PermissionsEdit;
use App\Livewire\Admin\SchoolPeriods\Index as SchoolYearsIndex;
use App\Livewire\Admin\SchoolPeriods\Create as SchoolYearsCreate;
use App\Livewire\Admin\SchoolPeriods\Edit as SchoolYearsEdit;
use App\Livewire\Admin\SchoolPeriods\Show as SchoolYearsShow;
use App\Livewire\Admin\SchoolPeriods\Index as SchoolPeriodsIndex;
use App\Livewire\Admin\SchoolPeriods\Create as SchoolPeriodsCreate;
use App\Livewire\Admin\SchoolPeriods\Edit as SchoolPeriodsEdit;
use App\Livewire\Admin\SchoolPeriods\Show as SchoolPeriodsShow;
use App\Livewire\Admin\NivelesEducativos\Index as NivelesEducativosIndex;
use App\Livewire\Admin\NivelesEducativos\Create as NivelesEducativosCreate;
use App\Livewire\Admin\NivelesEducativos\Edit as NivelesEducativosEdit;
use App\Livewire\Admin\Turnos\Index as TurnosIndex;
use App\Livewire\Admin\Turnos\Create as TurnosCreate;
use App\Livewire\Admin\Turnos\Edit as TurnosEdit;
use App\Livewire\Admin\Students\Index as StudentsIndex;
use App\Livewire\Admin\Students\Create as StudentsCreate;
use App\Livewire\Admin\Students\Edit as StudentsEdit;
use App\Livewire\Admin\Students\Show as StudentsShow;
use App\Livewire\Admin\Students\Import as StudentsImport;
use App\Livewire\Admin\Students\ImportNew as StudentsImportNew;
use App\Livewire\Admin\Students\QrAccess;
use App\Livewire\Admin\ActiveSessions;
// Componentes para matrículas
use App\Livewire\Admin\Programas\Index as ProgramasIndex;
use App\Livewire\Admin\Programas\Create as ProgramasCreate;
use App\Livewire\Admin\Programas\Edit as ProgramasEdit;
use App\Livewire\Admin\Programas\Show as ProgramasShow;
use App\Livewire\Admin\ConceptosPago\Index as ConceptosPagoIndex;
use App\Livewire\Admin\ConceptosPago\Create as ConceptosPagoCreate;
use App\Livewire\Admin\ConceptosPago\Edit as ConceptosPagoEdit;

// Empresas
Route::get('/empresas', EmpresasIndex::class)->name('empresas.index');
Route::get('/empresas/crear', EmpresasCreate::class)->name('empresas.create');
Route::get('/empresas/{empresa}/editar', EmpresasEdit::class)->name('empresas.edit');

// Países
Route::get('/paises', \App\Livewire\Admin\Paises\PaisIndex::class)->name('paises.index');
Route::get('/paises/crear', \App\Livewire\Admin\Paises\Create::class)->name('paises.create');
Route::get('/paises/{pais}/editar', \App\Livewire\Admin\Paises\Edit::class)->name('paises.edit');

// Sucursales
Route::get('/sucursales', SucursalesIndex::class)->name('sucursales.index');
Route::get('/sucursales/crear', SucursalesCreate::class)->name('sucursales.create');
Route::get('/sucursales/{sucursal}/editar', SucursalesEdit::class)->name('sucursales.edit');
Route::get('/sucursales/{sucursal}', SucursalesShow::class)->name('sucursales.show');

// Usuarios
Route::get('/usuarios', UsersIndex::class)->name('users.index');
Route::get('/usuarios/crear', UsersCreate::class)->name('users.create');
Route::get('/usuarios/{user}/editar', UsersEdit::class)->name('users.edit');
 // Perfil de usuario
Route::prefix('profile')->group(function () {
    Route::get('/', \App\Livewire\Admin\Users\Profile\Index::class)->name('users.profile');
    Route::get('/{user_id}/password', \App\Livewire\Admin\Users\Profile\ChangePassword::class)->name('users.password');
    Route::get('/{user_id}/history', \App\Livewire\Admin\Users\Profile\HistoryUser::class)->name('users.history');
});

// Roles
Route::get('/roles', RolesIndex::class)->name('roles.index');
Route::get('/roles/crear', RolesCreate::class)->name('roles.create');
Route::get('/roles/{role}/editar', RolesEdit::class)->name('roles.edit');
Route::get('/roles/{role}', RolesShow::class)->name('roles.show');

// Permisos
Route::get('/permisos', PermissionsIndex::class)->name('permissions.index');
Route::get('/permisos/crear', PermissionsCreate::class)->name('permissions.create');
Route::get('/permisos/{permission}/editar', PermissionsEdit::class)->name('permissions.edit');

// Años escolares
Route::get('/school-years', SchoolYearsIndex::class)->name('school-years.index');
Route::get('/school-years/crear', SchoolYearsCreate::class)->name('school-years.create');
Route::get('/school-years/{schoolYear}/editar', SchoolYearsEdit::class)->name('school-years.edit');
Route::get('/school-years/{schoolYear}', SchoolYearsShow::class)->name('school-years.show');

// Periodos escolares
Route::get('/school-periods', SchoolPeriodsIndex::class)->name('school-periods.index');
Route::get('/school-periods/crear', SchoolPeriodsCreate::class)->name('school-periods.create');
Route::get('/school-periods/{schoolPeriod}/editar', SchoolPeriodsEdit::class)->name('school-periods.edit');
Route::get('/school-periods/{schoolPeriod}', SchoolPeriodsShow::class)->name('school-periods.show');

// Niveles Educativos
Route::get('/niveles-educativos', NivelesEducativosIndex::class)->name('niveles-educativos.index');
Route::get('/niveles-educativos/crear', NivelesEducativosCreate::class)->name('niveles-educativos.create');
Route::get('/niveles-educativos/{nivel}/editar', NivelesEducativosEdit::class)->name('niveles-educativos.edit');

// Turnos
Route::get('/turnos', TurnosIndex::class)->name('turnos.index');
Route::get('/turnos/crear', TurnosCreate::class)->name('turnos.create');
Route::get('/turnos/{turno}/editar', TurnosEdit::class)->name('turnos.edit');

// Estudiantes
Route::get('/students', StudentsIndex::class)->name('students.index');
Route::get('/students/crear', StudentsCreate::class)->name('students.create');
Route::get('/students/import', StudentsImportNew::class)->name('students.import');
Route::get('/students/{student}/editar', StudentsEdit::class)->name('students.edit');
Route::get('/students/{student}', StudentsShow::class)->name('students.show');
Route::get('/students/{student}/historico', \App\Livewire\Admin\Students\Historico::class)->name('students.historico');
Route::get('/students/qr-access', QrAccess::class)->name('students.qr-access');
Route::get('/access/students', QrAccess::class)->name('access.students');


// Sesiones activas
Route::get('/active-sessions', ActiveSessions::class)->name('active-sessions.index');

// Monitoreo
Route::prefix('monitoreo')->as('monitoreo.')->group(function () {
    Route::get('/servidor', \App\Livewire\Admin\Monitoreo\Servidor::class)->name('servidor');
    Route::get('/base-datos', \App\Livewire\Admin\Monitoreo\BaseDatos::class)->name('base-datos');
    Route::get('/estudiantes', \App\Livewire\Admin\Monitoreo\Estudiantes::class)->name('estudiantes');
    Route::get('/accesos', \App\Livewire\Admin\Monitoreo\Accesos::class)->name('accesos');
});

// Tasas de Cambio
Route::get('/tasas-cambio', \App\Livewire\Admin\ExchangeRates::class)->name('exchange-rates');

// Programas
Route::get('/programas', ProgramasIndex::class)->name('programas.index');
Route::get('/programas/crear', ProgramasCreate::class)->name('programas.create');
Route::get('/programas/{programa}/editar', ProgramasEdit::class)->name('programas.edit');
Route::get('/programas/{programa}', ProgramasShow::class)->name('programas.show');

// Materias (Subjects)
Route::get('/materias', \App\Livewire\Admin\Subjects\Index::class)->name('subjects.index');
Route::get('/materias/crear', \App\Livewire\Admin\Subjects\Create::class)->name('subjects.create');
Route::get('/materias/{subject}/editar', \App\Livewire\Admin\Subjects\Edit::class)->name('subjects.edit');
Route::get('/materias/{subject}', \App\Livewire\Admin\Subjects\Show::class)->name('subjects.show');
Route::get('/materias/{subject}/asignar-profesores', \App\Livewire\Admin\Subjects\AssignTeachers::class)->name('subjects.assign-teachers');
Route::get('/materias/{subject}/prerrequisitos', \App\Livewire\Admin\Subjects\Prerequisites::class)->name('subjects.prerequisites');

// Planes de Estudio (Study Plans)
Route::get('/planes-estudio', \App\Livewire\Admin\StudyPlans\Index::class)->name('study-plans.index');
Route::get('/planes-estudio/crear', \App\Livewire\Admin\StudyPlans\Create::class)->name('study-plans.create');
Route::get('/planes-estudio/{studyPlan}/editar', \App\Livewire\Admin\StudyPlans\Edit::class)->name('study-plans.edit');
Route::get('/planes-estudio/{studyPlan}', \App\Livewire\Admin\StudyPlans\Show::class)->name('study-plans.show');

// Profesores (Teachers)
Route::get('/profesores', \App\Livewire\Admin\Teachers\Index::class)->name('teachers.index');
Route::get('/profesores/crear', \App\Livewire\Admin\Teachers\Create::class)->name('teachers.create');
Route::get('/profesores/{teacher}/editar', \App\Livewire\Admin\Teachers\Edit::class)->name('teachers.edit');
Route::get('/profesores/{teacher}', \App\Livewire\Admin\Teachers\Show::class)->name('teachers.show');

// Conceptos de Pago
Route::get('/conceptos-pago', ConceptosPagoIndex::class)->name('conceptos-pago.index');
Route::get('/conceptos-pago/crear', ConceptosPagoCreate::class)->name('conceptos-pago.create');
Route::get('/conceptos-pago/{concepto}/editar', ConceptosPagoEdit::class)->name('conceptos-pago.edit');

// Series de Documentos
Route::get('/series', \App\Livewire\Admin\Series\Index::class)->name('series.index');
Route::get('/series/crear', \App\Livewire\Admin\Series\Create::class)->name('series.create');
Route::get('/series/{serie}/editar', \App\Livewire\Admin\Series\Edit::class)->name('series.edit');

// Matrículas
Route::get('/matriculas', \App\Livewire\Admin\Matriculas\Index::class)->name('matriculas.index');
Route::get('/matriculas/pendientes', \App\Livewire\Admin\Matriculas\Pendientes::class)->name('matriculas.pendientes');
Route::get('/matriculas/solventes', \App\Livewire\Admin\Matriculas\Solventes::class)->name('matriculas.solventes');
Route::get('/matriculas/crear', \App\Livewire\Admin\Matriculas\Create::class)->name('matriculas.create');
Route::get('/matriculas/cambiar-cuotas', \App\Livewire\Admin\Matriculas\CambiarCuotas::class)->name('matriculas.cambiar-cuotas');
Route::get('/matriculas/{matricula}/editar', \App\Livewire\Admin\Matriculas\Edit::class)->name('matriculas.edit');
Route::get('/matriculas/{matricula}', \App\Livewire\Admin\Matriculas\Show::class)->name('matriculas.show');

// Pagos
Route::get('/pagos', \App\Livewire\Admin\Pagos\Index::class)->name('pagos.index');
Route::get('/pagos/crear', \App\Livewire\Admin\Pagos\Create::class)->name('pagos.create');
Route::get('/pagos/{pago}/editar', \App\Livewire\Admin\Pagos\Edit::class)->name('pagos.edit');
Route::get('/pagos/{pago}', \App\Livewire\Admin\Pagos\Show::class)->name('pagos.show');
Route::get('/pagos/{pago}/print', [\App\Livewire\Admin\Pagos\Index::class, 'downloadReceipt'])->name('pagos.print');
Route::get('/pagos/comprobante/{comprobante}', \App\Livewire\Admin\Pagos\Comprobantes::class)->name('pagos.comprobante');

// Reportes
Route::prefix('reportes')->as('reportes.')->group(function () {
    Route::get('/estado-cuentas', \App\Livewire\Admin\Reportes\EstadoCuentas::class)->name('estado-cuentas');
    Route::get('/resumen-pagos', \App\Livewire\Admin\Reportes\ResumenPagos::class)->name('resumen-pagos');
    Route::get('/morosidad', \App\Livewire\Admin\Reportes\Morosidad::class)->name('morosidad');
    Route::get('/ingresos-totales', \App\Livewire\Admin\Reportes\IngresosTotales::class)->name('ingresos-totales');
    Route::get('/historico-matriculas', \App\Livewire\Admin\Reportes\HistoricoMatriculas::class)->name('historico-matriculas');

    // Reportes Académicos - Fase 1
    Route::get('/estadisticas-calificaciones-materia', \App\Livewire\Admin\Reportes\EstadisticasCalificacionesMateria::class)->name('estadisticas-calificaciones-materia');
    Route::get('/rendimiento-estudiantil-periodo', \App\Livewire\Admin\Reportes\RendimientoEstudiantilPeriodo::class)->name('rendimiento-estudiantil-periodo');
    Route::get('/asistencia-evaluaciones', \App\Livewire\Admin\Reportes\AsistenciaEvaluaciones::class)->name('asistencia-evaluaciones');
    Route::get('/boletines-calificaciones', \App\Livewire\Admin\Reportes\BoletinesCalificaciones::class)->name('boletines-calificaciones');
});


// Registro de Actividad
Route::get('/activity-log', \App\Livewire\Admin\ActivityLog::class)->name('activity-log');

// Sistema de Mensajería Interna
Route::get('/mensajeria', \App\Livewire\Admin\Mensajeria\ChatIndex::class)->name('mensajeria.index');

// Biblioteca Digital
Route::get('/biblioteca', \App\Livewire\Admin\Biblioteca\BibliotecaIndex::class)->name('biblioteca.index');

// Reuniones
Route::get('/reuniones', \App\Livewire\Admin\Reuniones\Index::class)->name('reuniones.index');

// Cajas
Route::get('/cajas', \App\Livewire\Admin\Cajas\Index::class)->name('cajas.index');
Route::get('/cajas/crear', \App\Livewire\Admin\Cajas\Create::class)->name('cajas.create');
Route::get('/cajas/{caja}', \App\Livewire\Admin\Cajas\Show::class)->name('cajas.show');
Route::get('/cajas/{caja}/export', [\App\Http\Controllers\Admin\CajaExportController::class, 'export'])->name('cajas.export');

// Reglas de Morosidad
Route::get('/reglas-morosidad', \App\Livewire\Admin\LatePaymentRules\Index::class)->name('late-payment-rules.index');

// Notificaciones
Route::get('/notifications', \App\Livewire\Admin\Notifications\Index::class)->name('notifications.index');

// WhatsApp - Nuevas rutas separadas
Route::prefix('whatsapp')->as('whatsapp.')->group(function () {
    // Dashboard principal
    Route::get('/dashboard', \App\Livewire\Admin\Whatsapp\WhatsAppDashboard::class)->name('dashboard');

    // Gestión de conexión
    Route::get('/connection', \App\Livewire\Admin\Whatsapp\WhatsAppConnection::class)->name('connection');

    // Enviar mensajes
    Route::get('/send-messages', \App\Livewire\Admin\Whatsapp\WhatsAppSendMessages::class)->name('send-messages');

    // Plantillas
    Route::get('/templates', \App\Livewire\Admin\Whatsapp\WhatsAppTemplates::class)->name('templates.index');

    // Historial
    Route::get('/history', \App\Livewire\Admin\Whatsapp\WhatsAppHistory::class)->name('history');

    // Mensajes programados
    Route::get('/scheduled-messages', \App\Livewire\Admin\Whatsapp\WhatsAppScheduledMessages::class)->name('scheduled-messages');

    // Mantener rutas antiguas para compatibilidad temporal
    Route::get('/', \App\Livewire\Admin\Whatsapp\Index::class)->name('index');

    // Estadísticas
    Route::get('/statistics', \App\Livewire\Admin\Whatsapp\WhatsAppStatistics::class)->name('statistics');
});

// Exportador de Base de Datos
Route::get('/exportar-base-datos', \App\Livewire\Admin\DatabaseExport::class)->name('database-export');

// WhatsApp
Route::get('/whatsapp', \App\Livewire\Admin\Whatsapp\Index::class)->name('whatsapp.index');

// ===== CONTROL DE ESTUDIOS =====

// Aulas (Classrooms)
Route::get('/aulas', \App\Livewire\Admin\Classroom\Index::class)->name('classrooms.index');
Route::get('/aulas/crear', \App\Livewire\Admin\Classroom\Create::class)->name('classrooms.create');
Route::get('/aulas/{classroom}/editar', \App\Livewire\Admin\Classroom\Edit::class)->name('classrooms.edit');
Route::get('/aulas/{classroom}', \App\Livewire\Admin\Classroom\Show::class)->name('classrooms.show');

// Período de Evaluación
Route::get('/lapsos-evaluacion', \App\Livewire\Admin\EvaluationPeriods\Index::class)->name('evaluation-periods.index');
Route::get('/lapsos-evaluacion/crear', \App\Livewire\Admin\EvaluationPeriods\Create::class)->name('evaluation-periods.create');
Route::get('/lapsos-evaluacion/{evaluationPeriod}/editar', \App\Livewire\Admin\EvaluationPeriods\Edit::class)->name('evaluation-periods.edit');

// Tipos de Evaluación
Route::get('/tipos-evaluacion', \App\Livewire\Admin\EvaluationTypes\Index::class)->name('evaluation-types.index');
Route::get('/tipos-evaluacion/crear', \App\Livewire\Admin\EvaluationTypes\Create::class)->name('evaluation-types.create');
Route::get('/tipos-evaluacion/{evaluationType}/editar', \App\Livewire\Admin\EvaluationTypes\Edit::class)->name('evaluation-types.edit');

// Evaluaciones
Route::get('/evaluaciones', \App\Livewire\Admin\Evaluations\Index::class)->name('evaluations.index');
Route::get('/evaluaciones/crear', \App\Livewire\Admin\Evaluations\Create::class)->name('evaluations.create');
Route::get('/evaluaciones/{evaluation}/editar', \App\Livewire\Admin\Evaluations\Edit::class)->name('evaluations.edit');
Route::get('/evaluaciones/{evaluation}', \App\Livewire\Admin\Evaluations\Show::class)->name('evaluations.show');

// Calificaciones
Route::get('/calificaciones', \App\Livewire\Admin\Grades\Index::class)->name('grades.index');
Route::get('/calificaciones/registrar/{evaluation}', \App\Livewire\Admin\Grades\Register::class)->name('grades.register');
Route::get('/calificaciones/estudiante/{student}', \App\Livewire\Admin\Grades\StudentGrades::class)->name('grades.student');

// ===== SECCIONES Y HORARIOS =====

// Horarios - Rutas específicas deben ir antes que las rutas con parámetros
Route::get('/horarios', \App\Livewire\Admin\Schedules\Index::class)->name('schedules.index');
Route::get('/horarios/crear', \App\Livewire\Admin\Schedules\Create::class)->name('schedules.create');
Route::get('/horarios/{schedule}/editar', \App\Livewire\Admin\Schedules\Edit::class)->name('schedules.edit');
Route::get('/horarios/{schedule}', \App\Livewire\Admin\Schedules\Show::class)->name('schedules.show');

// Secciones
Route::get('/secciones', \App\Livewire\Admin\Sections\Index::class)->name('sections.index');
Route::get('/secciones/crear', \App\Livewire\Admin\Sections\Create::class)->name('sections.create');
Route::get('/secciones/{section}/editar', \App\Livewire\Admin\Sections\Edit::class)->name('sections.edit');
Route::get('/secciones/{section}', \App\Livewire\Admin\Sections\Show::class)->name('sections.show');

// ===== ASISTENCIA DIARIA =====

Route::get('/asistencia', \App\Livewire\Admin\Attendance\Index::class)->name('attendance.index');
Route::get('/asistencia/registrar', \App\Livewire\Admin\Attendance\Register::class)->name('attendance.register');
Route::get('/asistencia/estudiante/{student}', \App\Livewire\Admin\Attendance\StudentReport::class)->name('attendance.student');

// ===== LIBRO DE VIDA (CONDUCTA) =====

Route::get('/libro-vida', \App\Livewire\Admin\ConductRecords\Index::class)->name('conduct-records.index');
Route::get('/libro-vida/crear', \App\Livewire\Admin\ConductRecords\Create::class)->name('conduct-records.create');
Route::get('/libro-vida/{conductRecord}', \App\Livewire\Admin\ConductRecords\Show::class)->name('conduct-records.show');
Route::get('/libro-vida/estudiante/{student}', \App\Livewire\Admin\ConductRecords\StudentHistory::class)->name('conduct-records.student');

// ===== ACTAS DE NOTAS =====

Route::get('/actas-notas', \App\Livewire\Admin\GradeReports\Index::class)->name('grade-reports.index');
Route::get('/actas-notas/crear', \App\Livewire\Admin\GradeReports\Create::class)->name('grade-reports.create');
Route::get('/actas-notas/{gradeReport}', \App\Livewire\Admin\GradeReports\Show::class)->name('grade-reports.show');

// ===== CONSTANCIAS Y CERTIFICADOS =====

Route::get('/certificados', \App\Livewire\Admin\Certificates\Index::class)->name('certificates.index');
Route::get('/certificados/crear', \App\Livewire\Admin\Certificates\Create::class)->name('certificates.create');
Route::get('/certificados/{certificate}', \App\Livewire\Admin\Certificates\Show::class)->name('certificates.show');

// ===== SEGUIMIENTO ACADÉMICO - FASE 3 =====

// Historial Académico
Route::get('/seguimiento-academico/historial', \App\Livewire\Admin\AcademicTracking\AcademicHistory::class)->name('academic-tracking.academic-history');

// Control de Promoción
Route::get('/seguimiento-academico/control-promocion', \App\Livewire\Admin\AcademicTracking\PromotionControl::class)->name('academic-tracking.promotion-control');

// Gestión de Períodos de Recuperación
Route::get('/seguimiento-academico/periodos-recuperacion', \App\Livewire\Admin\AcademicTracking\RecoveryPeriodsManagement::class)->name('academic-tracking.recovery-periods');
