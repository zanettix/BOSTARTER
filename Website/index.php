<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <title>Bostarter - Home</title>
    <style>
        /* Stile base per il body */
        body {
            margin: 0;
            padding: 0;
            font-family: 'Arial', sans-serif;
            background-color: #f4f4f9;
            color: #333;
        }
        /* Contenitore centrale */
        .container {
            max-width: 600px;
            margin: 50px auto;
            background-color: #ffffff;
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
        form {
            margin: 15px 0;
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
        }
        button:hover {
            background-color: #3f51b5;
        }
        /* Stile specifico per il bottone Dashboard */
        .dashboard-form {
            margin-top: 40px; /* Maggiore distanza dagli altri bottoni */
        }
        .dashboard-btn {
            background-color: #e53935; /* Colore rosso */
        }
        .dashboard-btn:hover {
            background-color: #d32f2f;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Benvenuto/a su Bostarter!</h1>
        
        <!-- Bottone per registrarsi -->
        <form action="signup_form.php" method="get">
            <button type="submit">Registrati</button>
        </form>
        
        <!-- Bottone per effettuare il login -->
        <form action="login_form.php" method="get">
            <button type="submit">Accedi</button>
        </form>
        
        <!-- Bottone per accedere alla dashboard -->
        <form action="statistiche.php" method="get">
            <button type="submit" class="dashboard-btn">Statistiche</button>
        </form>
    </div>
</body>
</html>
