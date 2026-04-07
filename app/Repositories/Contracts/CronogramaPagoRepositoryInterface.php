<?php

namespace App\Repositories\Contracts;

use App\Repositories\BaseRepositoryInterface;

interface CronogramaPagoRepositoryInterface extends BaseRepositoryInterface
{
    public function getByMatricula($matriculaId);
    public function getVencidos();
    public function getPendientes();
    public function getByDateRange($fechaInicio, $fechaFin);
    public function updateSolvencyStatus($id, $estado);
    public function getTotalPendienteByMatricula($matriculaId);
}