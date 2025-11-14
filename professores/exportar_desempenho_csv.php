<?php
session_start();

// HEADERS DE SEGURANÇA
header("X-Frame-Options: DENY");
header("X-Content-Type-Options: nosniff");
header("X-XSS-Protection: 1; mode=block");
header("Referrer-Policy: strict-origin-when-cross-origin");

// VALIDAÇÃO RIGOROSA DE SESSÃO
if (!isset($_SESSION["logado"]) || $_SESSION["logado"] !== true || $_SESSION["tipo_usuario"] !== "professor") {
    header("Location: ../index.php?erro=acesso_negado");
    exit();
}

// VALIDAÇÃO DE CSRF TOKEN (opcional para GET, mas boa prática)
if (isset($_GET['csrf_token']) && $_GET['csrf_token'] !== $_SESSION['csrf_token']) {
    error_log("Tentativa de CSRF detectada na exportação CSV");
    die("Erro de segurança. Acesso negado.");
}

$host = "localhost";
$user = "root";
$password = "SenhaIrada@2024!";
$database = "projeto_residencia";

// CONEXÃO SEGURA
$conectar = mysqli_connect($host, $user, $password, $database);
if (!$conectar) {
    error_log("Erro de conexão na exportação CSV");
    die("Erro interno do sistema. Tente novamente mais tarde.");
}

// CONFIGURAÇÕES DE SEGURANÇA
mysqli_set_charset($conectar, "utf8mb4");

/**
 * FUNÇÃO PARA SANITIZAR DADOS PARA CSV
 */
function sanitizarParaCSV($dado) {
    if ($dado === null) {
        return '';
    }
    
    // Converter para string
    $dado = (string)$dado;
    
    // Remover ou substituir caracteres problemáticos para CSV
    $dado = str_replace(["\r", "\n", "\t"], ' ', $dado);
    $dado = trim($dado);
    
    // Se contiver ponto e vírgula, aspas ou quebras de linha, colocar entre aspas
    if (strpos($dado, ';') !== false || strpos($dado, '"') !== false || strpos($dado, "\n") !== false) {
        $dado = '"' . str_replace('"', '""', $dado) . '"';
    }
    
    return $dado;
}

/**
 * FUNÇÃO PARA CONVERTER PARA ENCODING CORRETO
 */
function converterParaEncoding($dado, $encoding = 'Windows-1252') {
    if (function_exists('mb_convert_encoding')) {
        return mb_convert_encoding($dado, $encoding, 'UTF-8');
    }
    
    // Fallback para iconv se mbstring não estiver disponível
    if (function_exists('iconv')) {
        return iconv('UTF-8', $encoding . '//TRANSLIT', $dado);
    }
    
    // Se nenhuma função de conversão disponível, retornar original
    return $dado;
}

// BUSCAR DADOS COMPLETOS PARA EXPORTAÇÃO COM PREPARED STATEMENT
$sql_export = "SELECT
               a.nome as aluno_nome,
               a.escolaridade as serie,
               p.materia,
               p.titulo as prova_titulo,
               ap.nota,
               ap.data_realizacao,
               CASE
                   WHEN ap.nota >= 7 THEN 'Aprovado'
                   WHEN ap.nota >= 5 THEN 'Recuperação'
                   ELSE 'Reprovado'
               END as status_aprovacao,
               p.numero_questoes,
               (SELECT COUNT(*) FROM Aluno_Provas ap2
                WHERE ap2.Aluno_idAluno = a.idAluno
                AND ap2.status IN ('realizada', 'corrigida')) as total_provas_aluno
               FROM Aluno a
               INNER JOIN Aluno_Provas ap ON a.idAluno = ap.Aluno_idAluno
               INNER JOIN Provas p ON ap.Provas_idProvas = p.idProvas
               WHERE ap.status IN ('realizada', 'corrigida')
               ORDER BY a.nome, p.materia, ap.data_realizacao";

$stmt_export = mysqli_prepare($conectar, $sql_export);

if (!$stmt_export) {
    error_log("Erro ao preparar query de exportação: " . mysqli_error($conectar));
    die("Erro interno do sistema.");
}

mysqli_stmt_execute($stmt_export);
$result_export = mysqli_stmt_get_result($stmt_export);

if (!$result_export) {
    error_log("Erro na query de exportação: " . mysqli_error($conectar));
    mysqli_stmt_close($stmt_export);
    mysqli_close($conectar);
    die('Erro ao gerar relatório.');
}

// CONFIGURAR HEADERS PARA DOWNLOAD COM ENCODING CORRETO
$filename = "desempenho_geral_" . date('Y-m-d_H-i') . ".csv";

// Headers para CSV com suporte a caracteres especiais
header('Content-Type: text/csv; charset=UTF-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Content-Transfer-Encoding: binary');
header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
header('Pragma: public');
header('Expires: 0');

// CRIAR BOM (Byte Order Mark) PARA UTF-8 - IMPORTANTE PARA EXCEL
echo "\xEF\xBB\xBF"; // BOM para UTF-8

// CRIAR ARQUIVO DE SAÍDA
$output = fopen('php://output', 'w');

// ESCREVER CABEÇALHO COM CARACTERES ESPECIAIS CORRETOS
$cabecalho = [
    'Aluno',
    'Série',
    'Matéria',
    'Prova',
    'Nota',
    'Data Realização',
    'Status',
    'Nº Questões',
    'Total Provas Aluno'
];

// Escrever cabeçalho com encoding correto
fputcsv($output, $cabecalho, ';');

// ESCREVER DADOS COM SANITIZAÇÃO E FORMATAÇÃO
while ($row = mysqli_fetch_assoc($result_export)) {
    // Sanitizar e formatar cada campo
    $dados_linha = [
        sanitizarParaCSV($row['aluno_nome']),
        sanitizarParaCSV($row['serie']),
        sanitizarParaCSV($row['materia']),
        sanitizarParaCSV($row['prova_titulo']),
        sanitizarParaCSV(number_format($row['nota'], 2, ',', '')), // Formato brasileiro
        sanitizarParaCSV(date('d/m/Y', strtotime($row['data_realizacao']))), // Formato brasileiro
        sanitizarParaCSV($row['status_aprovacao']),
        sanitizarParaCSV($row['numero_questoes']),
        sanitizarParaCSV($row['total_provas_aluno'])
    ];
    
    fputcsv($output, $dados_linha, ';');
}

// ALTERNATIVA: Se ainda houver problemas com caracteres, usar esta versão:
if (isset($_GET['force_windows']) && $_GET['force_windows'] === '1') {
    rewind($output);
    $conteudo = stream_get_contents($output);
    
    // Converter para Windows-1252 para Excel em português
    if (function_exists('mb_convert_encoding')) {
        $conteudo = mb_convert_encoding($conteudo, 'Windows-1252', 'UTF-8');
    }
    
    echo $conteudo;
}

fclose($output);

// LIMPEZA SEGURA
mysqli_stmt_close($stmt_export);
mysqli_close($conectar);
exit();