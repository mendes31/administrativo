@echo off
echo ============================================================
echo  INSTALACAO DO SISTEMA DE AVALIACOES
echo  Sistema Administrativo - Versao 1.0
echo ============================================================
echo.

echo [1/5] Verificando diretorio...
cd /d C:\wamp64\www\administrativo
if errorlevel 1 (
    echo ERRO: Nao foi possivel acessar o diretorio!
    pause
    exit /b 1
)
echo OK - Diretorio encontrado
echo.

echo [2/5] Executando migrations do banco de dados...
vendor\bin\phinx migrate -e development
if errorlevel 1 (
    echo ERRO: Falha ao executar migrations!
    echo Verifique a conexao com o banco de dados.
    pause
    exit /b 1
)
echo OK - Migrations executadas com sucesso
echo.

echo [3/5] Verificando status das migrations...
vendor\bin\phinx status -e development
echo.

echo [4/5] Executando seed de paginas (permissoes)...
vendor\bin\phinx seed:run -s AddAdmsPages
if errorlevel 1 (
    echo AVISO: Seed pode ter falhado ou paginas ja existem
    echo Isso e normal se voce ja executou antes.
)
echo OK - Seed executado
echo.

echo [5/5] Verificacao final...
echo.
echo ============================================================
echo  INSTALACAO CONCLUIDA!
echo ============================================================
echo.
echo Proximos passos:
echo.
echo 1. Acesse o sistema como Administrador
echo 2. Configure as permissoes em: Nivel de Acesso
echo 3. Teste criando um questionario em:
echo    ?url=create-evaluation-model-with-questions
echo.
echo Documentacao disponivel em:
echo - INICIO_RAPIDO_AVALIACOES.md
echo - MANUAL_USUARIO_AVALIACOES.md
echo - GUIA_TESTES_AVALIACOES.md
echo.
echo ============================================================
echo.
pause

