<?php

namespace App\adms\Controllers\trainings;

use App\adms\Models\Repository\UsersRepository;
use App\adms\Models\Repository\PositionsRepository;
use App\adms\Models\Repository\TrainingPositionsRepository;
use App\adms\Models\Repository\TrainingUsersRepository;
use App\adms\Models\Repository\TrainingsRepository;

class TrainingMatrixService
{
    public function updateMatrixForUser(int $userId): void
    {
        $usersRepo = new UsersRepository();
        $trainingPositionsRepo = new TrainingPositionsRepository();
        $trainingUsersRepo = new TrainingUsersRepository();

        $user = $usersRepo->getUser($userId);
        $userPosition = $user['user_position_id']; // cargo único do usuário

        // Treinamentos obrigatórios para o cargo do usuário
        $mandatoryTrainings = $trainingPositionsRepo->getTrainingsByPosition($userPosition);
        $mandatoryTrainings = array_unique($mandatoryTrainings);

        // Remove todos os vínculos que não são mais obrigatórios
        $trainingUsersRepo->deleteByUserAndNotInTrainings($userId, $mandatoryTrainings);

        // Garante que todos os obrigatórios estejam na matriz
        foreach ($mandatoryTrainings as $trainingId) {
            $trainingUsersRepo->insertOrUpdate($userId, $trainingId, 'dentro_do_prazo');
        }
    }

    public function updateMatrixForAllUsers(): void
    {
        $usersRepo = new UsersRepository();
        $users = $usersRepo->getAllUsers();
        foreach ($users as $user) {
            $this->updateMatrixForUser($user['id']);
        }
    }
    
    /**
     * Recria vínculos quando um treinamento é reativado
     */
    public function recreateLinksForReactivatedTraining(int $trainingId): array
    {
        $trainingUsersRepo = new TrainingUsersRepository();
        $trainingPositionsRepo = new TrainingPositionsRepository();
        $usersRepo = new UsersRepository();
        $trainingsRepo = new TrainingsRepository();
        
        $results = [
            'users_added' => 0,
            'users_skipped' => 0,
            'reciclagem_added' => 0
        ];
        
        // Buscar cargos vinculados ao treinamento
        $linkedPositions = $trainingPositionsRepo->getPositionsByTraining($trainingId);
        
        foreach ($linkedPositions as $position) {
            // Buscar usuários com este cargo
            $usersWithPosition = $usersRepo->getUsersByPosition($position['adms_position_id']);
            
            foreach ($usersWithPosition as $user) {
                // Verificar se o usuário já realizou este treinamento
                $lastCompleted = $trainingUsersRepo->getLastCompletedTraining($user['id'], $trainingId);
                
                if (!$lastCompleted) {
                    // Usuário nunca realizou - adicionar à matriz
                    $trainingUsersRepo->insertOrUpdate(
                        $user['id'], 
                        $trainingId, 
                        'dentro_do_prazo', 
                        'cargo', 
                        null, 
                        'reativacao'
                    );
                    $results['users_added']++;
                } else {
                    // Usuário já realizou - verificar se precisa de reciclagem
                    $training = $trainingsRepo->getTraining($trainingId);
                    if ($training['reciclagem'] && $training['reciclagem_periodo']) {
                        // Verificar se a reciclagem está vencida
                        if ($trainingUsersRepo->isReciclagemVencida(
                            $lastCompleted['data_realizacao'], 
                            $training['reciclagem_periodo']
                        )) {
                            // Reciclagem vencida - adicionar novo ciclo
                            $trainingUsersRepo->insertOrUpdate(
                                $user['id'], 
                                $trainingId, 
                                'dentro_do_prazo', 
                                'cargo', 
                                null, 
                                'reciclagem_reativacao'
                            );
                            $results['reciclagem_added']++;
                        } else {
                            $results['users_skipped']++;
                        }
                    } else {
                        $results['users_skipped']++;
                    }
                }
            }
        }
        
        return $results;
    }

