<?php if (!empty($_SESSION['msg'])): ?>
    <div class="alert alert-<?= $_SESSION['msg_type'] ?? 'info' ?>">
        <?= $_SESSION['msg'] ?>
    </div>
    <?php unset($_SESSION['msg'], $_SESSION['msg_type']); ?>
<?php endif; ?>

<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Teste de Notificações de Treinamentos</h3>
                </div>
                <div class="card-body">
                    <div class="alert alert-info">
                        <h5><i class="fas fa-info-circle"></i> Informações</h5>
                        <p>Esta página permite testar o sistema de notificações automáticas de treinamentos.</p>
                        <ul>
                            <li>O sistema buscará treinamentos vencidos e próximos do vencimento</li>
                            <li>E-mails serão enviados para os colaboradores com pendências</li>
                            <li>Certifique-se de que as configurações de e-mail estão corretas no arquivo .env</li>
                        </ul>
                    </div>

                    <form method="POST" action="<?= $_ENV['URL_ADM'] ?>send-notification">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="card">
                                    <div class="card-header">
                                        <h5>Configurações de E-mail</h5>
                                    </div>
                                    <div class="card-body">
                                        <p><strong>Host:</strong> <?= $_ENV['MAIL_HOST'] ?? 'Não configurado' ?></p>
                                        <p><strong>Porta:</strong> <?= $_ENV['MAIL_PORT'] ?? 'Não configurado' ?></p>
                                        <p><strong>Usuário:</strong> <?= $_ENV['MAIL_USERNAME'] ?? 'Não configurado' ?></p>
                                        <p><strong>Criptografia:</strong> <?= $_ENV['MAIL_ENCRYPTI'] ?? 'Não configurado' ?></p>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="card">
                                    <div class="card-header">
                                        <h5>Ações</h5>
                                    </div>
                                    <div class="card-body">
                                        <button type="submit" class="btn btn-primary btn-lg">
                                            <i class="fas fa-paper-plane"></i> Enviar Notificações
                                        </button>
                                        <p class="mt-3 text-muted">
                                            <small>Clique no botão acima para disparar as notificações de treinamentos pendentes.</small>
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </form>

                    <div class="mt-4">
                        <h5>Logs de Notificação</h5>
                        <div class="alert alert-warning">
                            <p><strong>Para verificar os logs:</strong></p>
                            <ul>
                                <li>Verifique o arquivo de log do sistema</li>
                                <li>Os e-mails enviados são registrados automaticamente</li>
                                <li>Erros de envio também são logados</li>
                            </ul>
                        </div>
                    </div>

                    <div class="mt-4">
                        <h5>Configuração para Gmail</h5>
                        <div class="alert alert-success">
                            <p><strong>Para usar Gmail:</strong></p>
                            <ol>
                                <li>Ative a verificação em duas etapas na sua conta Google</li>
                                <li>Gere uma senha de app específica para este sistema</li>
                                <li>Configure no arquivo .env:
                                    <pre>MAIL_HOST=smtp.gmail.com
MAIL_USERNAME=seu-email@gmail.com
MAIL_PASSWORD=sua-senha-de-app
MAIL_PORT=587
MAIL_ENCRYPTI=TLS</pre>
                                </li>
                            </ol>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div> 