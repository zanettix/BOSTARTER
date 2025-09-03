<?php

$conn = new mysqli("127.0.0.1", "root", "", "sample", 3306);
if ($conn->connect_error) {
    die("Connessione fallita: " . $conn->connect_error);
}
$conn->set_charset("utf8");

// Query per la classifica dei creatori affidabili dalla view classifica_creatori_affidabili
// Aggiungiamo l'indirizzo email per poterlo usare nei link
$queryCreatori = "SELECT c.nickname, c.affidabilita, u.indirizzoEmail 
                  FROM classifica_creatori_affidabili c
                  JOIN UTENTE u ON c.nickname = u.nickname
                  ORDER BY c.affidabilita DESC";
$resultCreatori = $conn->query($queryCreatori);

// Query per la classifica dei progetti aperti ordinati per completamento decrescente
$queryProgetti = "SELECT nome, budget, total_funding
                  FROM view_progetti_vicinanza
                  ORDER BY (total_funding / budget) DESC";
$resultProgetti = $conn->query($queryProgetti);

// Query per la classifica dei migliori finanziatori dalla view view_classifica_finanziatori
$queryFinanziatori = "SELECT nickname, totale_finanziamenti, indirizzoEmailUtente
                      FROM view_classifica_finanziatori
                      ORDER BY totale_finanziamenti DESC";