    /**
     * Recria vínculos quando um usuário é reativado
     */
    public function recreateLinksForReactivatedUser(int $userId): array
    {
        $trainingUsersRepo = new TrainingUsersRepository();
        $trainingPositionsRepo = new TrainingPositionsRepository();
        $usersRepo = new UsersRepository();
        $trainingsRepo = new TrainingsRepository();
        
        $results = [
            'trainings_added' => 0,
            'trainings_skipped' => 0,
            'reciclagem_added' => 0
        ];
        
        // Buscar dados do usuário
        $user = $usersRepo->getUser($userId);
        if (!$user) {
            return $results;
        }
        
        $userPosition = $user['user_position_id'];
        
        // Buscar treinamentos obrigatórios para o cargo do usuário
        $mandatoryTrainingIds = $trainingPositionsRepo->getTrainingsByPosition($userPosition);
        
        foreach ($mandatoryTrainingIds as $trainingId) {
            
            // Verificar se o usuário já realizou este treinamento
            $lastCompleted = $trainingUsersRepo->getLastCompletedTraining($userId, $trainingId);
            
            if (!$lastCompleted) {
                // Usuário nunca realizou - adicionar à matriz
                $trainingUsersRepo->insertOrUpdate(
                    $userId, 
                    $trainingId, 
                    'dentro_do_prazo', 
                    'cargo', 
                    null, 
                    'reativacao_usuario'
                );
                $results['trainings_added']++;
            } else {
                // Usuário já realizou - verificar se precisa de reciclagem
                $training = $trainingsRepo->getTraining($trainingId);
                if ($training && $training['reciclagem'] && $training['reciclagem_periodo']) {
                    // Verificar se a reciclagem está vencida
                    if ($trainingUsersRepo->isReciclagemVencida(
                        $lastCompleted['data_realizacao'], 
                        $training['reciclagem_periodo']
                    )) {
                        // Reciclagem vencida - adicionar novo ciclo
                        $trainingUsersRepo->insertOrUpdate(
                            $userId, 
                            $trainingId, 
                            'dentro_do_prazo', 
                            'cargo', 
                            null, 
                            'reciclagem_reativacao_usuario'
                        );
                        $results['reciclagem_added']++;
                    } else {
                        $results['trainings_skipped']++;
                    }
                } else {
                    $results['trainings_skipped']++;
                }
            }
        }
        
        return $results;
    }
    
    /**
     * Verifica e corrige vínculos quando um usuário ganha cargo obrigatório
     */
    public function checkAndFixUserLinks(int $userId): array
    {
        $trainingUsersRepo = new TrainingUsersRepository();
        $trainingPositionsRepo = new TrainingPositionsRepository();
        $usersRepo = new UsersRepository();
        
        $results = [
            'converted_to_cargo' => 0,
            'kept_individual' => 0,
            'no_changes' => 0
        ];
        
        $user = $usersRepo->getUser($userId);
        if (!$user) {
            return $results;
        }
        
        $userPosition = $user['user_position_id'];
        
        // Buscar treinamentos obrigatórios para o cargo do usuário
        $mandatoryTrainingIds = $trainingPositionsRepo->getTrainingsByPosition($userPosition);
        
        foreach ($mandatoryTrainingIds as $trainingId) {
            // Verificar se já existe vínculo
            $existingLink = $trainingUsersRepo->getByUserAndTraining($userId, $trainingId);
            
            if ($existingLink) {
                if ($existingLink['tipo_vinculo'] === 'individual') {
                    // Converter vínculo individual para cargo
                    $trainingUsersRepo->insertOrUpdate(
                        $userId, 
                        $trainingId, 
                        $existingLink['status'], 
                        'cargo', 
                        null, 
                        'conversao_individual_para_cargo'
                    );
                    $results['converted_to_cargo']++;
                } else {
                    // Já é vínculo por cargo
                    $results['no_changes']++;
                }
            } else {
                // Criar novo vínculo por cargo
                $trainingUsersRepo->insertOrUpdate(
                    $userId, 
                    $trainingId, 
                    'dentro_do_prazo', 
                    'cargo', 
                    null, 
                    'novo_cargo_obrigatorio'
                );
                $results['converted_to_cargo']++;
            }
        }
        
        return $results;
    }
} 