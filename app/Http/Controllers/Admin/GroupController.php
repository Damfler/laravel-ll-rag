<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Group;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class GroupController extends Controller
{
    public function index(): Response
    {
        $groups = Group::withCount('users')
            ->orderBy('name')
            ->get();

        return Inertia::render('Admin/Groups/Index', [
            'groups' => $groups,
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Admin/Groups/Create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name'        => ['required', 'string', 'max:100', 'unique:groups,name'],
            'description' => ['nullable', 'string', 'max:500'],
        ]);

        $validated['slug'] = Str::slug($validated['name']);

        $group = Group::create($validated);

        return redirect()->route('admin.groups.edit', $group)
            ->with('success', "Группа «{$group->name}» создана.");
    }

    public function edit(Group $group): Response
    {
        return Inertia::render('Admin/Groups/Edit', [
            'group'      => $group->load('users'),
            'allUsers'   => User::orderBy('name')->get(['id', 'name', 'email', 'default_role']),
        ]);
    }

    public function update(Request $request, Group $group)
    {
        $validated = $request->validate([
            'name'        => ['required', 'string', 'max:100', 'unique:groups,name,' . $group->id],
            'description' => ['nullable', 'string', 'max:500'],
            'user_ids'    => ['nullable', 'array'],
            'user_ids.*'  => ['exists:users,id'],
        ]);

        $userIds = $validated['user_ids'] ?? [];
        unset($validated['user_ids']);

        $validated['slug'] = Str::slug($validated['name']);
        $group->update($validated);
        $group->users()->sync($userIds);

        return redirect()->route('admin.groups.edit', $group)
            ->with('success', "Группа «{$group->name}» обновлена.");
    }

    public function destroy(Group $group)
    {
        $name = $group->name;
        $group->delete();

        return redirect()->route('admin.groups.index')
            ->with('success', "Группа «{$name}» удалена.");
    }
}
