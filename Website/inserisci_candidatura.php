<?php
session_start();

// Controlla che sia stato passato il nome del progetto tramite GET
if (!isset($_GET['nome']) || empty($_GET['nome'])) {
    die("Progetto non specificato.");
}
$nome_progetto = $_GET['nome'];

$conn = new mysqli("127.0.0.1", "root", "", "sample", 3306);
if($conn->connect_error){
    die("Connessione fallita: " . $conn->connect_error);
}
$conn->set_charset("utf8");

// Per il controllo delle competenze usiamo l'indirizzo email dell'utente dalla sessione
$indirizzoEmail = isset($_SESSION['indirizzoEmail']) ? $conn->real_escape_string($_SESSION['indirizzoEmail']) : "test@example.com";

$message = "";

// Gestione del POST per candidarsi a un profilo
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["candidati"])) {
    // Recupera l'id del profilo selezionato
    $idProfilo = $conn->real_escape_string($_POST["idProfilo"]);
    
    // Chiamata alla stored procedure inserisciCandidatura
    try {
        $queryCandidatura = "CALL inserisciCandidatura('$indirizzoEmail', '$idProfilo')";
        $conn->query($queryCandidatura);
        // Svuota i result set residui
        while($conn->more_results() && $conn->next_result()){
            ;
        }
        $message = "Candidatura inserita correttamente.";
    } catch (mysqli_sql_exception $e) {
        $message = "Errore nella candidatura: " . $e->getMessage();
    }
}

// Recupera tutti i profili associati al progetto dalla tabella PROFILO
$queryProfili = "SELECT id, nome FROM PROFILO WHERE nomeProgetto = '".$conn->real_escape_string($nome_progetto)."'";
$resultProfili = $conn->query($queryProfili);
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <title>Inserisci Candidatura - <?php echo htmlspecialchars($nome_progetto); ?></title>
    <style>
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
            border: 1px solid #ddd;
            border-radius: 8px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            padding: 30px;
            overflow-x: auto;
        }
        h1 {
            text-align: center;
            font-size: 32px;
            color: #444;
            margin-bottom: 30px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        table, th, td {
            border: 1px solid #ddd;
        }
        th, td {
            padding: 10px;
            text-align: center;
        }
        th {
            background-color: #f4f4f4;
        }
        .btn-candidati {
            background-color: #5c6bc0;
            color: #fff;
            border: none;
            padding: 6px 12px;
            border-radius: 4px;
            font-size: 14px;
            cursor: pointer;
            transition: background-color 0.3s ease;
        }
        .btn-candidati:hover {
            background-color: #3f51b5;
        }
        .not-qualified {
            color: #d9534f; /* rosso */
            font-weight: bold;
        }
        .back-button {
            background-color: #888888;
            color: #fff;
            border: none;
            padding: 8px 16px;
            font-size: 14px;
            border-radius: 5px;
            text-decoration: none;
            display: inline-block;
            margin-top: 20px;
            transition: background-color 0.3s ease;
        }
        .back-button:hover {
            background-color: #777777;
        }
    </style>
</head>
<body>
<div class="container">
    <h1>Profili per <?php echo htmlspecialchars($nome_progetto); ?></h1>
    
    <?php if (!empty($message)): ?>
    <script>
        alert("<?php echo htmlspecialchars($message, ENT_QUOTES, 'UTF-8'); ?>");
    </script>
    <?php endif; ?>
    
    <?php if($resultProfili->num_rows > 0): ?>
    <table>
        <thead>
            <tr>
                <th>Nome Profilo</th>
                <th>Candidati</th>
            </tr>
        </thead>
        <tbody>
            <?php 
            // Per ogni profilo, controlliamo se il profilo è già stato accettato
            while($row = $resultProfili->fetch_assoc()): 
                $profile_id = $row["id"];
                
                // Verifica se in ACCETTARE esiste già un record per questo profilo
                $queryAccepted = "SELECT COUNT(*) AS cnt FROM ACCETTARE WHERE idProfilo = '$profile_id'";
                $resultAccepted = $conn->query($queryAccepted);
                $acceptedRow = $resultAccepted->fetch_assoc();
                $accepted = ($acceptedRow['cnt'] > 0);
                $resultAccepted->free();
                
                // Verifica se l'utente possiede le competenze richieste per questo profilo
                $queryVerifica = "SELECT COUNT(*) AS cnt 
                                  FROM RICHIEDERE r 
                                  LEFT JOIN INDICARE i ON r.nomeCompetenza = i.nomeCompetenza 
                                      AND i.indirizzoEmailUtente = '$indirizzoEmail'
                                  WHERE r.idProfilo = '$profile_id'
                                    AND (i.livello IS NULL OR i.livello < r.livello)";
                $resultVerifica = $conn->query($queryVerifica);
                $check = $resultVerifica->fetch_assoc();
                $qualified = ($check['cnt'] == 0);
                $resultVerifica->free();
            ?>
            <tr>
                <td>
                    <a href="visualizza_profilo.php?idProfilo=<?php echo urlencode($profile_id); ?>">
                        <?php echo htmlspecialchars($row["nome"]); ?>
                    </a>
                </td>
                <td>
                    <?php if ($accepted): ?>
                        <span class="not-qualified">non disponibile</span>
                    <?php elseif ($qualified): ?>
                        <form method="post" action="inserisci_candidatura.php?nome=<?php echo urlencode($nome_progetto); ?>">
                            <input type="hidden" name="idProfilo" value="<?php echo htmlspecialchars($profile_id); ?>">
                            <button type="submit" name="candidati" class="btn-candidati">Candidati</button>
                        </form>
                    <?php else: ?>
                        <span class="not-qualified">Non qualificato</span>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
    <?php else: ?>
        <p>Nessun profilo trovato per questo progetto.</p>
    <?php endif; ?>
    <div style="text-align: center;">
        <a href="progetti.php" class="back-button">Torna alla lista dei progetti</a>
    </div>
</div>
<?php
$resultProfili->free();
$conn->close();
?>
</body>
</html>
