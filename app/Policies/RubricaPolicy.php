<?php

namespace App\Policies;

use App\Models\Rubrica;
use App\Models\User;

/**
 * Permisos del módulo de rúbricas.
 *
 * RubricaController llama a authorize() en todos sus métodos. Sin esta clase,
 * Laravel no encuentra ninguna política y deniega el acceso a todos los roles,
 * dejando el módulo completo inutilizable.
 */
class RubricaPolicy
{
    /**
     * Consultar el listado de rúbricas.
     *
     * El jurado necesita consultarlas para calificar con la rúbrica que
     * corresponde, y el tutor para orientar a sus estudiantes.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasRole(['Administrador', 'Coordinador', 'Docente/Tutor', 'Jurado']);
    }

    /**
     * Consultar una rúbrica concreta con sus criterios.
     */
    public function view(User $user, Rubrica $rubrica): bool
    {
        return $this->viewAny($user);
    }

    /**
     * Crear rúbricas: define los criterios de calificación institucionales.
     */
    public function create(User $user): bool
    {
        return $user->hasRole(['Administrador', 'Coordinador']);
    }

    /**
     * Modificar una rúbrica.
     */
    public function update(User $user, Rubrica $rubrica): bool
    {
        return $user->hasRole(['Administrador', 'Coordinador']);
    }

    /**
     * Eliminar una rúbrica. El controlador impide además borrar las que ya
     * tienen evaluaciones asociadas.
     */
    public function delete(User $user, Rubrica $rubrica): bool
    {
        return $user->hasRole(['Administrador', 'Coordinador']);
    }
}
