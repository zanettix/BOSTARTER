<?php
session_start();

// Abilita la visualizzazione degli errori per il debug
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Attiva il report degli errori MySQLi come eccezioni
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

try {
    // Crea la connessione usando MySQLi
    $conn = new mysqli("127.0.0.1", "root", "", "sample", 3306);
    $conn->set_charset("utf8");
} catch (mysqli_sql_exception $e) {
    die("Connessione fallita: " . $e->getMessage());
}

// Recupera i dati inviati dal form
$ruolo = isset($_POST['ruolo']) ? $_POST['ruolo'] : 'utente';
$nickname = $_POST['nickname'] ?? die("Errore: nickname non fornito.");
$pass     = $_POST['password'] ?? die("Errore: password non fornita.");

// Preparazione della stored procedure per il login in base al ruolo
if ($ruolo === 'utente') {
    $query = "CALL loginUtente(?, ?)";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("ss", $nickname, $pass);
} elseif ($ruolo === 'creatore') {
    $query = "CALL loginCreatore(?, ?)";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("ss", $nickname, $pass);
} elseif ($ruolo === 'amministratore') {
    $codice_sicurezza = $_POST['codice_sicurezza'] ?? die("Errore: codice di sicurezza non fornito.");
    $query = "CALL loginAmministratore(?, ?, ?)";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("sss", $nickname, $pass, $codice_sicurezza);
} else {
    die("Errore: ruolo non gestito in questo script.");
}

try {
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result) {
        $row = $result->fetch_assoc();
        // Qui la stored procedure ha confermato che le credenziali sono corrette.
        // Ora recuperiamo l'indirizzo email dall'utente nella tabella UTENTE,
        // dato il nickname e la password.
        $stmt->close();
        
        $queryEmail = "SELECT indirizzoEmail FROM UTENTE WHERE nickname = ? AND password_ = ?";
        $emailStmt = $conn->prepare($queryEmail);
        $emailStmt->bind_param("ss", $nickname, $pass);
        $emailStmt->execute();
        $emailResult = $emailStmt->get_result();
        if ($emailRow = $emailResult->fetch_assoc()) {
            $indirizzoEmail = $emailRow['indirizzoEmail'];
        } else {
            die("Errore: Email non trovata per il nickname fornito.");
        }
        $emailStmt->close();
        
        // Imposta le variabili di sessione per mantenere l'autenticazione
        $_SESSION['indirizzoEmail'] = $indirizzoEmail;
        $_SESSION['nickname'] = $nickname;
        $_SESSION['ruolo'] = $ruolo;
        
        // Redirigi l'utente alla dashboard in base al ruolo
        if ($ruolo === 'utente') {
            header("Location: dashboard.php");
        } elseif ($ruolo === 'creatore') {
            header("Location: dashboard.php");
        } elseif ($ruolo === 'amministratore') {
            header("Location: dashboard.php");
        }
        exit();
    } else {
        throw new Exception("Autenticazione non riuscita.");
    }
} catch (mysqli_sql_exception $ex) {
    ?>
    <!DOCTYPE html>
    <html lang="it">
    <head>
        <meta charset="UTF-8">
        <title>Errore Login - Bostarter</title>
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
                color: #e53935;
                margin-bottom: 30px;
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
                cursor: pointer;
                text-decoration: none;
            }
            .back-button:hover {
                background-color: #777777;
            }
        </style>
    </head>
    <body>
        <div class="container">
            <h1>Errore Login</h1>
            <p><?php echo $ex->getMessage(); ?></p>
            <a href="login_form.php" class="back-button">Torna al Login</a>
        </div>
    </body>
    </html>
    <?php
}
$conn->close();
?>
