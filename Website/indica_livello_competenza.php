<?php
session_start();

$conn = new mysqli("127.0.0.1", "root", "", "sample", 3306);
if ($conn->connect_error) {
    die("Connessione fallita: " . $conn->connect_error);
}
$conn->set_charset("utf8");

// Recupera il parametro di ricerca, se presente
$search = isset($_GET['search']) ? $conn->real_escape_string($_GET['search']) : '';
$query = "SELECT nome FROM COMPETENZA";
if ($search !== '') {
    $query .= " WHERE nome LIKE '%$search%'";
}
$result = $conn->query($query);
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <title>Indica Livello Competenza - Bostarter</title>
    <style>
        body {
            margin: 0;
            padding: 0;
            font-family: Arial, sans-serif;
            background-color: #f4f4f9;
            color: #333;
        }
        .container {
            max-width: 800px;
            margin: 50px auto;
            background-color: #fff;
            border: 1px solid #ddd;
            border-radius: 8px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            padding: 30px;
        }
        h1 {
            text-align: center;
            font-size: 32px;
            color: #444;
            margin-bottom: 30px;
        }
        .search-bar {
            text-align: center;
            margin-bottom: 20px;
        }
        .search-bar input[type="text"] {
            padding: 10px;
            width: 60%;
            max-width: 400px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 14px;
        }
        .search-bar input[type="submit"] {
            background-color: #5c6bc0;
            color: #fff;
            border: none;
            padding: 10px 15px;
            font-size: 14px;
            border-radius: 4px;
            cursor: pointer;
            margin-left: 10px;
            transition: background-color 0.3s ease;
        }
        .search-bar input[type="submit"]:hover {
            background-color: #3f51b5;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        table, th, td {
            border: 1px solid #ddd;
        }
        th, td {
            padding: 10px;
            text-align: center;
        }
        th {
            background-color: #f4f4f4;
        }
        .btn-assign {
            background-color: #5c6bc0;
            color: #fff;
            border: none;
            padding: 6px 12px;
            border-radius: 4px;
            text-decoration: none;
            font-size: 14px;
            transition: background-color 0.3s ease;
            cursor: pointer;
        }
        .btn-assign:hover {
            background-color: #3f51b5;
        }
        .btn-create {
            background-color: #388e3c;
            color: #fff;
            border: none;
            padding: 10px 20px;
            font-size: 16px;
            border-radius: 5px;
            cursor: pointer;
            transition: background-color 0.3s ease;
        }
        .btn-create:hover {
            background-color: #2e7d32;
        }
        .back-button {
            background-color: #888888;
            color: #fff;
            border: none;
            padding: 8px 16px;
            font-size: 14px;
            border-radius: 5px;
            text-decoration: none;
            display: inline-block;
            margin-top: 20px;
        }
        .back-button:hover {
            background-color: #777777;
        }
        /* Stili per il modal per il livello */
        #modalOverlay {
            display: none;
            position: fixed;
            top: 0; left: 0;
            width: 100%; height: 100%;
            background-color: rgba(0,0,0,0.5);
            z-index: 1000;
        }
        #modal {
            position: absolute;
            top: 50%;
            left: 50%;
            width: 300px;
            padding: 20px;
            background: #fff;
            border-radius: 8px;
            transform: translate(-50%, -50%);
            box-shadow: 0 2px 5px rgba(0,0,0,0.3);
            text-align: center;
        }
        #modal input[type="number"] {
            width: 80%;
            padding: 10px;
            font-size: 16px;
            margin-bottom: 20px;
        }
        #modal button {
            background-color: #5c6bc0;
            color: #fff;
            border: none;
            padding: 10px 20px;
            font-size: 16px;
            border-radius: 5px;
            cursor: pointer;
            transition: background-color 0.3s ease;
            margin: 0 5px;
        }
        #modal button:hover {
            background-color: #3f51b5;
        }
        /* Stili per il modal di creazione competenza (solo admin) */
        #modalCreateOverlay {
            display: none;
            position: fixed;
            top: 0; left: 0;
            width: 100%; height: 100%;
            background-color: rgba(0,0,0,0.5);
            z-index: 1100;
        }
        #modalCreate {
            position: absolute;
            top: 50%;
            left: 50%;
            width: 300px;
            padding: 20px;
            background: #fff;
            border-radius: 8px;
            transform: translate(-50%, -50%);
            box-shadow: 0 2px 5px rgba(0,0,0,0.3);
            text-align: center;
        }
        #modalCreate input[type="text"] {
            width: 80%;
            padding: 10px;
            font-size: 16px;
            margin-bottom: 20px;
        }
        #modalCreate button {
            background-color: #388e3c;
            color: #fff;
            border: none;
            padding: 10px 20px;
            font-size: 16px;
            border-radius: 5px;
            cursor: pointer;
            transition: background-color 0.3s ease;
            margin: 0 5px;
        }
        #modalCreate button:hover {
            background-color: #2e7d32;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Lista Competenze</h1>
        <div class="search-bar">
            <form action="indica_livello_competenza.php" method="get">
                <input type="text" name="search" placeholder="Cerca competenza..." value="<?php echo htmlspecialchars($search); ?>">
                <input type="submit" value="Cerca">
            </form>
        </div>
        <?php if($result->num_rows > 0): ?>
        <table>
            <thead>
                <tr>
                    <th>Competenza</th>
                    <th>Livello</th>
                </tr>
            </thead>
            <tbody>
                <?php while($row = $result->fetch_assoc()): ?>
                <tr>
                    <td><?php echo htmlspecialchars($row['nome']); ?></td>
                    <td>
                        <!-- Bottone "Indica" che apre il modal per indicare il livello -->
                        <button class="btn-assign" onclick="openModal('<?php echo htmlspecialchars($row['nome']); ?>')">Indica</button>
                    </td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
        <?php else: ?>
            <p>Nessuna competenza trovata.</p>
        <?php endif; ?>
        
        <!-- Pulsante "Crea Nuova Competenza" al centro in fondo, visibile solo se l'utente è amministratore -->
        <div style="text-align: center; margin-top: 30px;">
            <?php if(isset($_SESSION['ruolo']) && $_SESSION['ruolo'] === 'amministratore'): ?>
                <button class="btn-create" onclick="openCreateModal()">Crea Nuova Competenza</button>
            <?php endif; ?>
        </div>
        
        <div style="text-align: center; margin-top: 20px;">
            <a href="dashboard.php" class="back-button">Indietro</a>
        </div>
    </div>

    <!-- Modal per indicare il livello della competenza -->
    <div id="modalOverlay">
        <div id="modal">
            <h2>Assegna Livello</h2>
            <form id="modalForm" action="process_indica_livello.php" method="post">
                <!-- Campo nascosto per il nome della competenza -->
                <input type="hidden" name="competenza" id="modalCompetenza">
                <label for="livello">Inserisci il livello (0-5):</label><br>
                <input type="number" name="livello" id="livello" min="0" max="5" required><br>
                <button type="submit">Conferma</button>
                <button type="button" onclick="closeModal()">Annulla</button>
            </form>
        </div>
    </div>

    <!-- Modal per creare una nuova competenza (visibile solo agli amministratori) -->
    <div id="modalCreateOverlay">
        <div id="modalCreate">
            <h2>Crea Competenza</h2>
            <form id="createForm" action="process_crea_competenza.php" method="post">
                <label for="nomeCompetenza">Nome competenza:</label><br>
                <input type="text" name="nomeCompetenza" id="nomeCompetenza" required><br>
                <button type="submit">Invia</button>
                <button type="button" onclick="closeCreateModal()">Annulla</button>
            </form>
        </div>
    </div>

    <script>
        // Funzioni per il modal di "Assegna Livello"
        function openModal(competenza) {
            document.getElementById('modalCompetenza').value = competenza;
            document.getElementById('modalOverlay').style.display = 'block';
        }
        function closeModal() {
            document.getElementById('modalOverlay').style.display = 'none';
        }
        // Funzioni per il modal di "Crea Competenza" (solo admin)
        function openCreateModal() {
            document.getElementById('modalCreateOverlay').style.display = 'block';
        }
        function closeCreateModal() {
            document.getElementById('modalCreateOverlay').style.display = 'none';
        }
    </script>
    <?php
    $result->free();
    $conn->close();
    ?>
</body>
</html>
