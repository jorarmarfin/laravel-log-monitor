<div class="stats" id="stats-bar">
    @foreach (($stats ?? []) as $level => $count)
        <span><strong class="badge level-{{ $level }}">{{ strtoupper($level) }}</strong> {{ $count }}</span>
    @endforeach
</div>
