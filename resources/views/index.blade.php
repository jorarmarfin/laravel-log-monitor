@extends('log-monitor::layout')

@section('content')
    <div class="app">
        <aside>
            <div id="file-list">
                @include('log-monitor::components.file-list', ['files' => $files, 'selected' => $selected])
            </div>
        </aside>

        <main>
            <div class="toolbar">
                <input type="search" id="search-input" placeholder="Buscar en logs...">
                <select id="level-select">
                    <option value="all">Todos</option>
                    @foreach ($levels as $level)
                        <option value="{{ $level }}">{{ ucfirst($level) }}</option>
                    @endforeach
                </select>
                <select id="order-select">
                    <option value="desc">Más recientes</option>
                    <option value="asc">Más antiguos</option>
                </select>
                <select id="per-page-select">
                    @foreach (($pagination['options'] ?? [25, 50, 100, 250]) as $option)
                        <option value="{{ $option }}" @selected($option === ($pagination['per_page'] ?? 50))>{{ $option }}</option>
                    @endforeach
                </select>
                @if ($autoRefresh)
                    <label style="display:flex;align-items:center;gap:4px;font-size:13px;">
                        <input type="checkbox" id="auto-refresh-toggle" checked> Auto refresh
                    </label>
                @endif
                @if ($allowDownload)
                    <button type="button" id="download-btn">Descargar</button>
                @endif
                @if ($allowClear)
                    <button type="button" id="clear-btn">Limpiar</button>
                    <button type="button" id="clear-all-btn">Vaciar todos</button>
                @endif
            </div>

            <div id="entries-list"></div>

            <div class="pagination">
                <button type="button" id="prev-page">&laquo; Anterior</button>
                <span id="page-indicator"></span>
                <button type="button" id="next-page">Siguiente &raquo;</button>
            </div>
        </main>
    </div>

    <div id="stats-container">
        <footer class="stats" id="stats-bar"></footer>
    </div>

    <form id="clear-form" method="POST" style="display:none;">
        @csrf
    </form>

    <script>
    (function () {
        const state = {
            file: @json($selected?->identifier),
            page: 1,
            perPage: {{ (int) ($pagination['per_page'] ?? 50) }},
            level: 'all',
            search: '',
            order: 'desc',
            autoRefreshInterval: {{ (int) $autoRefreshInterval }},
        };

        const baseUrl = @json(url()->current());
        const csrfToken = @json(csrf_token());
        let refreshTimer = null;

        const entriesList = document.getElementById('entries-list');
        const statsBar = document.getElementById('stats-bar');
        const pageIndicator = document.getElementById('page-indicator');

        function levelClass(level) {
            return 'level-' + level;
        }

        function escapeHtml(str) {
            const div = document.createElement('div');
            div.textContent = str ?? '';
            return div.innerHTML;
        }

        function renderEntries(payload) {
            entriesList.innerHTML = '';

            if (!payload.data.length) {
                entriesList.innerHTML = '<p class="empty">No hay entradas para mostrar.</p>';
            }

            payload.data.forEach(function (entry, index) {
                const el = document.createElement('div');
                el.className = 'entry ' + levelClass(entry.level);

                const time = entry.timestamp ? new Date(entry.timestamp).toLocaleString() : '';

                el.innerHTML = `
                    <div class="entry-head">
                        <span class="badge ${levelClass(entry.level)}">${escapeHtml(entry.level)}</span>
                        <span class="entry-time">${escapeHtml(time)}</span>
                    </div>
                    <div class="entry-message">${escapeHtml(entry.message)}</div>
                    <div class="entry-actions">
                        ${entry.stack_trace ? '<button type="button" class="toggle-trace">Ver stack trace</button>' : ''}
                        <button type="button" class="copy-entry">Copiar</button>
                    </div>
                    ${entry.stack_trace ? `<pre class="stack-trace">${escapeHtml(entry.stack_trace)}</pre>` : ''}
                `;

                const toggleBtn = el.querySelector('.toggle-trace');
                if (toggleBtn) {
                    toggleBtn.addEventListener('click', function () {
                        const trace = el.querySelector('.stack-trace');
                        trace.classList.toggle('open');
                        toggleBtn.textContent = trace.classList.contains('open') ? 'Ocultar stack trace' : 'Ver stack trace';
                    });
                }

                el.querySelector('.copy-entry').addEventListener('click', function () {
                    const text = [
                        time ? `[${time}]` : '',
                        entry.level.toUpperCase(),
                        '',
                        entry.message,
                        entry.stack_trace ? '\nStack trace:\n' + entry.stack_trace : '',
                    ].filter(Boolean).join('\n');

                    navigator.clipboard.writeText(text);
                });

                entriesList.appendChild(el);
            });

            const totalPages = Math.max(1, Math.ceil(payload.total / payload.per_page));
            pageIndicator.textContent = `Página ${payload.page} de ${totalPages} (${payload.total} entradas)`;

            statsBar.innerHTML = Object.entries(payload.stats).map(function ([level, count]) {
                return `<span><strong class="badge ${levelClass(level)}">${level.toUpperCase()}</strong> ${count}</span>`;
            }).join('');
        }

        function fetchEntries() {
            if (!state.file) {
                return;
            }

            const params = new URLSearchParams({
                page: state.page,
                per_page: state.perPage,
                level: state.level,
                q: state.search,
                order: state.order,
            });

            fetch(`${baseUrl}/${encodeURIComponent(state.file)}?${params.toString()}`, {
                headers: { 'Accept': 'application/json' },
            })
                .then(function (res) { return res.json(); })
                .then(renderEntries)
                .catch(function () {});
        }

        document.getElementById('search-input')?.addEventListener('input', function (e) {
            state.search = e.target.value;
            state.page = 1;
            fetchEntries();
        });

        document.getElementById('level-select')?.addEventListener('change', function (e) {
            state.level = e.target.value;
            state.page = 1;
            fetchEntries();
        });

        document.getElementById('order-select')?.addEventListener('change', function (e) {
            state.order = e.target.value;
            fetchEntries();
        });

        document.getElementById('per-page-select')?.addEventListener('change', function (e) {
            state.perPage = parseInt(e.target.value, 10);
            state.page = 1;
            fetchEntries();
        });

        document.getElementById('prev-page')?.addEventListener('click', function () {
            if (state.page > 1) {
                state.page -= 1;
                fetchEntries();
            }
        });

        document.getElementById('next-page')?.addEventListener('click', function () {
            state.page += 1;
            fetchEntries();
        });

        document.getElementById('download-btn')?.addEventListener('click', function () {
            if (state.file) {
                window.location = `${baseUrl}/${encodeURIComponent(state.file)}/download`;
            }
        });

        document.getElementById('clear-btn')?.addEventListener('click', function () {
            if (!state.file) return;

            if (!confirm('¿Seguro que deseas vaciar este archivo?')) {
                return;
            }

            fetch(`${baseUrl}/${encodeURIComponent(state.file)}/clear`, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json',
                },
            }).then(fetchEntries);
        });

        document.getElementById('clear-all-btn')?.addEventListener('click', function () {
            if (!confirm('¿Seguro que deseas vaciar TODOS los archivos de log? Esta acción no se puede deshacer.')) {
                return;
            }

            fetch(`${baseUrl}/clear-all`, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json',
                },
            }).then(fetchEntries);
        });

        function setupAutoRefresh() {
            const toggle = document.getElementById('auto-refresh-toggle');

            function start() {
                if (refreshTimer) clearInterval(refreshTimer);
                refreshTimer = setInterval(fetchEntries, Math.max(3, state.autoRefreshInterval) * 1000);
            }

            function stop() {
                if (refreshTimer) clearInterval(refreshTimer);
            }

            if (toggle) {
                toggle.addEventListener('change', function () {
                    toggle.checked ? start() : stop();
                });

                if (toggle.checked) start();
            }
        }

        document.querySelectorAll('.file-item').forEach(function (link) {
            link.addEventListener('click', function (e) {
                e.preventDefault();
                state.file = link.dataset.file;
                state.page = 1;

                document.querySelectorAll('.file-item').forEach(function (el) {
                    el.classList.remove('active');
                });
                link.classList.add('active');

                fetchEntries();
            });
        });

        setupAutoRefresh();
        fetchEntries();
    })();
    </script>
@endsection
