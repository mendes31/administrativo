#!/bin/bash
# Script para renomear o arquivo TrainingKPIDashboard.php na produção
# Execute este script a partir do diretório raiz do projeto

# Ir para o diretório raiz do projeto (ajuste o caminho se necessário)
cd /home/administrativotiaraju/www/administrativo

# Verificar se o arquivo existe antes de renomear
if [ -f "app/adms/Controllers/trainings/TrainingKPIDashboard.php" ]; then
    echo "Renomeando arquivo..."
    mv app/adms/Controllers/trainings/TrainingKPIDashboard.php app/adms/Controllers/trainings/TrainingKpiDashboard.php
    echo "Arquivo renomeado com sucesso!"
    
    # Regenerar autoloader do Composer
    echo "Regenerando autoloader do Composer..."
    composer dump-autoload
    
    echo "Concluído!"
else
    echo "Arquivo não encontrado: app/adms/Controllers/trainings/TrainingKPIDashboard.php"
    echo "Verificando se já foi renomeado..."
    if [ -f "app/adms/Controllers/trainings/TrainingKpiDashboard.php" ]; then
        echo "O arquivo já está com o nome correto: TrainingKpiDashboard.php"
    else
        echo "Arquivo não encontrado. Verifique o caminho."
    fi
fi

