<?php
session_start();

// Includi il file che gestisce il log degli eventi su MongoDB
require_once 'log_event.php';

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    die("Metodo non consentito.");
}

// Verifica che i campi obbligatori siano stati inviati
if (!isset($_POST["idCommento"]) || empty($_POST["idCommento"])) {
    die("ID del commento non specificato.");
}
if (!isset($_POST["testoRisposta"]) || empty(trim($_POST["testoRisposta"]))) {
    die("Testo della risposta non specificato.");
}

$idCommento    = intval($_POST["idCommento"]);
$testoRisposta = trim($_POST["testoRisposta"]);
$data_corrente = date("Y-m-d");

// Recupera l'indirizzo email del creatore dalla sessione
if (!isset($_SESSION["indirizzoEmail"])) {
    die("Accesso non autorizzato.");
}
$indirizzoEmailCreatore = $_SESSION["indirizzoEmail"];

// Recupera il nome del progetto dal POST (campo hidden presente nel form)
$nomeProgetto = isset($_POST["nomeProgetto"]) ? $_POST["nomeProgetto"] : "";

if (empty($nomeProgetto)) {
    die("Nome progetto non specificato.");
}

$conn = new mysqli("127.0.0.1", "root", "", "sample", 3306);
if ($conn->connect_error) {
    die("Connessione fallita: " . $conn->connect_error);
}
$conn->set_charset("utf8");

// Prepara e chiama la stored procedure inserisciRisposta
$query = "CALL inserisciRisposta(?, ?, ?, ?)";
$stmt = $conn->prepare($query);
if (!$stmt) {
    die("Preparazione della query fallita: " . $conn->error);
}
$stmt->bind_param("isss", $idCommento, $data_corrente, $testoRisposta, $indirizzoEmailCreatore);

$message = "";
try {
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result) {
        $row = $result->fetch_assoc();
        $message = $row["Messaggio"];
        $result->free();
    } else {
        $message = "Risposta inserita correttamente.";
    }
    
    // Se l'operazione ha avuto successo, registra l'evento su MongoDB
    if (strpos($message, "inserita") !== false) {
        logEvent("Risposta inserita per il commento ID $idCommento dal creatore '$indirizzoEmailCreatore' per il progetto '$nomeProgetto'.");
    }
    
} catch (Exception $e) {
    $message = "Errore nell'inserimento della risposta: " . $e->getMessage();
}

$stmt->close();
$conn->close();

// Reindirizza a visualizza_commenti.php, passando il nome del progetto
$redirectUrl = "visualizza_commenti.php?nome=" . urlencode($nomeProgetto);

echo "<script>
        alert('" . htmlspecialchars($message, ENT_QUOTES, 'UTF-8') . "');
        window.location.href = '" . $redirectUrl . "';
      </script>";
exit();
?>
