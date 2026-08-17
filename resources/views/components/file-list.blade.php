@foreach ($files as $file)
    <a
        href="{{ route('log-monitor.index', ['file' => $file->identifier]) }}"
        class="file-item {{ $selected && $selected->identifier === $file->identifier ? 'active' : '' }}"
        data-file="{{ $file->identifier }}"
    >
        {{ $file->name }}
        <small>{{ $file->humanSize() }} · {{ $file->modifiedAt->diffForHumans() }}</small>
    </a>
@endforeach

@if (empty($files))
    <p class="empty">No log files found.</p>
@endif
