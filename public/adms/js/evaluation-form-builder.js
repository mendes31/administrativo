/**
 * Form Builder para Criação de Questionários de Avaliação
 * Sistema Administrativo - Módulo de Avaliações
 */

let questaoIndex = 0;
let totalPontosGlobal = 0;

document.addEventListener('DOMContentLoaded', function() {
    console.log('JavaScript do Form Builder carregado!');
    
    // Inicializar
    const btnAdicionar = document.getElementById('btnAdicionarQuestao');
    console.log('Botão adicionar encontrado:', !!btnAdicionar);
    
    if (btnAdicionar) {
        btnAdicionar.addEventListener('click', adicionarQuestao);
        
        // Adicionar uma questão inicial SOMENTE se não houver questões existentes
        // (para tela de criação)
        if (typeof questoesExistentes === 'undefined' || !questoesExistentes || questoesExistentes.length === 0) {
            console.log('Adicionando questão inicial vazia (tela de criação)');
            adicionarQuestao();
        } else {
            console.log('Não adicionando questão vazia - existem questões a carregar');
        }
    }

    // Validação do formulário
    const form = document.getElementById('formQuestionarioCompleto');
    console.log('Formulário encontrado:', !!form);
    
    if (form) {
        form.addEventListener('submit', validarFormulario);
        console.log('Event listener de submit adicionado ao formulário');
    }
});

/**
 * Adicionar nova questão ao formulário
 */
function adicionarQuestao() {
    const template = document.getElementById('templateQuestao');
    const container = document.getElementById('questoesContainer');
    
    const clone = template.content.cloneNode(true);
    const questaoCard = clone.querySelector('.questao-card');
    
    // Substituir INDEX pelo número atual
    questaoCard.innerHTML = questaoCard.innerHTML.replace(/INDEX/g, questaoIndex);
    questaoCard.setAttribute('data-questao-index', questaoIndex);
    
    container.appendChild(clone);
    
    // Buscar o card recém-adicionado
    const novoCard = container.querySelector(`[data-questao-index="${questaoIndex}"]`);
    
    // Atualizar número visual
    novoCard.querySelector('.numero').textContent = questaoIndex + 1;
    
    // Configurar eventos
    configurarQuestao(novoCard, questaoIndex);
    
    questaoIndex++;
    atualizarTotalPontos();
    
    // Scroll suave até a nova questão
    novoCard.scrollIntoView({ behavior: 'smooth', block: 'center' });
}

/**
 * Configurar eventos de uma questão
 */
function configurarQuestao(questaoCard, index) {
    const tipoSelect = questaoCard.querySelector('.tipoQuestao');
    const alternativasContainer = questaoCard.querySelector('.alternativas-container');
    const gabaritoVFContainer = questaoCard.querySelector('.gabarito-vf-container');
    const gabaritoTextoContainer = questaoCard.querySelector('.gabarito-texto-container');
    
    // Evento de mudança de tipo
    tipoSelect.addEventListener('change', function() {
        // Esconder todos e remover required de campos escondidos
        alternativasContainer.style.display = 'none';
        gabaritoVFContainer.style.display = 'none';
        gabaritoTextoContainer.style.display = 'none';
        
        // Remover required de alternativas quando escondidas
        const alternativasInputs = alternativasContainer.querySelectorAll('input[type="text"]');
        alternativasInputs.forEach(input => input.removeAttribute('required'));
        
        // Atualizar atributo data-tipo para estilização
        questaoCard.setAttribute('data-tipo', this.value);
        
        switch(this.value) {
            case 'multipla_escolha':
                alternativasContainer.style.display = 'block';
                
                // Adicionar required nas alternativas visíveis
                const alternativasInputsVisiveis = alternativasContainer.querySelectorAll('input[type="text"]');
                alternativasInputsVisiveis.forEach(input => input.setAttribute('required', 'required'));
                
                // Adicionar 4 alternativas padrão se não existem
                const alternativasList = questaoCard.querySelector('.alternativas-list');
                if (alternativasList.children.length === 0) {
                    for (let i = 0; i < 4; i++) {
                        adicionarAlternativa(questaoCard, index, i);
                    }
                }
                break;
                
            case 'verdadeiro_falso':
                gabaritoVFContainer.style.display = 'block';
                break;
                
            case 'texto':
            case 'numerica':
                gabaritoTextoContainer.style.display = 'block';
                break;
        }
    });
    
    // Botão adicionar alternativa
    const btnAdicionarAlt = questaoCard.querySelector('.btnAdicionarAlternativa');
    if (btnAdicionarAlt) {
        btnAdicionarAlt.addEventListener('click', function() {
            const alternativasList = questaoCard.querySelector('.alternativas-list');
            const numAlternativas = alternativasList.children.length;
            adicionarAlternativa(questaoCard, index, numAlternativas);
        });
    }
    
    // Botão remover questão
    const btnRemover = questaoCard.querySelector('.btnRemoverQuestao');
    if (btnRemover) {
        btnRemover.addEventListener('click', function() {
            if (confirm('Deseja realmente remover esta questão?')) {
                questaoCard.remove();
                renumerarQuestoes();
                atualizarTotalPontos();
            }
        });
    }
    
    // Atualizar total de pontos ao mudar
    const inputPontos = questaoCard.querySelector('.inputPontos');
    if (inputPontos) {
        inputPontos.addEventListener('change', atualizarTotalPontos);
    }
    
    // Disparar evento change inicial para definir data-tipo e exibir campos corretos
    if (tipoSelect) {
        tipoSelect.dispatchEvent(new Event('change'));
    }
}

