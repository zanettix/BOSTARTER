<?php
session_start();

// Includi il file che gestisce il log degli eventi su MongoDB
require_once 'log_event.php';

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    die("Metodo non consentito.");
}

// Verifica che il progetto sia specificato tramite GET
if (!isset($_GET['nome']) || empty($_GET['nome'])) {
    die("Progetto non specificato.");
}
$nome_progetto = $_GET['nome'];

// Verifica che il commento sia stato inviato tramite POST
if (!isset($_POST['commento']) || empty(trim($_POST['commento']))) {
    die("Commento non specificato.");
}
$commento = trim($_POST['commento']);

// Recupera l'indirizzo email dalla sessione
$indirizzoEmail = isset($_SESSION['indirizzoEmail']) ? $_SESSION['indirizzoEmail'] : 'test@example.com';

// Imposta la data corrente
$data_corrente = date('Y-m-d');

// Connessione al database
$conn = new mysqli("127.0.0.1", "root", "", "sample", 3306);
if ($conn->connect_error) {
    die("Connessione fallita: " . $conn->connect_error);
}
$conn->set_charset("utf8");

// Chiamata alla stored procedure inserisciCommento
$queryInsert = "CALL inserisciCommento('$indirizzoEmail', '$nome_progetto', '$data_corrente', '$commento')";
if ($conn->query($queryInsert)) {
    // Gestisce eventuali result set multipli della chiamata a stored procedure
    while($conn->more_results() && $conn->next_result()) { }
    $message = "Commento inserito correttamente.";
} else {
    $message = "Errore nell'inserimento del commento: " . $conn->error;
}

// Se il commento è stato inserito correttamente, registra l'evento su MongoDB
if (strpos($message, "inserito") !== false) {
    logEvent("Commento inserito per il progetto '$nome_progetto' da parte dell'utente '$indirizzoEmail'. Testo commento: '$commento'.");
}

$conn->close();

// Reindirizza alla pagina di visualizzazione dei commenti, passando il nome del progetto e il messaggio
header("Location: visualizza_commenti.php?nome=" . urlencode($nome_progetto) . "&message=" . urlencode($message));
exit();
?>

