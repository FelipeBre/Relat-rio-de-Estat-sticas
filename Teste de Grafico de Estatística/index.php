<?php
/**
 * API de Vendas - Retorna dados de vendas diárias e semanais em formato JSON.
 * 
 * @author
 * @version 1.0
 */

header('Content-Type: application/json');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');

// Configurações de cache
$cacheFile = __DIR__ . '/cache/vendas.json';
$cacheTime = 300; // 5 minutos

// Verifica se o cache é válido
if (file_exists($cacheFile) && (time() - filemtime($cacheFile) < $cacheTime)) {
    readfile($cacheFile);
    exit;
}

try {
    // Configurações de conexão com o banco de dados
    $host = 'localhost';
    $dbname = 'db_powerpc';
    $username = 'usuario';
    $password = 'senha';

    // Estabelecendo a conexão com PDO
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    // Configurando o modo de erro do PDO para exceções
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Data atual
    $dataAtual = new DateTime();

    // Data de início para vendas diárias (últimos 7 dias)
    $dataInicioDiaria = (clone $dataAtual)->modify('-6 days')->format('Y-m-d');

    // Consulta para vendas diárias
    $stmtDaily = $pdo->prepare("
        SELECT DATE(data_venda) AS dia, SUM(valor) AS total
        FROM vendas
        WHERE data_venda >= :data_inicio
        GROUP BY dia
        ORDER BY dia
    ");
    $stmtDaily->bindParam(':data_inicio', $dataInicioDiaria);
    $stmtDaily->execute();
    $dailyResults = $stmtDaily->fetchAll(PDO::FETCH_ASSOC);

    $labelsDiarias = [];
    $dadosDiarios = [];

    foreach ($dailyResults as $row) {
        $labelsDiarias[] = $row['dia'];
        $dadosDiarios[] = (float) $row['total'];
    }

    // Data de início para vendas semanais (últimas 4 semanas)
    $dataInicioSemanal = (clone $dataAtual)->modify('-4 weeks')->format('Y-m-d');

    // Consulta para vendas semanais
    $stmtWeekly = $pdo->prepare("
        SELECT YEAR(data_venda) AS ano, WEEK(data_venda, 1) AS semana, SUM(valor) AS total
        FROM vendas
        WHERE data_venda >= :data_inicio
        GROUP BY ano, semana
        ORDER BY ano, semana
    ");
    $stmtWeekly->bindParam(':data_inicio', $dataInicioSemanal);
    $stmtWeekly->execute();
    $weeklyResults = $stmtWeekly->fetchAll(PDO::FETCH_ASSOC);

    $labelsSemanais = [];
    $dadosSemanais = [];

    foreach ($weeklyResults as $row) {
        $labelsSemanais[] = "{$row['ano']}-S{$row['semana']}";
        $dadosSemanais[] = (float) $row['total'];
    }

    // Estrutura dos dados para JSON
    $dados = [
        'vendas_diarias' => [
            'labels' => $labelsDiarias,
            'dados' => $dadosDiarios
        ],
        'vendas_semanais' => [
            'labels' => $labelsSemanais,
            'dados' => $dadosSemanais
        ]
    ];

    // Codifica os dados em JSON
    $json = json_encode($dados, JSON_UNESCAPED_UNICODE);

    // Salva o resultado no cache
    if (!is_dir(__DIR__ . '/cache')) {
        mkdir(__DIR__ . '/cache', 0755, true);
    }
    file_put_contents($cacheFile, $json);

    // Retorna o JSON
    echo $json;

    // Fecha a conexão com o banco de dados
    $pdo = null;

} catch (PDOException $e) {
    // Registra o erro em um log
    error_log($e->getMessage());

    // Retorna uma mensagem de erro genérica
    echo json_encode([
        'error' => 'Erro ao processar os dados. Tente novamente mais tarde.'
    ]);
}
?>
