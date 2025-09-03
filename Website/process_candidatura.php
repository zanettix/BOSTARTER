<?php
session_start();

// Verifica che il metodo della richiesta sia POST
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    die("Metodo non consentito.");
}

// Includi il file che gestisce il log degli eventi su MongoDB
require_once 'log_event.php';

// Verifica che i campi necessari siano stati inviati
if (!isset($_POST['emailCandidato']) || empty(trim($_POST['emailCandidato']))) {
    die("Email del candidato non specificata.");
}
if (!isset($_POST['idProfilo']) || empty(trim($_POST['idProfilo']))) {
    die("ID del profilo non specificato.");
}
if (!isset($_POST['azione']) || empty(trim($_POST['azione']))) {
    die("Azione non specificata.");
}

$emailCandidato = trim($_POST['emailCandidato']);
$idProfilo = trim($_POST['idProfilo']);
$azione = trim($_POST['azione']);

// Recupera l'indirizzo email del creatore dalla sessione
if (!isset($_SESSION['indirizzoEmail'])) {
    die("Accesso non autorizzato.");
}
$indirizzoEmailCreatore = $_SESSION['indirizzoEmail'];

$conn = new mysqli("127.0.0.1", "root", "", "sample", 3306);
if ($conn->connect_error) {
    die("Connessione fallita: " . $conn->connect_error);
}
$conn->set_charset("utf8");

$message = "";
$query = "";

if ($azione === "accetta") {
    // Chiamata alla stored procedure accettaCandidatura
    $query = "CALL accettaCandidatura(?, ?, ?)";
} elseif ($azione === "rifiuta") {
    // Chiamata alla stored procedure rifiutaCandidatura
    $query = "CALL rifiutaCandidatura(?, ?, ?)";
} else {
    die("Azione non valida.");
}

$stmt = $conn->prepare($query);
if (!$stmt) {
    die("Preparazione della query fallita: " . $conn->error);
}
// Il primo parametro è l'ID del profilo (INT), gli altri due sono stringhe
$stmt->bind_param("iss", $idProfilo, $emailCandidato, $indirizzoEmailCreatore);

try {
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result && $row = $result->fetch_assoc()) {
        $message = $row["Messaggio"];
        $result->free();
    } else {
        $message = ($azione === "accetta") ? "Candidatura accettata." : "Candidatura rifiutata.";
    }
    
    // Registra l'evento su MongoDB per la candidatura
    if ($azione === "accetta") {
        logEvent("Candidatura per il profilo con ID $idProfilo da parte dell'utente $emailCandidato accettata dal creatore $indirizzoEmailCreatore.");
    } elseif ($azione === "rifiuta") {
        logEvent("Candidatura per il profilo con ID $idProfilo da parte dell'utente $emailCandidato rifiutata dal creatore $indirizzoEmailCreatore.");
    }
    
} catch (Exception $e) {
    $message = "Errore nell'elaborazione della candidatura: " . $e->getMessage();
}

$stmt->close();
$conn->close();

echo "<script>
        alert('" . htmlspecialchars($message, ENT_QUOTES, 'UTF-8') . "');
        window.location.href = 'progetti.php';
      </script>";
exit();
?>
