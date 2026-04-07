<?php

namespace App\Repositories\Contracts;

use App\Repositories\BaseRepositoryInterface;

interface MatriculaRepositoryInterface extends BaseRepositoryInterface
{
    public function getAllWithRelationships();
    public function getByEstudiante($estudianteId);
    public function getByPrograma($programaId);
    public function getSolventes();
    public function getMorosas();
    public function createWithPaymentSchedule(array $data, array $paymentSchedule);
    public function updateSolvencyStatus($matriculaId);
}