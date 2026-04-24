<?php

namespace App\adms\Models\Repository;

use App\adms\Helpers\TimelineHashtagHelper;
use App\adms\Helpers\TimelineReactionHelper;
use App\adms\Models\Services\DbConnection;
use PDO;

class TimelineRepository extends DbConnection
{
    /**
     * @param array<int, string>|null $imagePaths
     */
    public function createPost(
        int $userId,
        string $content,
        ?array $imagePaths,
        ?string $videoPath = null,
        ?int $sharedFromPostId = null,
        string $postType = 'regular'
    ): int
    {
        $postType = $postType === 'poll' ? 'poll' : 'regular';
        $sql = 'INSERT INTO adms_timeline_posts (user_id, content, image_path, video_path, shared_from_post_id, post_type, status, created_at, updated_at)
                VALUES (:uid, :content, :img, :vid, :shared, :post_type, "active", NOW(), NOW())';
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':uid', $userId, PDO::PARAM_INT);
        $stmt->bindValue(':content', $content, PDO::PARAM_STR);
        // Mantém compatibilidade com posts antigos: se vier apenas 1 imagem, guarda na coluna image_path.
        // Para múltiplas imagens, a lista fica em adms_timeline_post_images.
        $singleImagePath = null;
        if ($imagePaths !== null && count($imagePaths) === 1) {
            $singleImagePath = $imagePaths[0];
        }
        $stmt->bindValue(':img', $singleImagePath, $singleImagePath !== null ? PDO::PARAM_STR : PDO::PARAM_NULL);
        $stmt->bindValue(':vid', $videoPath, $videoPath !== null ? PDO::PARAM_STR : PDO::PARAM_NULL);
        $stmt->bindValue(':shared', $sharedFromPostId, $sharedFromPostId !== null ? PDO::PARAM_INT : PDO::PARAM_NULL);
        $stmt->bindValue(':post_type', $postType, PDO::PARAM_STR);
        $stmt->execute();
        $postId = (int)$this->getConnection()->lastInsertId();

        // Insere múltiplas imagens em tabela auxiliar.
        if ($imagePaths !== null && count($imagePaths) > 1) {
            $ins = $this->getConnection()->prepare(
                'INSERT INTO adms_timeline_post_images (post_id, image_path, sort_order, created_at)
                 VALUES (:p, :img, :ord, NOW())'
            );
            $ord = 0;
            foreach ($imagePaths as $imgPath) {
                if ($imgPath === null || $imgPath === '') continue;
                $ins->execute([':p' => $postId, ':img' => $imgPath, ':ord' => $ord]);
                $ord++;
            }
        }

