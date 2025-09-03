<?php
session_start();

// Includi il logger (se lo stai usando)
require_once 'log_event.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    die("Metodo non consentito.");
}

// Verifica che tutti i campi obbligatori siano stati inviati
$required = ['nome', 'descrizione', 'prezzo', 'quantita', 'nomeProgetto'];
foreach ($required as $field) {
    if (!isset($_POST[$field]) || trim($_POST[$field]) === '') {
        die("Campo '$field' non specificato.");
    }
}

$nome         = trim($_POST['nome']);
$descrizione  = trim($_POST['descrizione']);
$prezzo       = floatval($_POST['prezzo']);
$quantita     = intval($_POST['quantita']);
$nomeProgetto = trim($_POST['nomeProgetto']);

// Controlli server-side
if ($prezzo <= 0) {
    die("Il prezzo deve essere maggiore di 0.");
}
if ($quantita <= 0) {
    die("La quantità deve essere maggiore di 0.");
}

// Connessione al database
$conn = new mysqli("127.0.0.1", "root", "", "sample", 3306);
if ($conn->connect_error) {
    die("Connessione fallita: " . $conn->connect_error);
}
$conn->set_charset("utf8");

// Chiama la stored procedure
$stmt = $conn->prepare("CALL inserisciComponente(?, ?, ?, ?, ?)");
if (!$stmt) {
    die("Errore nella preparazione della query: " . $conn->error);
}
$stmt->bind_param("ssdis", $nome, $descrizione, $prezzo, $quantita, $nomeProgetto);

try {
    $stmt->execute();
    // Se la procedura restituisce un SELECT con Messaggio
    if ($result = $stmt->get_result()) {
        $row = $result->fetch_assoc();
        $message = $row['Messaggio'];
        $result->free();
    } else {
        // In assenza di SELECT di feedback, fornisci un messaggio generico
        $message = "Componente inserita correttamente.";
    }
    // Logga l'evento se è andato a buon fine
    if (strpos(strtolower($message), 'correttamente') !== false && function_exists('logEvent')) {
        logEvent("Componente '$nome' aggiunta al progetto '$nomeProgetto' (prezzo: $prezzo, quantità: $quantita).");
    }
} catch (Exception $e) {
    $message = "Errore nell'inserimento della componente: " . $e->getMessage();
}

$stmt->close();
$conn->close();

// Feedback all'utente e redirect
echo "<script>
        alert('" . htmlspecialchars($message, ENT_QUOTES, 'UTF-8') . "');
        window.location.href = 'progetti.php';
      </script>";
exit();
