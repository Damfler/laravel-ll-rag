<?php

namespace App\Http\Controllers;

use App\Models\Group;
use App\Models\Space;
use App\Models\Tag;
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
        return Inertia::render('Spaces/Create', [
            'groups' => Group::orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name'        => ['required', 'string', 'max:100'],
            'description' => ['nullable', 'string', 'max:500'],
            'icon'        => ['nullable', 'string', 'max:10'],
            'color'       => ['nullable', 'string', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'visibility'  => ['required', 'in:public,restricted,private'],
            'group_ids'   => ['nullable', 'array'],
            'group_ids.*' => ['exists:groups,id'],
        ]);

        $groupIds = $validated['group_ids'] ?? [];
        unset($validated['group_ids']);

        $validated['owner_id'] = auth()->id();
        $validated['slug']     = Str::slug($validated['name']);
        $validated['icon']     = $validated['icon'] ?? '📁';

        $space = Space::create($validated);

        if ($validated['visibility'] === Space::VISIBILITY_RESTRICTED && $groupIds) {
            $space->groups()->sync($groupIds);
        }

        return redirect()->route('spaces.show', $space)
            ->with('success', 'Раздел успешно создан.');
    }

    public function show(Space $space): Response
    {
        $this->authorizeAccess($space);

        $pages = $space->rootPages()
            ->with([
                'tags',
                'children' => fn ($q) => $q->with(['children', 'tags'])->published(),
            ])
            ->published()
            ->get();

        $tags = Tag::whereHas('pages', fn ($q) => $q->where('space_id', $space->id))
            ->orderBy('name')
            ->get(['id', 'name', 'color']);

        return Inertia::render('Spaces/Show', [
            'space' => $space->load('owner'),
            'pages' => $pages,
            'tags'  => $tags,
        ]);
    }

    public function edit(Space $space): Response
    {
        $this->authorize('update', $space);

        return Inertia::render('Spaces/Edit', [
            'space'  => $space->load('groups'),
            'groups' => Group::orderBy('name')->get(['id', 'name']),
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
            'visibility'  => ['required', 'in:public,restricted,private'],
            'group_ids'   => ['nullable', 'array'],
            'group_ids.*' => ['exists:groups,id'],
        ]);

        $groupIds          = $validated['group_ids'] ?? [];
        $validated['icon'] = $validated['icon'] ?? $space->icon ?? '📁';
        unset($validated['group_ids']);

        $space->update($validated);

        $space->groups()->sync(
            $validated['visibility'] === Space::VISIBILITY_RESTRICTED ? $groupIds : []
        );

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
        $user = auth()->user();

        if ($user->isAdmin() || $space->owner_id === $user->id) {
            return;
        }

        if ($space->visibility === Space::VISIBILITY_PUBLIC) {
            return;
        }

        if ($space->visibility === Space::VISIBILITY_RESTRICTED) {
            $userGroupIds  = $user->groups()->pluck('groups.id');
            $spaceGroupIds = $space->groups()->pluck('groups.id');
            if ($userGroupIds->intersect($spaceGroupIds)->isNotEmpty()) {
                return;
            }
        }

        abort(403);
    }
}
