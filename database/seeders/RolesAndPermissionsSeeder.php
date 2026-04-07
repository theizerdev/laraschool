<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RolesAndPermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Definir módulos y sus permisos
        $modules = [
            'empresas' => [
                'access empresas',
                'create empresas',
                'edit empresas',
                'delete empresas',
            ],
            'paises' => [
                'access paises',
                'create paises',
                'edit paises',
                'delete paises',
            ],
            'sucursales' => [
                'access sucursales',
                'create sucursales',
                'edit sucursales',
                'delete sucursales',
            ],
            'users' => [
                'access users',
                'create users',
                'edit users',
                'delete users',
            ],
            'roles' => [
                'access roles',
                'create roles',
                'edit roles',
                'delete roles',
                'assign roles',
            ],
            'permissions' => [
                'access permissions',
                'create permissions',
                'edit permissions',
                'delete permissions',
                'assign permissions',
            ],
            'school_years' => [
                'access school years',
                'create school years',
                'edit school years',
                'delete school years',
            ],
            'school_periods' => [
                'access school periods',
                'create school periods',
                'edit school periods',
                'delete school periods',
            ],
            'educational_levels' => [
                'access educational levels',
                'create educational levels',
                'edit educational levels',
                'delete educational levels',
            ],
            'turnos' => [
                'access turnos',
                'create turnos',
                'edit turnos',
                'delete turnos',
            ],
            'students' => [
                'access students',
                'create students',
                'edit students',
                'delete students',
                'access student control',
                'export students',
                'import students',
                'view student historico',
            ],
            'active_sessions' => [
                'view active sessions',
                'delete active sessions',
            ],
            'dashboard' => [
                'access dashboard',
                'dashboard.alerts',
                'dashboard.financial',
                'dashboard.academic',
                'dashboard.access',
                'dashboard.charts',
            ],
            'niveles_educativos' => [
                'access niveles educativos',
                'create niveles educativos',
                'edit niveles educativos',
                'delete niveles educativos',
            ],
            'monitoreo' => [
                'access monitoreo',
                'view monitoreo servidor',
                'view monitoreo base-datos',
                'view monitoreo estudiantes',
                'view monitoreo accesos',
                'export monitoreo accesos',
            ],
            // Módulo de matrículas
            'matriculas' => [
                'access matriculas',
                'create matriculas',
                'edit matriculas',
                'delete matriculas',
                'view matriculas',
                'cambiar cuotas matriculas',
            ],
            // Módulo de pagos
            'pagos' => [
                'access pagos',
                'create pagos',
                'edit pagos',
                'delete pagos',
                'view pagos',
            ],
            // Módulo de conceptos de pago
            'conceptos_pago' => [
                'access conceptos pago',
                'create conceptos pago',
                'edit conceptos pago',
                'delete conceptos pago',
                'view conceptos pago',
            ],
            // Módulo de programas
            'programas' => [
                'access programas',
                'create programas',
                'edit programas',
                'delete programas',
                'view programas',
            ],
            // Módulo de reportes
            'reportes' => [
                'access reportes',
                'view estado cuentas',
                'view resumen pagos',
                'view morosidad',
                'view ingresos totales',
                'view historico matriculas',
                'export reportes',
                // Reportes Académicos - Fase 1
                'view estadisticas calificaciones materia',
                'view rendimiento estudiantil periodo',
                'view asistencia evaluaciones',
                'view boletines calificaciones',
            ],
            // Módulo de actividad
            'activity_log' => [
                'access activity log',
                'view activity log',
                'delete activity log',
                'export activity log',
            ],
            // Módulo de exportación de base de datos
            'database_export' => [
                'access database export',
                'export database',
            ],
            // Módulo de mensajería interna
            'mensajeria' => [
                'access mensajeria',
                'create mensajeria',
                'edit mensajeria',
                'delete mensajeria',
                'send mensajeria',
                'receive mensajeria',
                'manage mensajeria templates',
            ],
            // Módulo de biblioteca digital
            'biblioteca' => [
                'access biblioteca',
                'create biblioteca',
                'edit biblioteca',
                'delete biblioteca',
                'upload biblioteca',
                'download biblioteca',
                'share biblioteca',
                'manage biblioteca categories',
            ],
            // Módulo de series de documentos
            'series' => [
                'access series',
                'create series',
                'edit series',
                'delete series',
            ],
            // Módulo de cajas
            'cajas' => [
                'access cajas',
                'create cajas',
                'edit cajas',
                'delete cajas',
                'view cajas',
            ],
            // Módulo de tasas de cambio
            'exchange_rates' => [
                'view exchange-rates',
                'fetch exchange-rates',
                'edit exchange-rates',
                'manage exchange-rates',
            ],
            // Módulo de reuniones
            'reuniones' => [
                'access reuniones',
                'create reuniones',
                'edit reuniones',
                'delete reuniones',
                'view reuniones',
            ],
            // Módulo de reglas de morosidad
            'late_payment_rules' => [
                'access late payment rules',
                'create late payment rules',
                'edit late payment rules',
                'delete late payment rules',
            ],
            // Módulo de WhatsApp
            'whatsapp' => [
                'access whatsapp',
                'create whatsapp templates',
                'edit whatsapp templates',
                'delete whatsapp templates',
                'send whatsapp messages',
                'schedule whatsapp messages',
                'view whatsapp statistics',
                'export whatsapp reports',
                'retry failed whatsapp messages',
                'view whatsapp retry statistics',
                'manage whatsapp auto retry',
            ],

            // Materias (Subjects)
            'subjects' => [
                'access subjects',
                'view subjects',
                'create subjects',
                'edit subjects',
                'delete subjects',
                'assign teachers',
                'assign students',
                'manage subject schedules',
                'manage prerequisites',
            ],
            // Planes de Estudio (Study Plans)
            'study_plans' => [
                'access study_plans',
                'view study_plans',
                'create study_plans',
                'edit study_plans',
                'delete study_plans',
                'export study_plans',
            ],
            // Profesores (Teachers)
            'teachers' => [
                'access teachers',
                'view teachers',
                'create teachers',
                'edit teachers',
                'delete teachers',
                'export teachers',
                'manage teacher assignments',
                'view teacher schedules',
                'manage teacher schedules',
            ],
            // Control de Estudios - Lapsos (Evaluation Periods)
            'evaluation_periods' => [
                'access evaluation periods',
                'view evaluation periods',
                'create evaluation periods',
                'edit evaluation periods',
                'delete evaluation periods',
                'manage evaluation periods',
            ],
            // Control de Estudios - Tipos de Evaluación (Evaluation Types)
            'evaluation_types' => [
                'access evaluation types',
                'view evaluation types',
                'create evaluation types',
                'edit evaluation types',
                'delete evaluation types',
                'manage evaluation types',
            ],
            // Control de Estudios - Evaluaciones (Evaluations)
            'evaluations' => [
                'access evaluations',
                'view evaluations',
                'create evaluations',
                'edit evaluations',
                'delete evaluations',
                'manage evaluations',
                'approve evaluations',
                'publish evaluations',
            ],
            // Control de Estudios - Calificaciones (Grades)
            'grades' => [
                'access grades',
                'view grades',
                'create grades',
                'edit grades',
                'delete grades',
                'manage grades',
                'import grades',
                'export grades',
                'publish grades',
                'approve grades',
            ],
            // Secciones y Horarios - Aulas (Classrooms)
            'classrooms' => [
                'access classrooms',
                'view classrooms',
                'create classrooms',
                'edit classrooms',
                'delete classrooms',
                'manage classrooms',
                'assign classrooms',
            ],
            // Secciones y Horarios - Secciones (Sections)
            'sections' => [
                'access sections',
                'view sections',
                'create sections',
                'edit sections',
                'delete sections',
                'manage sections',
                'assign sections',
                'enroll students sections',
                'manage section schedules',
            ],
            // Secciones y Horarios - Horarios (Schedules)
            'schedules' => [
                'access',
                'view',
                'create',
                'edit',
                'delete',
                'manage',
                'assign',
                'conflict resolution',
                'publish',
            ],
            // Seguimiento Académico - Historial Académico
            'academic_records' => [
                'access academic records',
                'view academic records',
                'create academic records',
                'edit academic records',
                'delete academic records',
                'manage academic records',
                'export academic records',
                'view student academic history',
                'manage student promotion',
                'manage student retention',
                'manage student withdrawal',
                'view academic statistics',
                'generate academic reports',
            ],
            // Seguimiento Académico - Períodos de Recuperación
            'recovery_periods' => [
                'access recovery periods',
                'view recovery periods',
                'create recovery periods',
                'edit recovery periods',
                'delete recovery periods',
                'manage recovery periods',
                'approve recovery periods',
                'activate recovery periods',
                'manage recovery registrations',
                'view recovery statistics',
                'export recovery reports',
            ],
            // Seguimiento Académico - Control de Promoción
            'promotion_control' => [
                'access promotion control',
                'view promotion control',
                'manage student promotion',
                'manage student retention',
                'approve promotions',
                'generate promotion reports',
                'view promotion statistics',
                'manage grade repetition',
                'manage academic warnings',
            ],
        ];

        // Crear permisos organizados por módulos
        foreach ($modules as $module => $permissions) {
            foreach ($permissions as $permission) {
                Permission::firstOrCreate(
                    ['name' => $permission],
                    ['module' => $module]
                );
            }
        }

        // Crear roles y asignar permisos
        $superAdminRole = Role::firstOrCreate(['name' => 'Super Administrador']);
        $adminRole = Role::firstOrCreate(['name' => 'Administrador']);
        $recepcionistaRole = Role::firstOrCreate(['name' => 'Recepcionista']);
        $profesorRole = Role::firstOrCreate(['name' => 'Profesor']);

        // Asignar todos los permisos al Super Administrador
        $superAdminRole->syncPermissions(Permission::all());

        // Asignar permisos al Administrador (todos menos los de super admin)
        $adminPermissions = Permission::whereNotIn('name', [
            'assign roles',
            'assign permissions'
        ])->get();
        $adminRole->syncPermissions($adminPermissions);

        // Asignar permisos de Control de Estudios y Secciones y Horarios a Administradores y Super Administradores
        $academicPermissions = Permission::whereIn('module', [
            'evaluation_periods',
            'evaluation_types',
            'evaluations',
            'grades',
            'classrooms',
            'sections',
            'schedules'
        ])->get();

        $superAdminRole->givePermissionTo($academicPermissions);
        $adminRole->givePermissionTo($academicPermissions);

        // Asignar permisos de Planes de Estudio a Administradores y Super Administradores
        $studyPlanPermissions = Permission::whereIn('module', [
            'study_plans'
        ])->get();

        $superAdminRole->givePermissionTo($studyPlanPermissions);
        $adminRole->givePermissionTo($studyPlanPermissions);

        // Asignar permisos de Reportes Académicos a Administradores y Super Administradores
        $academicReportsPermissions = Permission::whereIn('name', [
            'view estadisticas calificaciones materia',
            'view rendimiento estudiantil periodo',
            'view asistencia evaluaciones',
            'view boletines calificaciones'
        ])->get();

        $superAdminRole->givePermissionTo($academicReportsPermissions);
        $adminRole->givePermissionTo($academicReportsPermissions);

        // Asignar permisos de Seguimiento Académico a Administradores y Super Administradores
        $academicTrackingPermissions = Permission::whereIn('module', [
            'academic_records',
            'recovery_periods',
            'promotion_control'
        ])->get();

        $superAdminRole->givePermissionTo($academicTrackingPermissions);
        $adminRole->givePermissionTo($academicTrackingPermissions);

        // Asignar permisos al Recepcionista (estudiantes, matrículas, pagos, profesores, dashboard básico y algunos de Control de Estudios)
        $recepcionistaPermissions = Permission::whereIn('name', [
            'access students',
            'create students',
            'edit students',
            'view student historico',
            'access matriculas',
            'create matriculas',
            'edit matriculas',
            'view matriculas',
            'access pagos',
            'create pagos',
            'view pagos',
            'access teachers',
            'view teachers',
            'access dashboard',
            'dashboard.alerts',
            'dashboard.academic',
            'dashboard.access',
            'access evaluation periods',
            'view evaluation periods',
            'access evaluation types',
            'view evaluation types',
            'access classrooms',
            'view classrooms',
            'access sections',
            'view sections',
            'access schedules',
            'view schedules'
        ])->get();
        $recepcionistaRole->syncPermissions($recepcionistaPermissions);

        // Asignar permisos básicos al Profesor (profesores, dashboard básico y Control de Estudios limitado)
        $profesorPermissions = Permission::whereIn('name', [
            'access dashboard',
            'dashboard.academic',
            'dashboard.access',
            'access teachers',
            'view teachers',
            'edit teachers',
            'access evaluation periods',
            'view evaluation periods',
            'access evaluation types',
            'view evaluation types',
            'access evaluations',
            'view evaluations',
            'create evaluations',
            'access grades',
            'view grades',
            'create grades',
            'edit grades',
            'access classrooms',
            'view classrooms',
            'access sections',
            'view sections',
            'access schedules',
            'view schedules',
            'view estadisticas calificaciones materia',
            'view rendimiento estudiantil periodo',
            'view asistencia evaluaciones',
            'view boletines calificaciones'
        ])->get();
        $profesorRole->syncPermissions($profesorPermissions);

        // Asignar permisos de mensajería, biblioteca, series, cajas, reuniones, países, exportación de base de datos y WhatsApp a Administradores y Super Administradores
        $mensajeriaBibliotecaSeriesCajasPaisesExportPermissions = Permission::whereIn('module', [
            'mensajeria',
            'biblioteca',
            'series',
            'cajas',
            'reuniones',
            'paises',
            'database_export',
            'whatsapp'
        ])->get();

        $superAdminRole->givePermissionTo($mensajeriaBibliotecaSeriesCajasPaisesExportPermissions);
        $adminRole->givePermissionTo($mensajeriaBibliotecaSeriesCajasPaisesExportPermissions);
    }
}
