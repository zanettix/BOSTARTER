<?php
/*
session_start();
if (!isset($_SESSION['nickname'])) {
    header("Location: login_form.php");
    exit();
}

// Get user role from session
$ruolo = isset($_SESSION['ruolo']) ? $_SESSION['ruolo'] : 'utente';
$userEmail = isset($_SESSION['indirizzoEmail']) ? $_SESSION['indirizzoEmail'] : '';

// Check if project name is provided
if (!isset($_GET['nome']) || empty($_GET['nome'])) {
    header("Location: progetti.php");
    exit();
}
*/
$nomeProgetto = $_GET['nome'];

// Database connection
$conn = new mysqli("127.0.0.1", "root", "", "sample", 3306);
if ($conn->connect_error) {
    die("Connessione fallita: " . $conn->connect_error);
}
$conn->set_charset("utf8");

// Function to sanitize output
function e($string) {
    return htmlspecialchars($string, ENT_QUOTES, 'UTF-8');
}

// Get project details
$stmt = $conn->prepare("SELECT p.nome, p.descrizione, p.data_inserimento, p.data_limite, 
                       p.budget, p.stato, p.indirizzoEmailCreatore, u.nickname 
                       FROM PROGETTO p 
                       JOIN UTENTE u ON p.indirizzoEmailCreatore = u.indirizzoEmail 
                       WHERE p.nome = ?");
$stmt->bind_param("s", $nomeProgetto);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    // Project not found
    $conn->close();
    header("Location: progetti.php");
    exit();
}

$progetto = $result->fetch_assoc();
$stmt->close();

// Calculate days remaining if project is open
$daysRemaining = null;
if (strtolower($progetto['stato']) === 'aperto') {
    $dataLimite = new DateTime($progetto['data_limite']);
    $today = new DateTime();
    $interval = $today->diff($dataLimite);
    $daysRemaining = $interval->days;
    $isPast = $dataLimite < $today;
}

// Calculate total funding received
$stmt = $conn->prepare("SELECT SUM(importo) as totale FROM FINANZIAMENTO WHERE nomeProgetto = ?");
$stmt->bind_param("s", $nomeProgetto);
$stmt->execute();
$resultFunding = $stmt->get_result();
$funding = $resultFunding->fetch_assoc();
$totalFunding = $funding['totale'] ?: 0;
$percentageFunded = ($progetto['budget'] > 0) ? ($totalFunding / $progetto['budget']) * 100 : 0;
$stmt->close();

// Get project components if any
$componenti = [];
$stmt = $conn->prepare("SELECT nome, descrizione, prezzo, quantita FROM COMPONENTE WHERE nomeProgetto = ?");
$stmt->bind_param("s", $nomeProgetto);
$stmt->execute();
$resultComponenti = $stmt->get_result();
while ($row = $resultComponenti->fetch_assoc()) {
    $componenti[] = $row;
}
$stmt->close();

// Get the project type (hardware/software)
$isHardware = count($componenti) > 0;

// Get profiles if it's a software project
$profili = [];
if (!$isHardware) {
    $stmt = $conn->prepare("SELECT id, nome FROM PROFILO WHERE nomeProgetto = ?");
    $stmt->bind_param("s", $nomeProgetto);
    $stmt->execute();
    $resultProfili = $stmt->get_result();
    while ($row = $resultProfili->fetch_assoc()) {
        $profili[] = $row;
    }
    $stmt->close();
}

// Get rewards for this project
$rewards = [];
$stmt = $conn->prepare("SELECT codice, descrizione, foto FROM REWARD WHERE nomeProgetto = ?");
$stmt->bind_param("s", $nomeProgetto);
$stmt->execute();
$resultRewards = $stmt->get_result();
while ($row = $resultRewards->fetch_assoc()) {
    $rewards[] = $row;
}
$stmt->close();

// Function to format date in Italian format
function formatDate($date) {
    $dateObj = new DateTime($date);
    return $dateObj->format('d/m/Y');
}
?>

