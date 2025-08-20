<?php
if (!defined('C8L6K7E')) {
    header("Location: /");
    die("Erro: Página não encontrada<br>");
}
?>

<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Criar Dados de Teste para Notificações</h3>
                </div>
                <div class="card-body">
                    <div class="alert alert-warning">
                        <h5><i class="fas fa-exclamation-triangle"></i> Atenção</h5>
                        <p>Esta ferramenta cria dados de teste para simular treinamentos vencidos e próximos do vencimento.</p>
                        <ul>
                            <li>Cria vínculos de treinamentos para usuários existentes</li>
                            <li>Gera aplicações vencidas (30 dias atrás)</li>
                            <li>Gera aplicações próximas do vencimento (15 dias atrás)</li>
                            <li>Atualiza status para 'vencido' e 'proximo_vencimento'</li>
                        </ul>
                    </div>

                    <form method="POST" action="<?= $_ENV['URL_ADM'] ?>create-test-data/create">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="card">
                                    <div class="card-header">
                                        <h5>Dados que serão criados</h5>
                                    </div>
                                    <div class="card-body">
                                        <ul>
                                            <li><strong>Vínculos:</strong> Todos os usuários × Todos os treinamentos</li>
                                            <li><strong>Aplicações vencidas:</strong> 30 dias atrás</li>
                                            <li><strong>Aplicações próximas:</strong> 15 dias atrás</li>
                                            <li><strong>Status:</strong> 'vencido' e 'proximo_vencimento'</li>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="card">
                                    <div class="card-header">
                                        <h5>Ações</h5>
                                    </div>
                                    <div class="card-body">
                                        <button type="submit" class="btn btn-warning btn-lg" onclick="return confirm('Tem certeza que deseja criar dados de teste?')">
                                            <i class="fas fa-database"></i> Criar Dados de Teste
                                        </button>
                                        <p class="mt-3 text-muted">
                                            <small>Após criar os dados, você poderá testar as notificações.</small>
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </form>

                    <div class="mt-4">
                        <h5>Como testar após criar os dados:</h5>
                        <div class="alert alert-info">
                            <ol>
                                <li><strong>Configure o Gmail</strong> no arquivo .env</li>
                                <li><strong>Execute via CLI:</strong> 
                                    <code>php app/adms/Controllers/Services/NotificacaoTreinamentosCli.php</code>
                                </li>
                                <li><strong>Ou via Web:</strong> Acesse a página de teste de notificações</li>
                                <li><strong>Verifique os e-mails</strong> enviados para os usuários</li>
                            </ol>
                        </div>
                    </div>

                    <div class="mt-4">
                        <h5>Configuração Gmail Rápida:</h5>
                        <div class="alert alert-success">
                            <p><strong>Edite o arquivo .env:</strong></p>
                            <pre>MAIL_HOST=smtp.gmail.com
MAIL_USERNAME=seu-email@gmail.com
MAIL_PASSWORD=sua-senha-de-app
MAIL_PORT=587
MAIL_ENCRYPTI=TLS</pre>
                            <p><strong>Passos:</strong></p>
                            <ol>
                                <li>Ative verificação em duas etapas no Google</li>
                                <li>Gere senha de app em "Segurança" > "Senhas de app"</li>
                                <li>Use a senha de 16 caracteres gerada</li>
                            </ol>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div> 