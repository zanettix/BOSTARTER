<?php
session_start();
if (!isset($_SESSION['nickname'])) {
    header("Location: login_form.php");
    exit();
}

// Get user role from session
$ruolo = isset($_SESSION['ruolo']) ? $_SESSION['ruolo'] : 'utente';
$userEmail = isset($_SESSION['indirizzoEmail']) ? $_SESSION['indirizzoEmail'] : '';

// Database connection
$conn = new mysqli("127.0.0.1", "root", "", "sample", 3306);
if ($conn->connect_error) {
    die("Connessione fallita: " . $conn->connect_error);
}
$conn->set_charset("utf8");

$message = "";
$search = isset($_GET['search']) ? $conn->real_escape_string($_GET['search']) : '';
$finanziaProject = isset($_GET['action']) && $_GET['action'] == 'finanzia' && isset($_GET['nome']) ? 
                   $conn->real_escape_string($_GET['nome']) : '';
$viewComponents = isset($_GET['action']) && $_GET['action'] == 'viewComponents' && isset($_GET['nome']) ? 
                 $conn->real_escape_string($_GET['nome']) : '';

// Function to sanitize output
function e($string) {
    return htmlspecialchars($string, ENT_QUOTES, 'UTF-8');
}

// Get projects
$query = "SELECT nome, descrizione, data_inserimento, budget, stato FROM PROGETTO";
if ($search !== '') {
    $query .= " WHERE nome LIKE '%$search%'";
}
$result = $conn->query($query);

// Get user projects for creators
$userProjects = [];
if ($ruolo === 'creatore' && !empty($userEmail)) {
    $queryUserProjects = "SELECT nome FROM PROGETTO WHERE indirizzoEmailCreatore = ?";
    $stmt = $conn->prepare($queryUserProjects);
    $stmt->bind_param("s", $userEmail);
    $stmt->execute();
    $resultUserProjects = $stmt->get_result();
    while ($r = $resultUserProjects->fetch_assoc()) {
        $userProjects[] = $r;
    }
    $stmt->close();
}