<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($progetto['nome']) ?> - Dettagli Progetto - Bostarter</title>
    <style>
        /* Base styles */
        body { 
            margin: 0; 
            padding: 0; 
            font-family: Arial, sans-serif; 
            background-color: #f4f4f9; 
            color: #333; 
        }
        .container { 
            max-width: 1000px; 
            margin: 50px auto; 
            background-color: #fff; 
            border-radius: 8px; 
            box-shadow: 0 2px 5px rgba(0,0,0,0.1); 
            padding: 30px; 
        }
        h1, h2, h3 { 
            color: #444; 
        }
        h1 { 
            font-size: 32px; 
            margin-bottom: 5px; 
            text-align: center;
        }
        h2 {
            font-size: 24px;
            margin-top: 30px;
            margin-bottom: 15px;
            border-bottom: 1px solid #eee;
            padding-bottom: 10px;
        }
        
        /* Project info styles */
        .project-meta {
            color: #666;
            font-size: 14px;
            margin-bottom: 20px;
            text-align: center;
        }
        .project-description {
            line-height: 1.6;
            margin-bottom: 30px;
            white-space: pre-line;
        }
        .project-status {
            display: inline-block;
            padding: 5px 10px;
            border-radius: 4px;
            font-size: 14px;
            font-weight: bold;
            margin-left: 10px;
        }
        .status-open {
            background-color: #e8f5e9;
            color: #388e3c;
        }
        .status-closed {
            background-color: #ffebee;
            color: #d32f2f;
        }
        
        /* Stats panel */
        .stats-panel {
            display: flex;
            justify-content: space-between;
            margin: 20px 0 30px;
            background-color: #f9f9f9;
            border-radius: 8px;
            padding: 20px;
        }
        .stats-item {
            text-align: center;
            flex: 1;
        }
        .stats-value {
            font-size: 22px;
            font-weight: bold;
            color: #5c6bc0;
            margin-bottom: 5px;
        }
        .stats-label {
            color: #666;
            font-size: 14px;
        }
        
        /* Progress bar */
        .progress-container {
            margin: 20px 0;
            background-color: #f5f5f5;
            border-radius: 4px;
            height: 20px;
            position: relative;
        }
        .progress-bar {
            background-color: #5c6bc0;
            height: 100%;
            border-radius: 4px;
            width: <?= min($percentageFunded, 100) ?>%;
        }
        .progress-text {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            text-align: center;
            line-height: 20px;
            color: <?= $percentageFunded > 50 ? '#fff' : '#333' ?>;
            font-size: 12px;
            font-weight: bold;
        }
        
        /* Tables */
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
        }
        table, th, td {
            border: 1px solid #ddd;
        }
        th, td {
            padding: 10px;
            text-align: left;
        }
        th {
            background-color: #f4f4f4;
        }
        
        /* Rewards and components */
        .rewards-grid, .components-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 20px;
            margin: 20px 0;
        }
        .reward-card, .component-card {
            border: 1px solid #ddd;
            border-radius: 8px;
            padding: 15px;
            background-color: #fff;
        }
        .reward-title, .component-title {
            font-weight: bold;
            margin-bottom: 8px;
        }
        .reward-description, .component-description {
            color: #666;
            font-size: 14px;
            margin-bottom: 10px;
        }
        .reward-image {
            max-width: 100%;
            height: auto;
            border-radius: 4px;
            margin-top: 10px;
        }
        .component-price {
            font-weight: bold;
            color: #5c6bc0;
        }
        
        /* Buttons */
        .btn {
            display: inline-block;
            border: none;
            padding: 8px 16px;
            font-size: 14px;
            border-radius: 4px;
            cursor: pointer;
            transition: background-color 0.3s;
            text-decoration: none;
            margin-right: 10px;
            margin-bottom: 10px;
        }
        .btn-primary {
            background-color: #5c6bc0;
            color: #fff;
        }
        .btn-primary:hover {
            background-color: #3f51b5;
        }
        .btn-secondary {
            background-color: #888;
            color: #fff;
        }
        .btn-secondary:hover {
            background-color: #777;
        }
        .btn-container {
            margin-top: 30px;
            text-align: center;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1><?= e($progetto['nome']) ?></h1>
        
        <div class="project-meta">
            Creato da: <strong><?= e($progetto['nickname']) ?></strong> | 
            Data inserimento: <?= formatDate($progetto['data_inserimento']) ?> | 
            Stato: <span class="project-status <?= strtolower($progetto['stato']) === 'aperto' ? 'status-open' : 'status-closed' ?>">
                <?= e(ucfirst(strtolower($progetto['stato']))) ?>
            </span>
        </div>
        
        <div class="stats-panel">
            <div class="stats-item">
                <div class="stats-value">€ <?= number_format($totalFunding, 2, ',', '.') ?></div>
                <div class="stats-label">Finanziamento raccolto</div>
            </div>
            <div class="stats-item">
                <div class="stats-value">€ <?= number_format($progetto['budget'], 2, ',', '.') ?></div>
                <div class="stats-label">Budget richiesto</div>
            </div>
            <div class="stats-item">
                <div class="stats-value">
                    <?php if (strtolower($progetto['stato']) === 'aperto'): ?>
                        <?= $isPast ? 'Scaduto' : $daysRemaining . ' giorni' ?>
                    <?php else: ?>
                        -
                    <?php endif; ?>
                </div>
                <div class="stats-label">
                    <?php if (strtolower($progetto['stato']) === 'aperto'): ?>
                        <?= $isPast ? 'Data limite superata' : 'Giorni rimanenti' ?>
                    <?php else: ?>
                        Progetto chiuso
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <div class="progress-container">
            <div class="progress-bar"></div>
            <div class="progress-text"><?= round($percentageFunded) ?>% finanziato</div>
        </div>
        
        <h2>Descrizione</h2>
        <div class="project-description">
            <?= e($progetto['descrizione']) ?>
        </div>
        
        <?php if ($isHardware && count($componenti) > 0): ?>
            <h2>Componenti Richiesti</h2>
            <table>
                <thead>
                    <tr>
                        <th>Nome</th>
                        <th>Descrizione</th>
                        <th>Prezzo</th>
                        <th>Quantità</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($componenti as $componente): ?>
                        <tr>
                            <td><?= e($componente['nome']) ?></td>
                            <td><?= e($componente['descrizione']) ?></td>
                            <td>€ <?= number_format($componente['prezzo'], 2, ',', '.') ?></td>
                            <td><?= e($componente['quantita']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php elseif (count($profili) > 0): ?>
            <h2>Profili Richiesti</h2>
            <ul>
                <?php foreach ($profili as $profilo): ?>
                    <li>
                        <strong><?= e($profilo['nome']) ?></strong>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
        
        <?php if (count($rewards) > 0): ?>
            <h2>Rewards</h2>
            <div class="rewards-grid">
                <?php foreach ($rewards as $reward): ?>
                    <div class="reward-card">
                        <div class="reward-title">Reward: <?= e($reward['codice']) ?></div>
                        <div class="reward-description"><?= e($reward['descrizione']) ?></div>
                        <?php if (!empty($reward['foto'])): ?>
                            <img src="<?= e($reward['foto']) ?>" alt="Immagine reward" class="reward-image">
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
        
        <div class="btn-container">
            <button onclick="goBack()" class="btn btn-secondary">Indietro</button>
        </div>
    </div>

    <script>
        function goBack() {
            // Controlla se c'è una pagina precedente nella cronologia
            if (document.referrer && document.referrer !== window.location.href) {
                // Torna alla pagina precedente
                window.history.back();
            } else {
                // Se non c'è una pagina precedente, vai alla pagina progetti di default
                window.location.href = 'visualizza_progetti.php';
            }
        }
    </script>

    <?php $conn->close(); ?>
</body>
</html>