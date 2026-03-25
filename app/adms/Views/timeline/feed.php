<?php
$urlAdm = rtrim($_ENV['URL_ADM'] ?? '', '/') . '/';
$csrfCreate = \App\adms\Helpers\CSRFHelper::generateCSRFToken('timeline_create_post');
?>
<link rel="stylesheet" href="<?php echo htmlspecialchars($urlAdm); ?>public/adms/css/timeline-feed.css?v=3">

<div class="container-fluid px-3 px-md-4">
    <?php include __DIR__ . '/../partials/alerts.php'; ?>
    <div class="row justify-content-center">
        <div class="col-12">
            <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-2 mb-3 mt-3">
                <h2 class="mb-0 mobile-hide-page-title"><i class="fas fa-stream text-info me-2"></i>Timeline</h2>
                <nav aria-label="breadcrumb" class="ms-md-auto mobile-hide-breadcrumb">
                    <ol class="breadcrumb mb-0">
                        <li class="breadcrumb-item"><a href="<?php echo htmlspecialchars($urlAdm); ?>dashboard">Dashboard</a></li>
                        <li class="breadcrumb-item active">Timeline</li>
                    </ol>
                </nav>
            </div>
        </div>
    </div>

    <div class="timeline-layout">
        <div class="timeline-main">
            <?php if (!empty($this->data['can_create'])): ?>
            <div class="card timeline-composer-card mb-4">
                <div class="card-body">
                    <form method="post" action="<?php echo htmlspecialchars($urlAdm); ?>create-timeline-post" enctype="multipart/form-data" class="timeline-composer-form" id="timelineComposerForm" novalidate>
                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfCreate); ?>">
                        <label class="form-label fw-semibold">Nova publicação</label>
                        <textarea name="content" id="timelineComposerText" class="form-control mb-2 timeline-mention-field" rows="3" placeholder="Compartilhe algo com a equipe…" autocomplete="off"></textarea>
                        <p class="small text-muted mb-2">Menções: digite <code>@</code> e o <strong>username</strong> (login) — a lista filtra por username. O botão <strong>Mencionar</strong> também insere <code>@username</code>.</p>
                        <div class="row g-2 mb-2">
                            <div class="col-12 col-md-6">
                                <label class="form-label small mb-0 text-muted">Imagem (arquivo)</label>
                                <input type="file" name="image" id="timelineFileImage" class="form-control form-control-sm" accept="image/jpeg,image/png,image/gif,image/webp">
                            </div>
                            <div class="col-12 col-md-6">
                                <label class="form-label small mb-0 text-muted">Vídeo curto (MP4 ou WebM, máx. 50MB)</label>
                                <input type="file" name="video" id="timelineFileVideo" class="form-control form-control-sm" accept="video/mp4,video/webm">
                            </div>
                        </div>
                        <div class="d-flex flex-wrap gap-2 mb-3">
                            <button type="button" class="btn btn-outline-secondary btn-sm" id="btnTimelineMention" data-bs-toggle="modal" data-bs-target="#modalTimelineMention"><i class="fas fa-at me-1"></i>Mencionar</button>
                            <button type="button" class="btn btn-outline-secondary btn-sm" id="btnTimelinePhotoCam" title="Usa a câmera do dispositivo (HTTPS recomendado)"><i class="fas fa-camera me-1"></i>Foto (câmera)</button>
                            <button type="button" class="btn btn-outline-secondary btn-sm" id="btnTimelineVideoCam" title="Gravação curta em WebM no navegador"><i class="fas fa-video me-1"></i>Vídeo (câmera)</button>
                        </div>
                        <button type="submit" class="btn btn-info text-white fw-semibold px-4"><i class="fas fa-paper-plane me-1"></i>Publicar</button>
                    </form>
                </div>
            </div>
            <?php endif; ?>

            <?php if (!empty($this->data['can_moderate'])): ?>
            <div class="alert alert-light border py-2 px-3 small mb-3">
                <a href="<?php echo htmlspecialchars($urlAdm); ?>timeline-moderate" class="fw-semibold"><i class="fas fa-shield-alt me-1"></i>Moderação e denúncias</a>
            </div>
            <?php endif; ?>

            <?php include __DIR__ . '/partials/feed_posts.php'; ?>

            <?php if (!empty($this->data['pagination']['html'])): ?>
                <div class="mt-3"><?php echo $this->data['pagination']['html']; ?></div>
            <?php endif; ?>
        </div>

        <aside class="timeline-sidebar d-none d-lg-block">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <h6 class="fw-bold text-muted text-uppercase small mb-3">Dicas</h6>
                    <ul class="small text-muted ps-3 mb-0">
                        <li class="mb-2">Respeite o ambiente corporativo.</li>
                        <li class="mb-2">Comunicados oficiais continuam em <a href="<?php echo htmlspecialchars($urlAdm); ?>list-informativos">Informativos</a>.</li>
                        <li class="mb-2">Menções: <code>@</code> + <strong>username</strong> (autocomplete por login); vídeos curtos por arquivo ou pela câmera.</li>
                    </ul>
                </div>
            </div>
        </aside>
    </div>
