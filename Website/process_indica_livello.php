<?php
session_start();
if (!isset($_SESSION['indirizzoEmail'])) {
    header("Location: login_form.php");
    exit();
}

$indirizzoEmail = $_SESSION['indirizzoEmail'];
$competenza = $_POST['competenza'] ?? die("Errore: Competenza non fornita.");
$livello = isset($_POST['livello']) ? intval($_POST['livello']) : die("Errore: Livello non fornito.");

$conn = new mysqli("127.0.0.1", "root", "", "sample", 3306);
$conn->set_charset("utf8");

$query = "CALL indicaLivello(?, ?, ?)";
$stmt = $conn->prepare($query);
$stmt->bind_param("ssi", $indirizzoEmail, $competenza, $livello);

$message = "";

try {
    $stmt->execute();
    // Forza il buffering dei risultati e processa eventuali result set
    $stmt->store_result();
    if ($stmt->num_rows > 0) {
        $stmt->bind_result($message);
        $stmt->fetch();
    } else {
        $message = "Livello assegnato correttamente.";
    }
    
    // Se l'operazione è andata a buon fine, logga l'evento
    require 'log_event.php';
    logEvent("Livello " . $livello . " assegnato per la competenza '" . $competenza . "' all'utente " . $indirizzoEmail);
    
} catch (mysqli_sql_exception $ex) {
    $message = "Errore: " . $ex->getMessage();
}
$stmt->close();
$conn->close();
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <title>Risultato Assegnazione Livello - Bostarter</title>
    <style>
        body {
            margin: 0;
            padding: 0;
            font-family: Arial, sans-serif;
            background-color: #f4f4f9;
            color: #333;
        }
        .container {
            max-width: 600px;
            margin: 50px auto;
            background-color: #fff;
            border: 1px solid #ddd;
            border-radius: 8px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            padding: 30px;
            text-align: center;
        }
        h1 {
            font-size: 28px;
            color: #444;
            margin-bottom: 20px;
        }
        p {
            font-size: 16px;
            margin: 20px 0;
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
        }
        .back-button:hover {
            background-color: #777777;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Risultato Assegnazione Livello</h1>
        <p><?php echo htmlspecialchars($message); ?></p>
        <a href="indica_livello_competenza.php" class="back-button">Indietro</a>
    </div>
</body>
</html>
