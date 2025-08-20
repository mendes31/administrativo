<?php

namespace App\adms\Models\Repository;

use App\adms\Models\Services\DbConnection;
use PDO;

/**
 * Repository responsável em verificar se existe um registro com dados fornecidos.
 *
 * Esta classe fornece um método para verificar se um valor específico já está presente em uma coluna de uma tabela
 * no banco de dados. Ela pode ser usada para garantir a unicidade de valores, como em validações de formulários.
 *
 * @package App\adms\Models\Repository
 * @author Rafael Mendes
 */
class UniqueValueRepository extends DbConnection
{

    /**
     * Verificar se um valor já existe na tabela.
     *
     * Este método verifica se um valor específico já está presente em uma coluna de uma tabela no banco de dados.
     * Ele retorna `false` se o valor já estiver cadastrado e `true` se não estiver. A função também suporta a
     * exclusão de um registro específico da verificação, útil para cenários de edição.
     *
     * @param string $table Nome da tabela onde a verificação será realizada.
     * @param string $column Nome da coluna onde o valor será pesquisado.
     * @param mixed $value Valor a ser verificado na coluna.
     * @param int|null $except ID do registro a ser excluído da verificação, se aplicável.
     * @return bool `false` se o valor fornecido já estiver cadastrado, `true` caso contrário.
     */
    public function getRecord(string $table, string $column, $value, $except = null): bool
    {
        // QUERY para recuperar o registro do baco de dados
        $sql = "SELECT COUNT(id) as count FROM `{$table}` WHERE `{$column}` = :value";

        // Se houver o ID de exceção, adicionar condição à consulta
        if($except !== null){
            $sql .= " AND `id` != :except";
        }
       

        // Preparar a QUERY
        $stmt = $this->getConnection()->prepare($sql);

        // Substituir os links da QUERY pelo valor
        $stmt->bindParam(':value', $value, PDO::PARAM_STR);
        if($except !== null){
            $stmt->bindParam(':except', $except, PDO::PARAM_INT);
        }

        // Executar a QUERY
        $stmt->execute();

        // Retornar falso se o valor fornecido já estiver cadastrado e verdadeiro caso contrário
        return $stmt->fetchColumn() === 0;
    }
}
