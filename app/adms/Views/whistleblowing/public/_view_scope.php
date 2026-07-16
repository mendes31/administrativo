<?php

declare(strict_types=1);

/**
 * Variáveis do canal público — injetadas por {@see \App\adms\Controllers\whistleblowing\CanalDenuncia::render()}.
 *
 * @var string $base_url
 * @var string $url_adm
 * @var string $view
 * @var string $title
 * @var string|null $error
 * @var string $csrf_token
 * @var array<string, mixed> $old
 * @var list<string> $categories
 * @var list<string> $risk_levels
 * @var string $protocol
 * @var string $password
 * @var array<string, mixed> $report
 * @var list<array<string, mixed>> $messages
 * @var list<array<string, mixed>> $attachments
 */
$base_url = isset($base_url) ? (string) $base_url : '';
$url_adm = isset($url_adm) ? (string) $url_adm : '';
$view = isset($view) ? (string) $view : 'home';
$title = isset($title) ? (string) $title : 'Canal de Denúncias';
$error = isset($error) ? $error : null;
$csrf_token = isset($csrf_token) ? (string) $csrf_token : '';
$old = isset($old) && is_array($old) ? $old : [];
$categories = isset($categories) && is_array($categories) ? $categories : [];
$risk_levels = isset($risk_levels) && is_array($risk_levels) ? $risk_levels : [];
$protocol = isset($protocol) ? (string) $protocol : '';
$password = isset($password) ? (string) $password : '';
$report = isset($report) && is_array($report) ? $report : [];
$messages = isset($messages) && is_array($messages) ? $messages : [];
$attachments = isset($attachments) && is_array($attachments) ? $attachments : [];
$captcha_enabled = !empty($captcha_enabled);
$captcha_site_key = isset($captcha_site_key) ? (string) $captcha_site_key : '';
$captcha_provider = isset($captcha_provider) ? (string) $captcha_provider : 'hcaptcha';