$resultFinanziatori = $conn->query($queryFinanziatori);
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <title>Statistiche - Classifiche</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f4f9;
            color: #333;
            margin: 0;
            padding: 20px;
        }
        .container {
            max-width: 900px;
            margin: 0 auto 40px auto;
            background: #fff;
            padding: 20px;
            border: 1px solid #ddd;
            border-radius: 8px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        h1, h2 {
            text-align: center;
            color: #444;
            margin-top: 0;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
        }
        th, td {
            padding: 10px;
            border: 1px solid #ddd;
            text-align: center;
        }
        th {
            background-color: #f4f4f4;
        }
        /* Classi per il podio: Creatori e Finanziatori */
        .gold { background-color: #ffd700; }
        .silver { background-color: #C0C0C0; }
        .bronze { background-color: #cd7f32; }
        /* (Opzionale) Classi per evidenziare i progetti in podio */
        .p_gold { background-color: #ffd700; }
        .p_silver { background-color: #C0C0C0; }
        .p_bronze { background-color: #cd7f32; }
        /* Stili per la progress bar (per i progetti) */
        .progress-container {
            width: 100%;
            background-color: #ddd;
            border-radius: 4px;
            overflow: hidden;
            height: 20px;
        }
        .progress-bar {
            height: 100%;
            background-color: #388e3c;
            text-align: center;
            color: #fff;
            line-height: 20px;
            font-size: 12px;
        }
        .back-button {
            display: inline-block;
            margin-top: 20px;
            padding: 10px 20px;
            background-color: #5c6bc0;
            color: #fff;
            text-decoration: none;
            border-radius: 5px;
            transition: background-color 0.3s ease;
        }
        .back-button:hover {
            background-color: #3f51b5;
        }
        a {
            text-decoration: none;
            color: #2196F3;
            font-weight: bold;
            transition: color 0.3s ease;
        }
        a:hover {
            color: #0D47A1;
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <!-- Classifica Creatori Affidabili -->
    <div class="container">
        <h1>Classifica Creatori Affidabili</h1>
        <table>
            <thead>
                <tr>
                    <th>Posizione</th>
                    <th>Nickname</th>
                    <th>Affidabilità (%)</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $position = 1;
                while ($row = $resultCreatori->fetch_assoc()):
                    $class = "";
                    if ($position == 1) {
                        $class = "gold";
                    } elseif ($position == 2) {
                        $class = "silver";
                    } elseif ($position == 3) {
                        $class = "bronze";
                    }
                    // Nickname cliccabile che rimanda a visualizza_utente.php
                    $emailLink = "<a href='visualizza_utente.php?email=" . urlencode($row['indirizzoEmail']) . "'>" . htmlspecialchars($row['nickname']) . "</a>";
                ?>
                <tr class="<?php echo $class; ?>">
                    <td><?php echo $position; ?></td>
                    <td><?php echo $emailLink; ?></td>
                    <td><?php echo htmlspecialchars($row['affidabilita']); ?></td>
                </tr>
                <?php 
                    $position++;
                endwhile;
                $resultCreatori->free();
                ?>
            </tbody>
        </table>
    </div>

    <!-- Classifica Progetti Aperti Vicini al Completamento -->
    <div class="container">
        <h2>Progetti Aperti Più Vicini al Completamento</h2>
        <table>
            <thead>
                <tr>
                    <th>Posizione</th>
                    <th>Nome Progetto</th>
                    <th>Budget</th>
                    <th>Finanziato</th>
                    <th>Completamento</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $position = 1;
                while ($row = $resultProgetti->fetch_assoc()):
                    $budget = floatval($row['budget']);
                    $totalFunding = floatval($row['total_funding']);
                    $percent = 0;
                    if ($budget > 0) {
                        $percent = round(($totalFunding / $budget) * 100);
                        if ($percent > 100) {
                            $percent = 100;
                        }
                    }
                    $p_class = "";
                    if ($position == 1) {
                        $p_class = "p_gold";
                    } elseif ($position == 2) {
                        $p_class = "p_silver";
                    } elseif ($position == 3) {
                        $p_class = "p_bronze";
                    }
                ?>
                <tr class="<?php echo $p_class; ?>">
                    <td><?php echo $position; ?></td>
                    <td>
                        <a href="visualizza_progetto.php?nome=<?php echo urlencode($row['nome']); ?>">
                            <?php echo htmlspecialchars($row['nome']); ?>
                        </a>
                    </td>
                    <td><?php echo htmlspecialchars($row['budget']); ?></td>
                    <td><?php echo htmlspecialchars($row['total_funding']); ?></td>
                    <td>
                        <div class="progress-container">
                            <div class="progress-bar" style="width: <?php echo $percent; ?>%;">
                                <?php echo $percent; ?>%
                            </div>
                        </div>
                    </td>
                </tr>
                <?php 
                    $position++;
                endwhile;
                $resultProgetti->free();
                ?>
            </tbody>
        </table>
    </div>

    <!-- Classifica Migliori Finanziatori -->
    <div class="container">
        <h2>Classifica Migliori Finanziatori</h2>
        <table>
            <thead>
                <tr>
                    <th>Posizione</th>
                    <th>Nickname</th>
                    <th>Totale Finanziato</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $position = 1;
                while ($row = $resultFinanziatori->fetch_assoc()):
                    $class = "";
                    if ($position == 1) {
                        $class = "gold";
                    } elseif ($position == 2) {
                        $class = "silver";
                    } elseif ($position == 3) {
                        $class = "bronze";
                    }
                    // Il nickname diventa link cliccabile che rimanda a visualizza_utente.php passando come parametro l'email
                    $emailLink = "<a href='visualizza_utente.php?email=" . urlencode($row['indirizzoEmailUtente']) . "'>" . htmlspecialchars($row['nickname']) . "</a>";
                ?>
                <tr class="<?php echo $class; ?>">
                    <td><?php echo $position; ?></td>
                    <td><?php echo $emailLink; ?></td>
                    <td><?php echo htmlspecialchars($row['totale_finanziamenti']); ?></td>
                </tr>
                <?php 
                    $position++;
                endwhile;
                $resultFinanziatori->free();
                $conn->close();
                ?>
            </tbody>
        </table>
    </div>
    
    <div style="text-align: center;">
        <a href="index.php" class="back-button">Torna alla Home</a>
    </div>
</body>
</html>