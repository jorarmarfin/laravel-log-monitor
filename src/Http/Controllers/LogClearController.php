<?php

declare(strict_types=1);

namespace Luiscamp\LaravelLogMonitor\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Luiscamp\LaravelLogMonitor\Contracts\LogRepositoryInterface;
use Luiscamp\LaravelLogMonitor\Exceptions\InvalidLogFileException;

final class LogClearController extends Controller
{
    public function __construct(
        private readonly LogRepositoryInterface $repository,
    ) {}

    public function __invoke(string $file): JsonResponse
    {
        if (! (bool) config('log-monitor.allow_clear', false)) {
            abort(403, 'Clearing log files is disabled.');
        }

        try {
            $logFile = $this->repository->find($file);
        } catch (InvalidLogFileException) {
            return response()->json(['message' => 'Log file not found.'], 404);
        }

        if ($logFile === null) {
            return response()->json(['message' => 'Log file not found.'], 404);
        }

        $this->repository->clear($logFile);

        return response()->json(['message' => 'Log file cleared.']);
    }
}
