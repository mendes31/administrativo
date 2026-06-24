<?php

declare(strict_types=1);

namespace App\adms\Controllers\help;

use App\adms\Helpers\ContextHelpHelper;
use App\adms\Views\Services\LoadViewService;

class ContextHelp
{
    private array $data = [];

    public function index(string|int|null $topic = null): void
    {
        $raw = is_string($topic) ? trim($topic) : '';
        if ($raw === '' && isset($_GET['ctx'])) {
            $raw = trim((string) $_GET['ctx']);
        }

        if ($raw === '') {
            $topicId = 'index';
        } elseif (ContextHelpHelper::findTopicInManifest($raw) !== null) {
            $topicId = $raw;
        } else {
            $topicId = ContextHelpHelper::resolveTopicFromPageSlug($raw);
        }

        $meta = ContextHelpHelper::findTopicInManifest($topicId);
        $html = ContextHelpHelper::renderTopicHtml($topicId);

        if ($html === null && $topicId !== 'index') {
            $topicId = 'sst-visao-geral';
            $meta = ContextHelpHelper::findTopicInManifest($topicId);
            $html = ContextHelpHelper::renderTopicHtml($topicId);
        }

        $this->data['topic_id'] = $topicId;
        $this->data['topic_meta'] = $meta;
        $this->data['topic_html'] = $html ?? '<p class="text-muted">Documentação em elaboração para este tópico.</p>';
        $this->data['topics'] = ContextHelpHelper::flatTopicList();
        $this->data['manifest'] = ContextHelpHelper::loadManifest();
        $this->data['title_head'] = ($meta['title'] ?? 'Manual do sistema') . ' — Ajuda';

        (new LoadViewService('adms/Views/context_help/view', $this->data))->loadViewHelp();
    }
}
