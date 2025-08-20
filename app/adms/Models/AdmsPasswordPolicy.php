<?php

namespace App\adms\Models;

class AdmsPasswordPolicy
{
    public int $id;
    public int $vencimento_dias;
    public int $comprimento_minimo;
    public int $min_maiusculas;
    public int $min_minusculas;
    public int $min_digitos;
    public int $min_nao_alfanumericos;
    public int $historico_senhas;
    public int $tentativas_bloqueio;
    public int $tentativas_bloqueio_temporario;
    public int $tempo_bloqueio_temporario;
    public string $created_at;
    public string $updated_at;
    public string $nivel_seguranca;
    public string $exemplo_senha;
    public string $bloqueio_temporario;
    public string $notificar_usuario_bloqueio;
    public string $notificar_admins_bloqueio;
    public string $forcar_logout_troca_senha;
    public string $expirar_sessao_por_tempo;
    public int $tempo_expiracao_sessao;
} 