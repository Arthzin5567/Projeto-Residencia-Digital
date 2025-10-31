// math-config.js - Configuração centralizada do KaTeX

// Configurações globais do KaTeX
const katexConfig = {
    delimiters: [
        {left: '$$', right: '$$', display: true},
        {left: '$', right: '$', display: false},
        {left: '\\(', right: '\\)', display: false},
        {left: '\\[', right: '\\]', display: true}
    ],
    throwOnError: false,
    trust: true,
    strict: false
};

// Função principal para renderizar equações
function renderEquacoesMatematicas(elemento = document.body) {
    if (typeof renderMathInElement !== 'undefined') {
        renderMathInElement(elemento, katexConfig);
    } else {
        console.warn('KaTeX não carregou corretamente');
    }
}

// Função para renderizar em elementos específicos (conteúdo dinâmico)
function renderizarEquacoesNoElemento(elemento) {
    renderEquacoesMatematicas(elemento);
}

// Renderizar automaticamente quando a página carrega
document.addEventListener('DOMContentLoaded', function() {
    renderEquacoesMatematicas();
});

// Função para verificar se KaTeX está carregado
function katexEstaPronto() {
    return typeof renderMathInElement !== 'undefined';
}

// Exportar funções para uso global (se necessário)
window.MathRenderer = {
    renderEquacoesMatematicas,
    renderizarEquacoesNoElemento,
    katexEstaPronto
};