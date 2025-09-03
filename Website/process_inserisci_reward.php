<?php
session_start();

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    die("Metodo non consentito.");
}

// Includi il file che gestisce il log degli eventi su MongoDB
require_once 'log_event.php';

// Verifica che i campi obbligatori siano stati inviati
if (!isset($_POST["nomeProgetto"]) || empty(trim($_POST["nomeProgetto"]))) {
    die("Progetto non specificato.");
}
if (!isset($_POST["codiceReward"]) || empty(trim($_POST["codiceReward"]))) {
    die("Codice Reward non specificato.");
}
if (!isset($_POST["descrizioneReward"]) || empty(trim($_POST["descrizioneReward"]))) {
    die("Descrizione Reward non specificata.");
}
if (!isset($_POST["foto"]) || empty(trim($_POST["foto"]))) {
    die("Foto non specificata.");
}

// Recupera i dati inviati dal form
$nomeProgetto      = trim($_POST["nomeProgetto"]);
$codiceReward      = trim($_POST["codiceReward"]);
$descrizioneReward = trim($_POST["descrizioneReward"]);
$foto              = trim($_POST["foto"]);

// Recupera l'indirizzo email dell'utente dalla sessione
if (!isset($_SESSION["indirizzoEmail"])) {
    die("Accesso non autorizzato.");
}
$indirizzoEmail = $_SESSION["indirizzoEmail"];

$conn = new mysqli("127.0.0.1", "root", "", "sample", 3306);
if ($conn->connect_error) {
    die("Connessione fallita: " . $conn->connect_error);
}
$conn->set_charset("utf8");

// Chiamata alla stored procedure inserisciReward
$query = "CALL inserisciReward(?, ?, ?, ?)";
$stmt = $conn->prepare($query);
$stmt->bind_param("ssss", $codiceReward, $descrizioneReward, $foto, $nomeProgetto);

$message = "";
try {
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result) {
        $row = $result->fetch_assoc();
        $message = $row["Messaggio"];
    } else {
        $message = "Reward inserita correttamente.";
    }
    
    // Se l'operazione ha avuto successo, registra l'evento su MongoDB
    if (strpos($message, "inserita") !== false) {
        logEvent("Reward '$codiceReward' inserita correttamente per il progetto '$nomeProgetto' dall'utente '$indirizzoEmail'.");
    }
    
} catch (Exception $e) {
    $message = "Errore: " . $e->getMessage();
}

$stmt->close();
$conn->close();

// Mostra il messaggio in un popup e reindirizza alla pagina dei progetti
echo "<script>
        alert('" . htmlspecialchars($message, ENT_QUOTES, 'UTF-8') . "'); 
        window.location.href='progetti.php';
      </script>";
exit();
?>
