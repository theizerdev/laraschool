<?php

namespace App\Traits;

use Illuminate\Database\Eloquent\Builder;

trait SoftDeleteForReports
{
    /**
     * Scope para obtener solo registros activos (no eliminados) para reportes
     */
    public function scopeActiveForReports(Builder $query): Builder
    {
        return $query->whereNull($this->getTable() . '.deleted_at');
    }

    /**
     * Scope para obtener todos los registros incluyendo los eliminados para reportes históricos
     */
    public function scopeWithDeletedForReports(Builder $query): Builder
    {
        return $query->withTrashed();
    }
}
