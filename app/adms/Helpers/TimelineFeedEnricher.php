<?php

declare(strict_types=1);

namespace App\adms\Helpers;

use App\adms\Models\Repository\TimelineRepository;
use App\adms\Models\Repository\UsersRepository;

/**
 * Enriquecimento comum do feed da timeline (mídias, reposts, menções, reações, enquetes).
 */
final class TimelineFeedEnricher
{
    /**
     * @param array<int, array<string, mixed>> $posts
     * @return array{
     *   posts: array<int, array<string, mixed>>,
     *   mention_name_map: array<int, string>,
     *   reaction_map: array<int, string>,
     *   reaction_summaries: array<int, array<string, int>>,
     *   poll_map: array<int, mixed>
     * }
     */
    public static function enrich(
        TimelineRepository $repo,
        UsersRepository $userRepo,
        array $posts,
        int $viewerUserId
    ): array {
        $postIdsForImages = array_map(static fn ($p) => (int)($p['id'] ?? 0), $posts);
        $imagesMap = $repo->getPostImagesByPostIds($postIdsForImages);
        foreach ($posts as &$p) {
            $pid = (int)($p['id'] ?? 0);
            $hasVideo = !empty($p['video_path']);
            if ($hasVideo) {
                $p['image_paths'] = [];
                continue;
            }

            $paths = $imagesMap[$pid] ?? [];
            if ($paths === [] && !empty($p['image_path'])) {
                $paths = [(string)$p['image_path']];
            }
            $p['image_paths'] = $paths;
        }
        unset($p);

        $sharedIds = [];
        foreach ($posts as $p) {
            $sid = (int)($p['shared_from_post_id'] ?? 0);
            if ($sid > 0) {
                $sharedIds[] = $sid;
            }
        }
        $sharedMap = $repo->getPostsWithAuthorByIds($sharedIds);
        if ($sharedMap !== []) {
            $sharedImagesMap = $repo->getPostImagesByPostIds(array_keys($sharedMap));
            foreach ($sharedMap as $sid => &$sp) {
                $simgs = $sharedImagesMap[$sid] ?? [];
                if ($simgs === [] && !empty($sp['image_path'])) {
                    $simgs = [(string)$sp['image_path']];
                }
                $sp['image_paths'] = $simgs;
            }
            unset($sp);
        }
        foreach ($posts as &$p) {
            $sid = (int)($p['shared_from_post_id'] ?? 0);
            $p['shared_post'] = $sid > 0 ? ($sharedMap[$sid] ?? null) : null;
        }
        unset($p);

        $mentionIds = TimelineMentionHelper::collectMentionIdsFromPosts($posts, $userRepo);
        foreach ($posts as $p) {
            $shared = $p['shared_post'] ?? null;
            if (is_array($shared)) {
                $mentionIds = array_values(array_unique(array_merge(
                    $mentionIds,
                    TimelineMentionHelper::collectMentionIdsFromPosts([$shared], $userRepo)
                )));
            }
        }
        $mentionNameMap = $userRepo->getIdNameMapForIds($mentionIds);

        $postIds = array_map(static fn ($p) => (int)($p['id'] ?? 0), $posts);
        $reactionMap = $repo->getUserReactionMap($viewerUserId, $postIds);
        $reactionSummaries = $repo->getReactionSummariesByPostIds($postIds);
        $pollMap = $repo->getPollsByPostIds($postIds, $viewerUserId, false);

        return [
            'posts' => $posts,
            'mention_name_map' => $mentionNameMap,
            'reaction_map' => $reactionMap,
            'reaction_summaries' => $reactionSummaries,
            'poll_map' => $pollMap,
        ];
    }
}
