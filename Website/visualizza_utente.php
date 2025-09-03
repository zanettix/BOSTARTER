<?php
session_start();

// Controlla se l'email dell'utente è stata passata come parametro GET
if (!isset($_GET['email']) || empty($_GET['email'])) {
    die("Email utente non specificata.");
}

$email = $_GET['email'];

$conn = new mysqli("127.0.0.1", "root", "", "sample", 3306);
if ($conn->connect_error) {
    die("Connessione fallita: " . $conn->connect_error);
}
$conn->set_charset("utf8");

// Recupera i dati anagrafici dell'utente
$queryUser = "SELECT indirizzoEmail, nickname, nome, cognome, anno_nascita, luogo_nascita 
              FROM UTENTE 
              WHERE indirizzoEmail = ?";
$stmt = $conn->prepare($queryUser);
$stmt->bind_param("s", $email);
$stmt->execute();
$resultUser = $stmt->get_result();
if ($resultUser->num_rows === 0) {
    die("Utente non trovato.");
}
$user = $resultUser->fetch_assoc();
$stmt->close();

// Recupera le competenze dell'utente (e il livello associato)
$querySkills = "SELECT nomeCompetenza, livello 
                FROM INDICARE 
                WHERE indirizzoEmailUtente = ?";
$stmt2 = $conn->prepare($querySkills);
$stmt2->bind_param("s", $email);
$stmt2->execute();
$resultSkills = $stmt2->get_result();
$skills = array();
while($row = $resultSkills->fetch_assoc()){
    $skills[] = $row;
}
$stmt2->close();

$conn->close();
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <title>Dettagli Utente</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f4f9;
            margin: 0;
            padding: 20px;
            color: #333;
        }
        .container {
            max-width: 800px;
            margin: 0 auto;
            background: #fff;
            padding: 20px;
            border: 1px solid #ddd;
            border-radius: 8px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        .section {
            margin-bottom: 30px;
        }
        .section h2 {
            background-color: #388e3c;
            color: #fff;
            padding: 10px;
            border-radius: 4px;
            margin-top: 0;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }
        table, th, td {
            border: 1px solid #ddd;
        }
        th, td {
            padding: 10px;
            text-align: left;
        }
        th {
            background-color: #f4f4f4;
        }
        .back-button {
            display: inline-block;
            margin-top: 20px;
            padding: 10px 20px;
            background-color: #888888;
            color: #fff;
            text-decoration: none;
            border-radius: 4px;
            transition: background-color 0.3s ease;
        }
        .back-button:hover {
            background-color: #777777;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Dettagli Utente</h1>
        
        <div class="section">
            <h2>Dati Anagrafici</h2>
            <table>
                <tr>
                    <th>Indirizzo Email</th>
                    <td><?php echo htmlspecialchars($user['indirizzoEmail']); ?></td>
                </tr>
                <tr>
                    <th>Nickname</th>
                    <td><?php echo htmlspecialchars($user['nickname']); ?></td>
                </tr>
                <tr>
                    <th>Nome</th>
                    <td><?php echo htmlspecialchars($user['nome']); ?></td>
                </tr>
                <tr>
                    <th>Cognome</th>
                    <td><?php echo htmlspecialchars($user['cognome']); ?></td>
                </tr>
                <tr>
                    <th>Anno di Nascita</th>
                    <td><?php echo htmlspecialchars($user['anno_nascita']); ?></td>
                </tr>
                <tr>
                    <th>Luogo di Nascita</th>
                    <td><?php echo htmlspecialchars($user['luogo_nascita']); ?></td>
                </tr>
            </table>
        </div>
        
        <div class="section">
            <h2>Competenze</h2>
            <?php if(count($skills) > 0): ?>
            <table>
                <thead>
                    <tr>
                        <th>Competenza</th>
                        <th>Livello</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($skills as $skill): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($skill['nomeCompetenza']); ?></td>
                        <td><?php echo htmlspecialchars($skill['livello']); ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <?php else: ?>
               <p>Non sono state inserite competenze.</p>
            <?php endif; ?>
        </div>
        
        <a href="javascript:history.back()" class="back-button">Indietro</a>
    </div>
</body>
</html>
