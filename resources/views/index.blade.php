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
                @if ($allowDelete)
                    <button type="button" id="delete-btn" class="danger">Eliminar archivo</button>
                    <button type="button" id="delete-all-btn" class="danger">Eliminar todos</button>
                @endif
            </div>

            <div class="stats" id="stats-bar"></div>

            <div id="entries-list"></div>

            <div class="pagination">
                <button type="button" id="prev-page">&laquo; Anterior</button>
                <span id="page-indicator"></span>
                <button type="button" id="next-page">Siguiente &raquo;</button>
            </div>
        </main>
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

        const MESSAGE_PREVIEW_LIMIT = 500;

        function copyToClipboard(text) {
            if (navigator.clipboard && window.isSecureContext) {
                return navigator.clipboard.writeText(text);
            }

            return new Promise(function (resolve, reject) {
                const textarea = document.createElement('textarea');
                textarea.value = text;
                textarea.style.position = 'fixed';
                textarea.style.opacity = '0';
                document.body.appendChild(textarea);
                textarea.focus();
                textarea.select();

                try {
                    document.execCommand('copy') ? resolve() : reject(new Error('copy command failed'));
                } catch (err) {
                    reject(err);
                } finally {
                    document.body.removeChild(textarea);
                }
            });
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

                const isLong = entry.message.length > MESSAGE_PREVIEW_LIMIT;
                const preview = isLong ? entry.message.slice(0, MESSAGE_PREVIEW_LIMIT) + '…' : entry.message;

                el.innerHTML = `
                    <div class="entry-head">
                        <span class="badge ${levelClass(entry.level)}">${escapeHtml(entry.level)}</span>
                        <span class="entry-time">${escapeHtml(time)}</span>
                    </div>
                    <div class="entry-message">${escapeHtml(preview)}</div>
                    <div class="entry-actions">
                        ${isLong ? '<button type="button" class="toggle-message">Ver completo</button>' : ''}
                        ${entry.stack_trace ? '<button type="button" class="toggle-trace">Ver stack trace</button>' : ''}
                        <button type="button" class="copy-entry">Copiar</button>
                    </div>
                    ${entry.stack_trace ? `<pre class="stack-trace">${escapeHtml(entry.stack_trace)}</pre>` : ''}
                `;

                if (isLong) {
                    const messageEl = el.querySelector('.entry-message');
                    const toggleMessageBtn = el.querySelector('.toggle-message');
                    let expanded = false;

                    toggleMessageBtn.addEventListener('click', function () {
                        expanded = !expanded;
                        messageEl.textContent = expanded ? entry.message : preview;
                        toggleMessageBtn.textContent = expanded ? 'Ver menos' : 'Ver completo';
                    });
                }

                const toggleBtn = el.querySelector('.toggle-trace');
                if (toggleBtn) {
                    toggleBtn.addEventListener('click', function () {
                        const trace = el.querySelector('.stack-trace');
                        trace.classList.toggle('open');
                        toggleBtn.textContent = trace.classList.contains('open') ? 'Ocultar stack trace' : 'Ver stack trace';
                    });
                }

                el.querySelector('.copy-entry').addEventListener('click', function (e) {
                    const button = e.currentTarget;
                    const text = [
                        time ? `[${time}]` : '',
                        entry.level.toUpperCase(),
                        '',
                        entry.message,
                        entry.stack_trace ? '\nStack trace:\n' + entry.stack_trace : '',
                    ].filter(Boolean).join('\n');

                    const originalLabel = button.textContent;

                    copyToClipboard(text).then(function () {
                        button.textContent = 'Copiado!';
                    }).catch(function () {
                        button.textContent = 'Error al copiar';
                    }).finally(function () {
                        setTimeout(function () {
                            button.textContent = originalLabel;
                        }, 1500);
                    });
                });

                entriesList.appendChild(el);
            });

            const totalPages = Math.max(1, Math.ceil(payload.total / payload.per_page));
            pageIndicator.textContent = `Página ${payload.page} de ${totalPages} (${payload.total} entradas)`;

            statsBar.innerHTML = Object.entries(payload.stats).map(function ([level, count]) {
                const active = state.level === level ? ' active' : '';
                return `<button type="button" class="stat-filter${active}" data-level="${level}"><strong class="badge ${levelClass(level)}">${level.toUpperCase()}</strong> ${count}</button>`;
            }).join('');

            statsBar.querySelectorAll('.stat-filter').forEach(function (statButton) {
                statButton.addEventListener('click', function () {
                    const level = statButton.dataset.level;

                    state.level = state.level === level ? 'all' : level;
                    state.page = 1;

                    const levelSelect = document.getElementById('level-select');
                    if (levelSelect) levelSelect.value = state.level;

                    fetchEntries();
                });
            });
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

        document.getElementById('delete-btn')?.addEventListener('click', function () {
            if (!state.file) return;

            if (!confirm(`¿Seguro que deseas ELIMINAR el archivo "${state.file}" del disco? Esta acción no se puede deshacer.`)) {
                return;
            }

            fetch(`${baseUrl}/${encodeURIComponent(state.file)}`, {
                method: 'DELETE',
                headers: {
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json',
                },
            }).then(refreshFileList);
        });

        document.getElementById('delete-all-btn')?.addEventListener('click', function () {
            if (!confirm('¿Seguro que deseas ELIMINAR TODOS los archivos de log del disco? Esta acción no se puede deshacer.')) {
                return;
            }

            fetch(`${baseUrl}/delete-all`, {
                method: 'DELETE',
                headers: {
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json',
                },
            }).then(refreshFileList);
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

        const fileListEl = document.getElementById('file-list');

        function bindFileItemClicks() {
            fileListEl.querySelectorAll('.file-item').forEach(function (link) {
                link.addEventListener('click', function (e) {
                    e.preventDefault();
                    state.file = link.dataset.file;
                    state.page = 1;

                    fileListEl.querySelectorAll('.file-item').forEach(function (el) {
                        el.classList.remove('active');
                    });
                    link.classList.add('active');

                    fetchEntries();
                });
            });
        }

        function renderFileList(files) {
            fileListEl.innerHTML = files.length
                ? files.map(function (file) {
                    const active = file.identifier === state.file ? ' active' : '';
                    const modified = file.modified_at ? new Date(file.modified_at).toLocaleString() : '';

                    return `<a href="#" class="file-item${active}" data-file="${escapeHtml(file.identifier)}">
                        ${escapeHtml(file.name)}
                        <small>${escapeHtml(file.human_size)} · ${escapeHtml(modified)}</small>
                    </a>`;
                }).join('')
                : '<p class="empty">No log files found.</p>';

            bindFileItemClicks();
        }

        function refreshFileList() {
            return fetch(`${baseUrl}/files`, { headers: { 'Accept': 'application/json' } })
                .then(function (res) { return res.json(); })
                .then(function (payload) {
                    const files = payload.data || [];
                    const stillExists = files.some(function (f) { return f.identifier === state.file; });

                    if (!stillExists) {
                        state.file = files.length ? files[0].identifier : null;
                        state.page = 1;
                    }

                    renderFileList(files);

                    if (state.file) {
                        fetchEntries();
                    } else {
                        entriesList.innerHTML = '<p class="empty">No hay archivos de log.</p>';
                        statsBar.innerHTML = '';
                        pageIndicator.textContent = '';
                    }
                });
        }

        bindFileItemClicks();
        setupAutoRefresh();
        fetchEntries();
    })();
    </script>
@endsection
