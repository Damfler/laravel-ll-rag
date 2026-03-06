<?php

namespace App\Http\Controllers;

use App\Models\Space;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class SpaceController extends Controller
{
    public function index(): Response
    {
        $spaces = Space::with('owner')
            ->accessibleBy(auth()->user())
            ->withCount('pages')
            ->latest()
            ->get();

        return Inertia::render('Spaces/Index', [
            'spaces' => $spaces,
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Spaces/Create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name'        => ['required', 'string', 'max:100'],
            'description' => ['nullable', 'string', 'max:500'],
            'icon'        => ['nullable', 'string', 'max:10'],
            'color'       => ['nullable', 'string', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'is_public'   => ['boolean'],
        ]);

        $validated['owner_id'] = auth()->id();
        $validated['slug']     = Str::slug($validated['name']);

        $space = Space::create($validated);

        return redirect()->route('spaces.show', $space)
            ->with('success', 'Раздел успешно создан.');
    }

    public function show(Space $space): Response
    {
        $this->authorizeAccess($space);

        $pages = $space->rootPages()
            ->with(['children' => fn ($q) => $q->with('children')->published()])
            ->published()
            ->get();

        return Inertia::render('Spaces/Show', [
            'space' => $space->load('owner'),
            'pages' => $pages,
        ]);
    }

    public function edit(Space $space): Response
    {
        $this->authorize('update', $space);

        return Inertia::render('Spaces/Edit', [
            'space' => $space,
        ]);
    }

    public function update(Request $request, Space $space)
    {
        $this->authorize('update', $space);

        $validated = $request->validate([
            'name'        => ['required', 'string', 'max:100'],
            'description' => ['nullable', 'string', 'max:500'],
            'icon'        => ['nullable', 'string', 'max:10'],
            'color'       => ['nullable', 'string', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'is_public'   => ['boolean'],
        ]);

        $space->update($validated);

        return redirect()->route('spaces.show', $space)
            ->with('success', 'Раздел обновлён.');
    }

    public function destroy(Space $space)
    {
        $this->authorize('delete', $space);

        $space->delete();

        return redirect()->route('spaces.index')
            ->with('success', 'Раздел удалён.');
    }

    private function authorizeAccess(Space $space): void
    {
        if (! $space->is_public && $space->owner_id !== auth()->id() && ! auth()->user()->isAdmin()) {
            abort(403);
        }
    }
}
