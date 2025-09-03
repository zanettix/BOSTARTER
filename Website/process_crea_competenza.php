<?php
session_start();

if (!isset($_SESSION['nickname'])) {
    // Se non c'è una variabile di sessione, reindirizza al login
    header("Location: login_form.php");
    exit();
}

// Includi il file che gestisce il log degli eventi su MongoDB
require_once 'log_event.php';

$indirizzoEmail = isset($_SESSION['indirizzoEmail']) ? $_SESSION['indirizzoEmail'] : 'test@example.com';

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    if (!isset($_POST['nomeCompetenza']) || empty(trim($_POST['nomeCompetenza']))) {
        die("Competenza non specificata.");
    }
    $nomeCompetenza = trim($_POST['nomeCompetenza']);
    
    $conn = new mysqli("127.0.0.1", "root", "", "sample", 3306);
    if ($conn->connect_error) {
        die("Connessione fallita: " . $conn->connect_error);
    }
    $conn->set_charset("utf8");

    // Prepara e chiama la stored procedure creaCompetenza
    $query = "CALL creaCompetenza(?, ?)";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("ss", $nomeCompetenza, $indirizzoEmail);
    
    try {
        $stmt->execute();
        // Se la procedura restituisce un result set, lo elaboriamo per ottenere il messaggio
        $result = $stmt->get_result();
        if ($result) {
            $row = $result->fetch_assoc();
            $message = $row["Messaggio"];
        } else {
            $message = "Competenza creata correttamente.";
        }
        
        // Se l'operazione ha avuto successo, registra l'evento su MongoDB
        if (strpos($message, "creata") !== false) {
            logEvent("Competenza '$nomeCompetenza' creata con successo da amministratore '$indirizzoEmail'.");
        }
        
    } catch (Exception $e) {
        $message = "Errore: " . $e->getMessage();
    }
    $stmt->close();
    $conn->close();
    
    // Mostra il messaggio in un popup e reindirizza alla pagina di assegnazione competenze
    echo "<script>alert('" . htmlspecialchars($message, ENT_QUOTES, 'UTF-8') . "'); window.location.href='indica_livello_competenza.php';</script>";
    exit();
} else {
    die("Metodo non consentito.");
}
?>

