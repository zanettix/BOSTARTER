<?php
session_start();

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    die("Metodo non consentito.");
}

// Includi il file che gestisce il log degli eventi su MongoDB
require_once 'log_event.php';

// Verifica che tutti i campi obbligatori siano stati inviati
if (!isset($_POST["nomeProgetto"]) || empty(trim($_POST["nomeProgetto"]))) {
    die("Progetto non specificato.");
}
if (!isset($_POST["codiceReward"]) || empty(trim($_POST["codiceReward"]))) {
    die("Reward non specificata.");
}
if (!isset($_POST["importo"]) || empty(trim($_POST["importo"]))) {
    die("Importo non specificato.");
}

// Recupera i dati inviati dal form
$nomeProgetto = trim($_POST["nomeProgetto"]);
$codiceReward = trim($_POST["codiceReward"]);
$importo = floatval($_POST["importo"]);  // converte in valore numerico

// Recupera l'indirizzo email dell'utente dalla sessione
if (!isset($_SESSION["indirizzoEmail"])) {
    die("Accesso non autorizzato.");
}
$indirizzoEmail = $_SESSION["indirizzoEmail"];

// Imposta la data corrente nel formato "YYYY-MM-DD"
$data_corrente = date("Y-m-d");

$conn = new mysqli("127.0.0.1", "root", "", "sample", 3306);
if ($conn->connect_error) {
    die("Connessione fallita: " . $conn->connect_error);
}
$conn->set_charset("utf8");

// Prepara e chiama la stored procedure "creaFinanziamento"
$query = "CALL creaFinanziamento(?, ?, ?, ?, ?)";
$stmt = $conn->prepare($query);
if(!$stmt) {
    die("Preparazione della query fallita: " . $conn->error);
}
// I tipi di binding sono: string, string, string (date), double, string
$stmt->bind_param("sssds", $indirizzoEmail, $nomeProgetto, $data_corrente, $importo, $codiceReward);

$message = "";
try {
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result) {
        $row = $result->fetch_assoc();
        $message = $row["Messaggio"];
        $result->free();
    } else {
        $message = "Finanziamento inserito correttamente.";
    }
    
    // Se l'operazione ha avuto successo, registra l'evento su MongoDB
    if (strpos($message, "inserito") !== false) {
        logEvent("Finanziamento inserito: utente '$indirizzoEmail' ha finanziato il progetto '$nomeProgetto' con importo $importo e reward '$codiceReward'.");
    }
    
} catch (Exception $e) {
    $message = "Errore nel finanziamento: " . $e->getMessage();
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
