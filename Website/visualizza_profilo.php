<?php
session_start();

// Verifica che sia stato passato l'id del profilo tramite GET
if (!isset($_GET['idProfilo']) || empty($_GET['idProfilo'])) {
    die("Profilo non specificato.");
}
$idProfilo = $_GET['idProfilo'];

// Connessione al database
$conn = new mysqli("127.0.0.1", "root", "", "sample", 3306);
if ($conn->connect_error) {
    die("Connessione fallita: " . $conn->connect_error);
}
$conn->set_charset("utf8");

// Query per estrarre le competenze richieste per il profilo
$query = "SELECT nomeCompetenza, livello FROM RICHIEDERE WHERE idProfilo = '".$conn->real_escape_string($idProfilo)."'";
$result = $conn->query($query);
if (!$result) {
    die("Errore nella query: " . $conn->error);
}
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <title>Dettaglio Profilo</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f4f9;
            color: #333;
            margin: 0;
            padding: 20px;
        }
        .container {
            max-width: 800px;
            margin: auto;
            background-color: #fff;
            border: 1px solid #ddd;
            border-radius: 8px;
            padding: 20px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        h1 {
            text-align: center;
            font-size: 28px;
            color: #444;
            margin-bottom: 20px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        th, td {
            border: 1px solid #ddd;
            padding: 10px;
            text-align: center;
        }
        th {
            background-color: #f4f4f4;
        }
        .back-button {
            display: block;
            width: 200px;
            margin: 20px auto;
            padding: 10px;
            text-align: center;
            background-color: #5c6bc0;
            color: #fff;
            text-decoration: none;
            border-radius: 4px;
        }
        .back-button:hover {
            background-color: #3f51b5;
        }
    </style>
</head>
<body>
<div class="container">
    <h1>Competenze Richieste</h1>
    <table>
        <thead>
            <tr>
                <th>Competenza</th>
                <th>Livello Richiesto</th>
            </tr>
        </thead>
        <tbody>
            <?php
            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    echo "<tr>";
                    echo "<td>" . htmlspecialchars($row['nomeCompetenza']) . "</td>";
                    echo "<td>" . htmlspecialchars($row['livello']) . "</td>";
                    echo "</tr>";
                }
            } else {
                echo "<tr><td colspan='2'>Nessuna competenza richiesta trovata per questo profilo.</td></tr>";
            }
            ?>
        </tbody>
    </table>
    <a href="javascript:history.back()" class="back-button">Indietro</a>
</div>
<?php
$result->free();
$conn->close();
?>
</body>
</html>