</div>

<div id="timelineMentionDropdown" class="timeline-mention-dropdown d-none" role="listbox" aria-label="Sugestões de menção"></div>

<?php if (!empty($this->data['can_create'])): ?>
<div class="modal fade" id="modalTimelineMention" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Mencionar colaborador</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <label class="form-label">Buscar por nome ou e-mail</label>
                <input type="text" class="form-control mb-2" id="timelineMentionSearch" placeholder="Digite nome ou e-mail" autocomplete="off">
                <div id="timelineMentionResults" class="list-group timeline-mention-results"></div>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Modal denúncia -->
<div class="modal fade" id="modalDenunciaTimeline" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Denunciar publicação</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="denunciaPostId" value="">
                <div class="mb-2">
                    <label class="form-label">Motivo</label>
                    <select id="denunciaMotivo" class="form-select">
                        <option value="Conteúdo inadequado">Conteúdo inadequado</option>
                        <option value="Assédio ou discriminação">Assédio ou discriminação</option>
                        <option value="Spam">Spam</option>
                        <option value="Outro">Outro</option>
                    </select>
                </div>
                <div class="mb-2">
                    <label class="form-label">Detalhes (opcional)</label>
                    <textarea id="denunciaDetalhes" class="form-control" rows="3"></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fechar</button>
                <button type="button" class="btn btn-danger" id="btnEnviarDenuncia">Enviar</button>
            </div>
        </div>
    </div>
</div>

