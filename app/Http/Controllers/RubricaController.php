<?php

namespace App\Http\Controllers;

use App\Models\Evento;
use App\Models\Rubrica;
use App\Models\CriterioRubrica;
use App\Support\CatalogosEvento;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\DB;

class RubricaController extends Controller
{
    use AuthorizesRequests;

    public function __construct()
    {
        $this->middleware(['auth', 'verified']);
    }

    /**
     * Mostrar lista de rúbricas
     */
    public function index(): Response
    {
        $this->authorize('viewAny', Rubrica::class);

        $rubricas = Rubrica::with(['criterios', 'evento'])
            ->withCount('evaluaciones')
            ->orderBy('created_at', 'desc')
            ->get();

        return Inertia::render('Rubricas/Index', [
            'rubricas' => $rubricas,
        ]);
    }

    /**
     * Mostrar formulario para crear rúbrica
     */
    public function create(): Response
    {
        $this->authorize('create', Rubrica::class);

        return Inertia::render('Rubricas/Create', [
            'eventos' => Evento::select('id', 'titulo')->orderBy('titulo')->get(),
            'modalidades' => CatalogosEvento::MODALIDADES,
        ]);
    }

    /**
     * Guardar nueva rúbrica
     */
    public function store(Request $request): RedirectResponse
    {
        $this->authorize('create', Rubrica::class);

        $request->validate([
            'nombre' => 'required|string|max:255',
            'descripcion' => 'required|string|max:2000',
            'evento_id' => 'nullable|exists:eventos,id',
            'modalidad' => 'nullable|string|in:' . implode(',', CatalogosEvento::MODALIDADES),
            'criterios' => 'required|array|min:1',
            'criterios.*.criterio' => 'required|string|max:255',
            'criterios.*.peso' => 'required|numeric|min:0.01|max:100',
            'criterios.*.puntaje_maximo' => 'required|integer|min:1|max:10',
        ], [
            'descripcion.required' => 'Describe para qué sirve esta rúbrica y cómo se realizará la evaluación.',
        ]);

        // Verificar que la suma de pesos sea 100%
        $totalPeso = array_sum(array_column($request->criterios, 'peso'));
        if (abs($totalPeso - 100) > 0.01) {
            return back()->withErrors([
                'criterios' => 'La suma de los pesos debe ser exactamente 100%. Total actual: ' . $totalPeso . '%'
            ]);
        }

        DB::transaction(function () use ($request) {
            // Crear la rúbrica
            $rubrica = Rubrica::create([
                'nombre' => $request->nombre,
                'descripcion' => $request->descripcion,
                'evento_id' => $request->evento_id ?: null,
                'modalidad' => $request->modalidad ?: null,
            ]);

            // Crear los criterios
            foreach ($request->criterios as $criterioData) {
                CriterioRubrica::create([
                    'rubrica_id' => $rubrica->id,
                    'criterio' => $criterioData['criterio'],
                    'peso' => $criterioData['peso'],
                    'puntaje_maximo' => $criterioData['puntaje_maximo'],
                ]);
            }
        });

        return redirect()->route('rubricas.index')
            ->with('success', 'Rúbrica creada exitosamente.');
    }

    /**
     * Mostrar rúbrica específica
     */
    public function show(Rubrica $rubrica): Response
    {
        $this->authorize('view', $rubrica);

        $user = auth()->user();
        $rubrica->load(['criterios', 'evento']);

        // Un jurado sólo debe ver sus propias evaluaciones con esta rúbrica,
        // no las que pusieron otros jurados. Administrador y Coordinador sí
        // ven todas, porque supervisan el proceso completo.
        $evaluaciones = $rubrica->evaluaciones()->with(['jurado', 'proyecto', 'eventoParticipante']);

        if ($user->hasRole('Jurado') && !$user->hasRole(['Administrador', 'Coordinador'])) {
            $evaluaciones->where('jurado_id', $user->id);
        }

        $rubrica->setRelation('evaluaciones', $evaluaciones->get());

        return Inertia::render('Rubricas/Show', [
            'rubrica' => $rubrica,
        ]);
    }

    /**
     * Mostrar formulario para editar rúbrica
     */
    public function edit(Rubrica $rubrica): Response
    {
        $this->authorize('update', $rubrica);

        $rubrica->load('criterios')->loadCount('evaluaciones');

        return Inertia::render('Rubricas/Edit', [
            'rubrica' => $rubrica,
            'eventos' => Evento::select('id', 'titulo')->orderBy('titulo')->get(),
            'modalidades' => CatalogosEvento::MODALIDADES,
        ]);
    }

