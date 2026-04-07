<?php

namespace App\Console\Commands;

use App\Models\Page;
use App\Models\PageVersion;
use App\Models\Space;
use App\Models\User;
use App\Services\Wiki\DokuWikiParser;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ImportDokuWiki extends Command
{
    protected $signature = 'wiki:import-dokuwiki
                            {--path=old_wiki/pages   : Путь к папке pages DokuWiki}
                            {--media=old_wiki/media  : Путь к папке media DokuWiki}
                            {--dry-run               : Показать что будет импортировано, без сохранения}
                            {--user=1                : ID пользователя-автора импортированных страниц}
                            {--skip-wiki             : Пропустить системные страницы wiki/}
                            {--fresh                 : Удалить все ранее импортированные данные перед запуском}';

    protected $description = 'Импортировать страницы из DokuWiki в формат новой вики';

    private DokuWikiParser $parser;
    private bool $dryRun;
    private int $authorId;
    private array $stats = [
        'spaces'  => 0,
        'pages'   => 0,
        'media'   => 0,
        'skipped' => 0,
        'errors'  => 0,
    ];

    public function handle(): int
    {
        $this->parser   = new DokuWikiParser();
        $this->dryRun   = (bool) $this->option('dry-run');
        $this->authorId = (int) $this->option('user');
        $pagesPath      = base_path($this->option('path'));
        $mediaPath      = base_path($this->option('media'));

        if (!is_dir($pagesPath)) {
            $this->error("Папка не найдена: {$pagesPath}");
            return self::FAILURE;
        }

        if (!User::find($this->authorId)) {
            $this->error("Пользователь с ID {$this->authorId} не найден.");
            return self::FAILURE;
        }

        if ($this->dryRun) {
            $this->warn('──── DRY RUN — изменения в базу НЕ сохраняются ────');
        }

        // --fresh: удаляем ранее импортированные данные
        if (!$this->dryRun && $this->option('fresh')) {
            if ($this->confirm('Удалить ВСЕ существующие пространства и страницы перед импортом?', false)) {
                $this->warn('Очистка базы...');
                \App\Models\PageVersion::query()->delete();
                \App\Models\Page::withTrashed()->forceDelete();
                \App\Models\Space::withTrashed()->forceDelete();
                $this->info('База очищена.');
            }
        }

        $this->info('Сканирование структуры DokuWiki...');

        // Получаем структуру: пространства → страницы
        $structure = $this->scanStructure($pagesPath);

        if ($this->option('skip-wiki')) {
            unset($structure['wiki']);
        }

        // Показываем план
        $this->displayPlan($structure);

        if (!$this->dryRun) {
            if (!$this->confirm('Продолжить импорт?', true)) {
                return self::SUCCESS;
            }
            $this->runImport($structure, $mediaPath);
        }

        $this->displayStats();

        return self::SUCCESS;
    }

    // ─── Сканирование структуры ───────────────────────────────────────────────

    /**
     * Возвращает:
     * [
     *   'spaceName' => [
     *     'index'    => 'path/to/index.txt',  // главная страница раздела
     *     'children' => [ ['file' => ..., 'children' => [...]], ... ]
     *   ]
     * ]
     */
    private function scanStructure(string $basePath): array
    {
        $spaces = [];

        // Находим все .txt файлы
        $allFiles = $this->findTxtFiles($basePath);

        // Группируем: топ-уровень vs вложенные
        $topLevel  = [];
        $subpages  = [];

        foreach ($allFiles as $relativePath) {
            $parts = explode('/', $relativePath);
            if (count($parts) === 1) {
                $topLevel[] = $relativePath;
            } else {
                $namespace = $parts[0];
                $subpages[$namespace][] = $relativePath;
            }
        }

        // Обрабатываем каждый namespace как space
        $allNamespaces = array_unique(
            array_merge(
                array_map(fn ($f) => pathinfo($f, PATHINFO_FILENAME), $topLevel),
                array_keys($subpages)
            )
        );

        foreach ($allNamespaces as $ns) {
            // Индексная страница пространства
            $indexFile = null;
            if (in_array($ns . '.txt', $topLevel)) {
                $indexFile = $ns . '.txt';
            }

            // Дочерние страницы
            $children = [];
            if (isset($subpages[$ns])) {
                $children = $this->buildTree($subpages[$ns], $ns);
            }

            // Пропускаем пустые пространства
            if ($indexFile === null && empty($children)) {
                continue;
            }

            $spaces[$ns] = [
                'index'    => $indexFile,
                'children' => $children,
            ];
        }

        // Страницы без namespace (нет папки с таким именем)
        $orphans = array_filter($topLevel, fn ($f) => !isset($spaces[pathinfo($f, PATHINFO_FILENAME)]));
        if (!empty($orphans)) {
            $spaces['__general'] = [
                'index'    => null,
                'children' => array_map(fn ($f) => ['file' => $f, 'children' => []], array_values($orphans)),
            ];
        }

        return $spaces;
    }

    /** Строит дерево страниц из плоского списка файлов */
    private function buildTree(array $files, string $namespace): array
    {
        $byDepth = [];
        foreach ($files as $file) {
            $rel   = substr($file, strlen($namespace) + 1); // убираем namespace/
            $parts = explode('/', $rel);
            $depth = count($parts) - 1;
            $byDepth[$depth][] = ['file' => $file, 'parts' => $parts];
        }

        // Собираем дерево рекурсивно
        return $this->buildTreeLevel($files, $namespace, $namespace . '/');
    }

    private function buildTreeLevel(array $files, string $namespace, string $prefix): array
    {
        $items = [];
        $dirs  = [];

        foreach ($files as $file) {
            $rel   = substr($file, strlen($prefix));
            $parts = explode('/', $rel);

            if (count($parts) === 1) {
                // Прямой потомок
                $items[pathinfo($parts[0], PATHINFO_FILENAME)] = [
                    'file'     => $file,
                    'children' => [],
                ];
            } else {
                // Есть подпапки
                $dirs[$parts[0]][] = $file;
            }
        }

        // Добавляем детей к родителям
        foreach ($dirs as $dir => $subFiles) {
            if (!isset($items[$dir])) {
                $items[$dir] = ['file' => null, 'children' => []];
            }
            $items[$dir]['children'] = $this->buildTreeLevel($subFiles, $namespace, $prefix . $dir . '/');
        }

        return array_values($items);
    }

    /** Возвращает список .txt файлов относительно basePath */
    private function findTxtFiles(string $basePath): array
    {
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($basePath, \FilesystemIterator::SKIP_DOTS)
        );

        $files = [];
        foreach ($iterator as $file) {
            if ($file->getExtension() !== 'txt') {
                continue;
            }
            $relative = str_replace($basePath . '/', '', $file->getPathname());
            $relative = str_replace('\\', '/', $relative);
            $files[]  = $relative;
        }

        sort($files);
        return $files;
    }

    // ─── Импорт ───────────────────────────────────────────────────────────────

    private function runImport(array $structure, string $mediaPath): void
    {
        // Копируем медиафайлы
        if (is_dir($mediaPath)) {
            $this->info('Копирование медиафайлов...');
            $this->copyMedia($mediaPath);
            // Убеждаемся, что storage:link существует
            $this->callSilently('storage:link');
        }

        // Находим или создаём пользователя-автора
        $basePath = base_path($this->option('path'));

        DB::transaction(function () use ($structure, $basePath) {
            foreach ($structure as $ns => $data) {
                $spaceName = $ns === '__general'
                    ? 'Общие страницы'
                    : $this->formatName($ns);

                $baseSlug = $ns === '__general' ? 'general' : $ns;
                $slug     = \Illuminate\Support\Str::slug($baseSlug);

                // Пропускаем уже импортированные пространства
                if (Space::withTrashed()->where('slug', $slug)->exists()) {
                    $this->warn("  ↷ Пропуск (уже существует): {$spaceName}");
                    $this->stats['skipped']++;
                    continue;
                }

                $this->info("Создание раздела: {$spaceName}");

                $space = Space::create([
                    'name'       => $spaceName,
                    'slug'       => $this->uniqueSpaceSlug($baseSlug),
                    'icon'       => '📄',
                    'visibility' => 'public',
                    'owner_id'   => $this->authorId,
                ]);
                $this->stats['spaces']++;

                // Индексная страница пространства
                $indexPage = null;
                if ($data['index']) {
                    $indexPage = $this->importPage(
                        $basePath . '/' . $data['index'],
                        $space,
                        null,
                        $this->formatName(pathinfo($data['index'], PATHINFO_FILENAME))
                    );
                }

                // Дочерние страницы
                foreach ($data['children'] as $child) {
                    $this->importPageTree($child, $basePath, $space, $indexPage?->id);
                }
            }
        });
    }

    private function importPageTree(array $node, string $basePath, Space $space, ?int $parentId): void
    {
        $page = null;

        if ($node['file']) {
            $title = $this->formatName(
                pathinfo($node['file'], PATHINFO_FILENAME)
            );
            $page = $this->importPage($basePath . '/' . $node['file'], $space, $parentId, $title);
        }

        foreach ($node['children'] as $child) {
            $this->importPageTree($child, $basePath, $space, $page?->id ?? $parentId);
        }
    }

    private function importPage(string $filePath, Space $space, ?int $parentId, string $fallbackTitle): ?Page
    {
        if (!file_exists($filePath)) {
            $this->stats['skipped']++;
            return null;
        }

        $raw = file_get_contents($filePath);
        if ($raw === false) {
            $this->stats['errors']++;
            return null;
        }

        try {
            $parsed = $this->parser->parse($raw);
        } catch (\Throwable $e) {
            $this->warn("  ⚠ Ошибка парсинга {$filePath}: {$e->getMessage()}");
            $this->stats['errors']++;
            return null;
        }

        // Берём заголовок из первого заголовка в файле, иначе — из имени файла
        $title = $this->parser->extractTitle($raw) ?? $fallbackTitle;

        $slug = $this->uniquePageSlug($space, Str::slug($title));

        $page = Page::create([
            'space_id'     => $space->id,
            'parent_id'    => $parentId,
            'title'        => $title,
            'slug'         => $slug,
            'content'      => $parsed['json'],
            'content_text' => $parsed['text'],
            'author_id'    => $this->authorId,
            'is_published' => true,
        ]);

        PageVersion::create([
            'page_id'        => $page->id,
            'author_id'      => $this->authorId,
            'title'          => $page->title,
            'content'        => $page->content,
            'content_text'   => $page->content_text,
            'version_number' => 1,
            'change_summary' => 'Импорт из DokuWiki',
        ]);

        $this->line("  ✓ {$page->title}");
        $this->stats['pages']++;

        return $page;
    }

    // ─── Медиафайлы ───────────────────────────────────────────────────────────

    private function copyMedia(string $mediaPath): void
    {
        $dest = storage_path('app/public/wiki-media');
        if (!is_dir($dest)) {
            mkdir($dest, 0775, true);
        }

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($mediaPath, \FilesystemIterator::SKIP_DOTS)
        );

        foreach ($iterator as $file) {
            // Пропускаем Zone.Identifier и системные файлы
            if (str_ends_with($file->getFilename(), ':Zone.Identifier')) {
                continue;
            }
            if (!$file->isFile()) {
                continue;
            }

            $relative = str_replace($mediaPath . '/', '', $file->getPathname());
            $relative = str_replace('\\', '/', $relative);
            $target   = $dest . '/' . $relative;

            if (!is_dir(dirname($target))) {
                mkdir(dirname($target), 0775, true);
            }

            if (copy($file->getPathname(), $target)) {
                $this->stats['media']++;
            }
        }

        $this->line("  Скопировано медиафайлов: {$this->stats['media']}");
    }

    // ─── Вспомогательные ─────────────────────────────────────────────────────

    private function displayPlan(array $structure): void
    {
        $this->table(
            ['Раздел (Space)', 'Страниц', 'Индексная страница'],
            array_map(function ($ns, $data) {
                $count = $this->countPages($data['children']) + ($data['index'] ? 1 : 0);
                return [
                    $ns === '__general' ? 'Общие страницы' : $this->formatName($ns),
                    $count,
                    $data['index'] ?? '—',
                ];
            }, array_keys($structure), array_values($structure))
        );
    }

    private function countPages(array $children): int
    {
        $count = 0;
        foreach ($children as $child) {
            if ($child['file']) {
                $count++;
            }
            $count += $this->countPages($child['children']);
        }
        return $count;
    }

    private function displayStats(): void
    {
        $this->newLine();
        $this->info('──── Итоги ────');
        $this->line("  Разделов создано:  {$this->stats['spaces']}");
        $this->line("  Страниц создано:   {$this->stats['pages']}");
        $this->line("  Медиафайлов:       {$this->stats['media']}");
        $this->line("  Пропущено:         {$this->stats['skipped']}");
        $this->line("  Ошибок:            {$this->stats['errors']}");
    }

    private function formatName(string $slug): string
    {
        return Str::headline(str_replace(['_', '-'], ' ', $slug));
    }

    private function uniqueSpaceSlug(string $base): string
    {
        $slug = Str::slug($base);
        $i    = 2;
        while (Space::withTrashed()->where('slug', $slug)->exists()) {
            $slug = Str::slug($base) . '-' . $i++;
        }
        return $slug;
    }

    private function uniquePageSlug(Space $space, string $base): string
    {
        $slug     = $base ?: 'page';
        $original = $slug;
        $i        = 2;
        while ($space->pages()->where('slug', $slug)->exists()) {
            $slug = $original . '-' . $i++;
        }
        return $slug;
    }
}