<script>
(function () {
    const base = <?php echo json_encode($urlAdm); ?>;
    const canReport = <?php echo !empty($this->data['can_report']) ? 'true' : 'false'; ?>;
    const canCreate = <?php echo !empty($this->data['can_create']) ? 'true' : 'false'; ?>;

    function escapeHtml(s) {
        const d = document.createElement('div');
        d.textContent = s || '';
        return d.innerHTML;
    }

    var ajaxHeaders = {
        'X-Requested-With': 'XMLHttpRequest',
        'Accept': 'application/json'
    };

    /** fetch + parse JSON; evita crash se o PHP devolver texto (ex.: Erro 004) */
    function fetchJson(url, options) {
        options = options || {};
        if (!options.credentials) {
            options.credentials = 'same-origin';
        }
        options.headers = Object.assign({}, ajaxHeaders, options.headers || {});
        return fetch(url, options).then(function (r) {
            return r.text().then(function (text) {
                var data;
                try {
                    data = JSON.parse(text);
                } catch (e) {
                    console.error('Resposta não é JSON:', (text || '').slice(0, 400));
                    throw new Error((text && text.indexOf('Erro') === 0) ? text : 'Resposta inválida do servidor.');
                }
                return data;
            });
        });
    }

    function bindPostActions() {
        document.querySelectorAll('.btn-timeline-like').forEach(function (btn) {
            btn.onclick = function () {
                const id = btn.getAttribute('data-post-id');
                fetchJson(base + 'timeline-like/' + id, { method: 'POST' })
                    .then(function (data) {
                        if (data && data.success) {
                            const c = document.querySelector('.timeline-like-count[data-post-id="' + id + '"]');
                            if (c) c.textContent = data.likes_count;
                            btn.classList.toggle('active', data.liked);
                            btn.querySelector('i').className = data.liked ? 'fas fa-heart' : 'far fa-heart';
                        }
                    })
                    .catch(function (err) { console.error(err); alert(err.message || 'Não foi possível curtir.'); });
            };
        });

        document.querySelectorAll('.btn-timeline-comments-toggle').forEach(function (btn) {
            btn.onclick = function () {
                const id = btn.getAttribute('data-post-id');
                const box = document.getElementById('comments-' + id);
                if (!box) return;
                box.classList.toggle('d-none');
                if (!box.classList.contains('d-none') && box.getAttribute('data-loaded') !== '1') {
                    fetchJson(base + 'timeline-comment/' + id, {})
                        .then(function (data) {
                            if (data && data.success) {
                                renderComments(id, data.comments || []);
                                box.setAttribute('data-loaded', '1');
                            }
                        })
                        .catch(function (err) { console.error(err); alert(err.message || 'Não foi possível carregar comentários.'); });
                }
            };
        });

        document.querySelectorAll('.btn-timeline-comment-send').forEach(function (btn) {
            btn.onclick = function () {
                const id = btn.getAttribute('data-post-id');
                const inp = document.querySelector('.timeline-comment-input[data-post-id="' + id + '"]');
                if (!inp || !inp.value.trim()) return;
                const fd = new FormData();
                fd.append('post_id', id);
                fd.append('content', inp.value.trim());
                fetchJson(base + 'timeline-comment/' + id, { method: 'POST', body: fd })
                    .then(function (data) {
                        if (data && data.success) {
                            inp.value = '';
                            renderComments(id, data.comments || []);
                            const box = document.getElementById('comments-' + id);
                            if (box) { box.classList.remove('d-none'); box.setAttribute('data-loaded', '1'); }
                        } else if (data && data.message) {
                            alert(data.message);
                        }
                    })
                    .catch(function (err) { console.error(err); alert(err.message || 'Não foi possível enviar o comentário.'); });
            };
        });

        document.querySelectorAll('.btn-timeline-report').forEach(function (btn) {
            btn.onclick = function () {
                const id = btn.getAttribute('data-post-id');
                document.getElementById('denunciaPostId').value = id;
                const m = new bootstrap.Modal(document.getElementById('modalDenunciaTimeline'));
                m.show();
            };
        });
    }

    function renderComments(postId, comments) {
        const wrap = document.querySelector('.timeline-comments-list[data-post-id="' + postId + '"]');
        if (!wrap) return;
        wrap.innerHTML = '';
        comments.forEach(function (c) {
            const div = document.createElement('div');
            div.className = 'mb-2 small timeline-comment-line';
            const body = c.content_html ? c.content_html : escapeHtml(c.content || '');
            div.innerHTML = '<strong>' + escapeHtml(c.author_name || '') + '</strong> · ' + escapeHtml(c.created_at || '') + '<br>' + body;
            wrap.appendChild(div);
        });
    }

    const btnDen = document.getElementById('btnEnviarDenuncia');
    if (btnDen) {
        btnDen.onclick = function () {
            if (!canReport) return;
            const id = document.getElementById('denunciaPostId').value;
            const fd = new FormData();
            fd.append('post_id', id);
            fd.append('reason', document.getElementById('denunciaMotivo').value);
            fd.append('details', document.getElementById('denunciaDetalhes').value);
            fetchJson(base + 'timeline-report', { method: 'POST', body: fd })
                .then(function (data) {
                    alert(data && data.message ? data.message : 'Enviado.');
                    bootstrap.Modal.getInstance(document.getElementById('modalDenunciaTimeline')).hide();
                })
                .catch(function (err) { console.error(err); alert(err.message || 'Não foi possível enviar a denúncia.'); });
        };
    }

    bindPostActions();

    const imgIn = document.getElementById('timelineFileImage');
    const vidIn = document.getElementById('timelineFileVideo');
    if (imgIn && vidIn) {
        imgIn.addEventListener('change', function () { if (imgIn.files && imgIn.files.length) vidIn.value = ''; });
        vidIn.addEventListener('change', function () { if (vidIn.files && vidIn.files.length) imgIn.value = ''; });
    }

    const ta = document.getElementById('timelineComposerText');
    const mentionSearch = document.getElementById('timelineMentionSearch');
    const mentionResults = document.getElementById('timelineMentionResults');
    const mentionDd = document.getElementById('timelineMentionDropdown');

    function appendMentionToComposer(username) {
        if (!ta || !username) return;
        const ins = '@' + username + ' ';
        ta.value = (ta.value || '') + (ta.value && !/\s$/.test(ta.value) ? ' ' : '') + ins;
        ta.focus();
        const mEl = document.getElementById('modalTimelineMention');
        if (mEl) {
            const mi = bootstrap.Modal.getInstance(mEl);
            if (mi) mi.hide();
        }
    }

    if (canCreate && mentionSearch && mentionResults) {
        let modalMentionTimer = null;
        mentionSearch.addEventListener('input', function () {
            clearTimeout(modalMentionTimer);
            const q = mentionSearch.value.trim();
            mentionResults.innerHTML = '';
            modalMentionTimer = setTimeout(function () {
                fetchJson(base + 'timeline-search-users?q=' + encodeURIComponent(q), {})
                    .then(function (data) {
                        mentionResults.innerHTML = '';
                        if (!data || !data.users || !data.users.length) {
                            mentionResults.innerHTML = '<div class="list-group-item text-muted small">Nenhum resultado</div>';
                            return;
                        }
                        data.users.forEach(function (u) {
                            const a = document.createElement('button');
                            a.type = 'button';
                            a.className = 'list-group-item list-group-item-action text-start';
                            a.innerHTML = '<span class="fw-semibold">@' + escapeHtml(u.username || '') + '</span><br><span class="small text-muted">' + escapeHtml(u.name || '') + ' · ' + escapeHtml(u.email || '') + '</span>';
                            a.onclick = function () { appendMentionToComposer(u.username || String(u.id)); mentionSearch.value = ''; };
                            mentionResults.appendChild(a);
                        });
                    });
            }, 250);
        });
    }

    (function timelineInlineMentions() {
        if (!mentionDd) return;

        var mState = { active: false, el: null, start: 0, query: '', users: [], hi: -1, timer: null };

        function getMentionContext(text, pos) {
            var before = text.slice(0, pos);
            var m = before.match(/(?:^|\s)@([^\s@]*)$/);
            if (!m) return null;
            var atIdx = before.length - m[0].length + m[0].indexOf('@');
            var query = text.slice(atIdx + 1, pos);
            if (query.indexOf(' ') >= 0 || query.indexOf('\n') >= 0) return null;
            return { start: atIdx, query: query };
        }

        function positionMentionDropdown(el) {
            var r = el.getBoundingClientRect();
            mentionDd.style.position = 'fixed';
            mentionDd.style.left = Math.round(r.left) + 'px';
            mentionDd.style.top = Math.round(r.bottom + 4) + 'px';
            mentionDd.style.width = Math.round(Math.max(280, r.width)) + 'px';
        }

        function hideMentionDd() {
            mentionDd.classList.add('d-none');
            mentionDd.innerHTML = '';
            mState = { active: false, el: null, start: 0, query: '', users: [], hi: -1, timer: null };
        }

        function renderMentionItems() {
            mentionDd.innerHTML = '';
            if (!mState.users.length) {
                mentionDd.innerHTML = '<div class="timeline-mention-dropdown-item text-muted small">Nenhum colaborador encontrado</div>';
                return;
            }
            mState.users.forEach(function (u, i) {
                var b = document.createElement('button');
                b.type = 'button';
                b.className = 'timeline-mention-dropdown-item' + (i === mState.hi ? ' active' : '');
                b.setAttribute('role', 'option');
                b.innerHTML = '<span class="fw-semibold">@' + escapeHtml(u.username || '') + '</span><span class="d-block small text-muted">' + escapeHtml(u.name || '') + ' · ' + escapeHtml(u.email || '') + '</span>';
                b.onclick = function (ev) {
                    ev.preventDefault();
                    applyMentionPick(u);
                };
                mentionDd.appendChild(b);
            });
        }

        function applyMentionPick(u) {
            if (!mState.el || !mState.active) return;
            var el = mState.el;
            var text = el.value;
            var pos = typeof el.selectionStart === 'number' ? el.selectionStart : text.length;
            var ctx = getMentionContext(text, pos);
            if (!ctx) {
                hideMentionDd();
                return;
            }
            var replacement = '@' + (u.username || u.id) + ' ';
            var newText = text.slice(0, ctx.start) + replacement + text.slice(pos);
            el.value = newText;
            var cursor = ctx.start + replacement.length;
            el.selectionStart = el.selectionEnd = cursor;
            el.focus();
            hideMentionDd();
        }

        function fetchMentionUsers(q) {
            fetchJson(base + 'timeline-search-users?q=' + encodeURIComponent(q), {})
                .then(function (data) {
                    mState.users = (data && data.users) ? data.users : [];
                    mState.hi = mState.users.length ? 0 : -1;
                    renderMentionItems();
                    mentionDd.classList.remove('d-none');
                })
                .catch(function () { hideMentionDd(); });
        }

        document.addEventListener('input', function (ev) {
            var el = ev.target;
            if (!el.classList || !el.classList.contains('timeline-mention-field')) return;
            var text = el.value;
            var pos = typeof el.selectionStart === 'number' ? el.selectionStart : text.length;
            var ctx = getMentionContext(text, pos);
            if (!ctx) {
                hideMentionDd();
                return;
            }
            mState.el = el;
            mState.start = ctx.start;
            mState.query = ctx.query;
            mState.active = true;
            positionMentionDropdown(el);
            clearTimeout(mState.timer);
            mState.timer = setTimeout(function () {
                fetchMentionUsers(ctx.query);
            }, 200);
        }, true);

        document.addEventListener('keydown', function (ev) {
            if (!mState.active || mentionDd.classList.contains('d-none')) return;
            var t = ev.target;
            if (!t.classList || !t.classList.contains('timeline-mention-field')) return;
            if (ev.key === 'Escape') {
                ev.preventDefault();
                hideMentionDd();
                return;
            }
            if (!mState.users.length) return;
            if (ev.key === 'ArrowDown') {
                ev.preventDefault();
                mState.hi = Math.min(mState.hi + 1, mState.users.length - 1);
                renderMentionItems();
                return;
            }
            if (ev.key === 'ArrowUp') {
                ev.preventDefault();
                mState.hi = Math.max(mState.hi - 1, 0);
                renderMentionItems();
                return;
            }
            if (ev.key === 'Enter' && mState.hi >= 0 && mState.users[mState.hi]) {
                ev.preventDefault();
                applyMentionPick(mState.users[mState.hi]);
            }
        }, true);

        document.addEventListener('click', function (ev) {
            if (!mState.active) return;
            if (ev.target.closest && ev.target.closest('#timelineMentionDropdown')) return;
            if (ev.target.closest && ev.target.closest('.timeline-mention-field')) return;
            hideMentionDd();
        });

        window.addEventListener('scroll', function () {
            if (mState.active && mState.el && !mentionDd.classList.contains('d-none')) {
                positionMentionDropdown(mState.el);
            }
        }, true);
    })();

    const btnPhoto = document.getElementById('btnTimelinePhotoCam');
    if (btnPhoto && imgIn) {
        btnPhoto.addEventListener('click', function () {
            if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
                alert('Seu navegador não suporta acesso à câmera. Use o campo de imagem.');
                return;
            }
            navigator.mediaDevices.getUserMedia({ video: { facingMode: 'user' }, audio: false }).then(function (stream) {
                const v = document.createElement('video');
                v.playsInline = true;
                v.muted = true;
                v.srcObject = stream;
                v.onloadedmetadata = function () {
                    v.play().then(function () {
                        const canvas = document.createElement('canvas');
                        canvas.width = v.videoWidth || 640;
                        canvas.height = v.videoHeight || 480;
                        const ctx = canvas.getContext('2d');
                        ctx.drawImage(v, 0, 0, canvas.width, canvas.height);
                        stream.getTracks().forEach(function (t) { t.stop(); });
                        canvas.toBlob(function (blob) {
                            if (!blob) return;
                            const dt = new DataTransfer();
                            dt.items.add(new File([blob], 'camera-' + Date.now() + '.jpg', { type: 'image/jpeg' }));
                            imgIn.files = dt.files;
                            vidIn.value = '';
                        }, 'image/jpeg', 0.88);
                    }).catch(function () { stream.getTracks().forEach(function (t) { t.stop(); }); });
                };
            }).catch(function () {
                alert('Não foi possível acessar a câmera. Verifique permissões e use HTTPS em produção.');
            });
        });
    }

    // Envio via fetch: em alguns navegadores (mobile/câmera) arquivos atribuídos com DataTransfer não entram no POST nativo.
    const composerForm = document.getElementById('timelineComposerForm');
    if (composerForm) {
        composerForm.addEventListener('submit', function (ev) {
            ev.preventDefault();
            const submitBtn = composerForm.querySelector('button[type="submit"]');
            if (submitBtn) {
                submitBtn.disabled = true;
            }
            const fd = new FormData(composerForm);
            const actionUrl = composerForm.getAttribute('action');
            fetch(actionUrl, {
                method: 'POST',
                body: fd,
                credentials: 'same-origin',
                redirect: 'follow',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json, text/html;q=0.9, */*;q=0.8'
                }
            })
                .then(function (response) {
                    var ct = (response.headers.get('Content-Type') || '').toLowerCase();
                    if (ct.indexOf('application/json') !== -1) {
                        return response.json().then(function (data) {
                            return { kind: 'json', data: data, response: response };
                        });
                    }
                    if (response.redirected && response.url) {
                        window.location.href = response.url;
                        return null;
                    }
                    return response.text().then(function (html) {
                        return { kind: 'html', response: response, html: html };
                    });
                })
                .then(function (result) {
                    if (!result) {
                        return;
                    }
                    if (result.kind === 'json') {
                        var d = result.data || {};
                        if (d.success === false || d.error) {
                            alert(d.error || d.message || 'Não foi possível publicar. Tente novamente.');
                            return;
                        }
                        window.location.href = base.replace(/\/?$/, '/') + 'timeline';
                        return;
                    }
                    var h = result.html || '';
                    if (h.indexOf('Erro 004') !== -1 || h.indexOf('Erro 003') !== -1) {
                        document.open();
                        document.write(h);
                        document.close();
                        return;
                    }
                    window.location.href = base.replace(/\/?$/, '/') + 'timeline';
                })
                .catch(function () {
                    alert('Não foi possível enviar a publicação. Verifique a conexão e tente novamente.');
                })
                .finally(function () {
                    if (submitBtn) {
                        submitBtn.disabled = false;
                    }
                });
        });
    }

    const btnVid = document.getElementById('btnTimelineVideoCam');
    if (btnVid && vidIn) {
        btnVid.addEventListener('click', function () {
            if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
                alert('Gravação no navegador indisponível. Envie um arquivo MP4/WebM.');
                return;
            }
            let mime = 'video/webm;codecs=vp8,opus';
            if (window.MediaRecorder && !MediaRecorder.isTypeSupported(mime)) {
                mime = 'video/webm';
            }
            navigator.mediaDevices.getUserMedia({ video: true, audio: true }).then(function (stream) {
                let rec;
                try {
                    rec = new MediaRecorder(stream, { mimeType: mime });
                } catch (e) {
                    stream.getTracks().forEach(function (t) { t.stop(); });
                    alert('Gravação não suportada neste navegador.');
                    return;
                }
                const chunks = [];
                rec.ondataavailable = function (e) { if (e.data && e.data.size) chunks.push(e.data); };
                rec.start(1000);
                const maxMs = 90000;
                const stopAll = function () {
                    if (rec.state !== 'inactive') rec.stop();
                    stream.getTracks().forEach(function (t) { t.stop(); });
                };
                alert('Gravação iniciada. Será encerrada automaticamente em até 90 segundos.');
                setTimeout(stopAll, maxMs);
                rec.onstop = function () {
                    const blob = new Blob(chunks, { type: 'video/webm' });
                    const dt = new DataTransfer();
                    dt.items.add(new File([blob], 'gravacao-' + Date.now() + '.webm', { type: 'video/webm' }));
                    vidIn.files = dt.files;
                    if (imgIn) imgIn.value = '';
                };
            }).catch(function () {
                alert('Não foi possível acessar câmera/microfone.');
            });
        });
    }
})();
</script>
