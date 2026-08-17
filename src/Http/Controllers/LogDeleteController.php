<?php

declare(strict_types=1);

namespace Luiscamp\LaravelLogMonitor\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Luiscamp\LaravelLogMonitor\Contracts\LogRepositoryInterface;
use Luiscamp\LaravelLogMonitor\Exceptions\InvalidLogFileException;

final class LogDeleteController extends Controller
{
    public function __construct(
        private readonly LogRepositoryInterface $repository,
    ) {}

    public function __invoke(string $file): JsonResponse
    {
        if (! (bool) config('log-monitor.allow_delete', false)) {
            abort(403, 'Deleting log files is disabled.');
        }

        try {
            $logFile = $this->repository->find($file);
        } catch (InvalidLogFileException) {
            return response()->json(['message' => 'Log file not found.'], 404);
        }

        if ($logFile === null) {
            return response()->json(['message' => 'Log file not found.'], 404);
        }

        $this->repository->delete($logFile);

        return response()->json(['message' => 'Log file deleted.']);
    }
}
