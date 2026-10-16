<?php
/**
 * Script de Sincronización Administrativa: Vargas DB
 * 
 * Sincroniza:
 * 1. Estudiante faltante: Christian Javier Torreyes Ceballos (doc: 36393483)
 * 2. Cajas faltantes:
 *    - 2026-10-05: Corte 1 (User 2 - Administración)
 *    - 2026-10-07: Corte 2 (User 2 - Administración)
 *    - 2026-10-15: Corte 2 (User 2 - Administración)
 * 3. Matrículas faltantes del 05/10:
 *    - Temp 341 (Yeinerlin Victoria Rodriguez Gil)
 *    - Temp 342 (Christian Javier Torreyes Ceballos)
 *    - Temp 343 (Said Andre Yeren Delgado)
 *    - Temp 344 (Sarai Celeste Yeren Delgado)
 * 4. Cronograma de pagos (payment_schedules) de esas 4 matrículas
 * 5. Pagos administrativos faltantes (24 pagos):
 *    - 14 pagos del 2026-10-05
 *    - 2 pagos del 2026-10-07
 *    - 8 pagos del 2026-10-15
 *    Con nuevos números correlativos consecutivos (R001 desde 1438, CE001 desde 226)
 *    y guardando en observaciones el recibo original offline.
 * 6. Detalles de pago (pago_detalles) y actualización de cuotas (payment_schedules)
 * 7. Actualización de series (correlativo_actual)
 */

$isDryRun = in_array('--dry-run', $argv);

$pdoV = new PDO('mysql:host=127.0.0.1;port=3307;dbname=vargas', 'root', '', [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
]);

$pdoT = new PDO('mysql:host=127.0.0.1;port=3307;dbname=vargas_sync_temp', 'root', '', [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
]);

echo "========================================================================\n";
echo "SINCRONIZACIÓN ADMINISTRATIVA: VARGAS <- 160102026.sql\n";
echo "MODO: " . ($isDryRun ? "DRY-RUN (SIMULACIÓN CON ROLLBACK)" : "EJECUCIÓN REAL (COMMIT)") . "\n";
echo "========================================================================\n\n";

$pdoV->beginTransaction();

