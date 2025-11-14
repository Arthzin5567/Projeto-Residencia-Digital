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

// Tenta chamar renderMathInElement quando disponível, com retries
function waitForRenderMathInElement(timeout = 3000, interval = 50) {
    const start = Date.now();

    return new Promise((resolve, reject) => {
        (function check() {
            if (typeof renderMathInElement !== 'undefined') {
                resolve(renderMathInElement);
            } else if (Date.now() - start >= timeout) {
                reject(new Error('renderMathInElement não ficou disponível dentro do tempo limite'));
            } else {
                setTimeout(check, interval);
            }
        })();
    });
}

async function renderEquacoesMatematicas(elemento = document.body) {
    try {
        await waitForRenderMathInElement();
        renderMathInElement(elemento, katexConfig);
        // console.debug('KaTeX: renderização concluída');
    } catch (e) {
        console.warn('KaTeX: não foi possível renderizar as equações:', e.message);
    }
}

// Função para renderizar em elementos específicos (conteúdo dinâmico)
function renderizarEquacoesNoElemento(elemento) {
    return renderEquacoesMatematicas(elemento);
}

// Função para verificar se KaTeX está carregado (sincrona)
function katexEstaPronto() {
    return typeof renderMathInElement !== 'undefined';
}

// Renderizar automaticamente quando a página carrega (após DOM pronto)
document.addEventListener('DOMContentLoaded', function() {
    // Se o script foi incluído com defer, os scripts do KaTeX devem já estar carregados,
    // mas usamos waitForRenderMathInElement para ter robustez em ambientes lentos.
    renderEquacoesMatematicas();
});

// Exportar funções para uso global
window.MathRenderer = {
    renderEquacoesMatematicas,
    renderizarEquacoesNoElemento,
    katexEstaPronto
};