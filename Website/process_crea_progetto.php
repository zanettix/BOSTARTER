<?php
session_start();
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    die("Metodo non consentito.");
}

// Includi il file che gestisce il log degli eventi su MongoDB
require_once 'log_event.php';

// Recupera i dati inviati dal form
if (!isset($_POST["nomeProgetto"]) || empty(trim($_POST["nomeProgetto"]))) {
    die("Nome progetto non specificato.");
}
$nomeProgetto = trim($_POST["nomeProgetto"]);
$descrizione  = trim($_POST["descrizione"]);
$data_limite  = $_POST["data_limite"];
$budget       = $_POST["budget"];
$stato        = 'aperto';
$indirizzoEmailCreatore = isset($_SESSION["indirizzoEmail"]) ? $_SESSION["indirizzoEmail"] : "test@example.com";

// Imposta data_inserimento come la data odierna
$data_inserimento = date("Y-m-d");

// Verifica che la data limite sia successiva alla data di inserimento
if ($data_limite <= $data_inserimento) {
    echo "<script>alert('La data limite deve essere successiva alla data di inserimento.'); window.history.back();</script>";
    exit();
}

$conn = new mysqli("127.0.0.1", "root", "", "sample", 3306);
if ($conn->connect_error) {
    die("Connessione fallita: " . $conn->connect_error);
}
$conn->set_charset("utf8");

// Preparazione e chiamata della stored procedure creaProgetto
$query = "CALL creaProgetto(?, ?, ?, ?, ?, ?, ?)";
$stmt = $conn->prepare($query);
$stmt->bind_param("ssssdss", $nomeProgetto, $descrizione, $data_inserimento, $data_limite, $budget, $stato, $indirizzoEmailCreatore);

try {
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result) {
        $row = $result->fetch_assoc();
        $message = $row["Messaggio"];
    } else {
        $message = "Progetto creato con successo.";
    }
    
    // Registra l'evento su MongoDB se la creazione del progetto ha avuto successo
    if (strpos($message, "creato") !== false) {
        logEvent("Progetto '$nomeProgetto' creato con successo dal creatore '$indirizzoEmailCreatore'.");
    }
    
} catch (Exception $e) {
    $message = "Errore: " . $e->getMessage();
}
$stmt->close();
$conn->close();

// Mostra il messaggio in un popup e reindirizza alla pagina dei progetti
echo "<script>alert('" . htmlspecialchars($message, ENT_QUOTES, 'UTF-8') . "'); window.location.href='progetti.php';</script>";
exit();
?>