    /**
     * Actualizar rúbrica
     */
    public function update(Request $request, Rubrica $rubrica): RedirectResponse
    {
        $this->authorize('update', $rubrica);

        // Tocar los criterios de una rúbrica que ya calificó proyectos
        // invalidaría esas notas (el peso y el puntaje máximo con que se
        // calcularon dejarían de coincidir con lo guardado). El nombre y la
        // descripción sí son seguros de cambiar siempre: no afectan ninguna
        // nota ya puesta. Antes esto se rechazaba entero, y como el enlace de
        // «Editar» ya venía oculto para estas rúbricas, quedaban congeladas
        // sin ninguna explicación ni forma de corregir ni un dato tan simple
        // como el nombre.
        if ($rubrica->evaluaciones()->exists()) {
            $request->validate([
                'nombre' => 'required|string|max:255',
                'descripcion' => 'required|string|max:2000',
                'evento_id' => 'nullable|exists:eventos,id',
                'modalidad' => 'nullable|string|in:' . implode(',', CatalogosEvento::MODALIDADES),
            ], [
                'descripcion.required' => 'Describe para qué sirve esta rúbrica y cómo se realizará la evaluación.',
            ]);

            $rubrica->update([
                'nombre' => $request->nombre,
                'descripcion' => $request->descripcion,
                'evento_id' => $request->evento_id ?: null,
                'modalidad' => $request->modalidad ?: null,
            ]);

            return redirect()->route('rubricas.index')->with(
                'success',
                'Se actualizó el nombre y la descripción. Los criterios no se pueden modificar porque la rúbrica ya calificó proyectos; usa «Duplicar» si necesitas una versión con otros criterios.'
            );
        }

        $request->validate([
            'nombre' => 'required|string|max:255',
            'descripcion' => 'required|string|max:2000',
            'evento_id' => 'nullable|exists:eventos,id',
            'modalidad' => 'nullable|string|in:' . implode(',', CatalogosEvento::MODALIDADES),
            'criterios' => 'required|array|min:1',
            'criterios.*.criterio' => 'required|string|max:255',
            'criterios.*.peso' => 'required|numeric|min:0.01|max:100',
            'criterios.*.puntaje_maximo' => 'required|integer|min:1|max:10',
        ], [
            'descripcion.required' => 'Describe para qué sirve esta rúbrica y cómo se realizará la evaluación.',
        ]);

        // Verificar que la suma de pesos sea 100%
        $totalPeso = array_sum(array_column($request->criterios, 'peso'));
        if (abs($totalPeso - 100) > 0.01) {
            return back()->withErrors([
                'criterios' => 'La suma de los pesos debe ser exactamente 100%. Total actual: ' . $totalPeso . '%'
            ]);
        }

        DB::transaction(function () use ($request, $rubrica) {
            // Actualizar la rúbrica
            $rubrica->update([
                'nombre' => $request->nombre,
                'descripcion' => $request->descripcion,
                'evento_id' => $request->evento_id ?: null,
                'modalidad' => $request->modalidad ?: null,
            ]);

            // Eliminar criterios existentes
            $rubrica->criterios()->delete();

            // Crear los nuevos criterios
            foreach ($request->criterios as $criterioData) {
                CriterioRubrica::create([
                    'rubrica_id' => $rubrica->id,
                    'criterio' => $criterioData['criterio'],
                    'peso' => $criterioData['peso'],
                    'puntaje_maximo' => $criterioData['puntaje_maximo'],
                ]);
            }
        });

        return redirect()->route('rubricas.index')
            ->with('success', 'Rúbrica actualizada exitosamente.');
    }

    /**
     * Crear una copia de la rúbrica, con los mismos criterios.
     *
     * Es la vía para "editar" una rúbrica que ya calificó proyectos: en vez
     * de tocar los criterios originales (lo que invalidaría notas ya
     * puestas), se parte de una copia nueva y ahí sí se pueden ajustar los
     * pesos o el puntaje libremente, porque todavía no tiene evaluaciones.
     */
    public function duplicar(Rubrica $rubrica): RedirectResponse
    {
        $this->authorize('create', Rubrica::class);

        $rubrica->load('criterios');

        $copia = DB::transaction(function () use ($rubrica) {
            $copia = Rubrica::create([
                'nombre' => $rubrica->nombre . ' (copia)',
                'descripcion' => $rubrica->descripcion,
                'evento_id' => $rubrica->evento_id,
                'modalidad' => $rubrica->modalidad,
            ]);

            foreach ($rubrica->criterios as $criterio) {
                CriterioRubrica::create([
                    'rubrica_id' => $copia->id,
                    'criterio' => $criterio->criterio,
                    'peso' => $criterio->peso,
                    'puntaje_maximo' => $criterio->puntaje_maximo,
                ]);
            }

            return $copia;
        });

        return redirect()->route('rubricas.edit', $copia)->with(
            'success',
            'Se creó una copia de "' . $rubrica->nombre . '". Ajusta los criterios que necesites; todavía no tiene evaluaciones.'
        );
    }

    /**
     * Eliminar rúbrica
     */
    public function destroy(Rubrica $rubrica): RedirectResponse
    {
        $this->authorize('delete', $rubrica);

        // Verificar si la rúbrica tiene evaluaciones asociadas
        if ($rubrica->evaluaciones()->exists()) {
            return redirect()->route('rubricas.index')
                ->with('error', 'No se puede eliminar una rúbrica que ya tiene evaluaciones asociadas.');
        }

        $rubrica->delete();

        return redirect()->route('rubricas.index')
            ->with('success', 'Rúbrica eliminada exitosamente.');
    }
}
