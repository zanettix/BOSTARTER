<?php
session_start();

// Verifica che il progetto sia specificato
if (!isset($_GET['nome']) || empty($_GET['nome'])) {
    die("Progetto non specificato.");
}
$nome_progetto = $_GET['nome'];

// Se presente, recupera il messaggio passato via GET (ad esempio dopo un inserimento)
$message = isset($_GET['message']) ? $_GET['message'] : "";

// Recupera l'indirizzo email dell'utente dalla sessione per il controllo delle risposte
$userEmail = isset($_SESSION['indirizzoEmail']) ? $_SESSION['indirizzoEmail'] : '';

$conn = new mysqli("127.0.0.1", "root", "", "sample", 3306);
if ($conn->connect_error) {
    die("Connessione fallita: " . $conn->connect_error);
}
$conn->set_charset("utf8");

// Recupera l'indirizzo email del creatore del progetto
$queryProject = "SELECT indirizzoEmailCreatore FROM PROGETTO WHERE nome = '".$conn->real_escape_string($nome_progetto)."'";
$resultProject = $conn->query($queryProject);
$projectCreator = "";
if ($resultProject && $rowProject = $resultProject->fetch_assoc()){
    $projectCreator = $rowProject['indirizzoEmailCreatore'];
    $resultProject->free();
} else {
    // Se il progetto non viene trovato, si può interrompere l'esecuzione oppure impostare il creatore a vuoto
    die("Progetto non trovato.");
}

// Recupera tutti i commenti top-level per il progetto (in ordine cronologico)
$queryComments = "SELECT id, data_, testo, indirizzoEmailUtente 
                  FROM COMMENTO 
                  WHERE nomeProgetto = '".$conn->real_escape_string($nome_progetto)."'
                  ORDER BY data_ ASC";
$resultComments = $conn->query($queryComments);
if(!$resultComments){
    die("Errore nella query dei commenti: " . $conn->error);
}

$comments = array();
$commentIds = array();
while($row = $resultComments->fetch_assoc()){
    $comments[] = $row;
    $commentIds[] = $row['id'];
}

// Recupera tutte le risposte per i commenti di questo progetto (se esistono)
$replies = array();
if(count($commentIds) > 0) {
    $ids = implode(",", $commentIds);
    $queryReplies = "SELECT idCommento, data_, testo, indirizzoEmailCreatore 
                     FROM RISPOSTA 
                     WHERE idCommento IN ($ids)
                     ORDER BY data_ ASC";
    $resultReplies = $conn->query($queryReplies);
    if($resultReplies){
        while($row = $resultReplies->fetch_assoc()){
            $replies[$row['idCommento']][] = $row;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <title>Commenti e Risposte per il Progetto <?php echo htmlspecialchars($nome_progetto); ?></title>
    <style>
        /* (Inserisci qui i tuoi stili CSS per la pagina) */
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
            font-size: 28px;
            color: #444;
            margin-bottom: 30px;
        }
        .comment {
            margin-bottom: 20px;
            border-bottom: 1px solid #eee;
            padding-bottom: 10px;
        }
        .reply {
            margin-left: 30px;
            padding-left: 10px;
            border-left: 2px solid #ddd;
            margin-top: 10px;
        }
        .comment-meta, .reply-meta {
            font-size: 12px;
            color: #777;
            margin-bottom: 5px;
        }
        .comment-text, .reply-text {
            font-size: 14px;
            line-height: 1.4;
        }
        .comment-form {
            margin-top: 30px;
        }
        .comment-form textarea {
            width: 100%;
            height: 100px;
            padding: 10px;
            font-size: 14px;
            border: 1px solid #ddd;
            border-radius: 4px;
            resize: vertical;
        }
        .comment-form button {
            background-color: #5c6bc0;
            color: #fff;
            border: none;
            padding: 10px 20px;
            font-size: 14px;
            border-radius: 4px;
            cursor: pointer;
            transition: background-color 0.3s ease;
            margin-top: 10px;
        }
        .comment-form button:hover {
            background-color: #3f51b5;
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
            display: inline-block;
            margin-top: 20px;
            transition: background-color 0.3s ease;
        }
        .back-button:hover {
            background-color: #777777;
        }
        /* Modal per "Rispondi" (solo per creatore) */
        #modalReplyOverlay {
            display: none;
            position: fixed;
            top: 0; left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.5);
            z-index: 1300;
        }
        #modalReply {
            position: absolute;
            top: 50%;
            left: 50%;
            width: 400px;
            padding: 20px;
            background: #fff;
            border-radius: 8px;
            transform: translate(-50%, -50%);
            box-shadow: 0 2px 5px rgba(0,0,0,0.3);
            text-align: center;
        }
        #modalReply textarea {
            width: 80%;
            padding: 10px;
            font-size: 14px;
            margin-bottom: 15px;
            border: 1px solid #ddd;
            border-radius: 4px;
            resize: vertical;
        }
        #modalReply button {
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
        #modalReply button:hover {
            background-color: #3f51b5;
        }
    </style>
