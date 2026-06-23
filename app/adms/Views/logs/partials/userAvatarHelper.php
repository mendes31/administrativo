<?php
if (!isset($renderUserAvatar) || !is_callable($renderUserAvatar)) {
    $renderUserAvatar = static function (array $row, int $sizePx): string {
        $uid = (int) ($row['user_id'] ?? 0);
        $name = (string) ($row['user_name'] ?? '—');
        $img = (string) ($row['user_image'] ?? '');
        $hasCustom = \App\adms\Helpers\ImageHelper::userImageExists($uid, $img);
        $style = sprintf('width:%dpx;height:%dpx;object-fit:cover;', $sizePx, $sizePx);
        if ($hasCustom) {
            return \App\adms\Helpers\ImageHelper::displayImage(
                'users/' . $uid . '/' . $img,
                [
                    'class' => 'rounded-circle flex-shrink-0 connected-user-thumb',
                    'style' => $style,
                    'alt' => 'Foto de ' . $name,
                ],
                'icon_user.png',
                'users'
            );
        }

        return \App\adms\Helpers\ImageHelper::renderInitialsAvatar($name, $sizePx, [
            'class' => 'flex-shrink-0 connected-user-thumb',
        ]);
    };
}