/**
 * Adicionar alternativa a uma questão de múltipla escolha
 */
function adicionarAlternativa(questaoCard, questaoIndex, altIndex) {
    const template = document.getElementById('templateAlternativa');
    const clone = template.content.cloneNode(true);
    
    const alternativaItem = clone.querySelector('.alternativa-item');
    alternativaItem.innerHTML = alternativaItem.innerHTML
        .replace(/INDEX/g, questaoIndex)
        .replace(/ALT_INDEX/g, altIndex);
    
    // Corrigir o value do radio button para usar o índice da alternativa
    const radioBtn = alternativaItem.querySelector('input[type="radio"]');
    if (radioBtn) {
        radioBtn.value = altIndex;
    }
    
    const alternativasList = questaoCard.querySelector('.alternativas-list');
    alternativasList.appendChild(clone);
    
    // Event listener para remover
    const novaAlternativa = alternativasList.lastElementChild;
    const btnRemover = novaAlternativa.querySelector('.btnRemoverAlternativa');
    if (btnRemover) {
        btnRemover.addEventListener('click', function() {
            if (alternativasList.children.length > 2) {
                novaAlternativa.remove();
            } else {
                alert('Mantenha pelo menos 2 alternativas!');
            }
        });
    }
}

/**
 * Renumerar questões após remoção
 */
function renumerarQuestoes() {
    const questoes = document.querySelectorAll('.questao-card');
    questoes.forEach((card, index) => {
        card.querySelector('.numero').textContent = index + 1;
    });
}

/**
 * Atualizar total de pontos
 */
function atualizarTotalPontos() {
    const inputsPontos = document.querySelectorAll('.inputPontos');
    let total = 0;
    
    inputsPontos.forEach(input => {
        const valor = parseFloat(input.value) || 0;
        total += valor;
    });
    
    totalPontosGlobal = total;
    
    // Atualizar exibição se existir elemento
    const displayTotal = document.getElementById('totalPontosDisplay');
    if (displayTotal) {
        displayTotal.textContent = total.toFixed(2);
    }
}

/**
 * Validar formulário antes de enviar
 */
function validarFormulario(e) {
    console.log('Iniciando validação do formulário...');
    
    const questoes = document.querySelectorAll('.questao-card');
    console.log('Questões encontradas:', questoes.length);
    
    // Validação básica - apenas verificar se tem questões
    if (questoes.length === 0) {
        console.log('Erro: Nenhuma questão encontrada');
        e.preventDefault();
        alert('Adicione pelo menos uma questão ao questionário!');
        return false;
    }
    
    // Confirmação final
    const totalQuestoes = questoes.length;
    console.log('Total de questões:', totalQuestoes);
    console.log('Total de pontos:', totalPontosGlobal);
    
    const confirmacao = confirm(
        `Você está prestes a criar um questionário com:\n\n` +
        `• ${totalQuestoes} questão(ões)\n` +
        `• ${totalPontosGlobal.toFixed(2)} pontos totais\n\n` +
        `Confirmar criação?`
    );
    
    if (!confirmacao) {
        console.log('Usuário cancelou a criação');
        e.preventDefault();
        return false;
    }
    
    console.log('Formulário validado com sucesso, enviando...');
    return true;
}

/**
 * Carregar questões existentes (para edição)
 */
