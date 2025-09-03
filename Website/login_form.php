<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <title>Login - Bostarter</title>
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
            margin-top: 20px;
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
        input[type="text"],
        input[type="password"] {
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
        <h1>Login</h1>
        <!-- Menu per scegliere il tipo di login -->
        <div class="menu">
            <button onclick="showSection('utente', this)">Utente</button>
            <button onclick="showSection('creatore', this)">Creatore</button>
            <button onclick="showSection('amministratore', this)">Amministratore</button>
        </div>
        
        <!-- Sezione Login Utente -->
        <div id="utente" class="form-section">
            <form action="process_login.php" method="post">
                <input type="hidden" name="ruolo" value="utente">
                <label for="nickname_utente">Nickname:</label>
                <input type="text" id="nickname_utente" name="nickname" required>
                
                <label for="password_utente">Password:</label>
                <input type="password" id="password_utente" name="password" required>
                
                <input type="submit" value="Accedi">
            </form>
        </div>
        
        <!-- Sezione Login Creatore -->
        <div id="creatore" class="form-section">
            <form action="process_login.php" method="post">
                <input type="hidden" name="ruolo" value="creatore">
                <label for="nickname_creatore">Nickname:</label>
                <input type="text" id="nickname_creatore" name="nickname" required>
                
                <label for="password_creatore">Password:</label>
                <input type="password" id="password_creatore" name="password" required>
                
                <input type="submit" value="Accedi">
            </form>
        </div>
        
        <!-- Sezione Login Amministratore -->
        <div id="amministratore" class="form-section">
            <form action="process_login.php" method="post">
                <input type="hidden" name="ruolo" value="amministratore">
                <label for="nickname_admin">Nickname:</label>
                <input type="text" id="nickname_admin" name="nickname" required>
                
                <label for="password_admin">Password:</label>
                <input type="password" id="password_admin" name="password" required>
                
                <label for="codice_sicurezza_admin">Codice di Sicurezza:</label>
                <input type="text" id="codice_sicurezza_admin" name="codice_sicurezza" required>
                
                <input type="submit" value="Accedi">
            </form>
        </div>
        
        <div style="text-align:center; margin-top:20px;">
            <a href="index.php" class="back-button">Indietro</a>
        </div>
    </div>
    
    <script>
        function showSection(sectionId, btn) {
            var sections = document.getElementsByClassName('form-section');
            for (var i = 0; i < sections.length; i++) {
                sections[i].style.display = 'none';
            }
            document.getElementById(sectionId).style.display = 'block';
            
            var buttons = document.querySelectorAll('.menu button');
            buttons.forEach(function(button) {
                button.classList.remove('active');
            });
            btn.classList.add('active');
        }
        
        // Mostra la sezione "Utente" di default
        document.addEventListener("DOMContentLoaded", function() {
            var defaultBtn = document.querySelector('.menu button');
            showSection('utente', defaultBtn);
        });
    </script>
</body>
</html>