try {
    // -------------------------------------------------------------------------
    // 1. ESTUDIANTE FALTANTE
    // -------------------------------------------------------------------------
    echo "1. SINCRONIZANDO ESTUDIANTES...\n";
    $studentMap = []; // temp_id => vargas_id

    // Verificar todos los estudiantes en temp
    $tempStudents = $pdoT->query("SELECT * FROM students")->fetchAll();
    foreach ($tempStudents as $ts) {
        $stmt = $pdoV->prepare("SELECT id FROM students WHERE documento_identidad = ?");
        $stmt->execute([$ts['documento_identidad']]);
        $existingId = $stmt->fetchColumn();
        
        if ($existingId) {
            $studentMap[$ts['id']] = (int)$existingId;
        } else {
            echo "   -> Insertando estudiante nuevo: {$ts['nombres']} {$ts['apellidos']} ({$ts['documento_identidad']})...\n";
            $cols = array_keys($ts);
            $colsWithoutId = array_filter($cols, fn($c) => $c !== 'id');
            $colNames = implode(', ', array_map(fn($c) => "`$c`", $colsWithoutId));
            $placeholders = implode(', ', array_fill(0, count($colsWithoutId), '?'));
            
            $values = [];
            foreach ($colsWithoutId as $c) {
                $values[] = $ts[$c];
            }
            
            $stmtInsert = $pdoV->prepare("INSERT INTO students ($colNames) VALUES ($placeholders)");
            $stmtInsert->execute($values);
            $newStudentId = (int)$pdoV->lastInsertId();
            $studentMap[$ts['id']] = $newStudentId;
            echo "      Asignado nuevo ID en Vargas: $newStudentId (Temp ID: {$ts['id']})\n";
        }
    }

    // -------------------------------------------------------------------------
    // 2. CAJAS
    // -------------------------------------------------------------------------
    echo "\n2. SINCRONIZANDO CAJAS...\n";
    $cajaMap = []; // temp_caja_id => vargas_caja_id

    // Cajas del pasado previas a 144 ya son idénticas
    for ($i = 1; $i <= 143; $i++) {
        $cajaMap[$i] = $i;
    }

    // Caja 144 (2026-10-05): No existe caja en Vargas para esa fecha
    $tc144 = $pdoT->query("SELECT * FROM cajas WHERE id = 144")->fetch();
    echo "   -> Creando Caja para 2026-10-05 (User {$tc144['user_id']}, Corte {$tc144['numero_corte']})...\n";
    $stmtCaja = $pdoV->prepare("
        INSERT INTO cajas (empresa_id, sucursal_id, user_id, fecha, numero_corte, monto_inicial, total_efectivo, total_transferencias, total_tarjetas, total_ingresos, monto_final, estado, fecha_apertura, fecha_cierre, observaciones_apertura, observaciones_cierre, created_at, updated_at)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    $stmtCaja->execute([
        $tc144['empresa_id'], $tc144['sucursal_id'], $tc144['user_id'], $tc144['fecha'], $tc144['numero_corte'],
        $tc144['monto_inicial'], $tc144['total_efectivo'], $tc144['total_transferencias'], $tc144['total_tarjetas'],
        $tc144['total_ingresos'], $tc144['monto_final'], $tc144['estado'], $tc144['fecha_apertura'], $tc144['fecha_cierre'],
        $tc144['observaciones_apertura'], $tc144['observaciones_cierre'], $tc144['created_at'], $tc144['updated_at']
    ]);
    $newCaja144Id = (int)$pdoV->lastInsertId();
    $cajaMap[144] = $newCaja144Id;
    echo "      Caja 05/10 creada con ID: $newCaja144Id (Total: \${$tc144['total_ingresos']})\n";

    // Caja 145 en Temp (2026-10-07, User 2): En Vargas Caja 145 es de User 4 (Corte 1)
    // Creamos caja para User 2 como Corte 2
    $tc145 = $pdoT->query("SELECT * FROM cajas WHERE id = 145")->fetch();
    echo "   -> Creando Caja para 2026-10-07 (User {$tc145['user_id']} - Administración, Corte 2)...\n";
    $stmtCaja->execute([
        $tc145['empresa_id'], $tc145['sucursal_id'], $tc145['user_id'], $tc145['fecha'], 2, // corte 2
        $tc145['monto_inicial'], $tc145['total_efectivo'], $tc145['total_transferencias'], $tc145['total_tarjetas'],
        $tc145['total_ingresos'], $tc145['monto_final'], $tc145['estado'], $tc145['fecha_apertura'], $tc145['fecha_cierre'],
        $tc145['observaciones_apertura'], $tc145['observaciones_cierre'], $tc145['created_at'], $tc145['updated_at']
    ]);
    $newCaja145Id = (int)$pdoV->lastInsertId();
    $cajaMap[145] = $newCaja145Id;
    echo "      Caja 07/10 creada con ID: $newCaja145Id (Total: \${$tc145['total_ingresos']})\n";

    // Caja 146 en Temp (2026-10-15, User 2): En Vargas Caja 150 es de User 4 (Corte 1)
    // Creamos caja para User 2 como Corte 2
    $tc146 = $pdoT->query("SELECT * FROM cajas WHERE id = 146")->fetch();
    echo "   -> Creando Caja para 2026-10-15 (User {$tc146['user_id']} - Administración, Corte 2)...\n";
    $stmtCaja->execute([
        $tc146['empresa_id'], $tc146['sucursal_id'], $tc146['user_id'], $tc146['fecha'], 2, // corte 2
        $tc146['monto_inicial'], $tc146['total_efectivo'], $tc146['total_transferencias'], $tc146['total_tarjetas'],
        $tc146['total_ingresos'], $tc146['monto_final'], $tc146['estado'], $tc146['fecha_apertura'], $tc146['fecha_cierre'],
        $tc146['observaciones_apertura'], $tc146['observaciones_cierre'], $tc146['created_at'], $tc146['updated_at']
    ]);
    $newCaja146Id = (int)$pdoV->lastInsertId();
    $cajaMap[146] = $newCaja146Id;
    echo "      Caja 15/10 creada con ID: $newCaja146Id (Total: \${$tc146['total_ingresos']})\n";


    // -------------------------------------------------------------------------
    // 3. MATRÍCULAS Y PAYMENT SCHEDULES
    // -------------------------------------------------------------------------
    echo "\n3. SINCRONIZANDO MATRÍCULAS Y PLANES DE PAGO...\n";
    $matriculaMap = []; // temp_mat_id => vargas_mat_id
    $scheduleMap = [];  // temp_sched_id => vargas_sched_id

    // Matrículas hasta la 339 ya son idénticas
    for ($i = 1; $i <= 339; $i++) {
        $matriculaMap[$i] = $i;
    }

    // Matrículas faltantes del 05/10: 341, 342, 343, 344
    $tempMatriculas = $pdoT->query("SELECT * FROM matriculas WHERE id IN (341, 342, 343, 344) ORDER BY id")->fetchAll();

    foreach ($tempMatriculas as $tm) {
        $newEstudianteId = $studentMap[$tm['estudiante_id']];
        echo "   -> Insertando Matrícula Temp #{$tm['id']} para Estudiante Vargas #$newEstudianteId (Fecha: {$tm['fecha_matricula']})...\n";
        
        $stmtMat = $pdoV->prepare("
            INSERT INTO matriculas (empresa_id, sucursal_id, estudiante_id, programa_id, periodo_id, fecha_matricula, costo, cuota_inicial, numero_cuotas, estado, solvente, created_at, updated_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $stmtMat->execute([
            $tm['empresa_id'], $tm['sucursal_id'], $newEstudianteId, $tm['programa_id'], $tm['periodo_id'],
            $tm['fecha_matricula'], $tm['costo'], $tm['cuota_inicial'], $tm['numero_cuotas'],
            $tm['estado'], $tm['solvente'], $tm['created_at'], $tm['updated_at']
        ]);
        $newMatId = (int)$pdoV->lastInsertId();
        $matriculaMap[$tm['id']] = $newMatId;
        echo "      Matrícula creada con ID: $newMatId\n";

        // Insertar payment_schedules para esta matrícula
        $tempSchedules = $pdoT->prepare("SELECT * FROM payment_schedules WHERE matricula_id = ? ORDER BY numero_cuota");
        $tempSchedules->execute([$tm['id']]);
        $schedules = $tempSchedules->fetchAll();

        $stmtSched = $pdoV->prepare("
            INSERT INTO payment_schedules (matricula_id, numero_cuota, monto, monto_pagado, fecha_vencimiento, fecha_pago, estado, created_at, updated_at, empresa_id, sucursal_id)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");

        foreach ($schedules as $sc) {
            $stmtSched->execute([
                $newMatId, $sc['numero_cuota'], $sc['monto'], 0.00, // inicializamos monto_pagado en 0, luego se sincronizará con los pagos
                $sc['fecha_vencimiento'], null, 'pendiente',
                $sc['created_at'], $sc['updated_at'], $sc['empresa_id'], $sc['sucursal_id']
            ]);
            $newSchedId = (int)$pdoV->lastInsertId();
            $scheduleMap[$sc['id']] = $newSchedId;
        }
        echo "      -> Creadas " . count($schedules) . " cuotas para Matrícula $newMatId\n";
    }


    // -------------------------------------------------------------------------
    // 4. PAGOS Y PAGO_DETALLES
    // -------------------------------------------------------------------------
    echo "\n4. SINCRONIZANDO PAGOS Y DETALLES...\n";
    
    // Obtener series actuales
    $serieR001 = $pdoV->query("SELECT * FROM series WHERE serie = 'R001'")->fetch();
    $serieCE001 = $pdoV->query("SELECT * FROM series WHERE serie = 'CE001'")->fetch();
    
    $correlativoR001 = (int)$serieR001['correlativo_actual'];
    $correlativoCE001 = (int)$serieCE001['correlativo_actual'];

    echo "   Series actuales en Vargas:\n";
    echo "   - R001:  Correlativo actual = $correlativoR001 (próximo: " . ($correlativoR001 + 1) . ")\n";
    echo "   - CE001: Correlativo actual = $correlativoCE001 (próximo: " . ($correlativoCE001 + 1) . ")\n";

    // Pagos a sincronizar: 1873 a 1896 (24 pagos)
    $tempPagos = $pdoT->query("
        SELECT * FROM pagos 
        WHERE id BETWEEN 1873 AND 1896 
        ORDER BY fecha ASC, id ASC
    ")->fetchAll();

    $stmtPago = $pdoV->prepare("
        INSERT INTO pagos (
            caja_id, serie_id, serie, numero, tipo_pago, fecha, matricula_id, user_id,
            subtotal, descuento, total, tasa_cambio, total_bolivares, metodo_pago,
            es_pago_mixto, detalles_pago_mixto, referencia, estado, observaciones,
            empresa_id, sucursal_id, created_at, updated_at
        ) VALUES (
            ?, ?, ?, ?, ?, ?, ?, ?,
            ?, ?, ?, ?, ?, ?,
            ?, ?, ?, ?, ?,
            ?, ?, ?, ?
        )
    ");

    $stmtDetalle = $pdoV->prepare("
        INSERT INTO pago_detalles (
            pago_id, concepto_pago_id, payment_schedule_id, descripcion, cantidad, precio_unitario, subtotal, created_at, updated_at
        ) VALUES (
            ?, ?, ?, ?, ?, ?, ?, ?, ?
        )
    ");

    $schedulesToSync = []; // lista de payment_schedule_ids a recalcular

    foreach ($tempPagos as $tp) {
        $tipoSerie = $tp['serie'];
        $serieId = null;
        $nuevoNumeroInt = 0;
        
        if ($tipoSerie === 'R001') {
            $correlativoR001++;
            $nuevoNumeroInt = $correlativoR001;
            $serieId = $serieR001['id'];
            $longitud = (int)$serieR001['longitud_correlativo'];
        } elseif ($tipoSerie === 'CE001') {
            $correlativoCE001++;
            $nuevoNumeroInt = $correlativoCE001;
            $serieId = $serieCE001['id'];
            $longitud = (int)$serieCE001['longitud_correlativo'];
        } else {
            throw new Exception("Serie desconocida: $tipoSerie en Temp Pago #{$tp['id']}");
        }

        $nuevoNumeroStr = str_pad($nuevoNumeroInt, $longitud, '0', STR_PAD_LEFT);
        $vargasMatriculaId = $matriculaMap[$tp['matricula_id']] ?? $tp['matricula_id'];
        $vargasCajaId = $cajaMap[$tp['caja_id']];

        $notaSync = "Sincronizado: Recibo original offline {$tp['serie']}-{$tp['numero']}";
        $obsFinal = !empty($tp['observaciones']) ? $notaSync . " | " . $tp['observaciones'] : $notaSync;

        $stmtPago->execute([
            $vargasCajaId,
            $serieId,
            $tipoSerie,
            $nuevoNumeroStr,
            $tp['tipo_pago'],
            $tp['fecha'],
            $vargasMatriculaId,
            $tp['user_id'],
            $tp['subtotal'],
            $tp['descuento'],
            $tp['total'],
            $tp['tasa_cambio'],
            $tp['total_bolivares'],
            $tp['metodo_pago'],
            $tp['es_pago_mixto'],
            $tp['detalles_pago_mixto'],
            $tp['referencia'],
            $tp['estado'],
            $obsFinal,
            $tp['empresa_id'],
            $tp['sucursal_id'],
            $tp['created_at'],
            $tp['updated_at']
        ]);
        $newPagoId = (int)$pdoV->lastInsertId();

        echo sprintf("   -> Pago Temp #%d => Vargas #%d | Fecha: %s | %s-%s (orig: %s-%s) | \$%.2f | Ref: %s | Caja: %d | Mat: %d\n",
            $tp['id'], $newPagoId, $tp['fecha'], $tipoSerie, $nuevoNumeroStr, $tp['serie'], $tp['numero'], $tp['total'], $tp['referencia'], $vargasCajaId, $vargasMatriculaId
        );

        // Detalles del pago
        $tempDetalles = $pdoT->prepare("SELECT * FROM pago_detalles WHERE pago_id = ? ORDER BY id");
        $tempDetalles->execute([$tp['id']]);
        $detalles = $tempDetalles->fetchAll();

        foreach ($detalles as $det) {
            $vargasSchedId = null;
            if ($det['payment_schedule_id']) {
                // Si la cuota pertenecía a las 4 matrículas nuevas, mapear
                if (isset($scheduleMap[$det['payment_schedule_id']])) {
                    $vargasSchedId = $scheduleMap[$det['payment_schedule_id']];
                } else {
                    // Si pertenecía a una matrícula previa existente, el schedule_id es el mismo
                    $vargasSchedId = $det['payment_schedule_id'];
                }
                $schedulesToSync[$vargasSchedId] = true;
            }

            $stmtDetalle->execute([
                $newPagoId,
                $det['concepto_pago_id'],
                $vargasSchedId,
                $det['descripcion'],
                $det['cantidad'],
                $det['precio_unitario'],
                $det['subtotal'],
                $det['created_at'],
                $det['updated_at']
            ]);
        }
    }

    // -------------------------------------------------------------------------
    // 5. ACTUALIZAR CORRELATIVOS EN TABLA SERIES
    // -------------------------------------------------------------------------
    echo "\n5. ACTUALIZANDO TABLA SERIES...\n";
    $stmtUpdateSerie = $pdoV->prepare("UPDATE series SET correlativo_actual = ?, updated_at = NOW() WHERE id = ?");
    $stmtUpdateSerie->execute([$correlativoR001, $serieR001['id']]);
    echo "   - Serie R001 actualizada a correlativo_actual = $correlativoR001\n";

    $stmtUpdateSerie->execute([$correlativoCE001, $serieCE001['id']]);
    echo "   - Serie CE001 actualizada a correlativo_actual = $correlativoCE001\n";


    // -------------------------------------------------------------------------
    // 6. ACTUALIZAR CUOTAS (PAYMENT_SCHEDULES) AFECTADAS
    // -------------------------------------------------------------------------
    echo "\n6. RECALCULANDO CUOTAS AFECTADAS EN PAYMENT_SCHEDULES...\n";
    $schedIds = array_keys($schedulesToSync);
    echo "   Total de cuotas afectadas a sincronizar: " . count($schedIds) . "\n";

    $stmtSum = $pdoV->prepare("
        SELECT COALESCE(SUM(pd.subtotal), 0)
        FROM pago_detalles pd
        JOIN pagos p ON pd.pago_id = p.id
        WHERE pd.payment_schedule_id = ? AND p.estado = 'aprobado'
    ");

    $stmtSchedUpdate = $pdoV->prepare("
        UPDATE payment_schedules 
        SET monto_pagado = ?,
            estado = ?,
            fecha_pago = ?,
            updated_at = NOW()
        WHERE id = ?
    ");

    $affectedMatriculas = [];

    foreach ($schedIds as $sid) {
        $stmtSum->execute([$sid]);
        $totalPagado = (float)$stmtSum->fetchColumn();

        $stmtCur = $pdoV->prepare("SELECT matricula_id, monto, fecha_vencimiento, estado FROM payment_schedules WHERE id = ?");
        $stmtCur->execute([$sid]);
        $curSched = $stmtCur->fetch();
        if (!$curSched) {
            echo "   [ALERTA] Schedule ID $sid no encontrado en Vargas!\n";
            continue;
        }

        $monto = (float)$curSched['monto'];
        $affectedMatriculas[$curSched['matricula_id']] = true;

        $nuevoEstado = 'pendiente';
        $fechaPago = null;

        if ($totalPagado >= ($monto - 0.01)) {
            $nuevoEstado = 'pagado';
            // Obtener fecha del último pago aplicado a esta cuota
            $stmtFecha = $pdoV->prepare("
                SELECT p.fecha FROM pago_detalles pd JOIN pagos p ON pd.pago_id = p.id 
                WHERE pd.payment_schedule_id = ? AND p.estado = 'aprobado' ORDER BY p.fecha DESC LIMIT 1
            ");
            $stmtFecha->execute([$sid]);
            $fechaPago = $stmtFecha->fetchColumn() ?: '2026-10-05';
        } elseif ($totalPagado > 0.00) {
            $nuevoEstado = 'parcial';
        } elseif (strtotime($curSched['fecha_vencimiento']) < time()) {
            $nuevoEstado = 'vencido';
        }

        $stmtSchedUpdate->execute([$totalPagado, $nuevoEstado, $fechaPago, $sid]);
        echo sprintf("   - Cuota ID %5d (Mat %3d): Monto \$%6.2f | Pagado: \$%6.2f | Estado: %-9s\n",
            $sid, $curSched['matricula_id'], $monto, $totalPagado, $nuevoEstado
        );
    }

    // -------------------------------------------------------------------------
    // 7. VERIFICAR TOTALES DE LAS CAJAS
    // -------------------------------------------------------------------------
    echo "\n7. RECALCULANDO TOTALES DE CAJAS SINCRONIZADAS...\n";
    $cajasToRecalc = [$newCaja144Id, $newCaja145Id, $newCaja146Id];
    foreach ($cajasToRecalc as $cid) {
        $stmtCajaPagos = $pdoV->prepare("
            SELECT COALESCE(SUM(total), 0) as total, COUNT(*) as count 
            FROM pagos 
            WHERE caja_id = ? AND estado = 'aprobado'
        ");
        $stmtCajaPagos->execute([$cid]);
        $row = $stmtCajaPagos->fetch();
        
        $pdoV->prepare("UPDATE cajas SET total_ingresos = ?, monto_final = ?, total_transferencias = ? WHERE id = ?")
             ->execute([$row['total'], $row['total'], $row['total'], $cid]);
             
        $cajaInfo = $pdoV->query("SELECT id, fecha, numero_corte, user_id, total_ingresos, estado FROM cajas WHERE id = $cid")->fetch();
        echo sprintf("   - Caja ID %d | Fecha: %s | Corte: %d | User: %d | Total Pagos: %d | Monto: \$%.2f | Estado: %s\n",
            $cajaInfo['id'], $cajaInfo['fecha'], $cajaInfo['numero_corte'], $cajaInfo['user_id'], $row['count'], $cajaInfo['total_ingresos'], $cajaInfo['estado']
        );
    }

    if ($isDryRun) {
        $pdoV->rollBack();
        echo "\n========================================================================\n";
        echo "SIMULACIÓN COMPLETADA EXITOSAMENTE (ROLLBACK EJECUTADO, NINGÚN CAMBIO APLICADO)\n";
        echo "========================================================================\n";
    } else {
        $pdoV->commit();
        echo "\n========================================================================\n";
        echo "SINCRONIZACIÓN COMPLETADA Y CONFIRMADA EXITOSAMENTE (COMMIT APLICADO)\n";
        echo "========================================================================\n";
    }

} catch (Exception $e) {
    if ($pdoV->inTransaction()) {
        $pdoV->rollBack();
    }
    echo "\n[ERROR CRÍTICO] La transacción fue cancelada (ROLLBACK):\n" . $e->getMessage() . "\n";
    echo $e->getTraceAsString() . "\n";
    exit(1);
}
