<?php

namespace App\Http\Controllers;

use App\Models\Page;
use App\Models\Space;
use App\Models\Tag;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class PageController extends Controller
{
    public function create(Space $space): Response
    {
        $this->authorize('editor', $space);

        $pages = $space->pages()->select('id', 'title', 'parent_id', 'depth')->get();

        return Inertia::render('Wiki/Create', [
            'space' => $space,
            'pages' => $pages,
            'tags'  => Tag::orderBy('name')->get(),
        ]);
    }

    public function store(Request $request, Space $space)
    {
        $this->authorize('editor', $space);

        $validated = $request->validate([
            'title'       => ['required', 'string', 'max:255'],
            'content'     => ['nullable', 'array'],
            'content_text'=> ['nullable', 'string'],
            'parent_id'   => ['nullable', 'exists:pages,id'],
            'is_published'=> ['boolean'],
            'tags'        => ['nullable', 'array'],
            'tags.*'      => ['string', 'max:50'],
        ]);

        $validated['author_id']  = auth()->id();
        $validated['space_id']   = $space->id;
        $validated['slug']       = $this->uniqueSlug($space, $validated['title']);

        $tags = $validated['tags'] ?? [];
        unset($validated['tags']);

        $page = Page::create($validated);

        // Сохраняем теги (создаём если нет)
        $tagIds = collect($tags)->map(fn ($name) => Tag::firstOrCreate(
            ['slug' => Str::slug($name)],
            ['name' => $name]
        ))->pluck('id');

        $page->tags()->sync($tagIds);

        // Первая версия
        \App\Models\PageVersion::create([
            'page_id'        => $page->id,
            'author_id'      => auth()->id(),
            'title'          => $page->title,
            'content'        => $page->content,
            'content_text'   => $page->content_text,
            'version_number' => 1,
        ]);

        return redirect()->route('pages.show', [$space, $page])
            ->with('success', 'Страница создана.');
    }

    public function show(Space $space, Page $page): Response
    {
        $page->load(['author', 'lastEditor', 'tags', 'children' => fn ($q) => $q->published()]);

        return Inertia::render('Wiki/Show', [
            'space'       => $space,
            'page'        => $page,
            'breadcrumbs' => $page->breadcrumbs(),
            'siblings'    => $space->pages()
                ->where('parent_id', $page->parent_id)
                ->select('id', 'title', 'slug')
                ->orderBy('sort_order')
                ->get(),
        ]);
    }

    public function edit(Space $space, Page $page): Response
    {
        $this->authorize('editor', $space);

        return Inertia::render('Wiki/Edit', [
            'space'  => $space,
            'page'   => $page->load('tags'),
            'pages'  => $space->pages()->select('id', 'title', 'parent_id')->where('id', '!=', $page->id)->get(),
            'tags'   => Tag::orderBy('name')->get(),
        ]);
    }

    public function update(Request $request, Space $space, Page $page)
    {
        $this->authorize('editor', $space);

        $validated = $request->validate([
            'title'        => ['required', 'string', 'max:255'],
            'content'      => ['nullable', 'array'],
            'content_text' => ['nullable', 'string'],
            'parent_id'    => ['nullable', 'exists:pages,id'],
            'is_published' => ['boolean'],
            'tags'         => ['nullable', 'array'],
            'tags.*'       => ['string', 'max:50'],
            'change_summary' => ['nullable', 'string', 'max:255'],
        ]);

        $tags = $validated['tags'] ?? [];
        unset($validated['tags'], $validated['change_summary']);

        $validated['last_edited_by'] = auth()->id();

        $page->update($validated);

        $tagIds = collect($tags)->map(fn ($name) => Tag::firstOrCreate(
            ['slug' => Str::slug($name)],
            ['name' => $name]
        ))->pluck('id');

        $page->tags()->sync($tagIds);

        return redirect()->route('pages.show', [$space, $page])
            ->with('success', 'Страница сохранена.');
    }

    public function destroy(Space $space, Page $page)
    {
        $this->authorize('editor', $space);

        $page->delete();

        return redirect()->route('spaces.show', $space)
            ->with('success', 'Страница удалена.');
    }

    public function history(Space $space, Page $page): Response
    {
        return Inertia::render('Wiki/History', [
            'space'    => $space,
            'page'     => $page,
            'versions' => $page->versions()->with('author')->paginate(20),
        ]);
    }

    private function uniqueSlug(Space $space, string $title): string
    {
        $slug     = Str::slug($title);
        $original = $slug;
        $i        = 2;

        while ($space->pages()->where('slug', $slug)->exists()) {
            $slug = "{$original}-{$i}";
            $i++;
        }

        return $slug;
    }
}
