<?php
session_start();

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    die("Metodo non consentito.");
}

// Includi il file che gestisce il log degli eventi su MongoDB
require_once 'log_event.php';

// Verifica che i campi obbligatori siano stati inviati
if (!isset($_POST["nomeProfilo"]) || empty(trim($_POST["nomeProfilo"]))) {
    die("Nome profilo non specificato.");
}
if (!isset($_POST["nomeProgetto"]) || empty(trim($_POST["nomeProgetto"]))) {
    die("Nome progetto non specificato.");
}

$nomeProfilo  = trim($_POST["nomeProfilo"]);
$nomeProgetto = trim($_POST["nomeProgetto"]);

$conn = new mysqli("127.0.0.1", "root", "", "sample", 3306);
if ($conn->connect_error) {
    die("Connessione fallita: " . $conn->connect_error);
}
$conn->set_charset("utf8");

// Prepara e chiama la stored procedure inserisciProfilo
$query = "CALL inserisciProfilo(?, ?)";
$stmt = $conn->prepare($query);
if (!$stmt) {
    die("Preparazione della query fallita: " . $conn->error);
}
$stmt->bind_param("ss", $nomeProfilo, $nomeProgetto);

$message = "";
try {
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result) {
        $row = $result->fetch_assoc();
        $message = $row["Messaggio"];
        $result->free();
    } else {
        $message = "Profilo inserito correttamente.";
    }
    
    // Registra l'evento su MongoDB se l'operazione ha avuto successo
    if (strpos($message, "inserito") !== false || strpos($message, "inserita") !== false) {
        logEvent("Profilo '$nomeProfilo' inserito correttamente per il progetto '$nomeProgetto'.");
    }
    
} catch (Exception $e) {
    $message = "Errore nell'inserimento del profilo: " . $e->getMessage();
}

$stmt->close();
$conn->close();

// Mostra il messaggio in un popup e reindirizza alla pagina dei progetti
echo "<script>
        alert('" . htmlspecialchars($message, ENT_QUOTES, 'UTF-8') . "');
        window.location.href = 'progetti.php';
      </script>";
exit();
?>
