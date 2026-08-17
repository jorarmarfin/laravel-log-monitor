<?php

declare(strict_types=1);

namespace Luiscamp\LaravelLogMonitor\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Luiscamp\LaravelLogMonitor\Contracts\LogRepositoryInterface;

final class LogDeleteAllController extends Controller
{
    public function __construct(
        private readonly LogRepositoryInterface $repository,
    ) {}

    public function __invoke(): JsonResponse
    {
        if (! (bool) config('log-monitor.allow_delete', false)) {
            abort(403, 'Deleting log files is disabled.');
        }

        $deleted = $this->repository->deleteAll();

        return response()->json([
            'message' => 'All log files deleted.',
            'deleted' => array_map(fn ($file) => $file->name, $deleted),
        ]);
    }
}
