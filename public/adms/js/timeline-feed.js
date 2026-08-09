(function () {
    var cfg = window.__TimelineFeedInit || {};
    const base = cfg.base;
    const timelineResolvedPostId = cfg.timelineResolvedPostId != null ? Number(cfg.timelineResolvedPostId) : 0;
    const timelineResolvedCommentId = cfg.timelineResolvedCommentId != null ? Number(cfg.timelineResolvedCommentId) : 0;
    const timelineActiveTagJs = cfg.timelineActiveTagJs != null ? String(cfg.timelineActiveTagJs) : '';
    const canReport = !!cfg.canReport;
    const canCreate = !!cfg.canCreate;
    const canLike = !!cfg.canLike;
    const pollStateMap = cfg.pollStateMap != null ? cfg.pollStateMap : {};
    let currentPollVotesPostId = '';
    let timelineEditCsrf = cfg.timelineEditCsrf != null ? String(cfg.timelineEditCsrf) : '';
    let timelineCommentCsrf = cfg.timelineCommentCsrf != null ? String(cfg.timelineCommentCsrf) : '';
    let timelineLikeCsrf = cfg.timelineLikeCsrf != null ? String(cfg.timelineLikeCsrf) : '';
    let timelineReportCsrf = cfg.timelineReportCsrf != null ? String(cfg.timelineReportCsrf) : '';
    let timelineDeleteCsrf = cfg.timelineDeleteCsrf != null ? String(cfg.timelineDeleteCsrf) : '';

    function initTimelineLazyVideos(root) {
        var scope = root || document;
        var videos = scope.querySelectorAll('video.timeline-post-video-lazy[data-src]');
        if (!videos.length) {
            return;
        }
        function attachSrc(video) {
            var url = video.getAttribute('data-src');
            if (!url) {
                return;
            }
            video.src = url;
            video.removeAttribute('data-src');
            video.preload = 'metadata';
        }
        if (!('IntersectionObserver' in window)) {
            videos.forEach(attachSrc);
            return;
        }
        var observer = new IntersectionObserver(function (entries, obs) {
            entries.forEach(function (entry) {
                if (!entry.isIntersecting) {
                    return;
                }
                attachSrc(entry.target);
                obs.unobserve(entry.target);
            });
        }, { root: null, rootMargin: '240px 0px', threshold: 0.01 });
        videos.forEach(function (video) {
            observer.observe(video);
        });
    }
    initTimelineLazyVideos();
    window.initTimelineLazyVideos = initTimelineLazyVideos;

    var reactionOrder = ['like', 'love', 'care', 'haha', 'wow', 'sad', 'angry'];
    var reactionLabels = { like: 'Curtir', love: 'Amei', care: 'Cuidar', haha: 'Risada', wow: 'Uau', sad: 'Triste', angry: 'Raiva' };
    var reactionIconClasses = {
        like: 'fas fa-thumbs-up text-fb-like',
        love: 'fas fa-heart text-fb-love',
        care: 'fas fa-hand-holding-heart text-fb-care',
        haha: 'fas fa-laugh-beam text-fb-haha',
        wow: 'fas fa-grin-stars text-fb-wow',
        sad: 'fas fa-sad-tear text-fb-sad',
        angry: 'fas fa-angry text-fb-angry'
    };

    (function focusPostFromQuery() {
        try {
            var params = new URLSearchParams(window.location.search || '');
            var postId = timelineResolvedPostId > 0 ? String(timelineResolvedPostId) : params.get('post');
            if (!postId) return;
            var commentId = timelineResolvedCommentId > 0 ? String(timelineResolvedCommentId) : params.get('comment');
            var focusBody = params.get('focus') === 'body';

            function focusPostCard() {
                var target = document.getElementById('timeline-post-' + postId);
                if (!target) return;
                target.scrollIntoView({ behavior: 'smooth', block: 'center' });
                target.classList.add('timeline-post-focus');
                setTimeout(function () {
                    target.classList.remove('timeline-post-focus');
                }, 3400);
            }

            function focusPostBody() {
                setTimeout(function () {
                    var bodyEl = document.getElementById('timeline-post-body-' + postId);
                    if (!bodyEl) {
                        focusPostCard();
                        return;
                    }
                    bodyEl.scrollIntoView({ behavior: 'smooth', block: 'center' });
                    bodyEl.classList.add('timeline-post-body-focus');
                    setTimeout(function () {
                        bodyEl.classList.remove('timeline-post-body-focus');
                    }, 2200);
                }, 80);
            }

            if (commentId) {
                focusPostCard();
            } else if (focusBody) {
                focusPostBody();
                return;
            } else {
                focusPostCard();
                return;
            }

            if (!commentId) return;

            var box = document.getElementById('comments-' + postId);
            if (!box) return;
            box.classList.remove('d-none');

            function scrollToCommentLine() {
                var el = document.getElementById('timeline-comment-' + commentId);
                if (!el) return;
                el.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
                el.classList.add('timeline-comment-focus');
                setTimeout(function () {
                    el.classList.remove('timeline-comment-focus');
                }, 2200);
            }

            if (box.getAttribute('data-loaded') === '1') {
                setTimeout(scrollToCommentLine, 80);
            } else {
                fetchJson(base + 'timeline-comment/' + postId, {})
                    .then(function (data) {
                        if (!data || !data.success) return;
                        renderComments(postId, data.comments || []);
                        box.setAttribute('data-loaded', '1');
                        requestAnimationFrame(function () {
                            requestAnimationFrame(scrollToCommentLine);
                        });
                    })
                    .catch(function () { /* sem permissão ou erro: post já foi destacado */ });
            }
        } catch (e) {
            // ignore
        }
    })();

    function renderReactionStackHtml(summary) {
        summary = summary || {};
        var types = [];
        reactionOrder.forEach(function (t) {
            var n = summary[t] ? parseInt(summary[t], 10) : 0;
            if (n > 0 && types.length < 3) types.push(t);
        });
        if (!types.length) {
            return '<span class="timeline-reaction-stack-item timeline-reaction-stack-empty"><i class="far fa-thumbs-up opacity-50"></i></span>';
        }
        var html = '';
        types.forEach(function (t, idx) {
            var ic = reactionIconClasses[t] || reactionIconClasses.like;
            html += '<span class="timeline-reaction-stack-item" style="z-index:' + (10 - idx) + '"><i class="' + ic + '"></i></span>';
        });
        return html;
    }

    function updateReactionUI(postId, data) {
        var total = data.likes_count != null ? parseInt(data.likes_count, 10) : 0;
        var reaction = data.reaction;
        var summary = data.summary || {};
        var countEls = document.querySelectorAll('.timeline-like-count[data-post-id="' + postId + '"]');
        countEls.forEach(function (c) { c.textContent = total; });
        var stack = document.querySelector('.timeline-reaction-stack[data-post-id="' + postId + '"]');
        if (stack) stack.innerHTML = renderReactionStackHtml(summary);
        var hit = document.querySelector('.timeline-reactions-summary-hit[data-post-id="' + postId + '"]');
        if (hit) {
            if (total > 0) { hit.classList.remove('text-muted'); hit.classList.add('text-body'); }
            else { hit.classList.add('text-muted'); hit.classList.remove('text-body'); }
        }
        var mainBtn = document.querySelector('.timeline-curtir-main[data-post-id="' + postId + '"]');
        if (mainBtn) {
            var icEl = mainBtn.querySelector('i');
            var labEl = mainBtn.querySelector('.timeline-curtir-label');
            if (reaction && reactionIconClasses[reaction]) {
                if (icEl) icEl.className = reactionIconClasses[reaction];
                if (labEl) labEl.textContent = reactionLabels[reaction] || 'Curtir';
            } else {
                if (icEl) icEl.className = 'far fa-thumbs-up';
                if (labEl) labEl.textContent = 'Curtir';
            }
        }
        document.querySelectorAll('.timeline-reaction-tray-mobile[data-post-id="' + postId + '"]').forEach(function (tr) {
            tr.classList.remove('is-open');
        });
    }

    function buildReactionsModal(rows) {
        var tabList = document.getElementById('timelineReactionsTabList');
        var panels = document.getElementById('timelineReactionsTabPanels');
        if (!tabList || !panels) return;
        rows = rows || [];
        var counts = { all: 0 };
        reactionOrder.forEach(function (t) { counts[t] = 0; });
        rows.forEach(function (r) {
            counts.all++;
            var t = r.reaction_type || 'like';
            if (counts[t] !== undefined) counts[t]++;
        });
        var tabsHtml = '<button type="button" class="btn btn-sm btn-primary timeline-reaction-tab flex-shrink-0" data-filter="all">Tudo ' +
            (counts.all ? '<span class="badge bg-light text-primary ms-1">' + counts.all + '</span>' : '') + '</button>';
        reactionOrder.forEach(function (t) {
            if (!counts[t]) return;
            tabsHtml += '<button type="button" class="btn btn-sm btn-outline-secondary timeline-reaction-tab flex-shrink-0" data-filter="' + t + '">' +
                '<i class="' + (reactionIconClasses[t] || '') + '"></i> <span class="badge bg-secondary ms-1">' + counts[t] + '</span></button>';
        });
        tabList.className = 'd-flex flex-nowrap overflow-auto gap-1 px-2 pb-2 border-bottom timeline-reactions-tabs';
        tabList.innerHTML = tabsHtml;

        function renderList(filter) {
            var list = rows.filter(function (r) {
                if (filter === 'all') return true;
                return (r.reaction_type || 'like') === filter;
            });
            if (!list.length) {
                return '<p class="text-muted small mb-0">Ninguém nesta categoria.</p>';
            }
            var h = '<ul class="list-group list-group-flush">';
            list.forEach(function (row) {
                var rt = row.reaction_type || 'like';
                var ic = reactionIconClasses[rt] || reactionIconClasses.like;
                var uid = parseInt(row.user_id || 0, 10);
                var un = row.username ? '@' + escapeHtml(row.username) : '';
                var nameHtml = escapeHtml(row.name || '');
                if (uid > 0) {
                    nameHtml = '<a href="' + base + 'timeline-profile/' + uid + '" class="text-reset text-decoration-none timeline-reaction-user-link fw-semibold">' + escapeHtml(row.name || '') + '</a>';
                } else {
                    nameHtml = '<span class="fw-semibold">' + nameHtml + '</span>';
                }
                h += '<li class="list-group-item d-flex align-items-center gap-3 py-2 px-0 border-0 border-bottom">';
                h += '<span class="position-relative d-inline-flex timeline-react-avatar-wrap">';
                h += '<span class="rounded-circle bg-light d-flex align-items-center justify-content-center timeline-react-avatar-fallback" style="width:40px;height:40px"><i class="fas fa-user text-muted"></i></span>';
                h += '<span class="position-absolute bottom-0 end-0 rounded-circle bg-white border p-1" style="line-height:1"><i class="' + ic + '" style="font-size:0.65rem"></i></span>';
                h += '</span>';
                h += '<span class="flex-grow-1 min-w-0"><span class="d-block">' + nameHtml + '</span>';
                if (un) h += '<span class="text-muted small">' + un + '</span>';
                h += '</span></li>';
            });
            h += '</ul>';
            return h;
        }
        var currentFilter = 'all';
        panels.innerHTML = renderList(currentFilter);
        tabList.querySelectorAll('.timeline-reaction-tab').forEach(function (tab) {
            tab.onclick = function () {
                tabList.querySelectorAll('.timeline-reaction-tab').forEach(function (x) {
                    x.classList.remove('btn-primary');
                    x.classList.add('btn-outline-secondary');
                });
                tab.classList.remove('btn-outline-secondary');
                tab.classList.add('btn-primary');
                currentFilter = tab.getAttribute('data-filter') || 'all';
                panels.innerHTML = renderList(currentFilter);
            };
        });
    }

    function escapeHtml(s) {
        const d = document.createElement('div');
        d.textContent = s || '';
        return d.innerHTML;
    }

    /** Mesmo critério que {@see \App\adms\Helpers\ImageHelper::encodePathForServeFile}: não usar %2F nas barras. */
    function encodeServeFilePath(path) {
        if (!path) return '';
        return String(path).split('/').filter(function (seg) { return seg.length; }).map(function (seg) {
            return encodeURIComponent(seg);
        }).join('/');
    }

    function getUserAvatarPath(u) {
        if (!u || !u.id) return 'users/icon_user.png';
        var img = (u.image || '').trim();
        if (!img || img === 'icon_user.png') return 'users/icon_user.png';
        return 'users/' + String(u.id) + '/' + img;
    }

    function getUserAvatarUrl(u) {
        return base + 'serve-file?path=' + encodeServeFilePath(getUserAvatarPath(u));
    }

    function renderUserSuggestionHtml(u) {
        if (u && u.mention_all) {
            var username = escapeHtml(u.username || '');
            var name = escapeHtml(u.name || '');
            return '' +
                '<span class="timeline-suggestion-avatar-wrap timeline-suggestion-mention-all-wrap">' +
                '<span class="timeline-suggestion-mention-all-icon" aria-hidden="true"><i class="fas fa-users"></i></span>' +
                '</span>' +
                '<span class="timeline-suggestion-text-wrap">' +
                '<span class="fw-semibold">@' + username + '</span>' +
                '<span class="small text-muted d-block">' + name + '</span>' +
                '</span>';
        }
        if (u && u.mention_department) {
            var username = escapeHtml(u.username || '');
            var name = escapeHtml(u.name || '');
            return '' +
                '<span class="timeline-suggestion-avatar-wrap timeline-suggestion-mention-dept-wrap">' +
                '<span class="timeline-suggestion-mention-dept-icon" aria-hidden="true"><i class="fas fa-building"></i></span>' +
                '</span>' +
                '<span class="timeline-suggestion-text-wrap">' +
                '<span class="fw-semibold">@' + username + '</span>' +
                '<span class="small text-muted d-block">Departamento: ' + name + '</span>' +
                '</span>';
        }
        var username = escapeHtml(u && u.username ? u.username : '');
        var name = escapeHtml(u && u.name ? u.name : '');
        var email = escapeHtml(u && u.email ? u.email : '');
        var avatarUrl = getUserAvatarUrl(u);
        return '' +
            '<span class="timeline-suggestion-avatar-wrap">' +
            '<img src="' + avatarUrl + '" class="timeline-suggestion-avatar" alt="Avatar de @' + username + '" loading="lazy" decoding="async" fetchpriority="low">' +
            '</span>' +
            '<span class="timeline-suggestion-text-wrap">' +
            '<span class="fw-semibold">@' + username + '</span>' +
            '<span class="small text-muted d-block">' + name + (email ? ' · ' + email : '') + '</span>' +
            '</span>';
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

    function showTimelineInlineMessage(message, type) {
        var wrap = document.getElementById('timelineInlineFeedback');
        if (!wrap) {
            console.warn(message || 'Ocorreu um erro inesperado.');
            return;
        }
        var safeType = (type === 'success' || type === 'warning' || type === 'danger' || type === 'info') ? type : 'warning';
        var icon = safeType === 'success' ? 'fa-check-circle'
            : (safeType === 'danger' ? 'fa-circle-exclamation'
            : (safeType === 'info' ? 'fa-circle-info' : 'fa-triangle-exclamation'));
        wrap.innerHTML =
            '<div class="alert alert-' + safeType + ' timeline-inline-alert shadow-sm border-0 d-flex align-items-start gap-2 mb-3" role="alert">' +
                '<i class="fas ' + icon + ' mt-1" aria-hidden="true"></i>' +
                '<div class="flex-grow-1">' + escapeHtml(message || 'Ocorreu um erro inesperado.') + '</div>' +
                '<button type="button" class="btn-close ms-2" data-bs-dismiss="alert" aria-label="Fechar"></button>' +
            '</div>';
        wrap.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
    }

    function bindPostActions() {
        document.querySelectorAll('.timeline-reaction-pick-fb').forEach(function (pick) {
            pick.onclick = function (ev) {
                ev.preventDefault();
                ev.stopPropagation();
                var bundle = pick.closest('.timeline-curtir-bundle');
                var trayM = pick.closest('.timeline-reaction-tray-mobile');
                var id = pick.getAttribute('data-post-id') ||
                    (bundle && bundle.getAttribute('data-post-id')) ||
                    (trayM && trayM.getAttribute('data-post-id'));
                if (!id) return;
                var r = pick.getAttribute('data-reaction') || 'like';
                function sendLike(triesLeft) {
                    var fd = new FormData();
                    fd.append('reaction', r);
                    fd.append('csrf_token', timelineLikeCsrf);
                    return fetchJson(base + 'timeline-like/' + id, { method: 'POST', body: fd })
                    .then(function (data) {
                        if (data && data.csrf_expired && data.csrf_token && triesLeft > 0) {
                            timelineLikeCsrf = data.csrf_token;
                            return sendLike(triesLeft - 1);
                        }
                        if (data && data.csrf_token) {
                            timelineLikeCsrf = data.csrf_token;
                        }
                        if (data && data.success) {
                            updateReactionUI(id, data);
                        }
                    })
                    .catch(function (err) { console.error(err); showTimelineInlineMessage(err.message || 'Não foi possível registrar a reação.', 'danger'); });
                }
                sendLike(1);
            };
        });

        document.querySelectorAll('.timeline-curtir-main').forEach(function (btn) {
            btn.onclick = function (ev) {
                ev.preventDefault();
                ev.stopPropagation();
                var id = btn.getAttribute('data-post-id');
                if (!id) return;
                function sendLikeMain(triesLeft) {
                    var fd = new FormData();
                    fd.append('reaction', 'like');
                    fd.append('csrf_token', timelineLikeCsrf);
                    return fetchJson(base + 'timeline-like/' + id, { method: 'POST', body: fd })
                    .then(function (data) {
                        if (data && data.csrf_expired && data.csrf_token && triesLeft > 0) {
                            timelineLikeCsrf = data.csrf_token;
                            return sendLikeMain(triesLeft - 1);
                        }
                        if (data && data.csrf_token) {
                            timelineLikeCsrf = data.csrf_token;
                        }
                        if (data && data.success) {
                            updateReactionUI(id, data);
                        }
                    })
                    .catch(function (err) { console.error(err); showTimelineInlineMessage(err.message || 'Não foi possível registrar a reação.', 'danger'); });
                }
                sendLikeMain(1);
            };
        });

        document.querySelectorAll('.timeline-reaction-fb-more').forEach(function (chev) {
            chev.onclick = function (ev) {
                ev.preventDefault();
                ev.stopPropagation();
                var id = chev.getAttribute('data-post-id');
                var tray = document.querySelector('.timeline-reaction-tray-mobile[data-post-id="' + id + '"]');
                if (tray) tray.classList.toggle('is-open');
            };
        });

        document.addEventListener('click', function (ev) {
            if (ev.target.closest && (ev.target.closest('.timeline-reaction-tray-mobile') || ev.target.closest('.timeline-reaction-fb-more') || ev.target.closest('.timeline-comment-reaction-tray'))) {
                return;
            }
            document.querySelectorAll('.timeline-reaction-tray-mobile.is-open').forEach(function (t) {
                t.classList.remove('is-open');
            });
        });

        document.querySelectorAll('.timeline-reactions-summary-hit').forEach(function (btn) {
            btn.onclick = function () {
                var id = btn.getAttribute('data-post-id');
                var panels = document.getElementById('timelineReactionsTabPanels');
                if (panels) panels.innerHTML = '<p class="text-muted small mb-0 px-2">Carregando…</p>';
                fetchJson(base + 'timeline-post-reactions/' + id, {})
                    .then(function (data) {
                        if (!data || !data.success) return;
                        buildReactionsModal(data.reactions || []);
                        var mEl = document.getElementById('modalTimelineReactions');
                        if (mEl) {
                            var m = bootstrap.Modal.getInstance(mEl) || new bootstrap.Modal(mEl);
                            m.show();
                        }
                    })
                    .catch(function (err) {
                        console.error(err);
                        showTimelineInlineMessage(err.message || 'Não foi possível carregar as reações.', 'warning');
                    });
            };
        });

        function b64ToUtf8(b64) {
            try {
                var bin = atob(b64);
                if (typeof TextDecoder !== 'undefined') {
                    var bytes = new Uint8Array(bin.length);
                    for (var i = 0; i < bin.length; i++) bytes[i] = bin.charCodeAt(i);
                    return new TextDecoder('utf-8').decode(bytes);
                }
                return decodeURIComponent(escape(bin));
            } catch (e) {
                return '';
            }
        }

        function openShareComposer(postId, author, contentB64) {
            var taComposer = document.getElementById('timelineComposerText');
            var inputShared = document.getElementById('timelineComposerSharedFromPostId');
            var box = document.getElementById('timelineComposerSharePreview');
            var elAuthor = document.getElementById('timelineComposerShareAuthor');
            var elExcerpt = document.getElementById('timelineComposerShareExcerpt');
            if (!inputShared || !box || !elAuthor || !elExcerpt || !postId) return;
            var excerptRaw = (contentB64 ? b64ToUtf8(contentB64) : '').trim();
            if (!excerptRaw) excerptRaw = 'Sem texto';
            if (excerptRaw.length > 180) excerptRaw = excerptRaw.slice(0, 180) + '…';
            inputShared.value = String(postId);
            elAuthor.textContent = author || 'Usuário';
            elExcerpt.textContent = excerptRaw;
            box.classList.remove('d-none');
            if (taComposer) {
                taComposer.focus();
                taComposer.scrollIntoView({ behavior: 'smooth', block: 'center' });
            }
        }

        function clearShareComposer() {
            var inputShared = document.getElementById('timelineComposerSharedFromPostId');
            var box = document.getElementById('timelineComposerSharePreview');
            if (inputShared) inputShared.value = '';
            if (box) box.classList.add('d-none');
        }

        var btnClearShare = document.getElementById('timelineComposerShareClear');
        if (btnClearShare) {
            btnClearShare.onclick = function () { clearShareComposer(); };
        }

        document.querySelectorAll('.btn-timeline-share').forEach(function (btn) {
            btn.onclick = function () {
                var postId = btn.getAttribute('data-post-id') || '';
                var author = btn.getAttribute('data-post-author') || '';
                var contentB64 = btn.getAttribute('data-post-content-b64') || '';
                openShareComposer(postId, author, contentB64);
            };
        });

        document.querySelectorAll('.btn-timeline-edit-post').forEach(function (btn) {
            btn.onclick = function () {
                var id = btn.getAttribute('data-post-id');
                var b64 = btn.getAttribute('data-post-content-b64') || '';
                var content = b64 ? b64ToUtf8(b64) : '';
                document.getElementById('timelineEditPostId').value = id;
                document.getElementById('timelineEditPostContent').value = content;
                var mEl = document.getElementById('modalTimelineEditPost');
                if (mEl) {
                    var m = bootstrap.Modal.getInstance(mEl) || new bootstrap.Modal(mEl);
                    m.show();
                }
            };
        });

        var btnSaveEdit = document.getElementById('btnTimelineEditPostSave');
        if (btnSaveEdit) {
            btnSaveEdit.onclick = function () {
                var id = document.getElementById('timelineEditPostId').value;
                var content = document.getElementById('timelineEditPostContent').value || '';
                if (!id) {
                    return;
                }
                function sendEdit(triesLeft) {
                    var form = new FormData();
                    form.append('post_id', id);
                    form.append('content', content);
                    form.append('csrf_token', timelineEditCsrf);
                    return fetchJson(base + 'update-timeline-post', { method: 'POST', body: form })
                    .then(function (data) {
                        if (data && data.csrf_expired && data.csrf_token && triesLeft > 0) {
                            timelineEditCsrf = data.csrf_token;
                            return sendEdit(triesLeft - 1);
                        }
                        if (data && data.success) {
                            window.location.href = base + 'timeline';
                        } else if (data && data.message) {
                            showTimelineInlineMessage(data.message, 'warning');
                        }
                    })
                    .catch(function (err) { console.error(err); showTimelineInlineMessage(err.message || 'Não foi possível salvar.', 'danger'); });
                }
                sendEdit(1);
            };
        }

        document.querySelectorAll('.btn-timeline-delete-post').forEach(function (btn) {
            btn.onclick = function () {
                var id = btn.getAttribute('data-post-id');
                var hid = document.getElementById('timelineDeletePostId');
                if (hid) hid.value = id || '';
                var mEl = document.getElementById('modalTimelineDeletePost');
                if (mEl) {
                    var m = bootstrap.Modal.getInstance(mEl) || new bootstrap.Modal(mEl);
                    m.show();
                }
            };
        });

        var btnConfirmDelete = document.getElementById('btnTimelineDeletePostConfirm');
        if (btnConfirmDelete) {
            btnConfirmDelete.onclick = function () {
                var id = document.getElementById('timelineDeletePostId').value;
                if (!id) return;

                var fd = new FormData();
                fd.append('post_id', id);
                fd.append('csrf_token', timelineDeleteCsrf);

                btnConfirmDelete.disabled = true;
                function sendDelete(triesLeft) {
                    var form = new FormData();
                    form.append('post_id', id);
                    form.append('csrf_token', timelineDeleteCsrf);
                    return fetchJson(base + 'delete-timeline-post', { method: 'POST', body: form })
                    .then(function (data) {
                        if (data && data.csrf_expired && data.csrf_token && triesLeft > 0) {
                            timelineDeleteCsrf = data.csrf_token;
                            return sendDelete(triesLeft - 1);
                        }
                        if (data && data.success) {
                            window.location.href = base.replace(/\/?$/, '/') + 'timeline';
                        } else if (data && data.message) {
                            showTimelineInlineMessage(data.message, 'warning');
                        } else {
                            showTimelineInlineMessage('Não foi possível deletar a publicação.', 'danger');
                        }
                    })
                    .catch(function (err) {
                        console.error(err);
                        showTimelineInlineMessage(err.message || 'Não foi possível deletar.', 'danger');
                    });
                }
                sendDelete(1)
                    .finally(function () {
                        btnConfirmDelete.disabled = false;
                        var m = bootstrap.Modal.getInstance(document.getElementById('modalTimelineDeletePost'));
                        if (m) m.hide();
                    });
            };
        }

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
                        .catch(function (err) { console.error(err); showTimelineInlineMessage(err.message || 'Não foi possível carregar comentários.', 'danger'); });
                }
            };
        });

        document.querySelectorAll('.btn-timeline-comment-send').forEach(function (btn) {
            btn.onclick = function () {
                const id = btn.getAttribute('data-post-id');
                const inp = document.querySelector('.timeline-comment-input[data-post-id="' + id + '"]');
                if (!inp || !inp.value.trim()) return;
                function sendComment(triesLeft) {
                    const form = new FormData();
                    form.append('post_id', id);
                    form.append('content', inp.value.trim());
                    form.append('csrf_token', timelineCommentCsrf);
                    return fetchJson(base + 'timeline-comment/' + id, { method: 'POST', body: form })
                    .then(function (data) {
                        if (data && data.csrf_expired && data.csrf_token && triesLeft > 0) {
                            timelineCommentCsrf = data.csrf_token;
                            return sendComment(triesLeft - 1);
                        }
                        if (data && data.success) {
                            inp.value = '';
                            renderComments(id, data.comments || []);
                            const box = document.getElementById('comments-' + id);
                            if (box) { box.classList.remove('d-none'); box.setAttribute('data-loaded', '1'); }
                        } else if (data && data.message) {
                            showTimelineInlineMessage(data.message, 'warning');
                        }
                    })
                    .catch(function (err) { console.error(err); showTimelineInlineMessage(err.message || 'Não foi possível enviar o comentário.', 'danger'); });
                }
                sendComment(1);
            };
        });

        document.querySelectorAll('.timeline-poll-option-btn').forEach(function (btn) {
            btn.onclick = function () {
                if (btn.disabled) return;
                var postId = btn.getAttribute('data-post-id');
                var optionId = btn.getAttribute('data-option-id');
                if (!postId || !optionId) return;
                function sendVote(triesLeft) {
                    const form = new FormData();
                    form.append('post_id', postId);
                    form.append('poll_option_id', optionId);
                    form.append('csrf_token', timelineCommentCsrf);
                    return fetchJson(base + 'timeline-comment/' + postId, { method: 'POST', body: form })
                        .then(function (data) {
                            if (data && data.csrf_expired && data.csrf_token && triesLeft > 0) {
                                timelineCommentCsrf = data.csrf_token;
                                return sendVote(triesLeft - 1);
                            }
                            if (data && data.csrf_token) {
                                timelineCommentCsrf = data.csrf_token;
                            }
                            if (data && data.success) {
                                if (data.poll) {
                                    updatePollUI(postId, data.poll);
                                }
                            } else if (data && data.message) {
                                showTimelineInlineMessage(data.message, 'warning');
                            }
                        })
                        .catch(function (err) {
                            console.error(err);
                            showTimelineInlineMessage(err.message || 'Não foi possível registrar o voto.', 'danger');
                        });
                }
                sendVote(1);
            };
        });

        document.querySelectorAll('.btn-poll-votes-open').forEach(function (btn) {
            btn.onclick = function () {
                var postId = String(btn.getAttribute('data-post-id') || '');
                if (!postId) return;
                currentPollVotesPostId = postId;
                var poll = pollStateMap[postId] || null;
                var body = document.getElementById('timelinePollVotesBody');
                var search = document.getElementById('timelinePollVotesSearch');
                if (search) search.value = '';
                if (body) {
                    body.innerHTML = buildPollVotesModalHtml(poll, '');
                }
                var mEl = document.getElementById('modalTimelinePollVotes');
                if (mEl) {
                    var m = bootstrap.Modal.getInstance(mEl) || new bootstrap.Modal(mEl);
                    m.show();
                }
            };
        });

        var pollVotesSearch = document.getElementById('timelinePollVotesSearch');
        if (pollVotesSearch) {
            pollVotesSearch.addEventListener('input', function () {
                if (!currentPollVotesPostId) return;
                var poll = pollStateMap[currentPollVotesPostId] || null;
                var body = document.getElementById('timelinePollVotesBody');
                if (!body) return;
                body.innerHTML = buildPollVotesModalHtml(poll, pollVotesSearch.value || '');
            });
        }

        document.addEventListener('click', function (ev) {
            var openBtn = ev.target && ev.target.closest ? ev.target.closest('.timeline-comment-reactions-open') : null;
            if (!openBtn) return;
            ev.preventDefault();
            var cid = openBtn.getAttribute('data-comment-id');
            if (!cid) return;
            var panels = document.getElementById('timelineReactionsTabPanels');
            if (panels) panels.innerHTML = '<p class="text-muted small mb-0 px-2">Carregando…</p>';
            fetchJson(base + 'timeline-comment-like/' + cid, {})
                .then(function (data) {
                    if (!data || !data.success) return;
                    buildReactionsModal(data.reactions || []);
                    var mEl = document.getElementById('modalTimelineReactions');
                    if (mEl) {
                        var m = bootstrap.Modal.getInstance(mEl) || new bootstrap.Modal(mEl);
                        m.show();
                    }
                })
                .catch(function (err) {
                    console.error(err);
                    showTimelineInlineMessage(err.message || 'Não foi possível carregar as reações do comentário.', 'warning');
                });
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

    function renderCommentReactionSummaryHtml(summary) {
        summary = summary || {};
        var total = 0;
        Object.keys(summary).forEach(function (k) {
            total += parseInt(summary[k] || 0, 10);
        });
        if (total <= 0) {
            return '<span class="text-muted">0</span>';
        }
        return '<span class="text-body">' + total + '</span>';
    }

    function updateCommentReactionUI(commentId, data) {
        var summary = (data && data.summary) ? data.summary : {};
        var reaction = (data && data.reaction) ? data.reaction : null;
        document.querySelectorAll('.timeline-comment-like-count[data-comment-id="' + commentId + '"]').forEach(function (el) {
            el.innerHTML = renderCommentReactionSummaryHtml(summary);
        });
        document.querySelectorAll('.timeline-comment-react-main[data-comment-id="' + commentId + '"]').forEach(function (btn) {
            var ic = btn.querySelector('i');
            var lb = btn.querySelector('.timeline-comment-react-label');
            if (reaction && reactionIconClasses[reaction]) {
                if (ic) ic.className = reactionIconClasses[reaction];
                if (lb) lb.textContent = reactionLabels[reaction] || 'Curtir';
            } else {
                if (ic) ic.className = 'far fa-thumbs-up';
                if (lb) lb.textContent = 'Curtir';
            }
        });
    }

    function sendCommentReactionRequest(cid, reaction, triesLeft) {
        triesLeft = typeof triesLeft === 'number' ? triesLeft : 1;
        var form = new FormData();
        form.append('comment_id', cid);
        form.append('reaction', reaction || 'like');
        form.append('csrf_token', timelineLikeCsrf);
        return fetchJson(base + 'timeline-comment-like/' + cid, { method: 'POST', body: form })
            .then(function (data) {
                if (data && data.csrf_expired && data.csrf_token && triesLeft > 0) {
                    timelineLikeCsrf = data.csrf_token;
                    return sendCommentReactionRequest(cid, reaction, triesLeft - 1);
                }
                if (data && data.csrf_token) {
                    timelineLikeCsrf = data.csrf_token;
                }
                if (data && data.success) {
                    updateCommentReactionUI(cid, data);
                    var tray = document.querySelector('.timeline-comment-reaction-tray[data-comment-id="' + cid + '"]');
                    if (tray) tray.classList.add('d-none');
                } else if (data && data.message) {
                    showTimelineInlineMessage(data.message, 'warning');
                }
            })
            .catch(function (err) {
                console.error(err);
                showTimelineInlineMessage(err.message || 'Não foi possível registrar a reação no comentário.', 'danger');
            });
    }

    function bindCommentReactionControls(div) {
        if (!div) return;
        var mainBtn = div.querySelector('.timeline-comment-react-main');
        if (mainBtn) {
            mainBtn.onclick = function (ev) {
                ev.preventDefault();
                ev.stopPropagation();
                var tray = div.querySelector('.timeline-comment-reaction-tray');
                if (tray) tray.classList.toggle('d-none');
            };
        }
        div.querySelectorAll('.timeline-comment-reaction-pick').forEach(function (pick) {
            pick.onclick = function (ev) {
                ev.preventDefault();
                ev.stopPropagation();
                var cid = pick.getAttribute('data-comment-id');
                var reaction = pick.getAttribute('data-reaction') || 'like';
                if (!cid) return;
                sendCommentReactionRequest(cid, reaction, 1);
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
            const cid = parseInt(c.id || 0, 10);
            if (cid > 0) {
                div.id = 'timeline-comment-' + cid;
            }
            const body = c.content_html ? c.content_html : escapeHtml(c.content || '');
            const myReaction = c.my_reaction || null;
            const reactIcon = myReaction && reactionIconClasses[myReaction] ? reactionIconClasses[myReaction] : 'far fa-thumbs-up';
            const reactLabel = myReaction && reactionLabels[myReaction] ? reactionLabels[myReaction] : 'Curtir';
            const countHtml = renderCommentReactionSummaryHtml(c.reaction_summary || {});
            const reactionsHtml = canLike ? (
                '<div class="timeline-comment-actions mt-1" data-comment-id="' + cid + '">'
                + '  <button type="button" class="btn btn-link btn-sm p-0 text-decoration-none timeline-comment-react-main" data-comment-id="' + cid + '">'
                + '    <i class="' + reactIcon + '"></i> <span class="timeline-comment-react-label">' + escapeHtml(reactLabel) + '</span>'
                + '  </button>'
                + '  <span class="mx-2 text-muted">·</span>'
                + '  <button type="button" class="btn btn-link btn-sm p-0 text-decoration-none timeline-comment-reactions-open" data-comment-id="' + cid + '">'
                + '    <span class="timeline-comment-like-count" data-comment-id="' + cid + '">' + countHtml + '</span> reações'
                + '  </button>'
                + '  <span class="timeline-comment-reaction-tray d-none" data-comment-id="' + cid + '">'
                +      reactionOrder.map(function (t) {
                            var lab = reactionLabels[t] || 'Curtir';
                            var ic = reactionIconClasses[t] || reactionIconClasses.like;
                            return '<button type="button" class="btn btn-sm rounded-circle timeline-comment-reaction-pick border bg-white shadow-sm" data-comment-id="' + cid + '" data-reaction="' + t + '" title="' + escapeHtml(lab) + '"><i class="' + ic + '"></i></button>';
                        }).join('')
                + '  </span>'
                + '</div>'
            ) : '';
            var authorUid = parseInt(c.user_id || 0, 10);
            var authorLine = '<strong>' + escapeHtml(c.author_name || '') + '</strong>';
            if (authorUid > 0) {
                authorLine = '<strong><a href="' + base + 'timeline-profile/' + authorUid + '" class="text-reset text-decoration-none timeline-comment-author-link">' + escapeHtml(c.author_name || '') + '</a></strong>';
            }
            div.innerHTML = ''
                + authorLine + ' · ' + escapeHtml(c.created_at || '') + '<br>' + body
                + reactionsHtml;
            bindCommentReactionControls(div);
            wrap.appendChild(div);
        });
    }

    function formatDateTimeBr(input) {
        if (!input) return '';
        var iso = String(input).replace(' ', 'T');
        var d = new Date(iso);
        if (Number.isNaN(d.getTime())) return String(input);
        var dd = String(d.getDate()).padStart(2, '0');
        var mm = String(d.getMonth() + 1).padStart(2, '0');
        var yyyy = d.getFullYear();
        var hh = String(d.getHours()).padStart(2, '0');
        var min = String(d.getMinutes()).padStart(2, '0');
        return dd + '/' + mm + '/' + yyyy + ' ' + hh + ':' + min;
    }

    function updatePollUI(postId, poll) {
        if (!poll || !Array.isArray(poll.options)) return;
        pollStateMap[String(postId)] = poll;
        var card = document.querySelector('.timeline-poll-card[data-post-id="' + postId + '"]');
        if (!card) return;

        var optionsWrap = card.querySelector('.timeline-poll-options[data-post-id="' + postId + '"]');
        if (optionsWrap) {
            optionsWrap.querySelectorAll('.timeline-poll-option-btn').forEach(function (btn) {
                var oid = parseInt(btn.getAttribute('data-option-id') || '0', 10);
                var opt = poll.options.find(function (o) { return parseInt(o.id || 0, 10) === oid; });
                if (!opt) return;

                var votes = parseInt(opt.votes || 0, 10);
                var pct = Math.max(0, Math.min(100, parseInt(opt.percent || 0, 10)));
                var isMine = parseInt(poll.user_vote_option_id || 0, 10) === oid;
                var isOpen = (poll.status || '') === 'open';

                btn.classList.toggle('active', isMine);
                btn.disabled = !isOpen;

                var meta = btn.querySelector('.timeline-poll-option-meta');
                if (meta) {
                    meta.textContent = votes + ' voto' + (votes !== 1 ? 's' : '') + ' · ' + pct + '%';
                }
                var bar = btn.querySelector('.timeline-poll-option-bar');
                if (bar) {
                    bar.style.width = pct + '%';
                }
                btn.classList.remove('vote-updated');
                void btn.offsetWidth;
                btn.classList.add('vote-updated');
            });
        }

        var totalEl = card.querySelector('[data-poll-total="' + postId + '"]');
        if (totalEl) {
            totalEl.textContent = String(parseInt(poll.total_votes || 0, 10));
        }

        var statusEl = card.querySelector('[data-poll-status-label="' + postId + '"]');
        if (statusEl) {
            if ((poll.status || '') === 'scheduled') {
                statusEl.textContent = 'Inicia em ' + formatDateTimeBr(poll.starts_at || '');
            } else if ((poll.status || '') === 'closed') {
                statusEl.textContent = 'Encerrada em ' + formatDateTimeBr(poll.ends_at || '');
            } else {
                statusEl.textContent = 'Encerra em ' + formatDateTimeBr(poll.ends_at || '');
            }
        }
    }

    function buildPollVotesModalHtml(poll, term) {
        if (!poll || !Array.isArray(poll.options)) {
            return '<p class="text-muted mb-0">Nenhum dado de votação disponível.</p>';
        }
        term = (term || '').trim().toLowerCase();
        var html = '';
        var hasAny = false;
        poll.options.forEach(function (opt) {
            var voters = Array.isArray(opt.voters) ? opt.voters : [];
            if (term) {
                voters = voters.filter(function (v) {
                    var n = ((v && v.name) ? String(v.name) : '').toLowerCase();
                    var u = ((v && v.username) ? String(v.username) : '').toLowerCase();
                    return n.indexOf(term) !== -1 || u.indexOf(term) !== -1;
                });
            }
            var title = escapeHtml(opt.text || 'Opção');
            html += '<div class="mb-3">';
            html += '<div class="fw-semibold mb-1">' + title + ' <span class="text-muted">(' + (parseInt(opt.votes || 0, 10)) + ')</span></div>';
            if (!voters.length) {
                html += '<div class="text-muted">' + (term ? 'Nenhum voto para este filtro.' : 'Sem votos.') + '</div>';
            } else {
                hasAny = true;
                html += '<ul class="mb-0 ps-3">';
                voters.forEach(function (v) {
                    var vid = parseInt(v.user_id || 0, 10);
                    var rawName = (v && v.name) ? v.name : 'Usuário';
                    var nameHtml = escapeHtml(rawName);
                    if (vid > 0) {
                        nameHtml = '<a href="' + base + 'timeline-profile/' + vid + '" class="text-reset text-decoration-none timeline-poll-voter-link">' + escapeHtml(rawName) + '</a>';
                    }
                    var uname = (v && v.username) ? ' <span class="text-muted">(@' + escapeHtml(v.username) + ')</span>' : '';
                    html += '<li>' + nameHtml + uname + '</li>';
                });
                html += '</ul>';
            }
            html += '</div>';
        });
        if (term && !hasAny) {
            return '<p class="text-muted mb-0">Nenhum votante encontrado para "<strong>' + escapeHtml(term) + '</strong>".</p>';
        }
        return html;
    }

    const btnDen = document.getElementById('btnEnviarDenuncia');
    if (btnDen) {
        btnDen.onclick = function () {
            if (!canReport) return;
            const id = document.getElementById('denunciaPostId').value;
            function sendReport(triesLeft) {
                const form = new FormData();
                form.append('post_id', id);
                form.append('reason', document.getElementById('denunciaMotivo').value);
                form.append('details', document.getElementById('denunciaDetalhes').value);
                form.append('csrf_token', timelineReportCsrf);
                return fetchJson(base + 'timeline-report', { method: 'POST', body: form })
                .then(function (data) {
                    if (data && data.csrf_expired && data.csrf_token && triesLeft > 0) {
                        timelineReportCsrf = data.csrf_token;
                        return sendReport(triesLeft - 1);
                    }
                    if (data && data.csrf_token) {
                        timelineReportCsrf = data.csrf_token;
                    }
                    showTimelineInlineMessage(data && data.message ? data.message : 'Enviado.', 'success');
                    bootstrap.Modal.getInstance(document.getElementById('modalDenunciaTimeline')).hide();
                })
                .catch(function (err) { console.error(err); showTimelineInlineMessage(err.message || 'Não foi possível enviar a denúncia.', 'danger'); });
            }
            sendReport(1);
        };
    }

    bindPostActions();

    function initPostReadMore() {
        document.querySelectorAll('.timeline-post-body').forEach(function (wrap) {
            var textEl = wrap.querySelector('.timeline-post-text');
            var btn = wrap.querySelector('.timeline-post-readmore');
            if (!textEl || !btn) return;

            btn.classList.add('d-none');
            btn.setAttribute('aria-expanded', 'false');
            btn.textContent = 'Ler mais';

            /* Medir sem colapso: se for curto, não aplica collapsed (evita degradê ::after que deixa o texto acinzentado) */
            textEl.classList.remove('timeline-post-text-collapsed');
            var fullH = textEl.scrollHeight;
            var maxCollapsedPx = window.matchMedia('(min-width: 768px)').matches ? 116 : 74;
            if (fullH <= maxCollapsedPx + 4) {
                return;
            }

            textEl.classList.add('timeline-post-text-collapsed');
            btn.classList.remove('d-none');
            btn.onclick = function () {
                var expanded = btn.getAttribute('aria-expanded') === 'true';
                if (expanded) {
                    textEl.classList.add('timeline-post-text-collapsed');
                    btn.setAttribute('aria-expanded', 'false');
                    btn.textContent = 'Ler mais';
                } else {
                    textEl.classList.remove('timeline-post-text-collapsed');
                    btn.setAttribute('aria-expanded', 'true');
                    btn.textContent = 'Ler menos';
                }
            };
        });
    }
    initPostReadMore();

    // Visualizador de fotos (ao clicar nas thumbs)
    var timelineViewerImages = [];
    var timelineViewerIndex = 0;

    function getMediaViewerEls() {
        return {
            modalEl: document.getElementById('modalTimelineMediaCarousel'),
            imgEl: document.getElementById('timelineMediaViewerImg'),
            prevEl: document.getElementById('timelineMediaViewerPrev'),
            nextEl: document.getElementById('timelineMediaViewerNext'),
            countEl: document.getElementById('timelineMediaViewerCount'),
        };
    }

    function timelineShowViewerAt(idx) {
        var els = getMediaViewerEls();
        if (!els.modalEl || !els.imgEl) return;
        if (!timelineViewerImages || !timelineViewerImages.length) return;

        var max = timelineViewerImages.length;
        if (idx < 0) idx = max - 1;
        if (idx >= max) idx = 0;
        timelineViewerIndex = idx;

        var p = timelineViewerImages[timelineViewerIndex];
        els.imgEl.src = base + 'serve-file?path=' + encodeServeFilePath(p);

        if (els.countEl) {
            els.countEl.textContent = (timelineViewerIndex + 1) + ' / ' + max;
        }
    }

    document.addEventListener('click', function (ev) {
        var t = ev.target && ev.target.closest ? ev.target.closest('.timeline-media-clickable') : null;
        if (!t) return;
        ev.preventDefault();
        ev.stopPropagation();

        var imgsJson = t.getAttribute('data-images') || '[]';
        var imgs = [];
        try { imgs = JSON.parse(imgsJson); }
        catch (e) {
            try { imgs = JSON.parse((imgsJson || '').replace(/&quot;/g, '"')); } catch (e2) { imgs = []; }
        }
        if (!imgs || !imgs.length) return;

        var di = parseInt(t.getAttribute('data-index') || '0', 10);
        if (isNaN(di)) di = 0;

        timelineViewerImages = imgs;
        timelineShowViewerAt(di);

        var els = getMediaViewerEls();
        if (!els.modalEl) return;
        var modal = bootstrap.Modal.getOrCreateInstance(els.modalEl);
        modal.show();
    }, true);

    function initialsFromName(name) {
        var parts = String(name || '').trim().split(/\s+/).filter(Boolean);
        var out = '';
        for (var i = 0; i < parts.length && out.length < 2; i++) {
            out += parts[i].charAt(0);
        }
        return (out || 'U').toUpperCase();
    }

    function openTimelineAvatarZoom(btn) {
        var modalEl = document.getElementById('modalTimelineAvatarZoom');
        var imgEl = document.getElementById('timelineAvatarZoomImg');
        var initialsEl = document.getElementById('timelineAvatarZoomInitials');
        var titleEl = document.getElementById('modalTimelineAvatarZoomLabel');
        if (!modalEl || !imgEl || !initialsEl || typeof bootstrap === 'undefined') {
            return;
        }
        var name = btn.getAttribute('data-avatar-name') || 'Usuário';
        var src = btn.getAttribute('data-avatar-src') || '';
        if (titleEl) {
            titleEl.textContent = name;
        }
        if (src) {
            imgEl.src = src;
            imgEl.alt = name;
            imgEl.classList.remove('d-none');
            initialsEl.classList.add('d-none');
            initialsEl.classList.remove('d-inline-flex');
        } else {
            imgEl.classList.add('d-none');
            imgEl.removeAttribute('src');
            initialsEl.textContent = initialsFromName(name);
            initialsEl.classList.remove('d-none');
            initialsEl.classList.add('d-inline-flex');
        }
        bootstrap.Modal.getOrCreateInstance(modalEl).show();
    }

    document.addEventListener('click', function (ev) {
        var btn = ev.target && ev.target.closest ? ev.target.closest('.timeline-avatar-zoom-btn') : null;
        if (!btn) return;
        ev.preventDefault();
        ev.stopPropagation();
        openTimelineAvatarZoom(btn);
    }, true);

    // Bind botões prev/next sempre (re-busca elementos se necessário)
    document.addEventListener('click', function (e) {
        if (e.target && e.target.closest && e.target.closest('#timelineMediaViewerPrev')) {
            e.preventDefault();
            timelineShowViewerAt(timelineViewerIndex - 1);
        }
        if (e.target && e.target.closest && e.target.closest('#timelineMediaViewerNext')) {
            e.preventDefault();
            timelineShowViewerAt(timelineViewerIndex + 1);
        }
    }, true);

    document.addEventListener('hidden.bs.modal', function (e) {
        if (!e || !e.target || e.target.id !== 'modalTimelineMediaCarousel') return;
        var els = getMediaViewerEls();
        timelineViewerImages = [];
        timelineViewerIndex = 0;
        if (els.imgEl) els.imgEl.src = '';
        if (els.countEl) els.countEl.textContent = '';
    }, true);
    document.addEventListener('hidden.bs.modal', function (e) {
        if (!e || !e.target || e.target.id !== 'modalTimelinePollVotes') return;
        currentPollVotesPostId = '';
        var search = document.getElementById('timelinePollVotesSearch');
        if (search) search.value = '';
    }, true);

    const imgIn = document.getElementById('timelineFileImage');
    const vidIn = document.getElementById('timelineFileVideo');
    var timelinePreviewObjectUrls = [];
    var timelineSelectedImageFiles = [];
    var timelineSelectedVideoFile = null;

    function getTimelineComposerVideoFile() {
        if (timelineSelectedVideoFile) {
            return timelineSelectedVideoFile;
        }
        if (vidIn && vidIn.files && vidIn.files.length) {
            return vidIn.files[0];
        }
        return null;
    }

    function setTimelineComposerVideoFile(file) {
        timelineSelectedVideoFile = file || null;
        if (!vidIn) {
            return;
        }
        if (!file) {
            vidIn.value = '';
            return;
        }
        try {
            var dt = new DataTransfer();
            dt.items.add(file);
            vidIn.files = dt.files;
        } catch (e) {
            // Mantém referência em timelineSelectedVideoFile para o FormData manual.
        }
    }

    function getTimelineComposerImageFiles() {
        if (timelineSelectedImageFiles && timelineSelectedImageFiles.length) {
            return timelineSelectedImageFiles.slice(0, 6);
        }
        if (imgIn && imgIn.files && imgIn.files.length) {
            return Array.from(imgIn.files).slice(0, 6);
        }
        return [];
    }

    function setTimelinePreviewImageSrc(imgEl, file) {
        if (!imgEl || !file) {
            return;
        }
        if (window.URL && typeof URL.createObjectURL === 'function') {
            var u = URL.createObjectURL(file);
            timelinePreviewObjectUrls.push(u);
            imgEl.src = u;
            imgEl.onerror = function () {
                imgEl.onerror = null;
                try { URL.revokeObjectURL(u); } catch (e) { /* ignore */ }
                var idx = timelinePreviewObjectUrls.indexOf(u);
                if (idx >= 0) {
                    timelinePreviewObjectUrls.splice(idx, 1);
                }
                var reader = new FileReader();
                reader.onload = function (ev) {
                    if (ev.target && ev.target.result) {
                        imgEl.src = ev.target.result;
                    }
                };
                reader.readAsDataURL(file);
            };
            return;
        }
        var reader = new FileReader();
        reader.onload = function (ev) {
            if (ev.target && ev.target.result) {
                imgEl.src = ev.target.result;
            }
        };
        reader.readAsDataURL(file);
    }

    /** FormData(form) pode omitir campos quando arquivos foram atribuídos via DataTransfer. */
    function buildTimelineComposerFormData(form, csrfInput) {
        var fd = new FormData();
        var csrfVal = csrfInput ? String(csrfInput.value || '') : '';
        if (csrfVal) {
            fd.append('csrf_token', csrfVal);
        }
        Array.from(form.elements).forEach(function (el) {
            if (!el || !el.name || el.disabled) {
                return;
            }
            if (el.type === 'file') {
                return;
            }
            if (el.type === 'checkbox' || el.type === 'radio') {
                if (!el.checked) {
                    return;
                }
            }
            if (el.tagName === 'SELECT' && el.multiple) {
                Array.from(el.selectedOptions || []).forEach(function (opt) {
                    fd.append(el.name, opt.value);
                });
                return;
            }
            if (el.name === 'poll_options[]') {
                if (String(el.value || '').trim() !== '') {
                    fd.append('poll_options[]', el.value);
                }
                return;
            }
            if (el.name === 'csrf_token') {
                return;
            }
            fd.append(el.name, el.value);
        });
        getTimelineComposerImageFiles().forEach(function (file) {
            fd.append('images[]', file, file.name || 'photo.jpg');
        });
        var videoFile = getTimelineComposerVideoFile();
        if (videoFile) {
            fd.append('video', videoFile, videoFile.name || 'video.webm');
        }
        return fd;
    }

    function getTimelineComposerCsrfToken(csrfInput) {
        return csrfInput ? String(csrfInput.value || '').trim() : '';
    }

    function buildTimelineComposerRequestHeaders(csrfInput) {
        var headers = {
            'X-Requested-With': 'XMLHttpRequest',
            'Accept': 'application/json, text/html;q=0.9, */*;q=0.8'
        };
        var csrfVal = getTimelineComposerCsrfToken(csrfInput);
        if (csrfVal) {
            headers['X-CSRF-Token'] = csrfVal;
        }
        return headers;
    }

    function timelineComposerRevokePreviewUrls() {
        if (timelinePreviewObjectUrls && timelinePreviewObjectUrls.length) {
            timelinePreviewObjectUrls.forEach(function (u) {
                try { URL.revokeObjectURL(u); } catch (e) { /* ignore */ }
            });
        }
        timelinePreviewObjectUrls = [];
    }

    function timelineComposerSetMediaMode(hasMedia) {
        var card = document.getElementById('timelineComposerCard');
        var head = document.getElementById('timelineComposerCreateHead');
        var block = document.getElementById('timelineComposerMediaBlock');
        var opts = document.querySelector('#timelineComposerForm .timeline-composer-options');
        var taEl = document.getElementById('timelineComposerText');
        if (!card || !head || !block) {
            return;
        }
        if (hasMedia) {
            card.classList.add('timeline-composer-fb--with-media');
            head.classList.remove('d-none');
            block.classList.remove('d-none');
            if (opts) opts.classList.add('d-none');
            if (taEl) taEl.classList.add('timeline-composer-pill--expanded');
        } else {
            card.classList.remove('timeline-composer-fb--with-media');
            head.classList.add('d-none');
            block.classList.add('d-none');
            if (opts) opts.classList.remove('d-none');
            if (taEl) taEl.classList.remove('timeline-composer-pill--expanded');
        }
    }

    function timelineComposerRefreshMediaPreview() {
        var previewGrid = document.getElementById('timelineComposerPreviewImagesGrid');
        var previewVid = document.getElementById('timelineComposerPreviewVideo');
        var imageFiles = getTimelineComposerImageFiles();
        var videoFile = getTimelineComposerVideoFile();
        var hasVideo = !!videoFile;
        var hasImages = !hasVideo && imageFiles.length > 0;

        if (previewVid) {
            previewVid.removeAttribute('src');
            previewVid.classList.add('d-none');
        }
        if (previewGrid) {
            previewGrid.innerHTML = '';
            previewGrid.classList.add('d-none');
        }
        timelineComposerRevokePreviewUrls();

        if (!hasVideo && !hasImages) {
            timelineSelectedImageFiles = [];
            timelineComposerSetMediaMode(false);
            return;
        }

        if (hasVideo) {
            if (previewGrid) {
                previewGrid.innerHTML = '';
                previewGrid.classList.add('d-none');
            }
            if (previewVid && videoFile) {
                var vUrl = URL.createObjectURL(videoFile);
                timelinePreviewObjectUrls.push(vUrl);
                previewVid.src = vUrl;
                previewVid.classList.remove('d-none');
            }
            timelineSelectedImageFiles = [];
            timelineComposerSetMediaMode(true);
            return;
        }

        // Fotos (até 6)
        var files = imageFiles;
        timelineSelectedImageFiles = files;

        if (previewGrid) {
            files.forEach(function (f) {
                var im = document.createElement('img');
                im.className = 'timeline-composer-preview-thumb';
                im.alt = 'Pré-visualização';
                setTimelinePreviewImageSrc(im, f);
                previewGrid.appendChild(im);
            });
            previewGrid.classList.remove('d-none');
        }

        timelineComposerSetMediaMode(true);
        var taFire = document.getElementById('timelineComposerText');
        if (taFire) {
            taFire.dispatchEvent(new Event('input', { bubbles: true }));
        }
    }

    if (imgIn && vidIn) {
        imgIn.addEventListener('change', function () {
            if (!(imgIn.files && imgIn.files.length)) {
                timelineComposerRefreshMediaPreview();
                return;
            }

            // Fotos e vídeo não podem coexistir.
            setTimelineComposerVideoFile(null);

            // Permite adicionar mais fotos (append) até 6.
            var newFiles = Array.from(imgIn.files || []);
            var maxPhotos = 6;
            var merged = timelineSelectedImageFiles.concat(newFiles).slice(0, maxPhotos);
            timelineSelectedImageFiles = merged;

            var dt = new DataTransfer();
            merged.forEach(function (f) { dt.items.add(f); });
            imgIn.files = dt.files;

            timelineComposerRefreshMediaPreview();
        });
        vidIn.addEventListener('change', function () {
            if (vidIn.files && vidIn.files.length) {
                // Vídeo impede fotos no mesmo post.
                imgIn.value = '';
                timelineSelectedImageFiles = [];
                setTimelineComposerVideoFile(vidIn.files[0]);
            } else {
                setTimelineComposerVideoFile(null);
            }
            timelineComposerRefreshMediaPreview();
        });
    }

    var btnRemoveMedia = document.getElementById('btnTimelineComposerRemoveMedia');
    if (btnRemoveMedia && imgIn && vidIn) {
        btnRemoveMedia.addEventListener('click', function () {
            imgIn.value = '';
            setTimelineComposerVideoFile(null);
            timelineSelectedImageFiles = [];
            timelineComposerRefreshMediaPreview();
        });
    }

    const ta = document.getElementById('timelineComposerText');
    const pollTypeInput = document.getElementById('timelineComposerPostType');
    const pollBlock = document.getElementById('timelineComposerPollBlock');
    const btnPoll = document.getElementById('btnTimelinePoll');
    const btnPollClear = document.getElementById('timelineComposerPollClear');
    const btnPollAdd = document.getElementById('timelinePollAddOption');
    const pollOptionsWrap = document.getElementById('timelinePollOptionsWrap');
    const featureWrap = document.getElementById('timelineComposerFeatureWrap');
    const featureInput = document.getElementById('timelineComposerFeatured');
    const btnFeature = document.getElementById('btnTimelineFeature');
    function syncFeatureToggleUi() {
        if (!featureInput || !btnFeature) {
            return;
        }
        var on = !!featureInput.checked;
        btnFeature.classList.toggle('is-active', on);
        btnFeature.setAttribute('aria-pressed', on ? 'true' : 'false');
    }
    if (btnFeature && featureInput) {
        syncFeatureToggleUi();
        btnFeature.addEventListener('click', function () {
            featureInput.checked = !featureInput.checked;
            syncFeatureToggleUi();
        });
    }
    function setPollMode(on) {
        if (!pollTypeInput || !pollBlock) return;
        pollTypeInput.value = on ? 'poll' : 'regular';
        pollBlock.classList.toggle('d-none', !on);
        if (featureWrap) {
            featureWrap.classList.toggle('d-none', on);
        }
        if (on && featureInput) {
            featureInput.checked = false;
            syncFeatureToggleUi();
        }
    }
    if (btnPoll) {
        btnPoll.addEventListener('click', function () {
            setPollMode(!(pollTypeInput && pollTypeInput.value === 'poll'));
        });
    }
    if (btnPollClear) {
        btnPollClear.addEventListener('click', function () {
            setPollMode(false);
        });
    }
    if (btnPollAdd && pollOptionsWrap) {
        btnPollAdd.addEventListener('click', function () {
            var count = pollOptionsWrap.querySelectorAll('input[name="poll_options[]"]').length;
            if (count >= 5) return;
            var inp = document.createElement('input');
            inp.type = 'text';
            inp.name = 'poll_options[]';
            inp.className = 'form-control form-control-sm';
            inp.placeholder = 'Opção ' + (count + 1);
            pollOptionsWrap.appendChild(inp);
        });
    }
    const charLeftEl = document.getElementById('timelineComposerCharLeft');
    if (ta && charLeftEl) {
        const maxLen = parseInt(ta.getAttribute('maxlength') || '2000', 10);
        const updateCharLeft = function () {
            const len = (ta.value || '').length;
            const left = maxLen - len;
            charLeftEl.textContent = left >= 0 ? String(left) : '0';
        };
        ta.addEventListener('input', updateCharLeft);
        updateCharLeft();
        function autoGrowComposer() {
            var maxH = ta.classList.contains('timeline-composer-pill--expanded') ? 280 : 160;
            ta.style.height = 'auto';
            ta.style.height = Math.min(ta.scrollHeight, maxH) + 'px';
        }
        ta.addEventListener('input', autoGrowComposer);
        ta.addEventListener('focus', autoGrowComposer);
        autoGrowComposer();
    }

    const searchTabPosts = document.getElementById('timelineSearchTabPosts');
    const searchTabPeople = document.getElementById('timelineSearchTabPeople');
    const searchPanelPosts = document.getElementById('timelineSearchPanelPosts');
    const searchPanelPeople = document.getElementById('timelineSearchPanelPeople');
    const searchPostsInput = document.getElementById('timelineSearchPostsInput');
    const searchPeopleInput = document.getElementById('timelineSearchPeopleInput');
    const searchPeopleResults = document.getElementById('timelineSearchPeopleResults');

    if (searchTabPosts && searchTabPeople && searchPanelPosts && searchPanelPeople && searchPostsInput && searchPeopleInput && searchPeopleResults) {
        function setSearchMode(mode) {
            if (mode === 'people') {
                searchTabPeople.classList.add('active');
                searchTabPosts.classList.remove('active');
                searchPanelPeople.classList.remove('d-none');
                searchPanelPosts.classList.add('d-none');
                if (searchPeopleResults) searchPeopleResults.classList.remove('d-none');
            } else {
                searchTabPosts.classList.add('active');
                searchTabPeople.classList.remove('active');
                searchPanelPosts.classList.remove('d-none');
                searchPanelPeople.classList.add('d-none');
                if (searchPeopleResults) searchPeopleResults.classList.add('d-none');
            }
        }

        function applyPostFilter(q) {
            var query = (q || '').trim().toLowerCase();
            var cards = document.querySelectorAll('.timeline-post-card');
            cards.forEach(function (card) {
                var text = (card.textContent || '').toLowerCase();
                card.style.display = !query || text.indexOf(query) !== -1 ? '' : 'none';
            });
        }

        searchTabPosts.onclick = function () { setSearchMode('posts'); applyPostFilter(searchPostsInput.value); };
        searchTabPeople.onclick = function () { setSearchMode('people'); };

        var peopleTimer = null;
        searchPeopleInput.addEventListener('input', function () {
            clearTimeout(peopleTimer);
            var q = (searchPeopleInput.value || '').trim();
            if (!q) {
                searchPeopleResults.innerHTML = '';
                return;
            }
            peopleTimer = setTimeout(function () {
                fetchJson(base + 'timeline-search-users?q=' + encodeURIComponent(q), {})
                    .then(function (data) {
                        searchPeopleResults.innerHTML = '';
                        var users = (data && data.users) ? data.users : [];
                        if (!users.length) {
                            searchPeopleResults.innerHTML = '<div class="list-group-item text-muted small">Nenhum resultado</div>';
                            return;
                        }
                        users.forEach(function (u) {
                            var b = document.createElement('button');
                            b.type = 'button';
                            b.className = 'list-group-item list-group-item-action text-start timeline-suggestion-item';
                            b.innerHTML = renderUserSuggestionHtml(u);
                            b.onclick = function () {
                                var username = u.username || '';
                                var token = username ? '@' + username : '';
                                searchPostsInput.value = token;
                                applyPostFilter(token);
                                setSearchMode('posts');
                                searchPeopleInput.value = '';
                                searchPeopleResults.innerHTML = '';
                            };
                            searchPeopleResults.appendChild(b);
                        });
                    })
                    .catch(function () {
                        searchPeopleResults.innerHTML = '<div class="list-group-item text-muted small">Erro ao buscar</div>';
                    });
            }, 250);
        });

        searchPostsInput.addEventListener('input', function () {
            applyPostFilter(searchPostsInput.value);
        });

        searchPostsInput.addEventListener('keydown', function (ev) {
            if (ev.key !== 'Enter') return;
            ev.preventDefault();
            var q = (searchPostsInput.value || '').trim();
            var parts = [];
            if (q) parts.push('q=' + encodeURIComponent(q));
            if (timelineActiveTagJs) parts.push('tag=' + encodeURIComponent(timelineActiveTagJs));
            window.location.href = base + 'timeline' + (parts.length ? '?' + parts.join('&') : '');
        });

        // inicial
        setSearchMode('posts');
        applyPostFilter('');
    }

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
                            a.className = 'list-group-item list-group-item-action text-start timeline-suggestion-item';
                            a.innerHTML = renderUserSuggestionHtml(u);
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
            mentionDd.style.width = Math.round(Math.max(280, r.width)) + 'px';
            // Se o dropdown cair fora da viewport (ex.: dentro do modal), reposiciona acima do campo.
            var fallbackHeight = 240; // compatível com o CSS (max-height)
            var top = r.bottom + 4;
            if (top + fallbackHeight > (window.innerHeight || document.documentElement.clientHeight)) {
                top = r.top - fallbackHeight - 4;
            }
            mentionDd.style.top = Math.round(Math.max(8, top)) + 'px';
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
                b.className = 'timeline-mention-dropdown-item timeline-suggestion-item' + (i === mState.hi ? ' active' : '');
                b.setAttribute('role', 'option');
                b.innerHTML = renderUserSuggestionHtml(u);
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
                    // Garante que o dropdown fique acima do modal/backdrop (evita stacking-context do container).
                    if (mentionDd && mentionDd.parentNode !== document.body) {
                        document.body.appendChild(mentionDd);
                    }
                    if (mentionDd) {
                        mentionDd.style.zIndex = '20000';
                        mentionDd.style.pointerEvents = 'auto';
                        mentionDd.classList.remove('d-none');
                    }
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

    const modalCameraEl = document.getElementById('modalTimelineCamera');
    const previewEl = document.getElementById('timelineCameraPreview');
    const btnCamPhoto = document.getElementById('btnTimelineCameraPhoto');
    const btnCamVideo = document.getElementById('btnTimelineCameraVideo');
    const btnCamStop = document.getElementById('btnTimelineCameraStop');
    const btnCamSwitch = document.getElementById('btnTimelineCameraSwitch');
    const btnCamOpen = document.getElementById('btnTimelineCamera');
    const capturePhotoIn = document.getElementById('timelineFileCapturePhoto');
    const captureVideoIn = document.getElementById('timelineFileCaptureVideo');
    const cameraHintEl = document.getElementById('timelineCameraHint');

    if (modalCameraEl && previewEl && btnCamOpen && btnCamPhoto && btnCamVideo && btnCamStop && imgIn && vidIn && capturePhotoIn && captureVideoIn) {
        let camStream = null;
        let mediaRecorder = null;
        let mediaChunks = [];
        let stopTimer = null;
        let isRecording = false;
        let preferredFacingMode = 'environment';
        let availableVideoInputs = 0;

        function hasUsableVideoTrack(stream) {
            if (!stream || !stream.getVideoTracks) return false;
            var tracks = stream.getVideoTracks();
            if (!tracks || !tracks.length) return false;
            return tracks.some(function (t) { return t.readyState === 'live' && t.enabled !== false; });
        }

        function stopCameraTracks() {
            if (camStream) {
                camStream.getTracks().forEach(function (t) { t.stop(); });
                camStream = null;
            }
            if (previewEl) previewEl.srcObject = null;
            isRecording = false;
        }

        function releaseCameraSession() {
            if (stopTimer) {
                clearTimeout(stopTimer);
                stopTimer = null;
            }
            if (mediaRecorder && mediaRecorder.state !== 'inactive') {
                try { mediaRecorder.stop(); } catch (e) { /* ignore */ }
            }
            mediaRecorder = null;
            mediaChunks = [];
            stopCameraTracks();
            resetCameraUI();
            setCameraHint('Escolha `Foto` ou `Vídeo` para capturar.', false);
        }

        function resetCameraUI() {
            if (btnCamStop) btnCamStop.classList.add('d-none');
            if (btnCamVideo) btnCamVideo.disabled = false;
            if (btnCamPhoto) btnCamPhoto.disabled = false;
            if (btnCamSwitch) btnCamSwitch.disabled = false;
        }

        function updateSwitchCameraButton() {
            if (!btnCamSwitch) return;
            var canSwitch = canUseLiveCamera() && availableVideoInputs > 1;
            if (!canSwitch) {
                btnCamSwitch.classList.add('d-none');
                return;
            }
            var nextFacing = preferredFacingMode === 'user' ? 'environment' : 'user';
            btnCamSwitch.textContent = nextFacing === 'user' ? 'Usar frontal' : 'Usar traseira';
            btnCamSwitch.classList.remove('d-none');
        }

        function refreshAvailableCameras() {
            if (!navigator.mediaDevices || !navigator.mediaDevices.enumerateDevices) {
                availableVideoInputs = 0;
                updateSwitchCameraButton();
                return Promise.resolve();
            }
            return navigator.mediaDevices.enumerateDevices()
                .then(function (devices) {
                    availableVideoInputs = (devices || []).filter(function (d) { return d && d.kind === 'videoinput'; }).length;
                    updateSwitchCameraButton();
                })
                .catch(function () {
                    availableVideoInputs = 0;
                    updateSwitchCameraButton();
                });
        }

        function setCameraHint(message, isError) {
            if (!cameraHintEl) return;
            cameraHintEl.textContent = message || 'Escolha `Foto` ou `Vídeo` para capturar.';
            cameraHintEl.classList.toggle('text-danger', !!isError);
            cameraHintEl.classList.toggle('text-muted', !isError);
        }

        function getCameraErrorMessage(err) {
            var name = (err && err.name) ? String(err.name) : '';
            if (name === 'AbortError') {
                return 'A inicialização da câmera foi interrompida pelo navegador/dispositivo.';
            }
            if (name === 'NotAllowedError' || name === 'PermissionDeniedError') {
                return 'Permissão da câmera negada. Libere a permissão do site nas configurações do navegador.';
            }
            if (name === 'NotFoundError' || name === 'DevicesNotFoundError') {
                return 'Nenhuma câmera foi encontrada neste dispositivo.';
            }
            if (name === 'NotReadableError' || name === 'TrackStartError') {
                return 'A câmera está em uso por outro aplicativo.';
            }
            if (name === 'OverconstrainedError' || name === 'ConstraintNotSatisfiedError') {
                return 'Não foi possível atender à configuração de câmera solicitada.';
            }
            if (name === 'SecurityError') {
                return 'Acesso bloqueado por política de segurança do navegador/servidor.';
            }
            return (err && err.message) ? err.message : 'Não foi possível acessar a câmera ao vivo.';
        }

        function ensureCameraStream() {
            if (camStream && hasUsableVideoTrack(camStream)) return Promise.resolve(camStream);
            // Evita manter stream "zumbi" que pode travar câmera no SO/navegador.
            stopCameraTracks();
            if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
                return Promise.reject(new Error('Seu navegador não suporta acesso à câmera.'));
            }
            // Alguns devices/navegadores falham com facingMode específico.
            // Tentamos em cascata: preferência atual -> alternativo -> qualquer câmera.
            var altFacing = preferredFacingMode === 'user' ? 'environment' : 'user';
            var attempts = [
                { video: { facingMode: { ideal: preferredFacingMode } }, audio: false },
                { video: { facingMode: { ideal: altFacing } }, audio: false },
                { video: true, audio: false }
            ];
            var i = 0;
            function tryNext(lastErr) {
                if (i >= attempts.length) {
                    return Promise.reject(lastErr || new Error('Não foi possível iniciar a câmera.'));
                }
                var constraints = attempts[i++];
                return navigator.mediaDevices.getUserMedia(constraints).then(function (stream) {
                    camStream = stream;
                    previewEl.srcObject = stream;
                    var tracks = stream.getVideoTracks ? stream.getVideoTracks() : [];
                    if (tracks.length && tracks[0].getSettings) {
                        var fm = tracks[0].getSettings().facingMode;
                        if (fm === 'user' || fm === 'environment') {
                            preferredFacingMode = fm;
                        }
                    }
                    updateSwitchCameraButton();
                    return stream;
                }).catch(function (err) {
                    return tryNext(err);
                });
            }
            return tryNext(null).then(function (stream) {
                if (!stream || !stream.getVideoTracks || !stream.getVideoTracks().length) {
                    throw new Error('A câmera não retornou trilha de vídeo.');
                }
                return stream;
            });
        }

        function canUseLiveCamera() {
            return !!(navigator.mediaDevices && navigator.mediaDevices.getUserMedia);
        }

        function isSecureCameraContext() {
            var h = (window.location.hostname || '').toLowerCase();
            return window.isSecureContext === true || h === 'localhost' || h === '127.0.0.1';
        }

        function openCaptureWithFallback(captureInput, regularInput) {
            try {
                captureInput.click();
            } catch (e) {
                if (regularInput) regularInput.click();
                return;
            }
            // Alguns navegadores desktop ignoram `capture`; nesses casos abre seletor normal.
            setTimeout(function () {
                if (captureInput && captureInput.files && captureInput.files.length === 0 && regularInput) {
                    regularInput.click();
                }
            }, 300);
        }

        function openNativeCaptureFallback(kind) {
            var k = kind || 'photo';
            showTimelineInlineMessage('Câmera ao vivo exige HTTPS (ou localhost). Vamos abrir a captura nativa do dispositivo.', 'info');
            if (k === 'video') {
                openCaptureWithFallback(captureVideoIn, vidIn);
                return;
            }
            openCaptureWithFallback(capturePhotoIn, imgIn);
        }

        function openCameraModal() {
            const m = bootstrap.Modal.getInstance(modalCameraEl) || new bootstrap.Modal(modalCameraEl);
            m.show();
        }

        function isCameraModalVisible() {
            return !!(modalCameraEl && modalCameraEl.classList.contains('show'));
        }

        function handleLiveCameraFailure(err) {
            var msg = getCameraErrorMessage(err);
            console.warn('[TimelineCamera] Falha na prévia ao vivo:', {
                name: err && err.name ? err.name : null,
                message: err && err.message ? err.message : String(err || '')
            });
            setCameraHint(msg, true);
            showTimelineInlineMessage(msg + ' Vamos abrir a captura nativa do dispositivo.', 'warning');
            releaseCameraSession();
            var mi = bootstrap.Modal.getInstance(modalCameraEl);
            if (mi) mi.hide();
            openCaptureWithFallback(capturePhotoIn, imgIn);
        }

        function stopRecordingAndClose() {
            if (mediaRecorder && mediaRecorder.state !== 'inactive') {
                mediaRecorder.stop();
            } else {
                stopCameraTracks();
                var mi = bootstrap.Modal.getInstance(modalCameraEl);
                if (mi) mi.hide();
            }
        }

        btnCamOpen.addEventListener('click', function () {
            resetCameraUI();
            setCameraHint('Preparando câmera...', false);
            if (!isSecureCameraContext() || !canUseLiveCamera()) {
                setCameraHint('Prévia ao vivo indisponível neste contexto. Abrindo captura nativa...', true);
                openNativeCaptureFallback('photo');
                return;
            }
            openCameraModal();
        });

        modalCameraEl.addEventListener('shown.bs.modal', function () {
            resetCameraUI();
            setCameraHint('Solicitando permissão para câmera...', false);
            ensureCameraStream()
                .then(function (stream) {
                    return refreshAvailableCameras().then(function () { return stream; });
                })
                .then(function (stream) {
                    if (!previewEl || typeof previewEl.play !== 'function') {
                        setCameraHint('Câmera ativa. Escolha `Foto` ou `Vídeo` para capturar.', false);
                        return;
                    }
                    return previewEl.play().then(function () {
                        setCameraHint('Câmera ativa. Escolha `Foto` ou `Vídeo` para capturar.', false);
                    }).catch(function (playErr) {
                        // Em alguns mobiles ocorre AbortError transitório na primeira tentativa de play().
                        if (playErr && playErr.name === 'AbortError' && isCameraModalVisible()) {
                            return new Promise(function (resolve, reject) {
                                setTimeout(function () {
                                    if (!isCameraModalVisible()) {
                                        reject(playErr);
                                        return;
                                    }
                                    previewEl.play().then(function () {
                                        setCameraHint('Câmera ativa. Escolha `Foto` ou `Vídeo` para capturar.', false);
                                        resolve();
                                    }).catch(function (retryErr) {
                                        reject(retryErr);
                                    });
                                }, 250);
                            });
                        }
                        // Alguns navegadores falham ao iniciar o elemento de vídeo mesmo com stream válido.
                        if (stream && stream.getTracks) {
                            stream.getTracks().forEach(function (t) {
                                try { t.stop(); } catch (e) { /* ignore */ }
                            });
                        }
                        throw playErr;
                    });
                })
                .catch(function (e) {
                    handleLiveCameraFailure(e);
                });
        });

        if (btnCamSwitch) {
            btnCamSwitch.addEventListener('click', function () {
                if (isRecording) {
                    return;
                }
                btnCamSwitch.disabled = true;
                preferredFacingMode = preferredFacingMode === 'user' ? 'environment' : 'user';
                stopCameraTracks();
                setCameraHint('Alternando câmera...', false);
                ensureCameraStream()
                    .then(function () {
                        if (previewEl && typeof previewEl.play === 'function') {
                            return previewEl.play().catch(function () { /* ignore */ });
                        }
                    })
                    .then(function () {
                        setCameraHint('Câmera ativa. Escolha `Foto` ou `Vídeo` para capturar.', false);
                    })
                    .catch(function (e) {
                        handleLiveCameraFailure(e);
                    })
                    .finally(function () {
                        btnCamSwitch.disabled = false;
                    });
            });
        }

        previewEl.addEventListener('error', function () {
            // Captura casos como "Could not start video source".
            if (!isCameraModalVisible()) {
                return;
            }
            var e = new Error('Could not start video source');
            e.name = 'NotReadableError';
            handleLiveCameraFailure(e);
        });

        // Liberação defensiva: evita câmera "presa" ao trocar app/aba ou recarregar.
        document.addEventListener('visibilitychange', function () {
            if (document.visibilityState !== 'visible') {
                releaseCameraSession();
            }
        });
        window.addEventListener('pagehide', releaseCameraSession);
        window.addEventListener('beforeunload', releaseCameraSession);

        modalCameraEl.addEventListener('hidden.bs.modal', function () {
            releaseCameraSession();
        });

        btnCamPhoto.addEventListener('click', function () {
            if (!camStream) return;
            if (mediaRecorder && mediaRecorder.state !== 'inactive') {
                // Evita captura durante gravação.
                return;
            }
            const canvas = document.createElement('canvas');
            const w = previewEl.videoWidth || 640;
            const h = previewEl.videoHeight || 480;
            canvas.width = w;
            canvas.height = h;
            const ctx = canvas.getContext('2d');
            ctx.drawImage(previewEl, 0, 0, w, h);

            canvas.toBlob(function (blob) {
                if (!blob) return;
                const dt = new DataTransfer();
                dt.items.add(new File([blob], 'camera-' + Date.now() + '.jpg', { type: 'image/jpeg' }));
                // Fotos podem coexistir com outras fotos (até 6). Mas não com vídeo.
                setTimelineComposerVideoFile(null);
                var newFile = dt.files[0];
                var merged = (timelineSelectedImageFiles || []).concat([newFile]).slice(0, 6);
                timelineSelectedImageFiles = merged;
                var dt2 = new DataTransfer();
                merged.forEach(function (f) { dt2.items.add(f); });
                imgIn.files = dt2.files;
                timelineComposerRefreshMediaPreview();
                resetCameraUI();
                stopCameraTracks();
                var mi = bootstrap.Modal.getInstance(modalCameraEl);
                if (mi) mi.hide();
            }, 'image/jpeg', 0.88);
        });

        btnCamVideo.addEventListener('click', function () {
            if (!camStream) return;
            if (isRecording) return;
            mediaChunks = [];

            let mime = 'video/webm;codecs=vp8,opus';
            if (window.MediaRecorder && !MediaRecorder.isTypeSupported(mime)) {
                mime = 'video/webm';
            }

            try {
                mediaRecorder = new MediaRecorder(camStream, { mimeType: mime });
            } catch (e) {
                showTimelineInlineMessage('Gravação não suportada neste navegador.', 'warning');
                return;
            }

            isRecording = true;
            resetCameraUI();
            btnCamStop.classList.remove('d-none');
            btnCamVideo.disabled = true;
            btnCamPhoto.disabled = true;
            setCameraHint('Gravando vídeo... toque em "Parar" para finalizar.', false);

            mediaRecorder.ondataavailable = function (e) {
                if (e.data && e.data.size) mediaChunks.push(e.data);
            };
            mediaRecorder.onstop = function () {
                if (stopTimer) {
                    clearTimeout(stopTimer);
                    stopTimer = null;
                }
                const blob = new Blob(mediaChunks, { type: 'video/webm' });
                var recordedFile = new File([blob], 'gravacao-' + Date.now() + '.webm', { type: 'video/webm' });
                setTimelineComposerVideoFile(recordedFile);
                imgIn.value = '';
                timelineSelectedImageFiles = [];
                timelineComposerRefreshMediaPreview();

                resetCameraUI();
                stopCameraTracks();
                setCameraHint('Vídeo capturado com sucesso.', false);
                var mi = bootstrap.Modal.getInstance(modalCameraEl);
                if (mi) mi.hide();
            };

            mediaRecorder.start(1000);
            const maxMs = 90000;
            stopTimer = setTimeout(function () {
                if (mediaRecorder && mediaRecorder.state !== 'inactive') {
                    mediaRecorder.stop();
                }
            }, maxMs);
        });

        btnCamStop.addEventListener('click', function () {
            stopRecordingAndClose();
        });

        var btnCamList = document.getElementById('btnTimelineComposerCameraList');
        if (btnCamList) {
            btnCamList.addEventListener('click', function () {
                resetCameraUI();
                if (!isSecureCameraContext() || !canUseLiveCamera()) {
                    openNativeCaptureFallback('video');
                    return;
                }
                openCameraModal();
            });
        }

        capturePhotoIn.addEventListener('change', function () {
            var files = Array.from(capturePhotoIn.files || []);
            if (!files.length) return;
            setTimelineComposerVideoFile(null);
            var merged = (timelineSelectedImageFiles || []).concat(files).slice(0, 6);
            timelineSelectedImageFiles = merged;
            var dt = new DataTransfer();
            merged.forEach(function (f) { dt.items.add(f); });
            imgIn.files = dt.files;
            timelineComposerRefreshMediaPreview();
            capturePhotoIn.value = '';
        });

        captureVideoIn.addEventListener('change', function () {
            var files = Array.from(captureVideoIn.files || []);
            if (!files.length) return;
            setTimelineComposerVideoFile(files[0]);
            imgIn.value = '';
            timelineSelectedImageFiles = [];
            timelineComposerRefreshMediaPreview();
            captureVideoIn.value = '';
        });
    }

    // Envio via fetch: em alguns navegadores (mobile/câmera) arquivos atribuídos com DataTransfer não entram no POST nativo.
    const composerForm = document.getElementById('timelineComposerForm');
    if (composerForm) {
        const composerCsrfInput = document.getElementById('timelineComposerCsrfToken');
        composerForm.addEventListener('submit', function (ev) {
            ev.preventDefault();
            var maxVideoBytes = 50 * 1024 * 1024;
            var composerVideoFile = getTimelineComposerVideoFile();
            if (composerVideoFile && composerVideoFile.size > maxVideoBytes) {
                showTimelineInlineMessage('Vídeo muito grande. Limite de 50MB. Grave um vídeo mais curto e tente novamente.', 'warning');
                return;
            }
            const submitBtns = composerForm.querySelectorAll('button[type="submit"]');
            submitBtns.forEach(function (b) { b.disabled = true; });
            const actionUrl = composerForm.getAttribute('action');
            function sendComposer(maxRetry) {
                return fetch(actionUrl, {
                    method: 'POST',
                    body: buildTimelineComposerFormData(composerForm, composerCsrfInput),
                    credentials: 'same-origin',
                    redirect: 'follow',
                    headers: buildTimelineComposerRequestHeaders(composerCsrfInput)
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
                        if (d.csrf_expired && d.csrf_token && composerCsrfInput && maxRetry > 0) {
                            composerCsrfInput.value = d.csrf_token;
                            return sendComposer(maxRetry - 1);
                        }
                        if (d.success === false || d.error) {
                            showTimelineInlineMessage(d.error || d.message || 'Não foi possível publicar. Tente novamente.', 'warning');
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
                    showTimelineInlineMessage('Não foi possível enviar a publicação. Verifique a conexão e tente novamente.', 'danger');
                })
                .finally(function () {
                    submitBtns.forEach(function (b) { b.disabled = false; });
                });
            }
            sendComposer(1);
        });
    }
})();
