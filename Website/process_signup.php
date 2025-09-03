<?php
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

// Determina il ruolo; se non impostato, si assume "utente"
$ruolo = isset($_POST['ruolo']) ? $_POST['ruolo'] : 'utente';

// Recupera l'indirizzo email in base al ruolo
if ($ruolo === 'utente') {
    $indirizzoEmail = $_POST['indirizzoEmail'] ?? die("Errore: indirizzoEmail non fornito.");
} else {
    $indirizzoEmail = $_POST['email'] ?? die("Errore: email non fornita.");
}

// Verifica preliminare dell'anno di nascita se ruolo è "utente"
if ($ruolo === 'utente') {
    $anno_nascita = $_POST['anno_nascita'] ?? die("Errore: anno_nascita non fornito.");
    $anno_corrente = date("Y");
    
    // Verifica che l'anno di nascita abbia senso (non futuro e non troppo passato)
    if ($anno_nascita > $anno_corrente) {
        die("
        <!DOCTYPE html>
        <html lang='it'>
        <head>
            <meta charset='UTF-8'>
            <title>Errore Registrazione - Bostarter</title>
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
                    color: #444;
                    margin-bottom: 30px;
                }
                p {
                    font-size: 16px;
                    margin: 20px 0;
                }
                .error {
                    color: #e53935;
                }
                button {
                    background-color: #5c6bc0;
                    color: #fff;
                    border: none;
                    padding: 12px 20px;
                    font-size: 16px;
                    border-radius: 5px;
                    cursor: pointer;
                    transition: background-color 0.3s ease;
                    margin-top: 20px;
                }
                button:hover {
                    background-color: #3f51b5;
                }
            </style>
        </head>
        <body>
            <div class='container'>
                <h1 class='error'>Errore nella registrazione</h1>
                <p class='error'>L'anno di nascita non può essere nel futuro.</p>
                <form action='signup_form.php' method='get'>
                    <button type='submit'>Torna alla Registrazione</button>
                </form>
            </div>
        </body>
        </html>");
    }
    
    // Verifica che l'anno di nascita non sia troppo indietro nel tempo (es. oltre 120 anni fa)
    if ($anno_corrente - $anno_nascita > 120) {
        die("
        <!DOCTYPE html>
        <html lang='it'>
        <head>
            <meta charset='UTF-8'>
            <title>Errore Registrazione - Bostarter</title>
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
                    color: #444;
                    margin-bottom: 30px;
                }
                p {
                    font-size: 16px;
                    margin: 20px 0;
                }
                .error {
                    color: #e53935;
                }
                button {
                    background-color: #5c6bc0;
                    color: #fff;
                    border: none;
                    padding: 12px 20px;
                    font-size: 16px;
                    border-radius: 5px;
                    cursor: pointer;
                    transition: background-color 0.3s ease;
                    margin-top: 20px;
                }
                button:hover {
                    background-color: #3f51b5;
                }
            </style>
        </head>
        <body>
            <div class='container'>
                <h1 class='error'>Errore nella registrazione</h1>
                <p class='error'>L'anno di nascita inserito non è valido.</p>
                <form action='signup_form.php' method='get'>
                    <button type='submit'>Torna alla Registrazione</button>
                </form>
            </div>
        </body>
        </html>");
    }
    
    // Tutti gli altri dati utente
    $nickname     = $_POST['nickname'] ?? die("Errore: nickname non fornito.");
    $pass         = $_POST['password'] ?? die("Errore: password non fornita.");
    $nome         = $_POST['nome'] ?? die("Errore: nome non fornito.");
    $cognome      = $_POST['cognome'] ?? die("Errore: cognome non fornito.");
    $luogo_nascita= $_POST['luogo_nascita'] ?? die("Errore: luogo_nascita non fornito.");
    
    $query = "CALL signUpUtente(?, ?, ?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("sssssis", $indirizzoEmail, $nickname, $pass, $nome, $cognome, $anno_nascita, $luogo_nascita);
} elseif ($ruolo === 'creatore') {
    // Registrazione creatore: basta l'indirizzo email
    $query = "CALL signUpCreatore(?)";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("s", $indirizzoEmail);
} elseif ($ruolo === 'amministratore') {
    // Registrazione amministratore: email e codice di sicurezza
    $codice_sicurezza = $_POST['codice_sicurezza'] ?? die("Errore: codice_sicurezza non fornito.");
    $query = "CALL signUpAmministratore(?, ?)";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("ss", $indirizzoEmail, $codice_sicurezza);
} else {
    die("Ruolo non valido.");
}
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <title>Risultato Registrazione - Bostarter</title>
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
            color: #444;
            margin-bottom: 30px;
        }
        p {
            font-size: 16px;
            margin: 20px 0;
        }
        .error {
            color: #e53935;
        }
        button {
            background-color: #5c6bc0;
            color: #fff;
            border: none;
            padding: 12px 20px;
            font-size: 16px;
            border-radius: 5px;
            cursor: pointer;
            transition: background-color 0.3s ease;
            margin-top: 20px;
        }
        button:hover {
            background-color: #3f51b5;
        }
    </style>
</head>
<body>
    <div class="container">
        <?php
        try {
            $stmt->execute();
            $result = $stmt->get_result();
            if ($result) {
                // Mostra eventuali messaggi restituiti dalla stored procedure
                while ($row = $result->fetch_assoc()) {
                    echo "<p>" . $row["Messaggio"] . "</p>";
                }
                $result->free();
            } else {
                echo "<p>Registrazione eseguita con successo.</p>";
            }
            
            // Se la registrazione va a buon fine, logga l'evento su MongoDB
            require 'log_event.php';
            logEvent("Nuovo $ruolo registrato: " . $indirizzoEmail);
            
            // Bottone per tornare alla Home
            echo '<form action="index.php" method="get">
                    <button type="submit">Torna alla Home</button>
                  </form>';
        } catch (mysqli_sql_exception $ex) {
            echo "<h1 class='error'>Errore nella registrazione</h1>";
            
            // Personalizzazione dei messaggi di errore
            if (strpos($ex->getMessage(), "BIGINT UNSIGNED value is out of range") !== false) {
                echo "<p class='error'>L'anno di nascita inserito non è valido. Verifica di aver inserito un anno corretto.</p>";
            } else {
                echo "<p class='error'>" . $ex->getMessage() . "</p>";
            }
            
            echo '<form action="signup_form.php" method="get">
                    <button type="submit">Torna alla Registrazione</button>
                  </form>';
        }
        $stmt->close();
        $conn->close();
        ?>
    </div>
</body>
</html>