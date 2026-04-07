<?php

namespace App\Repositories\Contracts;

use App\Repositories\BaseRepositoryInterface;

interface EstudianteRepositoryInterface extends BaseRepositoryInterface
{
    public function getAllWithRelationships();
    public function search($searchTerm);
    public function getByNivelEducativo($nivelEducativoId);
    public function getMatriculados();
    public function getNoMatriculados();
}