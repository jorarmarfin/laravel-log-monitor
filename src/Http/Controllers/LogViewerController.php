<?php

declare(strict_types=1);

namespace Luiscamp\LaravelLogMonitor\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Luiscamp\LaravelLogMonitor\Contracts\LogParserInterface;
use Luiscamp\LaravelLogMonitor\Contracts\LogRepositoryInterface;
use Luiscamp\LaravelLogMonitor\Exceptions\InvalidLogFileException;
use Luiscamp\LaravelLogMonitor\Services\LogSearchService;

final class LogViewerController extends Controller
{
    public function __construct(
        private readonly LogRepositoryInterface $repository,
        private readonly LogParserInterface $parser,
        private readonly LogSearchService $search,
    ) {}

    public function index(Request $request)
    {
        $files = $this->repository->allFiles();
        $selected = $this->resolveSelectedFile($request);

        return view('log-monitor::index', [
            'files' => $files,
            'selected' => $selected,
            'levels' => config('log-monitor.levels', []),
            'pagination' => config('log-monitor.pagination', []),
            'allowDownload' => (bool) config('log-monitor.allow_download', true),
            'allowClear' => (bool) config('log-monitor.allow_clear', false),
            'allowDelete' => (bool) config('log-monitor.allow_delete', false),
            'autoRefresh' => (bool) config('log-monitor.auto_refresh', false),
            'autoRefreshInterval' => (int) config('log-monitor.auto_refresh_interval', 10),
        ]);
    }

    public function files(): JsonResponse
    {
        $files = array_map(
            fn ($file) => $file->toArray(),
            $this->repository->allFiles()
        );

        return response()->json(['data' => $files]);
    }

    public function show(Request $request, string $file): JsonResponse
    {
        try {
            $logFile = $this->repository->find($file);
        } catch (InvalidLogFileException) {
            return response()->json(['message' => 'Log file not found.'], 404);
        }

        if ($logFile === null) {
            return response()->json(['message' => 'Log file not found.'], 404);
        }

        $raw = $this->repository->contents($logFile);
        $entries = $this->parser->parse($raw);

        $stats = $this->search->stats($entries);

        $entries = $this->search->filter(
            $entries,
            level: $request->query('level'),
            search: $request->query('q'),
            from: $request->query('from'),
            to: $request->query('to'),
        );

        $entries = $this->search->sort($entries, (string) $request->query('order', 'desc'));

        $perPage = (int) $request->query('per_page', config('log-monitor.pagination.per_page', 50));
        $page = (int) $request->query('page', 1);

        $total = count($entries);
        $entries = $this->search->paginate($entries, $page, $perPage);

        return response()->json([
            'file' => $logFile->toArray(),
            'stats' => $stats,
            'total' => $total,
            'page' => $page,
            'per_page' => $perPage,
            'data' => array_map(fn ($entry) => $entry->toArray(), $entries),
        ]);
    }

    private function resolveSelectedFile(Request $request)
    {
        $requested = $request->query('file');

        if (is_string($requested) && $requested !== '') {
            try {
                $file = $this->repository->find($requested);
            } catch (InvalidLogFileException) {
                $file = null;
            }

            if ($file !== null) {
                return $file;
            }
        }

        return $this->repository->defaultFile();
    }
}
