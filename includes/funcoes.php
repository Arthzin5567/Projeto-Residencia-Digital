//  FUNÇÃO PARA FORMATAR TELEFONE
function formatarTelefone($telefone) {
    if (empty($telefone) || $telefone == 0) {
        return '';
    }
    
    // Converter para string e remover caracteres não numéricos
    $telefone = preg_replace('/\D/', '', (string)$telefone);
    
    // Formatar baseado no tamanho
    if (strlen($telefone) === 11) {
        return '(' . substr($telefone, 0, 2) . ') ' . substr($telefone, 2, 5) . '-' . substr($telefone, 7);
    } elseif (strlen($telefone) === 10) {
        return '(' . substr($telefone, 0, 2) . ') ' . substr($telefone, 2, 4) . '-' . substr($telefone, 6);
    } else {
        return $telefone; // Retorna sem formatação se não for 10 ou 11 dígitos
    }
}