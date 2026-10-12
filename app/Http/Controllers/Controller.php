<?php

namespace App\Http\Controllers;

use Illuminate\Routing\Controller as BaseController;

/**
 * ============================================================================
 * CONTROLADOR BASE
 * ============================================================================
 *
 * Clase de la que heredan todos los controladores del sistema.
 *
 * Está vacía a propósito. Su función es servir de punto único donde añadir
 * comportamiento común a todos los controladores si alguna vez hace falta:
 * bastaría con escribirlo aquí y quedaría disponible en los diecisiete
 * controladores sin tocar ninguno.
 *
 * Es «abstract» porque no representa ninguna pantalla concreta: no tiene
 * sentido instanciarla por sí sola, solo heredar de ella.
 */
abstract class Controller extends BaseController
{
    //
}