        return $postId;
    }

    /**
     * Verifica se já existe, no dia atual, uma publicação de tempo de empresa
     * feita por um autor para um destinatário específico (baseado em menção @username e anos).
     */
    public function hasTenureCongratsPostToday(int $authorUserId, int $targetUserId, ?int $years): bool
    {
        if ($authorUserId <= 0 || $targetUserId <= 0) {
            return false;
        }
        $today = date('Y-m-d');
        $sql = 'SELECT p.id, u.username
                FROM adms_timeline_posts p
                INNER JOIN adms_users u ON u.id = :target_id
                WHERE p.user_id = :author_id
                  AND p.status = "active"
                  AND DATE(p.created_at) = :today
                ORDER BY p.created_at DESC
                LIMIT 20';
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->execute([
            ':author_id' => $authorUserId,
            ':target_id' => $targetUserId,
            ':today' => $today,
        ]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        if ($rows === []) {
            return false;
        }
        $username = '';
        foreach ($rows as $r) {
            $username = (string)($r['username'] ?? '');
            break;
        }
        if ($username === '') {
            return false;
        }
        $needleBase = 'Parabéns pelos seus ';
        $needleUser = '@' . $username;
        foreach ($rows as $row) {
            $pid = (int)($row['id'] ?? 0);
            if ($pid <= 0) {
                continue;
            }
            $pRow = $this->getPostById($pid);
            if (!$pRow) {
                continue;
            }
            $content = (string)($pRow['content'] ?? '');
            if ($content === '') {
                continue;
            }
            if (strpos($content, $needleUser) === false) {
                continue;
            }
            if ($years !== null) {
                $needleYears = $needleBase . $years . ' ano(s) de empresa';
                if (strpos($content, $needleYears) !== false) {
                    return true;
                }
            } else {
                if (strpos($content, 'Parabéns pelo seu tempo de casa') !== false
                    || strpos($content, 'Parabéns pelo seu tempo de empresa') !== false) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * @param array<int, string> $options
     */
    public function createPollForPost(int $postId, string $question, array $options, ?string $startsAt, string $endsAt): int
    {
        $pdo = $this->getConnection();
        $stmt = $pdo->prepare(
            'INSERT INTO adms_timeline_polls (post_id, question, starts_at, ends_at, created_at, updated_at)
             VALUES (:p, :q, :s, :e, NOW(), NOW())'
        );
        $stmt->execute([
            ':p' => $postId,
            ':q' => mb_substr(trim($question), 0, 255),
            ':s' => $startsAt !== null ? $startsAt : null,
            ':e' => $endsAt,
        ]);
        $pollId = (int) $pdo->lastInsertId();
        if ($pollId <= 0) {
            return 0;
        }
        $ins = $pdo->prepare(
            'INSERT INTO adms_timeline_poll_options (poll_id, option_text, sort_order, created_at)
             VALUES (:p, :t, :o, NOW())'
        );
        $ord = 0;
        foreach ($options as $opt) {
            $txt = trim((string) $opt);
            if ($txt === '') {
                continue;
            }
            $ins->execute([':p' => $pollId, ':t' => mb_substr($txt, 0, 255), ':o' => $ord]);
            $ord++;
        }

        return $pollId;
    }

    /**
     * @param array<int> $userIds
     */
    public function replaceMentions(string $entityType, int $entityId, array $userIds): void
    {
        if (!in_array($entityType, ['post', 'comment'], true)) {
            return;
        }
        $del = $this->getConnection()->prepare(
            'DELETE FROM adms_timeline_mentions WHERE entity_type = :t AND entity_id = :e'
        );
        $del->execute([':t' => $entityType, ':e' => $entityId]);

        $userIds = array_values(array_unique(array_filter(array_map('intval', $userIds), static fn ($v) => $v > 0)));
        $ins = $this->getConnection()->prepare(
            'INSERT INTO adms_timeline_mentions (entity_type, entity_id, mentioned_user_id, created_at)
             VALUES (:t, :e, :u, NOW())'
        );
        foreach ($userIds as $uid) {
            try {
                $ins->execute([':t' => $entityType, ':e' => $entityId, ':u' => $uid]);
            } catch (\Throwable) {
                // duplicata ou FK — ignorar
            }
        }
    }

    /**
     * @return array<int>
     */
    public function getMentionedUserIds(string $entityType, int $entityId): array
    {
        if (!in_array($entityType, ['post', 'comment'], true) || $entityId <= 0) {
            return [];
        }
        $sql = 'SELECT mentioned_user_id
                FROM adms_timeline_mentions
                WHERE entity_type = :t AND entity_id = :e';
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->execute([':t' => $entityType, ':e' => $entityId]);
        $rows = $stmt->fetchAll(PDO::FETCH_COLUMN) ?: [];

        return array_values(array_unique(array_filter(array_map('intval', $rows), static fn ($v) => $v > 0)));
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function getFeedPosts(int $page, int $perPage, ?string $tag = null, ?string $searchQuery = null): array
    {
        $page = max(1, $page);
        $offset = ($page - 1) * $perPage;
        $tag = TimelineHashtagHelper::normalizeTag((string) $tag);
        $searchQuery = $searchQuery !== null ? trim($searchQuery) : '';
        if (mb_strlen($searchQuery) > 200) {
            $searchQuery = mb_substr($searchQuery, 0, 200);
        }
        $sql = 'SELECT p.*, u.name AS author_name, u.image AS author_image,
                       (SELECT COUNT(*) FROM adms_timeline_likes l WHERE l.post_id = p.id) AS likes_count,
                       (SELECT COUNT(*) FROM adms_timeline_comments c WHERE c.post_id = p.id AND c.status = "active") AS comments_count
                FROM adms_timeline_posts p
                INNER JOIN adms_users u ON u.id = p.user_id
                WHERE p.status = "active"';
        if ($tag !== '') {
            $sql .= ' AND EXISTS (
                        SELECT 1
                        FROM adms_timeline_post_tags pt
                        INNER JOIN adms_timeline_tags t ON t.id = pt.tag_id
                        WHERE pt.post_id = p.id AND t.tag = :tag
                      )';
        }
        if ($searchQuery !== '') {
            $sql .= ' AND p.content LIKE :qsearch';
        }
        $sql .= '
                ORDER BY p.created_at DESC
                LIMIT :lim OFFSET :off';
        $stmt = $this->getConnection()->prepare($sql);
        if ($tag !== '') {
            $stmt->bindValue(':tag', $tag, PDO::PARAM_STR);
        }
        if ($searchQuery !== '') {
            $stmt->bindValue(':qsearch', '%' . $searchQuery . '%', PDO::PARAM_STR);
        }
        $stmt->bindValue(':lim', $perPage, PDO::PARAM_INT);
        $stmt->bindValue(':off', $offset, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    /**
     * Feed do perfil na timeline.
     * Inclui publicações:
     * - criadas pelo próprio usuário; e
     * - de terceiros que mencionam esse usuário no post.
     *
     * @return array<int, array<string, mixed>>
     */
    public function getFeedPostsByUserId(int $userId, int $page, int $perPage): array
    {
        if ($userId <= 0) {
            return [];
        }
        $page = max(1, $page);
        $offset = ($page - 1) * $perPage;
        $sql = 'SELECT p.*, u.name AS author_name, u.image AS author_image,
                       (SELECT COUNT(*) FROM adms_timeline_likes l WHERE l.post_id = p.id) AS likes_count,
                       (SELECT COUNT(*) FROM adms_timeline_comments c WHERE c.post_id = p.id AND c.status = "active") AS comments_count
                FROM adms_timeline_posts p
                INNER JOIN adms_users u ON u.id = p.user_id
                LEFT JOIN adms_timeline_mentions m 
                       ON m.entity_type = "post" 
                      AND m.entity_id = p.id
                WHERE p.status = "active"
                  AND (p.user_id = :uid OR m.mentioned_user_id = :uid)
                GROUP BY p.id
                ORDER BY p.created_at DESC
                LIMIT :lim OFFSET :off';
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':uid', $userId, PDO::PARAM_INT);
        $stmt->bindValue(':lim', $perPage, PDO::PARAM_INT);
        $stmt->bindValue(':off', $offset, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public function countActivePostsByUserId(int $userId): int
    {
        if ($userId <= 0) {
            return 0;
        }
        $sql = 'SELECT COUNT(DISTINCT p.id) AS c
                FROM adms_timeline_posts p
                LEFT JOIN adms_timeline_mentions m 
                       ON m.entity_type = "post" 
                      AND m.entity_id = p.id
                WHERE p.status = "active"
                  AND (p.user_id = :uid OR m.mentioned_user_id = :uid)';
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->execute([':uid' => $userId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return (int)($row['c'] ?? 0);
    }

    public function countActivePosts(?string $tag = null, ?string $searchQuery = null): int
    {
        $tag = TimelineHashtagHelper::normalizeTag((string) $tag);
        $searchQuery = $searchQuery !== null ? trim($searchQuery) : '';
        if (mb_strlen($searchQuery) > 200) {
            $searchQuery = mb_substr($searchQuery, 0, 200);
        }
        $sql = 'SELECT COUNT(*) AS c FROM adms_timeline_posts p WHERE p.status = "active"';
        if ($tag !== '') {
            $sql .= ' AND EXISTS (
                        SELECT 1
                        FROM adms_timeline_post_tags pt
                        INNER JOIN adms_timeline_tags t ON t.id = pt.tag_id
                        WHERE pt.post_id = p.id AND t.tag = :tag
                      )';
        }
        if ($searchQuery !== '') {
            $sql .= ' AND p.content LIKE :qsearch';
        }
        $stmt = $this->getConnection()->prepare($sql);
        if ($tag !== '') {
            $stmt->bindValue(':tag', $tag, PDO::PARAM_STR);
        }
        if ($searchQuery !== '') {
            $stmt->bindValue(':qsearch', '%' . $searchQuery . '%', PDO::PARAM_STR);
        }
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return (int)($row['c'] ?? 0);
    }

    /**
     * Indica se o post possui a hashtag normalizada (filtro ativo na timeline).
     */
    public function postHasNormalizedTag(int $postId, string $normalizedTag): bool
    {
        $normalizedTag = TimelineHashtagHelper::normalizeTag($normalizedTag);
        if ($postId <= 0 || $normalizedTag === '') {
            return false;
        }
        $sql = 'SELECT 1
                FROM adms_timeline_post_tags pt
                INNER JOIN adms_timeline_tags t ON t.id = pt.tag_id
                WHERE pt.post_id = :p AND t.tag = :tag
                LIMIT 1';
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->execute([':p' => $postId, ':tag' => $normalizedTag]);
        return (bool) $stmt->fetchColumn();
    }

    /**
     * @param array<int> $postIds
     * @return array<int, string> post_id => reaction_type
     */
    public function getUserReactionMap(int $userId, array $postIds): array
    {
        if ($userId <= 0 || $postIds === []) {
            return [];
        }
        $placeholders = implode(',', array_fill(0, count($postIds), '?'));
        $sql = "SELECT post_id, reaction_type FROM adms_timeline_likes WHERE user_id = ? AND post_id IN ($placeholders)";
        $params = array_merge([$userId], $postIds);
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->execute($params);
        $map = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $map[(int)$row['post_id']] = TimelineReactionHelper::normalize((string)($row['reaction_type'] ?? 'like'));
        }
        return $map;
    }

    /**
     * @param array<int> $postIds
     * @return array<int, array<string, int>> post_id => [ reaction_type => count ]
     */
    public function getReactionSummariesByPostIds(array $postIds): array
    {
        if ($postIds === []) {
            return [];
        }
        $placeholders = implode(',', array_fill(0, count($postIds), '?'));
        $sql = "SELECT post_id, reaction_type, COUNT(*) AS c FROM adms_timeline_likes WHERE post_id IN ($placeholders) GROUP BY post_id, reaction_type";
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->execute($postIds);
        $out = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $pid = (int)($row['post_id'] ?? 0);
            $t = TimelineReactionHelper::normalize((string)($row['reaction_type'] ?? 'like'));
            if (!isset($out[$pid])) {
                $out[$pid] = [];
            }
            $out[$pid][$t] = (int)($row['c'] ?? 0);
        }
        return $out;
    }

    /**
     * @return array{liked: bool, reaction: ?string, likes_count: int, summary: array<string, int>}
     */
    public function setReaction(int $postId, int $userId, string $reactionType): array
    {
        $type = TimelineReactionHelper::normalize($reactionType);
        $sql = 'SELECT id, reaction_type FROM adms_timeline_likes WHERE post_id = :p AND user_id = :u LIMIT 1';
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->execute([':p' => $postId, ':u' => $userId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        $currentReaction = null;
        if ($row) {
            $existingType = TimelineReactionHelper::normalize((string)($row['reaction_type'] ?? 'like'));
            if ($existingType === $type) {
                $del = $this->getConnection()->prepare('DELETE FROM adms_timeline_likes WHERE post_id = :p AND user_id = :u');
                $del->execute([':p' => $postId, ':u' => $userId]);
            } else {
                $this->getConnection()->prepare(
                    'UPDATE adms_timeline_likes SET reaction_type = :t WHERE post_id = :p AND user_id = :u'
                )->execute([':t' => $type, ':p' => $postId, ':u' => $userId]);
                $currentReaction = $type;
            }
        } else {
            $this->getConnection()->prepare(
                'INSERT INTO adms_timeline_likes (post_id, user_id, reaction_type, created_at) VALUES (:p, :u, :t, NOW())'
            )->execute([':p' => $postId, ':u' => $userId, ':t' => $type]);
            $currentReaction = $type;
        }

        $cnt = $this->getConnection()->prepare('SELECT COUNT(*) AS c FROM adms_timeline_likes WHERE post_id = :p');
        $cnt->execute([':p' => $postId]);
        $total = (int)($cnt->fetch(PDO::FETCH_ASSOC)['c'] ?? 0);

        $summaryStmt = $this->getConnection()->prepare(
            'SELECT reaction_type, COUNT(*) AS c FROM adms_timeline_likes WHERE post_id = :p GROUP BY reaction_type'
        );
        $summaryStmt->execute([':p' => $postId]);
        $summary = [];
        while ($s = $summaryStmt->fetch(PDO::FETCH_ASSOC)) {
            $tk = TimelineReactionHelper::normalize((string)($s['reaction_type'] ?? 'like'));
            $summary[$tk] = (int)($s['c'] ?? 0);
        }

        return [
            'liked' => $currentReaction !== null,
            'reaction' => $currentReaction,
            'likes_count' => $total,
            'summary' => $summary,
        ];
    }

    /**
     * @return array<int, array{user_id: int, name: string, username: string, reaction_type: string, created_at: string}>
     */
    public function listReactionsForPost(int $postId): array
    {
        $sql = 'SELECT l.user_id, l.reaction_type, l.created_at, u.name, u.username
                FROM adms_timeline_likes l
                INNER JOIN adms_users u ON u.id = l.user_id
                WHERE l.post_id = :p
                ORDER BY l.created_at ASC';
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->execute([':p' => $postId]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        $out = [];
        foreach ($rows as $r) {
            $out[] = [
                'user_id' => (int)($r['user_id'] ?? 0),
                'name' => (string)($r['name'] ?? ''),
                'username' => (string)($r['username'] ?? ''),
                'reaction_type' => TimelineReactionHelper::normalize((string)($r['reaction_type'] ?? 'like')),
                'created_at' => (string)($r['created_at'] ?? ''),
            ];
        }
        return $out;
    }

    public function updatePostContentByAuthor(int $postId, int $authorUserId, string $content): bool
    {
        $content = trim($content);
        if ($content === '') {
            $content = ' ';
        }
        $sql = 'UPDATE adms_timeline_posts SET content = :c, updated_at = NOW(), edited_at = NOW()
                WHERE id = :id AND user_id = :uid AND status = "active"';
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->execute([
            ':c' => $content,
            ':id' => $postId,
            ':uid' => $authorUserId,
        ]);
        return $stmt->rowCount() > 0;
    }

    public function addComment(int $postId, int $userId, string $content): int
    {
        $content = trim($content);
        if ($content === '') {
            return 0;
        }
        $sql = 'INSERT INTO adms_timeline_comments (post_id, user_id, content, status, created_at)
                VALUES (:p, :u, :c, "active", NOW())';
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->execute([':p' => $postId, ':u' => $userId, ':c' => mb_substr($content, 0, 2000)]);
        return (int)$this->getConnection()->lastInsertId();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function getCommentsForPost(int $postId, int $limit = 50, int $currentUserId = 0): array
    {
        $sql = 'SELECT c.*, u.name AS author_name, u.image AS author_image
                FROM adms_timeline_comments c
                INNER JOIN adms_users u ON u.id = c.user_id
                WHERE c.post_id = :p AND c.status = "active"
                ORDER BY c.created_at ASC
                LIMIT ' . (int)$limit;
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':p', $postId, PDO::PARAM_INT);
        $stmt->execute();
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        if ($rows === []) {
            return [];
        }
        $commentIds = array_values(array_unique(array_filter(array_map(static fn ($r) => (int)($r['id'] ?? 0), $rows), static fn ($v) => $v > 0)));
        $summaryMap = $this->getCommentReactionSummariesByCommentIds($commentIds);
        $myReactionMap = $currentUserId > 0 ? $this->getUserCommentReactionMap($currentUserId, $commentIds) : [];
        foreach ($rows as &$row) {
            $cid = (int)($row['id'] ?? 0);
            $summary = $summaryMap[$cid] ?? [];
            $row['reaction_summary'] = $summary;
            $row['reactions_count'] = array_sum($summary);
            $row['my_reaction'] = $myReactionMap[$cid] ?? null;
        }
        unset($row);
        return $rows;
    }

    public function getCommentById(int $commentId): ?array
    {
        // Não usar "p.id AS post_id": em alguns drivers c.post_id pode ser sobrescrito
        // por p.id em arrays associativos, gerando link errado em notificações.
        $stmt = $this->getConnection()->prepare(
            'SELECT c.*, p.status AS post_status
             FROM adms_timeline_comments c
             INNER JOIN adms_timeline_posts p ON p.id = c.post_id
             WHERE c.id = :id
             LIMIT 1'
        );
        $stmt->execute([':id' => $commentId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    /**
     * @param array<int> $commentIds
     * @return array<int, string>
     */
    public function getUserCommentReactionMap(int $userId, array $commentIds): array
    {
        if ($userId <= 0) {
            return [];
        }
        $commentIds = array_values(array_unique(array_filter(array_map('intval', $commentIds), static fn ($v) => $v > 0)));
        if ($commentIds === []) {
            return [];
        }
        $placeholders = implode(',', array_fill(0, count($commentIds), '?'));
        $sql = "SELECT comment_id, reaction_type
                FROM adms_timeline_comment_likes
                WHERE user_id = ? AND comment_id IN ($placeholders)";
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->execute(array_merge([$userId], $commentIds));
        $map = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $map[(int)($row['comment_id'] ?? 0)] = TimelineReactionHelper::normalize((string)($row['reaction_type'] ?? 'like'));
        }
        return $map;
    }

    /**
     * @param array<int> $commentIds
     * @return array<int, array<string, int>>
     */
    public function getCommentReactionSummariesByCommentIds(array $commentIds): array
    {
        $commentIds = array_values(array_unique(array_filter(array_map('intval', $commentIds), static fn ($v) => $v > 0)));
        if ($commentIds === []) {
            return [];
        }
        $placeholders = implode(',', array_fill(0, count($commentIds), '?'));
        $sql = "SELECT comment_id, reaction_type, COUNT(*) AS qty
                FROM adms_timeline_comment_likes
                WHERE comment_id IN ($placeholders)
                GROUP BY comment_id, reaction_type";
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->execute($commentIds);
        $out = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $cid = (int)($row['comment_id'] ?? 0);
            $type = TimelineReactionHelper::normalize((string)($row['reaction_type'] ?? 'like'));
            if (!isset($out[$cid])) {
                $out[$cid] = [];
            }
            $out[$cid][$type] = (int)($row['qty'] ?? 0);
        }
        return $out;
    }

    public function setCommentReaction(int $commentId, int $userId, string $reactionType): array
    {
        $commentId = (int)$commentId;
        $userId = (int)$userId;
        $type = TimelineReactionHelper::normalize($reactionType);

        $sel = $this->getConnection()->prepare(
            'SELECT id, reaction_type FROM adms_timeline_comment_likes WHERE comment_id = :c AND user_id = :u LIMIT 1'
        );
        $sel->execute([':c' => $commentId, ':u' => $userId]);
        $row = $sel->fetch(PDO::FETCH_ASSOC);
        $liked = true;
        if ($row) {
            $existingType = TimelineReactionHelper::normalize((string)($row['reaction_type'] ?? 'like'));
            if ($existingType === $type) {
                $del = $this->getConnection()->prepare('DELETE FROM adms_timeline_comment_likes WHERE id = :id');
                $del->execute([':id' => (int)$row['id']]);
                $liked = false;
                $type = null;
            } else {
                $upd = $this->getConnection()->prepare(
                    'UPDATE adms_timeline_comment_likes SET reaction_type = :t, updated_at = NOW() WHERE id = :id'
                );
                $upd->execute([':t' => $type, ':id' => (int)$row['id']]);
            }
        } else {
            $ins = $this->getConnection()->prepare(
                'INSERT INTO adms_timeline_comment_likes (comment_id, user_id, reaction_type, created_at, updated_at)
                 VALUES (:c, :u, :t, NOW(), NOW())'
            );
            $ins->execute([':c' => $commentId, ':u' => $userId, ':t' => $type]);
        }

        $summaryMap = $this->getCommentReactionSummariesByCommentIds([$commentId]);
        $summary = $summaryMap[$commentId] ?? [];
        return [
            'liked' => $liked,
            'reaction' => $liked ? $type : null,
            'likes_count' => array_sum($summary),
            'summary' => $summary,
        ];
    }

    /**
     * @return array<int, array{user_id: int, name: string, username: string, reaction_type: string, created_at: string}>
     */
    public function listReactionsForComment(int $commentId): array
    {
        $sql = 'SELECT l.user_id, l.reaction_type, l.created_at, u.name, u.username
                FROM adms_timeline_comment_likes l
                INNER JOIN adms_users u ON u.id = l.user_id
                WHERE l.comment_id = :c
                ORDER BY l.created_at ASC';
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->execute([':c' => $commentId]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        $out = [];
        foreach ($rows as $r) {
            $out[] = [
                'user_id' => (int)($r['user_id'] ?? 0),
                'name' => (string)($r['name'] ?? ''),
                'username' => (string)($r['username'] ?? ''),
                'reaction_type' => TimelineReactionHelper::normalize((string)($r['reaction_type'] ?? 'like')),
                'created_at' => (string)($r['created_at'] ?? ''),
            ];
        }
        return $out;
    }

    public function createReport(int $postId, int $reporterId, string $reason, ?string $details): int
    {
        $sql = 'INSERT INTO adms_timeline_reports (post_id, reporter_user_id, reason, details, status, created_at)
                VALUES (:p, :r, :reason, :det, "open", NOW())';
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->execute([
            ':p' => $postId,
            ':r' => $reporterId,
            ':reason' => mb_substr($reason, 0, 120),
            ':det' => $details !== null ? mb_substr($details, 0, 5000) : null,
        ]);
        return (int)$this->getConnection()->lastInsertId();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function listOpenReports(int $limit = 100): array
    {
        $sql = 'SELECT r.*, p.content AS post_excerpt, u.name AS reporter_name
                FROM adms_timeline_reports r
                INNER JOIN adms_timeline_posts p ON p.id = r.post_id
                INNER JOIN adms_users u ON u.id = r.reporter_user_id
                WHERE r.status = "open"
                ORDER BY r.created_at DESC
                LIMIT ' . (int)$limit;
        return $this->getConnection()->query($sql)->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public function hidePost(int $postId, int $moderatorId, string $reason): bool
    {
        $sql = 'UPDATE adms_timeline_posts SET status = "hidden", hidden_by = :m, hidden_reason = :rsn, updated_at = NOW() WHERE id = :id AND status = "active"';
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->execute([':m' => $moderatorId, ':rsn' => mb_substr($reason, 0, 255), ':id' => $postId]);
        return $stmt->rowCount() > 0;
    }

    public function markReportReviewed(int $reportId, int $moderatorId): bool
    {
        $sql = 'UPDATE adms_timeline_reports SET status = "reviewed", reviewed_by = :m, reviewed_at = NOW() WHERE id = :id';
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->execute([':m' => $moderatorId, ':id' => $reportId]);
        return $stmt->rowCount() > 0;
    }

    public function getPostById(int $postId): ?array
    {
        $sql = 'SELECT * FROM adms_timeline_posts WHERE id = :id LIMIT 1';
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->execute([':id' => $postId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    /**
     * @param array<int> $postIds
     * @return array<int, array<string, mixed>>
     */
    public function getPostsWithAuthorByIds(array $postIds): array
    {
        $postIds = array_values(array_unique(array_filter(array_map('intval', $postIds), static fn ($v) => $v > 0)));
        if ($postIds === []) {
            return [];
        }
        $placeholders = implode(',', array_fill(0, count($postIds), '?'));
        $sql = "SELECT p.*, u.name AS author_name, u.image AS author_image
                FROM adms_timeline_posts p
                INNER JOIN adms_users u ON u.id = p.user_id
                WHERE p.id IN ($placeholders)";
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->execute($postIds);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        $out = [];
        foreach ($rows as $row) {
            $out[(int)($row['id'] ?? 0)] = $row;
        }

        return $out;
    }

    /**
     * @param array<int> $postIds
     * @return array<int, array<int, string>> post_id => [image_path,...]
     */
    public function getPostImagesByPostIds(array $postIds): array
    {
        $postIds = array_values(array_unique(array_filter(array_map('intval', $postIds), static fn ($v) => $v > 0)));
        if ($postIds === []) {
            return [];
        }

        $placeholders = implode(',', array_fill(0, count($postIds), '?'));
        $sql = 'SELECT post_id, image_path
                FROM adms_timeline_post_images
                WHERE post_id IN (' . $placeholders . ')
                ORDER BY sort_order ASC, id ASC';
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->execute($postIds);

        $out = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $pid = (int)($row['post_id'] ?? 0);
            if ($pid <= 0) continue;
            if (!isset($out[$pid])) $out[$pid] = [];
            $p = (string)($row['image_path'] ?? '');
            if ($p !== '') $out[$pid][] = $p;
        }
        return $out;
    }

    /**
     * @return array<int, string>
     */
    public function getPostImagesByPostId(int $postId): array
    {
        $map = $this->getPostImagesByPostIds([$postId]);
        return $map[$postId] ?? [];
    }

    public function syncPostTags(int $postId, string $content): void
    {
        if ($postId <= 0) {
            return;
        }
        $tags = TimelineHashtagHelper::extractNormalizedTags($content);
        $pdo = $this->getConnection();
        $pdo->prepare('DELETE FROM adms_timeline_post_tags WHERE post_id = :p')
            ->execute([':p' => $postId]);
        if ($tags === []) {
            return;
        }

        $insTag = $pdo->prepare(
            'INSERT INTO adms_timeline_tags (tag, created_at)
             VALUES (:tag, NOW())
             ON DUPLICATE KEY UPDATE id = LAST_INSERT_ID(id)'
        );
        $insLink = $pdo->prepare(
            'INSERT IGNORE INTO adms_timeline_post_tags (post_id, tag_id, created_at)
             VALUES (:p, :t, NOW())'
        );
        foreach ($tags as $tag) {
            $insTag->execute([':tag' => $tag]);
            $tagId = (int) $pdo->lastInsertId();
            if ($tagId <= 0) {
                continue;
            }
            $insLink->execute([':p' => $postId, ':t' => $tagId]);
        }
    }

    /**
     * @param array<int> $postIds
     * @return array<int, array<string, mixed>>
     */
    public function getPollsByPostIds(array $postIds, int $currentUserId = 0): array
    {
        $postIds = array_values(array_unique(array_filter(array_map('intval', $postIds), static fn ($v) => $v > 0)));
        if ($postIds === []) {
            return [];
        }
        $placeholders = implode(',', array_fill(0, count($postIds), '?'));
        $sql = "SELECT p.id AS poll_id, p.post_id, p.question, p.starts_at, p.ends_at,
                       o.id AS option_id, o.option_text, o.sort_order
                FROM adms_timeline_polls p
                INNER JOIN adms_timeline_poll_options o ON o.poll_id = p.id
                WHERE p.post_id IN ($placeholders)
                ORDER BY p.id ASC, o.sort_order ASC, o.id ASC";
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->execute($postIds);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        if ($rows === []) {
            return [];
        }

        $pollIds = [];
        $out = [];
        foreach ($rows as $r) {
            $postId = (int)($r['post_id'] ?? 0);
            $pollId = (int)($r['poll_id'] ?? 0);
            $optionId = (int)($r['option_id'] ?? 0);
            if ($postId <= 0 || $pollId <= 0 || $optionId <= 0) {
                continue;
            }
            $pollIds[] = $pollId;
            if (!isset($out[$postId])) {
                $out[$postId] = [
                    'poll_id' => $pollId,
                    'question' => (string)($r['question'] ?? ''),
                    'starts_at' => (string)($r['starts_at'] ?? ''),
                    'ends_at' => (string)($r['ends_at'] ?? ''),
                    'options' => [],
                    'total_votes' => 0,
                    'status' => 'open',
                    'user_vote_option_id' => 0,
                ];
            }
            $out[$postId]['options'][] = [
                'id' => $optionId,
                'text' => (string)($r['option_text'] ?? ''),
                'sort_order' => (int)($r['sort_order'] ?? 0),
                'votes' => 0,
                'percent' => 0,
                'voters' => [],
            ];
        }

        $pollIds = array_values(array_unique($pollIds));
        $pl = implode(',', array_fill(0, count($pollIds), '?'));
        $cntStmt = $this->getConnection()->prepare(
            "SELECT option_id, COUNT(*) AS c FROM adms_timeline_poll_votes WHERE poll_id IN ($pl) GROUP BY option_id"
        );
        $cntStmt->execute($pollIds);
        $countByOption = [];
        while ($r = $cntStmt->fetch(PDO::FETCH_ASSOC)) {
            $countByOption[(int)($r['option_id'] ?? 0)] = (int)($r['c'] ?? 0);
        }

        $votersStmt = $this->getConnection()->prepare(
            "SELECT v.option_id, u.id AS user_id, u.name, u.username
             FROM adms_timeline_poll_votes v
             INNER JOIN adms_users u ON u.id = v.user_id
             WHERE v.poll_id IN ($pl)
             ORDER BY v.option_id ASC, v.created_at ASC"
        );
        $votersStmt->execute($pollIds);
        $votersByOption = [];
        while ($r = $votersStmt->fetch(PDO::FETCH_ASSOC)) {
            $oid = (int)($r['option_id'] ?? 0);
            if ($oid <= 0) {
                continue;
            }
            $votersByOption[$oid][] = [
                'user_id' => (int)($r['user_id'] ?? 0),
                'name' => (string)($r['name'] ?? ''),
                'username' => (string)($r['username'] ?? ''),
            ];
        }

        $voteByPoll = [];
        if ($currentUserId > 0) {
            $uvStmt = $this->getConnection()->prepare(
                "SELECT poll_id, option_id FROM adms_timeline_poll_votes WHERE user_id = ? AND poll_id IN ($pl)"
            );
            $uvStmt->execute(array_merge([$currentUserId], $pollIds));
            while ($r = $uvStmt->fetch(PDO::FETCH_ASSOC)) {
                $voteByPoll[(int)($r['poll_id'] ?? 0)] = (int)($r['option_id'] ?? 0);
            }
        }

        $now = strtotime(date('Y-m-d H:i:s'));
        foreach ($out as &$poll) {
            $starts = $poll['starts_at'] !== '' ? strtotime((string)$poll['starts_at']) : null;
            $ends = $poll['ends_at'] !== '' ? strtotime((string)$poll['ends_at']) : null;
            if ($starts !== null && $starts > $now) {
                $poll['status'] = 'scheduled';
            } elseif ($ends !== null && $ends <= $now) {
                $poll['status'] = 'closed';
            } else {
                $poll['status'] = 'open';
            }

            $total = 0;
            foreach ($poll['options'] as &$opt) {
                $votes = $countByOption[(int)$opt['id']] ?? 0;
                $opt['votes'] = $votes;
                $opt['voters'] = $votersByOption[(int)$opt['id']] ?? [];
                $total += $votes;
            }
            unset($opt);
            $poll['total_votes'] = $total;
            foreach ($poll['options'] as &$opt) {
                $opt['percent'] = $total > 0 ? (int) round(($opt['votes'] / $total) * 100) : 0;
            }
            unset($opt);

            $poll['user_vote_option_id'] = $voteByPoll[(int)$poll['poll_id']] ?? 0;
        }
        unset($poll);

        return $out;
    }

    public function voteOnPollForPost(int $postId, int $userId, int $optionId): ?array
    {
        $postId = (int)$postId;
        $userId = (int)$userId;
        $optionId = (int)$optionId;
        if ($postId <= 0 || $userId <= 0 || $optionId <= 0) {
            return null;
        }
        $pollStmt = $this->getConnection()->prepare('SELECT id, starts_at, ends_at FROM adms_timeline_polls WHERE post_id = :p LIMIT 1');
        $pollStmt->execute([':p' => $postId]);
        $poll = $pollStmt->fetch(PDO::FETCH_ASSOC);
        if (!$poll) {
            return null;
        }
        $pollId = (int)($poll['id'] ?? 0);
        if ($pollId <= 0) {
            return null;
        }
        $now = strtotime(date('Y-m-d H:i:s'));
        $starts = !empty($poll['starts_at']) ? strtotime((string)$poll['starts_at']) : null;
        $ends = !empty($poll['ends_at']) ? strtotime((string)$poll['ends_at']) : null;
        if (($starts !== null && $starts > $now) || ($ends !== null && $ends <= $now)) {
            return $this->getPollsByPostIds([$postId], $userId)[$postId] ?? null;
        }

        $optStmt = $this->getConnection()->prepare('SELECT id FROM adms_timeline_poll_options WHERE id = :o AND poll_id = :p LIMIT 1');
        $optStmt->execute([':o' => $optionId, ':p' => $pollId]);
        if (!$optStmt->fetch(PDO::FETCH_ASSOC)) {
            return null;
        }

        $upsert = $this->getConnection()->prepare(
            'INSERT INTO adms_timeline_poll_votes (poll_id, option_id, user_id, created_at)
             VALUES (:p, :o, :u, NOW())
             ON DUPLICATE KEY UPDATE option_id = VALUES(option_id)'
        );
        $upsert->execute([':p' => $pollId, ':o' => $optionId, ':u' => $userId]);

        return $this->getPollsByPostIds([$postId], $userId)[$postId] ?? null;
    }

    public function deletePostByAuthor(int $postId, int $authorUserId): bool
    {
        $sqlPost = 'DELETE FROM adms_timeline_posts WHERE id = :id AND user_id = :uid AND status = "active"';

        // Apagar na ordem certa para não deixar registros órfãos.
        $sqlDelMentions = 'DELETE FROM adms_timeline_mentions WHERE entity_type = "post" AND entity_id = :id';
        $sqlDelLikes = 'DELETE FROM adms_timeline_likes WHERE post_id = :id';
        $sqlDelComments = 'DELETE FROM adms_timeline_comments WHERE post_id = :id';
        $sqlDelReports = 'DELETE FROM adms_timeline_reports WHERE post_id = :id';
        $sqlDelImages = 'DELETE FROM adms_timeline_post_images WHERE post_id = :id';

        $pdo = $this->getConnection();
        try {
            $pdo->beginTransaction();

            (new GamificationLedgerRepository())->deleteLedgerRowsForTimelinePost($postId);

            $pdo->prepare($sqlDelMentions)->execute([':id' => $postId]);
            $pdo->prepare($sqlDelLikes)->execute([':id' => $postId]);
            $pdo->prepare($sqlDelComments)->execute([':id' => $postId]);
            $pdo->prepare($sqlDelReports)->execute([':id' => $postId]);
            $pdo->prepare($sqlDelImages)->execute([':id' => $postId]);

            $stmtPost = $pdo->prepare($sqlPost);
            $stmtPost->execute([':id' => $postId, ':uid' => $authorUserId]);
            $deleted = $stmtPost->rowCount() > 0;

            $pdo->commit();
            return $deleted;
        } catch (\Throwable $e) {
            try { $pdo->rollBack(); } catch (\Throwable $ignore) {}
            return false;
        }
    }

    public function deletePostByModerator(int $postId, int $moderatorUserId): bool
    {
        if ($moderatorUserId <= 0) {
            return false;
        }
        $sqlPost = 'DELETE FROM adms_timeline_posts WHERE id = :id AND status = "active"';

        $sqlDelMentions = 'DELETE FROM adms_timeline_mentions WHERE entity_type = "post" AND entity_id = :id';
        $sqlDelLikes = 'DELETE FROM adms_timeline_likes WHERE post_id = :id';
        $sqlDelComments = 'DELETE FROM adms_timeline_comments WHERE post_id = :id';
        $sqlDelReports = 'DELETE FROM adms_timeline_reports WHERE post_id = :id';
        $sqlDelImages = 'DELETE FROM adms_timeline_post_images WHERE post_id = :id';

        $pdo = $this->getConnection();
        try {
            $pdo->beginTransaction();

            (new GamificationLedgerRepository())->deleteLedgerRowsForTimelinePost($postId);

            $pdo->prepare($sqlDelMentions)->execute([':id' => $postId]);
            $pdo->prepare($sqlDelLikes)->execute([':id' => $postId]);
            $pdo->prepare($sqlDelComments)->execute([':id' => $postId]);
            $pdo->prepare($sqlDelReports)->execute([':id' => $postId]);
            $pdo->prepare($sqlDelImages)->execute([':id' => $postId]);

            $stmtPost = $pdo->prepare($sqlPost);
            $stmtPost->execute([':id' => $postId]);
            $deleted = $stmtPost->rowCount() > 0;

            $pdo->commit();
            return $deleted;
        } catch (\Throwable) {
            try { $pdo->rollBack(); } catch (\Throwable $ignore) {}
            return false;
        }
    }
}
