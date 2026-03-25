<?php

namespace App\adms\Models\Repository;

use App\adms\Models\Services\DbConnection;
use PDO;

class TimelineRepository extends DbConnection
{
    public function createPost(int $userId, string $content, ?string $imagePath, ?string $videoPath = null): int
    {
        $sql = 'INSERT INTO adms_timeline_posts (user_id, content, image_path, video_path, status, created_at, updated_at)
                VALUES (:uid, :content, :img, :vid, "active", NOW(), NOW())';
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':uid', $userId, PDO::PARAM_INT);
        $stmt->bindValue(':content', $content, PDO::PARAM_STR);
        $stmt->bindValue(':img', $imagePath, $imagePath !== null ? PDO::PARAM_STR : PDO::PARAM_NULL);
        $stmt->bindValue(':vid', $videoPath, $videoPath !== null ? PDO::PARAM_STR : PDO::PARAM_NULL);
        $stmt->execute();
        return (int)$this->getConnection()->lastInsertId();
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
     * @return array<int, bool> post_id => liked
     */
    public function getUserLikedMap(int $userId, array $postIds): array
    {
        if ($userId <= 0 || $postIds === []) {
            return [];
        }
        $placeholders = implode(',', array_fill(0, count($postIds), '?'));
        $sql = "SELECT post_id FROM adms_timeline_likes WHERE user_id = ? AND post_id IN ($placeholders)";
        $params = array_merge([$userId], $postIds);
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->execute($params);
        $map = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $map[(int)$row['post_id']] = true;
        }
        return $map;
    }

    public function toggleLike(int $postId, int $userId): array
    {
        $sql = 'SELECT id FROM adms_timeline_likes WHERE post_id = :p AND user_id = :u LIMIT 1';
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->execute([':p' => $postId, ':u' => $userId]);
        $exists = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($exists) {
            $del = $this->getConnection()->prepare('DELETE FROM adms_timeline_likes WHERE post_id = :p AND user_id = :u');
            $del->execute([':p' => $postId, ':u' => $userId]);
            $liked = false;
        } else {
            $ins = $this->getConnection()->prepare(
                'INSERT INTO adms_timeline_likes (post_id, user_id, created_at) VALUES (:p, :u, NOW())'
            );
            $ins->execute([':p' => $postId, ':u' => $userId]);
            $liked = true;
        }
        $cnt = $this->getConnection()->prepare('SELECT COUNT(*) AS c FROM adms_timeline_likes WHERE post_id = :p');
        $cnt->execute([':p' => $postId]);
        $row = $cnt->fetch(PDO::FETCH_ASSOC);
        return ['liked' => $liked, 'likes_count' => (int)($row['c'] ?? 0)];
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
}