function carregarQuestoesExistentes() {
    console.log('=== INICIANDO CARREGAMENTO DE QUESTÕES ===');
    console.log('questoesExistentes definida?', typeof questoesExistentes !== 'undefined');
    console.log('questoesExistentes:', questoesExistentes);
    
    if (typeof questoesExistentes === 'undefined' || !questoesExistentes || questoesExistentes.length === 0) {
        console.log('Nenhuma questão existente para carregar');
        return;
    }
    
    console.log('Total de questões a carregar:', questoesExistentes.length);
    
    questoesExistentes.forEach((questao, idx) => {
        console.log(`Carregando questão ${idx + 1}:`, questao);
        
        // Adicionar card de questão
        adicionarQuestao();
        
        // Aguardar um pouco para o card ser renderizado
        setTimeout(() => {
            const container = document.getElementById('questoesContainer');
            const todasQuestoes = container.querySelectorAll('.questao-card');
            const questaoCard = todasQuestoes[todasQuestoes.length - 1]; // Pegar a última adicionada
            
            if (!questaoCard) {
                console.error(`Card da questão ${idx} não encontrado!`);
                return;
            }
            
            console.log(`Preenchendo questão ${idx + 1}...`);
            
            // Preencher ID da questão (para update)
            const idInput = questaoCard.querySelector('.questao-id');
            if (idInput) {
                idInput.value = questao.id || '';
                console.log(`ID definido: ${questao.id}`);
            }
            
            // Preencher pontos
            const pontosInput = questaoCard.querySelector('.inputPontos');
            if (pontosInput) {
                pontosInput.value = questao.pontos || 1.00;
                console.log(`Pontos definidos: ${questao.pontos}`);
            }
            
            // Preencher pergunta
            const perguntaInput = questaoCard.querySelector('textarea[name*="[pergunta]"]');
            if (perguntaInput) {
                perguntaInput.value = questao.pergunta || '';
                console.log(`Pergunta definida: ${questao.pergunta}`);
            }
            
            // Preencher explicação
            const explicacaoInput = questaoCard.querySelector('textarea[name*="[explicacao]"]');
            if (explicacaoInput) {
                explicacaoInput.value = questao.explicacao || '';
            }
            
            // Preencher tipo (DEPOIS de preencher os outros campos)
            const tipoSelect = questaoCard.querySelector('.tipoQuestao');
            if (tipoSelect) {
                tipoSelect.value = questao.tipo;
                console.log(`Tipo definido: ${questao.tipo}`);
                
                // Disparar change para mostrar campos específicos do tipo
                setTimeout(() => {
                    tipoSelect.dispatchEvent(new Event('change'));
                    
                    // Aguardar campos específicos aparecerem
                    setTimeout(() => {
                        // Preencher conforme tipo
                        if (questao.tipo === 'multipla_escolha' && questao.alternativas && questao.alternativas.length > 0) {
                            console.log(`Carregando ${questao.alternativas.length} alternativas...`);
                            const alternativasList = questaoCard.querySelector('.alternativas-list');
                            if (alternativasList) {
                                alternativasList.innerHTML = ''; // Limpar
                                
                                questao.alternativas.forEach((alt, altIdx) => {
                                    adicionarAlternativa(questaoCard, idx, altIdx);
                                });
                                
                                // Preencher valores das alternativas
                                setTimeout(() => {
                                    const alternativaItems = alternativasList.querySelectorAll('.alternativa-item');
                                    questao.alternativas.forEach((alt, altIdx) => {
                                        if (alternativaItems[altIdx]) {
                                            const altInput = alternativaItems[altIdx].querySelector('input[type="text"]');
                                            if (altInput) {
                                                altInput.value = alt;
                                            }
                                            
                                            // Marcar correta
                                            if (questao.resposta_correta === alt) {
                                                const radioInput = alternativaItems[altIdx].querySelector('input[type="radio"]');
                                                if (radioInput) {
                                                    radioInput.checked = true;
                                                }
                                            }
                                        }
                                    });
                                    console.log('Alternativas preenchidas');
                                }, 100);
                            }
                            
                        } else if (questao.tipo === 'verdadeiro_falso') {
                            setTimeout(() => {
                                const vfSelect = questaoCard.querySelector('.gabarito-vf-container select[name*="resposta_correta_vf"]');
                                if (vfSelect && questao.resposta_correta) {
                                    vfSelect.value = questao.resposta_correta;
                                    console.log(`VF definido: ${questao.resposta_correta}`);
                                }
                            }, 100);
                            
                        } else if (questao.tipo === 'texto' || questao.tipo === 'numerica') {
                            setTimeout(() => {
                                const textoInput = questaoCard.querySelector('input[name*="[resposta_correta_texto]"]');
                                if (textoInput && questao.resposta_correta) {
                                    textoInput.value = questao.resposta_correta;
                                }
                            }, 100);
                        }
                        
                        atualizarTotalPontos();
                    }, 150);
                }, 100);
            }
        }, 200 * idx); // Delay progressivo para cada questão
    });
    
    // Atualizar total final após tudo carregar
    setTimeout(() => {
        atualizarTotalPontos();
        console.log('=== QUESTÕES CARREGADAS COM SUCESSO ===');
    }, 500 + (200 * questoesExistentes.length));
}

// Função global para ser chamada após DOMContentLoaded principal
window.carregarQuestoesExistentesGlobal = function() {
    console.log('Tentando carregar questões existentes...');
    carregarQuestoesExistentes();
};

