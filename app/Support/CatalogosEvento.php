<?php

namespace App\Support;

/**
 * Fuente única de las listas fijas que usa el módulo de eventos: la
 * modalidad y el área de participación de una propuesta.
 *
 * Antes «carrera» tenía su misma lista de opciones repetida en la
 * migración, en la validación del controlador y en dos páginas Vue; bastaba
 * con que una se desactualizara para que el formulario aceptara un valor
 * que el backend rechazaba. Modalidad y área se leen todas desde aquí:
 * migraciones, validación y las props que reciben las páginas Vue.
 */
class CatalogosEvento
{
    /**
     * Modalidades de participación de la convocatoria institucional.
     *
     * @var array<int, string>
     */
    public const MODALIDADES = [
        'Propuesta o idea de investigación',
        'Investigación en curso',
        'Investigación terminada',
        'Ideas de negocio',
        'Plan de negocio',
        'Empresa puesta en marcha',
        'Proyecto de desarrollo tecnológico e innovación',
    ];

    /**
     * Áreas de participación, según las áreas científicas de la OCDE que
     * usa la RedCOLSI.
     *
     * @var array<int, string>
     */
    public const AREAS_PARTICIPACION = [
        'Ciencias Agrarias',
        'Biológicas y del Mar',
        'Ciencias de la Salud',
        'Ciencias Exactas y de la Tierra',
        'Ciencias Humanas',
        'Ciencias Sociales',
        'Navales y de Seguridad',
        'Ingeniería',
        'Lingüística, Artes y Letras',
    ];
}