</head>
<body>
<div class="container">
    <h1>Commenti e Risposte per il Progetto "<?php echo htmlspecialchars($nome_progetto); ?>"</h1>

    <?php if(!empty($message)): ?>
        <script>
            alert("<?php echo htmlspecialchars($message, ENT_QUOTES, 'UTF-8'); ?>");
        </script>
    <?php endif; ?>

    <?php
    if(count($comments) > 0){
        foreach($comments as $comment){
            echo "<div class='comment'>";
            echo "<div class='comment-meta'><strong>" . htmlspecialchars($comment['indirizzoEmailUtente']) . "</strong> il " . htmlspecialchars($comment['data_']) . "</div>";
            echo "<div class='comment-text'>" . nl2br(htmlspecialchars($comment['testo'])) . "</div>";
            
            // Mostra il bottone "Rispondi" solo se l'utente ha ruolo "creatore" 
            // E inoltre deve essere lo stesso creatore del progetto (ovvero $userEmail === $projectCreator)
            if(isset($_SESSION['ruolo']) && $_SESSION['ruolo'] === 'creatore' && $userEmail === $projectCreator){
                $alreadyReplied = false;
                if(isset($replies[$comment['id']])){
                    foreach($replies[$comment['id']] as $reply){
                        if($reply['indirizzoEmailCreatore'] === $userEmail){
                            $alreadyReplied = true;
                            break;
                        }
                    }
                }
                if(!$alreadyReplied){
                    echo "<button class='btn-reply' onclick='openReplyModal(" . htmlspecialchars($comment['id']) . ")'>Rispondi</button>";
                }
            }
            
            // Visualizza le risposte per questo commento
            if(isset($replies[$comment['id']])){
                foreach($replies[$comment['id']] as $reply){
                    echo "<div class='reply'>";
                    echo "<div class='reply-meta'><strong>" . htmlspecialchars($reply['indirizzoEmailCreatore']) . "</strong> il " . htmlspecialchars($reply['data_']) . "</div>";
                    echo "<div class='reply-text'>" . nl2br(htmlspecialchars($reply['testo'])) . "</div>";
                    echo "</div>";
                }
            }
            echo "</div>";
        }
    } else {
        echo "<p>Nessun commento trovato per questo progetto.</p>";
    }
    ?>

    <div class="comment-form">
        <h2>Inserisci un Nuovo Commento</h2>
        <!-- Il form ora punta al nuovo script che gestisce l'inserimento -->
        <form action="process_inserisci_commento.php?nome=<?php echo urlencode($nome_progetto); ?>" method="post">
            <textarea name="commento" placeholder="Scrivi il tuo commento..." required></textarea>
            <br>
            <button type="submit" name="inserisciCommento">Invia Commento</button>
        </form>
    </div>

    <div style="text-align: center;">
        <a href="progetti.php" class="back-button">Indietro</a>
    </div>
</div>

<!-- Modal per rispondere a un commento (solo per creatore) -->
<div id="modalReplyOverlay">
    <div id="modalReply">
        <h2>Rispondi al Commento</h2>
        <form id="replyForm" action="process_inserisci_risposta.php" method="post">
            <!-- Campo nascosto per l'id del commento -->
            <input type="hidden" name="idCommento" id="modalReplyId">
            <!-- Campo nascosto per il nome del progetto -->
            <input type="hidden" name="nomeProgetto" value="<?php echo htmlspecialchars($nome_progetto); ?>">
            <textarea name="testoRisposta" placeholder="Inserisci la tua risposta..." rows="3" required></textarea>
            <br>
            <button type="submit" class="btn-confirm">Conferma</button>
            <button type="button" class="btn-cancel" onclick="closeReplyModal()">Annulla</button>
        </form>
    </div>
</div>

<script>
    function openReplyModal(commentId) {
        document.getElementById('modalReplyId').value = commentId;
        document.getElementById('modalReplyOverlay').style.display = 'block';
    }
    function closeReplyModal() {
        document.getElementById('modalReplyOverlay').style.display = 'none';
    }
</script>

<?php
$resultComments->free();
$conn->close();
?>
</body>
</html>
