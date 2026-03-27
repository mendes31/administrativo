<?php

namespace App\adms\Models\Repository;

use App\adms\Helpers\TimelineReactionHelper;
use App\adms\Models\Services\DbConnection;
use PDO;

class TimelineRepository extends DbConnection
{
    /**
     * @param array<int, string>|null $imagePaths
     */
    public function createPost(int $userId, string $content, ?array $imagePaths, ?string $videoPath = null): int
    {
        $sql = 'INSERT INTO adms_timeline_posts (user_id, content, image_path, video_path, status, created_at, updated_at)
                VALUES (:uid, :content, :img, :vid, "active", NOW(), NOW())';
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
    public function getFeedPosts(int $page, int $perPage): array
    {
        $page = max(1, $page);
        $offset = ($page - 1) * $perPage;
        $sql = 'SELECT p.*, u.name AS author_name, u.image AS author_image,
                       (SELECT COUNT(*) FROM adms_timeline_likes l WHERE l.post_id = p.id) AS likes_count,
                       (SELECT COUNT(*) FROM adms_timeline_comments c WHERE c.post_id = p.id AND c.status = "active") AS comments_count
                FROM adms_timeline_posts p
                INNER JOIN adms_users u ON u.id = p.user_id
                WHERE p.status = "active"
                ORDER BY p.created_at DESC
                LIMIT :lim OFFSET :off';
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':lim', $perPage, PDO::PARAM_INT);
        $stmt->bindValue(':off', $offset, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public function countActivePosts(): int
    {
        $stmt = $this->getConnection()->query('SELECT COUNT(*) AS c FROM adms_timeline_posts WHERE status = "active"');
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return (int)($row['c'] ?? 0);
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
     * @return array<int, array{name: string, username: string, reaction_type: string, created_at: string}>
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
    public function getCommentsForPost(int $postId, int $limit = 50): array
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
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
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
}
