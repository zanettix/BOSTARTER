<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <title>Registrazione - Bostarter</title>
    <style>
        body {
            margin: 0;
            padding: 0;
            font-family: 'Arial', sans-serif;
            background-color: #f4f4f9;
            color: #333;
        }
        .container {
            max-width: 600px;
            margin: 50px auto;
            background-color: #ffffff;
            border: 1px solid #ddd;
            border-radius: 8px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            padding: 30px;
        }
        h1, h2 {
            text-align: center;
            color: #444;
        }
        h1 {
            font-size: 28px;
            margin-bottom: 30px;
        }
        h2 {
            font-size: 22px;
            margin-top: 40px;
            margin-bottom: 15px;
        }
        .menu {
            display: flex;
            justify-content: space-around;
            margin-bottom: 30px;
        }
        .menu button {
            background-color: #5c6bc0;
            color: #fff;
            border: none;
            padding: 10px 20px;
            font-size: 16px;
            border-radius: 5px;
            cursor: pointer;
            transition: background-color 0.3s ease;
        }
        .menu button:hover {
            background-color: #3f51b5;
        }
        /* Bottone attivo: colore diverso */
        .menu button.active {
            background-color: #ff9800;
        }
        .form-section {
            display: none;
        }
        form {
            display: flex;
            flex-direction: column;
            align-items: center;
        }
        label {
            font-weight: bold;
            align-self: flex-start;
            margin-bottom: 5px;
        }
        input[type="email"],
        input[type="text"],
        input[type="password"],
        input[type="number"] {
            width: 100%;
            max-width: 400px;
            padding: 10px;
            margin-bottom: 20px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 14px;
        }
        input[type="submit"] {
            background-color: #5c6bc0;
            color: #fff;
            border: none;
            padding: 12px 20px;
            font-size: 16px;
            border-radius: 5px;
            cursor: pointer;
            transition: background-color 0.3s ease;
            width: 50%;
            margin-bottom: 15px;
        }
        input[type="submit"]:hover {
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
            text-align: center;
            width: 40%;
            margin-top: 20px;
        }
        .back-button:hover {
            background-color: #777777;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Registrazione</h1>
        
        <!-- Menu per scegliere il tipo di registrazione -->
        <div class="menu">
            <button onclick="showSection('utente', this)">Utente</button>
            <button onclick="showSection('creatore', this)">Creatore</button>
            <button onclick="showSection('amministratore', this)">Amministratore</button>
        </div>
        
        <!-- Sezione Registrazione Utente -->
        <div id="utente" class="form-section">
            <form action="process_signup.php" method="post">
                <label for="indirizzoEmail">Indirizzo Email:</label>
                <input type="email" name="indirizzoEmail" id="indirizzoEmail" required>
                
                <label for="nickname">Nickname:</label>
                <input type="text" name="nickname" id="nickname" required>
                
                <label for="password">Password:</label>
                <input type="password" name="password" id="password" required>
                
                <label for="nome">Nome:</label>
                <input type="text" name="nome" id="nome" required>
                
                <label for="cognome">Cognome:</label>
                <input type="text" name="cognome" id="cognome" required>
                
                <label for="anno_nascita">Anno di Nascita:</label>
                <input type="number" name="anno_nascita" id="anno_nascita" required>
                
                <label for="luogo_nascita">Luogo di Nascita:</label>
                <input type="text" name="luogo_nascita" id="luogo_nascita" required>
                
                <input type="submit" value="Registrati">
            </form>
        </div>
        
        <!-- Sezione Registrazione Creatore -->
        <div id="creatore" class="form-section">
            <form action="process_signup.php" method="post">
                <input type="hidden" name="ruolo" value="creatore">
                <label for="email_creatore">Indirizzo Email:</label>
                <input type="email" name="email" id="email_creatore" required>
                <input type="submit" value="Registrati">
            </form>
        </div>
        
        <!-- Sezione Registrazione Amministratore -->
        <div id="amministratore" class="form-section">
            <form action="process_signup.php" method="post">
                <input type="hidden" name="ruolo" value="amministratore">
                <label for="email_admin">Indirizzo Email:</label>
                <input type="email" name="email" id="email_admin" required>
                <label for="codice_sicurezza">Codice di Sicurezza:</label>
                <input type="text" name="codice_sicurezza" id="codice_sicurezza" required>
                <input type="submit" value="Registrati">
            </form>
        </div>
        
        <div style="text-align:center; margin-top:20px;">
            <a href="index.php" class="back-button">Indietro</a>
        </div>
    </div>
    
    <script>
        function showSection(sectionId, btn) {
            // Nascondi tutte le sezioni
            var sections = document.getElementsByClassName('form-section');
            for (var i = 0; i < sections.length; i++) {
                sections[i].style.display = 'none';
            }
            // Mostra la sezione richiesta
            document.getElementById(sectionId).style.display = 'block';
            
            // Rimuovi la classe "active" da tutti i bottoni del menu
            var buttons = document.querySelectorAll('.menu button');
            buttons.forEach(function(button) {
                button.classList.remove('active');
            });
            // Aggiungi la classe "active" al bottone cliccato
            btn.classList.add('active');
        }
        // Mostra la sezione "Utente" di default e attiva il relativo bottone
        document.addEventListener("DOMContentLoaded", function() {
            var defaultBtn = document.querySelector('.menu button');
            showSection('utente', defaultBtn);
        });
    </script>
</body>
</html>
