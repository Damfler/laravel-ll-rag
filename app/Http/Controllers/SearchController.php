<?php

namespace App\Http\Controllers;

use App\Models\Page;
use App\Models\Space;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class SearchController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $query   = $request->get('q', '');
        $results = collect();

        if (strlen($query) >= 2) {
            $results = Page::query()
                ->whereHas('space', fn ($q) => $q->accessibleBy(auth()->user()))
                ->published()
                ->where(fn ($q) => $q
                    ->where('title', 'ilike', "%{$query}%")
                    ->orWhere('content_text', 'ilike', "%{$query}%")
                )
                ->with(['space', 'tags'])
                ->select('id', 'space_id', 'title', 'slug', 'content_text', 'updated_at')
                ->limit(30)
                ->get()
                ->map(fn (Page $page) => [
                    'id'        => $page->id,
                    'title'     => $page->title,
                    'slug'      => $page->slug,
                    'space'     => ['id' => $page->space->id, 'name' => $page->space->name, 'slug' => $page->space->slug],
                    'excerpt'   => $this->excerpt($page->content_text, $query),
                    'tags'      => $page->tags,
                    'updated_at'=> $page->updated_at,
                ]);
        }

        return Inertia::render('Search/Index', [
            'query'   => $query,
            'results' => $results,
        ]);
    }

    private function excerpt(?string $text, string $query, int $length = 200): string
    {
        if (! $text) return '';

        $pos = mb_stripos($text, $query);
        if ($pos === false) {
            return mb_substr($text, 0, $length) . '…';
        }

        $start = max(0, $pos - 60);
        $excerpt = mb_substr($text, $start, $length);

        return ($start > 0 ? '…' : '') . $excerpt . '…';
    }
}
