<?php

declare(strict_types=1);

namespace Luiscamp\LaravelLogMonitor\Http\Controllers;

use Illuminate\Routing\Controller;
use Luiscamp\LaravelLogMonitor\Contracts\LogRepositoryInterface;
use Luiscamp\LaravelLogMonitor\Exceptions\InvalidLogFileException;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Response;

final class LogDownloadController extends Controller
{
    public function __construct(
        private readonly LogRepositoryInterface $repository,
    ) {}

    public function __invoke(string $file): BinaryFileResponse|Response
    {
        if (! (bool) config('log-monitor.allow_download', true)) {
            abort(403, 'Downloading log files is disabled.');
        }

        try {
            $logFile = $this->repository->find($file);
        } catch (InvalidLogFileException) {
            abort(404, 'Log file not found.');
        }

        if ($logFile === null) {
            abort(404, 'Log file not found.');
        }

        return response()->download($logFile->path, $logFile->name);
    }
}