// Function to check if financing is possible for a project
function canFinance($conn, $projectName) {
    $stmt = $conn->prepare("SELECT COUNT(*) AS cnt FROM REWARD 
                           WHERE nomeProgetto = ? 
                           AND codice NOT IN (SELECT codiceReward FROM FINANZIAMENTO WHERE nomeProgetto = ?)");
    $stmt->bind_param("ss", $projectName, $projectName);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $stmt->close();
    return $row['cnt'] > 0;
}

// Function to check if project has profiles for candidature
function hasProfiles($conn, $projectName) {
    $stmt = $conn->prepare("SELECT COUNT(*) AS cnt FROM PROFILO WHERE nomeProgetto = ?");
    $stmt->bind_param("s", $projectName);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $stmt->close();
    return $row['cnt'] > 0;
}

// Function to check if project has components
function hasComponents($conn, $projectName) {
    $stmt = $conn->prepare("SELECT COUNT(*) AS cnt FROM COMPONENTE WHERE nomeProgetto = ?");
    $stmt->bind_param("s", $projectName);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $stmt->close();
    return $row['cnt'] > 0;
}

// Get rewards for financing modal if needed
$availableRewards = [];
if (!empty($finanziaProject)) {
    $queryRewards = "SELECT codice, descrizione FROM REWARD 
                    WHERE nomeProgetto = ? 
                    AND codice NOT IN (SELECT codiceReward FROM FINANZIAMENTO WHERE nomeProgetto = ?)";
    $stmt = $conn->prepare($queryRewards);
    $stmt->bind_param("ss", $finanziaProject, $finanziaProject);
    $stmt->execute();
    $resultRewards = $stmt->get_result();
    while ($row = $resultRewards->fetch_assoc()) {
        $availableRewards[] = $row;
    }
    $stmt->close();
}

// Get components for viewComponents modal if needed
$projectComponents = [];
if (!empty($viewComponents)) {
    $queryComponents = "SELECT nome, descrizione, prezzo, quantita FROM COMPONENTE WHERE nomeProgetto = ?";
    $stmt = $conn->prepare($queryComponents);
    $stmt->bind_param("s", $viewComponents);
    $stmt->execute();
    $resultComponents = $stmt->get_result();
    while ($row = $resultComponents->fetch_assoc()) {
        $projectComponents[] = $row;
    }
    $stmt->close();
}

// Get candidature for creator projects if needed
$candidature = [];
if ($ruolo === 'creatore' && !empty($userEmail)) {
    $queryCandidature = "SELECT c.indirizzoEmailUtente, p.id AS idProfilo, p.nome AS nomeProfilo, p.nomeProgetto
                        FROM PROGETTO pr
                        INNER JOIN PROFILO p ON p.nomeProgetto = pr.nome
                        INNER JOIN CANDIDATURA c ON c.idProfilo = p.id
                        WHERE pr.indirizzoEmailCreatore = ?";
    $stmt = $conn->prepare($queryCandidature);
    $stmt->bind_param("s", $userEmail);
    $stmt->execute();
    $resultCandidature = $stmt->get_result();
    while ($row = $resultCandidature->fetch_assoc()) {
        $candidature[] = $row;
    }
    $stmt->close();
}
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Visualizza Progetti Disponibili - Bostarter</title>
    <style>
        /* Base styles */
        body { margin: 0; padding: 0; font-family: Arial, sans-serif; background-color: #f4f4f9; color: #333; }
        .container { max-width: 1000px; margin: 50px auto; background-color: #fff; border-radius: 8px; box-shadow: 0 2px 5px rgba(0,0,0,0.1); padding: 30px; overflow-x: auto; }
        h1, h2 { text-align: center; color: #444; }
        h1 { font-size: 32px; margin-bottom: 30px; }
        
        /* Form elements */
        input[type="text"], input[type="number"], input[type="date"], textarea {
            width: 80%; padding: 8px; font-size: 14px; margin-bottom: 15px;
            border: 1px solid #ddd; border-radius: 4px;
        }
        
        /* Search bar */
        .search-bar { text-align: center; margin-bottom: 20px; }
        .search-bar input[type="text"] { width: 60%; max-width: 400px; padding: 10px; }
        
        /* Tables */
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        table, th, td { border: 1px solid #ddd; }
        th, td { padding: 10px; text-align: center; }
        th { background-color: #f4f4f4; }
        
        /* Buttons */
        .btn-primary { background-color: #5c6bc0; color: #fff; }
        .btn-primary:hover { background-color: #3f51b5; }
        .btn-secondary { background-color: #888888; color: #fff; }
        .btn-secondary:hover { background-color: #777777; }
        .btn-success { background-color: #388e3c; color: #fff; }
        .btn-success:hover { background-color: #2e7d32; }
        
        .btn {
            border: none; padding: 8px 16px; font-size: 14px; 
            border-radius: 4px; cursor: pointer; transition: background-color 0.3s ease;
            display: inline-block; text-decoration: none;
        }
        
        .btn-lg {
            padding: 10px 20px; font-size: 16px; width: 250px; margin-top: 15px; 
        }
        
        /* Creator actions */
        .creator-actions { text-align: center; margin-top: 30px; }
        .creator-actions button { margin-bottom: 15px; }
        
        /* Modal styles */
        .modal-overlay {
            display: none; position: fixed; top: 0; left: 0;
            width: 100%; height: 100%; background-color: rgba(0,0,0,0.5);
            z-index: 1000;
        }
        .modal-content {
            position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%);
            background: #fff; border-radius: 8px; box-shadow: 0 2px 5px rgba(0,0,0,0.3);
            padding: 20px; text-align: center; width: 400px;
        }
        #modalCandidature .modal-content, #modalComponents .modal-content { width: 600px; }
        
        /* Project name link style */
        .project-link {
            color: #5c6bc0;
            text-decoration: none;
            font-weight: bold;
        }
        .project-link:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Progetti Disponibili</h1>
        <?php if (!empty($message)): ?>
            <script>alert("<?= e($message) ?>");</script>
        <?php endif; ?>
        
        <!-- Search bar -->
        <div class="search-bar">
            <form action="progetti.php" method="get">
                <input type="text" name="search" placeholder="Cerca progetto..." value="<?= e($search) ?>">
                <input type="submit" value="Cerca" class="btn btn-primary">
            </form>
        </div>
        
        <!-- Projects table -->
        <?php if($result->num_rows > 0): ?>
            <table>
                <thead>
                    <tr>
                        <th>Nome</th>
                        <th>Finanziamento</th>
                        <th>Commento</th>
                        <th>Candidature</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while($row = $result->fetch_assoc()): ?>
                        <tr>
                            <td>
                                <a href="visualizza_progetto.php?nome=<?= urlencode($row['nome']) ?>" class="project-link">
                                    <?= e($row['nome']) ?>
                                </a>
                            </td>
                            <td>
                                <?php if (strtolower($row['stato']) == 'aperto' && canFinance($conn, $row['nome'])): ?>
                                    <form action="progetti.php" method="get" style="margin:0;">
                                        <input type="hidden" name="nome" value="<?= e($row['nome']) ?>">
                                        <input type="hidden" name="action" value="finanzia">
                                        <button type="submit" class="btn btn-primary">Finanzia</button>
                                    </form>
                                <?php else: ?>
                                    -
                                <?php endif; ?>
                            </td>
                            <td>
                                <form action="visualizza_commenti.php" method="get" style="margin:0;">
                                    <input type="hidden" name="nome" value="<?= e($row['nome']) ?>">
                                    <button type="submit" class="btn btn-primary">Commenta</button>
                                </form>
                            </td>
                            <td>
                                <?php if (hasProfiles($conn, $row['nome'])): ?>
                                    <form action="inserisci_candidatura.php" method="get" style="margin:0;">
                                        <input type="hidden" name="nome" value="<?= e($row['nome']) ?>">
                                        <button type="submit" class="btn btn-primary">Candidati</button>
                                    </form>
                                <?php else: ?>
                                    -
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        <?php else: ?>
            <p>Nessun progetto trovato.</p>
        <?php endif; ?>
        
        <!-- Creator actions -->
        <?php if ($ruolo === 'creatore'): ?>
            <div class="creator-actions">
                <button class="btn btn-success btn-lg" onclick="openModal('modalProject')">Crea Nuovo Progetto</button><br>
                <button class="btn btn-success btn-lg" onclick="openModal('modalReward')">Inserisci Reward</button><br>
                <button class="btn btn-success btn-lg" onclick="openModal('modalProfilo')">Inserisci Profilo</button><br>
                <button class="btn btn-success btn-lg" onclick="openModal('modalComponente')">Inserisci Componenti</button><br>
                <button class="btn btn-success btn-lg" onclick="openModal('modalCandidature')">Visualizza Candidature</button>
            </div>
        <?php endif; ?>
        
        <div style="text-align: center; margin-top: 20px;">
            <a href="dashboard.php" class="btn btn-secondary">Indietro</a>
        </div>
    </div>
    
    <!-- Modal for "Finanzia Progetto" -->
    <?php if (!empty($finanziaProject)): ?>
        <div id="modalFinanzia" class="modal-overlay" style="display: block;">
            <div class="modal-content">
                <h2>Finanzia "<?= e($finanziaProject) ?>"</h2>
                <?php if (count($availableRewards) > 0): ?>
                    <form id="finanziaForm" action="process_finanziamento.php" method="post">
                        <input type="hidden" name="nomeProgetto" value="<?= e($finanziaProject) ?>">
                        <table>
                            <thead>
                                <tr>
                                    <th>Seleziona</th>
                                    <th>Reward</th>
                                    <th>Descrizione</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($availableRewards as $reward): ?>
                                    <tr>
                                        <td>
                                            <input type="radio" name="codiceReward" value="<?= e($reward['codice']) ?>" required>
                                        </td>
                                        <td><?= e($reward['codice']) ?></td>
                                        <td><?= e($reward['descrizione']) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                        <br>
                        <label for="importo">Importo da finanziare:</label><br>
                        <input type="number" name="importo" id="importo" step="0.01" required><br><br>
                        <button type="submit" class="btn btn-primary">Conferma</button>
                        <button type="button" class="btn btn-secondary" onclick="closeModal('modalFinanzia')">Annulla</button>
                    </form>
                <?php else: ?>
                    <p>Non ci sono reward disponibili per questo progetto.</p>
                    <button type="button" class="btn btn-secondary" onclick="closeModal('modalFinanzia')">Chiudi</button>
                <?php endif; ?>
            </div>
        </div>
    <?php endif; ?>
    
    <!-- Modal for "Visualizza Componenti" -->
    <?php if (!empty($viewComponents)): ?>
        <div id="modalComponents" class="modal-overlay" style="display: block;">
            <div class="modal-content">
                <h2>Componenti di "<?= e($viewComponents) ?>"</h2>
                <?php if (count($projectComponents) > 0): ?>
                    <table>
                        <thead>
                            <tr>
                                <th>Nome</th>
                                <th>Descrizione</th>
                                <th>Prezzo</th>
                                <th>Quantità</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($projectComponents as $comp): ?>
                                <tr>
                                    <td><?= e($comp['nome']) ?></td>
                                    <td><?= e($comp['descrizione']) ?></td>
                                    <td>€ <?= e($comp['prezzo']) ?></td>
                                    <td><?= e($comp['quantita']) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <p>Nessun componente trovato per questo progetto.</p>
                <?php endif; ?>
                <button type="button" class="btn btn-secondary" onclick="closeModal('modalComponents')" style="margin-top: 15px;">Chiudi</button>
            </div>
        </div>
    <?php endif; ?>
    
    <!-- Modal for "Crea Nuovo Progetto" -->
    <div id="modalProject" class="modal-overlay">
        <div class="modal-content">
            <h2>Crea Nuovo Progetto</h2>
            <form id="projectForm" action="process_crea_progetto.php" method="post">
                <label for="nomeProgetto">Nome Progetto:</label><br>
                <input type="text" name="nomeProgetto" id="nomeProgetto" required><br>
                
                <label for="descrizione">Descrizione:</label><br>
                <textarea name="descrizione" id="descrizione" rows="4" required></textarea><br>
                
                <label for="data_limite">Data Limite:</label><br>
                <input type="date" name="data_limite" id="data_limite" required><br>
                
                <label for="budget">Budget:</label><br>
                <input type="number" name="budget" id="budget" step="0.01" required><br>
                
                <button type="submit" class="btn btn-primary">Conferma</button>
                <button type="button" class="btn btn-secondary" onclick="closeModal('modalProject')">Annulla</button>
            </form>
        </div>
    </div>
    
    <!-- Modal for "Inserisci Reward" -->
    <div id="modalReward" class="modal-overlay">
        <div class="modal-content">
            <h2>Inserisci Reward</h2>
            <form id="rewardForm" action="process_inserisci_reward.php" method="post">
                <p>Seleziona il progetto:</p>
                <table>
                    <thead>
                        <tr>
                            <th>Seleziona</th>
                            <th>Nome Progetto</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($userProjects) > 0): ?>
                            <?php foreach($userProjects as $proj): ?>
                                <tr>
                                    <td>
                                        <input type="radio" name="nomeProgetto" value="<?= e($proj['nome']) ?>" required>
                                    </td>
                                    <td><?= e($proj['nome']) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="2">Nessun progetto trovato.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
                <br>
                <label for="codiceReward">Codice Reward:</label><br>
                <input type="text" name="codiceReward" id="codiceReward" required><br>
                
                <label for="descrizioneReward">Descrizione:</label><br>
                <textarea name="descrizioneReward" id="descrizioneReward" rows="3" required></textarea><br>
                
                <label for="foto">Foto (URL o percorso):</label><br>
                <input type="text" name="foto" id="foto" required><br>
                
                <button type="submit" class="btn btn-primary">Conferma</button>
                <button type="button" class="btn btn-secondary" onclick="closeModal('modalReward')">Annulla</button>
            </form>
        </div>
    </div>
    
    <!-- Modal for "Inserisci Profilo" -->
    <div id="modalProfilo" class="modal-overlay">
        <div class="modal-content">
            <h2>Inserisci Profilo</h2>
            <form id="profiloForm" action="process_inserisci_profilo.php" method="post">
                <p>Seleziona il progetto:</p>
                <table>
                    <thead>
                        <tr>
                            <th>Seleziona</th>
                            <th>Nome Progetto</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($userProjects) > 0): ?>
                            <?php foreach($userProjects as $proj): ?>
                                <tr>
                                    <td>
                                        <input type="radio" name="nomeProgetto" value="<?= e($proj['nome']) ?>" required>
                                    </td>
                                    <td><?= e($proj['nome']) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="2">Nessun progetto trovato.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
                <br>
                <label for="nomeProfilo">Nome Profilo:</label><br>
                <input type="text" name="nomeProfilo" id="nomeProfilo" required><br>
                
                <button type="submit" class="btn btn-primary">Conferma</button>
                <button type="button" class="btn btn-secondary" onclick="closeModal('modalProfilo')">Annulla</button>
            </form>
        </div>
    </div>
    
    <!-- Modal for "Inserisci Componenti" -->
    <div id="modalComponente" class="modal-overlay">
        <div class="modal-content">
            <h2>Inserisci Componenti</h2>
            <form id="componenteForm" action="process_componente.php" method="post" onsubmit="return validateComponenteForm()">
                <p>Seleziona il progetto:</p>
                <table>
                    <thead>
                        <tr>
                            <th>Seleziona</th>
                            <th>Nome Progetto</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($userProjects) > 0): ?>
                            <?php foreach($userProjects as $proj): ?>
                                <tr>
                                    <td>
                                        <input type="radio" name="nomeProgetto" value="<?= e($proj['nome']) ?>" required>
                                    </td>
                                    <td><?= e($proj['nome']) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="2">Nessun progetto trovato.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
                <br>
                <label for="nomeComponente">Nome Componente:</label><br>
                <input type="text" name="nome" id="nomeComponente" required><br>
                
                <label for="descrizioneComponente">Descrizione:</label><br>
                <textarea name="descrizione" id="descrizioneComponente" rows="3" required></textarea><br>
                
                <label for="prezzo">Prezzo:</label><br>
                <input type="number" name="prezzo" id="prezzo" step="0.01" min="0.01" required><br>
                
                <label for="quantita">Quantità:</label><br>
                <input type="number" name="quantita" id="quantita" min="1" required><br>
                
                <button type="submit" class="btn btn-primary">Conferma</button>
                <button type="button" class="btn btn-secondary" onclick="closeModal('modalComponente')">Annulla</button>
            </form>
        </div>
    </div>
    
    <!-- Modal for "Visualizza Candidature" -->
    <div id="modalCandidature" class="modal-overlay">
        <div class="modal-content">
            <h2>Candidature dei Tuoi Progetti</h2>
            <?php if (count($candidature) > 0): ?>
                <table>
                    <thead>
                        <tr>
                            <th>Indirizzo Email Candidato</th>
                            <th>Nome Profilo</th>
                            <th>Nome Progetto</th>
                            <th>Accetta</th>
                            <th>Rifiuta</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($candidature as $cand): ?>
                            <tr>
                                <td>
                                    <a href="visualizza_utente.php?email=<?= urlencode($cand['indirizzoEmailUtente']) ?>">
                                        <?= e($cand['indirizzoEmailUtente']) ?>
                                    </a>
                                </td>
                                <td><?= e($cand['nomeProfilo']) ?></td>
                                <td><?= e($cand['nomeProgetto']) ?></td>
                                <td>
                                    <form action="process_candidatura.php" method="post" style="margin:0;">
                                        <input type="hidden" name="emailCandidato" value="<?= e($cand['indirizzoEmailUtente']) ?>">
                                        <input type="hidden" name="idProfilo" value="<?= e($cand['idProfilo']) ?>">
                                        <input type="hidden" name="azione" value="accetta">
                                        <button type="submit" class="btn btn-primary">Accetta</button>
                                    </form>
                                </td>
                                <td>
                                    <form action="process_candidatura.php" method="post" style="margin:0;">
                                        <input type="hidden" name="emailCandidato" value="<?= e($cand['indirizzoEmailUtente']) ?>">
                                        <input type="hidden" name="idProfilo" value="<?= e($cand['idProfilo']) ?>">
                                        <input type="hidden" name="azione" value="rifiuta">
                                        <button type="submit" class="btn btn-primary">Rifiuta</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p>Nessuna candidatura trovata.</p>
            <?php endif; ?>
            <button type="button" class="btn btn-secondary" onclick="closeModal('modalCandidature')">Chiudi</button>
        </div>
    </div>
    
    <script>
        function openModal(modalId) {
            document.getElementById(modalId).style.display = 'block';
        }
        
        function closeModal(modalId) {
            document.getElementById(modalId).style.display = 'none';
        }
        
        function validateComponenteForm() {
            var prezzo = document.getElementById('prezzo').value;
            var quantita = document.getElementById('quantita').value;
            
            if (parseFloat(prezzo) <= 0) {
                alert('Il prezzo deve essere maggiore di 0');
                return false;
            }
            
            if (parseInt(quantita) <= 0) {
                alert('La quantità deve essere maggiore di 0');
                return false;
            }
            
            return true;
        }
    </script>
    <?php $conn->close(); ?>
</body>
</html>