<?php
session_start();

// Verificar se é professor
if (!isset($_SESSION["logado"]) || $_SESSION["logado"] !== true || $_SESSION["tipo_usuario"] !== "professor") {
    exit('Acesso negado.');
}

$conectar = mysqli_connect("localhost", "root", "", "projeto_residencia");

// Buscar dados completos para exportação
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

$result_export = mysqli_query($conectar, $sql_export);

if (!$result_export) {
    die('Erro na query de exportação: ' . mysqli_error($conectar));
}

// Configurar headers para download
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="desempenho_geral_' . date('Y-m-d_H-i') . '.csv"');

// Criar arquivo de saída
$output = fopen('php://output', 'w');

// Escrever cabeçalho
fputcsv($output, [
    'Aluno',
    'Série', 
    'Matéria',
    'Prova',
    'Nota',
    'Data Realização',
    'Status',
    'Nº Questões',
    'Total Provas Aluno'
], ';');

// Escrever dados
while ($row = mysqli_fetch_assoc($result_export)) {
    fputcsv($output, [
        $row['aluno_nome'],
        $row['serie'],
        $row['materia'],
        $row['prova_titulo'],
        number_format($row['nota'], 2, ',', ''),
        date('d/m/Y', strtotime($row['data_realizacao'])),
        $row['status_aprovacao'],
        $row['numero_questoes'],
        $row['total_provas_aluno']
    ], ';');
}

fclose($output);
exit();
?>