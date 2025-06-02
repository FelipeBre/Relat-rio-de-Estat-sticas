<?php
header('Content-Type: application/json');

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

    // Consulta para vendas diárias (últimos 7 dias)
    $stmtDaily = $pdo->prepare("
        SELECT DATE_FORMAT(data_venda, '%a') AS dia, SUM(valor) AS total
        FROM vendas
        WHERE data_venda >= CURDATE() - INTERVAL 6 DAY
        GROUP BY dia
        ORDER BY data_venda
    ");
    $stmtDaily->execute();
    $dailyResults = $stmtDaily->fetchAll(PDO::FETCH_ASSOC);

    $labels = [];
    $daily = [];

    foreach ($dailyResults as $row) {
        $labels[] = $row['dia'];
        $daily[] = (float) $row['total'];
    }

    // Consulta para vendas semanais (últimas 4 semanas)
    $stmtWeekly = $pdo->prepare("
        SELECT DATE_FORMAT(data_venda, '%u') AS semana, SUM(valor) AS total
        FROM vendas
        WHERE data_venda >= CURDATE() - INTERVAL 4 WEEK
        GROUP BY semana
        ORDER BY semana
    ");
    $stmtWeekly->execute();
    $weeklyResults = $stmtWeekly->fetchAll(PDO::FETCH_ASSOC);

    $weekly = [];

    foreach ($weeklyResults as $row) {
        $weekly[] = (float) $row['total'];
    }

    // Retornando os dados em formato JSON
    echo json_encode([
        'labels' => $labels,
        'daily' => $daily,
        'weekly' => $weekly
    ]);
} catch (PDOException $e) {
    // Em caso de erro, retornar uma mensagem de erro em formato JSON
    echo json_encode([
        'error' => 'Erro ao conectar ao banco de dados: ' . $e->getMessage()
    ]);
}
?>
