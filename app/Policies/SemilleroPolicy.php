<?php

namespace App\Policies;

use App\Models\Semillero;
use App\Models\User;

/**
 * ============================================================================
 * POLÍTICA DE ACCESO A LOS SEMILLEROS
 * ============================================================================
 *
 * Reúne en un solo sitio la respuesta a «¿puede este usuario hacer esto con
 * este semillero?». Los controladores consultan esta clase en lugar de
 * repetir las comprobaciones, de modo que si cambia una regla se cambia aquí
 * y queda aplicada en todo el sistema.
 *
 * Criterio general:
 *   - Consultar semilleros: cualquier usuario con sesión iniciada.
 *   - Crear y eliminar: solo Administrador y Coordinador.
 *   - Editar y aprobar solicitudes: Administrador, Coordinador y además el
 *     tutor asignado a ese semillero concreto.
 *
 * Laravel llama a estos métodos por su nombre, así que no deben renombrarse
 * aunque estén en inglés: «view», «create», «update» y «delete» son los
 * nombres que el framework espera encontrar.
 */
class SemilleroPolicy
{
    /**
     * ¿Puede ver el listado de semilleros?
     *
     * @param  User  $user  Usuario autenticado.
     * @return bool Siempre verdadero: el catálogo es visible para todos.
     */
    public function viewAny(User $user): bool
    {
        // El listado de semilleros es información pública dentro del sistema:
        // un estudiante necesita verlos todos para decidir a cuál postularse.
        return true;
    }

    /**
     * ¿Puede ver el detalle de un semillero concreto?
     *
     * @param  User  $user  Usuario autenticado.
     * @param  Semillero  $semillero  Semillero que se quiere consultar.
     * @return bool Siempre verdadero, por el mismo motivo que viewAny.
     */
    public function view(User $user, Semillero $semillero): bool
    {
        return true;
    }

    /**
     * ¿Puede crear un semillero nuevo?
     *
     * @param  User  $user  Usuario autenticado.
     * @return bool Verdadero solo para Administrador y Coordinador.
     */
    public function create(User $user): bool
    {
        return $user->hasRole(['Administrador', 'Coordinador']);
    }

    /**
     * ¿Puede modificar este semillero?
     *
     * @param  User  $user  Usuario autenticado.
     * @param  Semillero  $semillero  Semillero que se quiere modificar.
     * @return bool Verdadero para Administrador, Coordinador, o para el tutor
     *              asignado a ese semillero en particular.
     */
    public function update(User $user, Semillero $semillero): bool
    {
        // La segunda condición es la que da autonomía al docente: puede
        // gestionar su propio semillero sin poder tocar los de sus colegas.
        return $user->hasRole(['Administrador', 'Coordinador']) ||
               $user->id === $semillero->tutor_id;
    }

    /**
     * ¿Puede eliminar este semillero?
     *
     * @param  User  $user  Usuario autenticado.
     * @param  Semillero  $semillero  Semillero que se quiere eliminar.
     * @return bool Verdadero solo para Administrador y Coordinador.
     */
    public function delete(User $user, Semillero $semillero): bool
    {
        // El tutor queda fuera a propósito: puede editar su semillero, pero
        // borrarlo arrastraría los proyectos y participantes asociados, así
        // que esa decisión se reserva a la coordinación.
        return $user->hasRole(['Administrador', 'Coordinador']);
    }

    /**
     * ¿Puede aprobar o rechazar solicitudes de ingreso al semillero?
     *
     * @param  User  $user  Usuario autenticado.
     * @param  Semillero  $semillero  Semillero al que se solicita el ingreso.
     * @return bool Verdadero para Administrador, Coordinador y el tutor asignado.
     */
    public function approveJoin(User $user, Semillero $semillero): bool
    {
        return $user->hasRole(['Administrador', 'Coordinador']) ||
               $user->id === $semillero->tutor_id;
    }

    /**
     * ¿Puede restaurar un semillero eliminado?
     *
     * @param  User  $user  Usuario autenticado.
     * @param  Semillero  $semillero  Semillero eliminado.
     * @return bool Siempre falso.
     */
    public function restore(User $user, Semillero $semillero): bool
    {
        // El sistema no guarda semilleros eliminados (no usa borrado lógico),
        // así que no hay nada que restaurar. Se deja el método porque Laravel
        // lo espera, pero niega siempre.
        return false;
    }

    /**
     * ¿Puede eliminar un semillero de forma definitiva?
     *
     * @param  User  $user  Usuario autenticado.
     * @param  Semillero  $semillero  Semillero a eliminar.
     * @return bool Siempre falso.
     */
    public function forceDelete(User $user, Semillero $semillero): bool
    {
        // Nadie borra de forma irreversible desde la aplicación. Si hiciera
        // falta, se hace desde la base de datos con una copia de seguridad
        // delante.
        return false;
    }
}
