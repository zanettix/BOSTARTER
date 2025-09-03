<?php
session_start();
if (!isset($_SESSION['nickname'])) { 
    // Se non c'è una variabile di sessione, reindirizza alla pagina di login
    header("Location: login_form.php");
    exit();
}
$ruolo = isset($_SESSION['ruolo']) ? $_SESSION['ruolo'] : 'utente';
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <title>Dashboard <?php echo ucfirst($ruolo); ?> - Bostarter</title>
    <style>
        body {
            margin: 0;
            padding: 0;
            font-family: 'Arial', sans-serif;
            background-color: #f4f4f9;
            color: #333;
        }
        .container {
            max-width: 800px;
            margin: 50px auto;
            background-color: #ffffff;
            border: 1px solid #ddd;
            border-radius: 8px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            padding: 30px;
            text-align: center;
        }
        h1 {
            font-size: 32px;
            color: #444;
            margin-bottom: 30px;
        }
        .button-container {
            display: flex;
            flex-direction: column;
            gap: 15px;
            align-items: center;
        }
        .btn {
            background-color: #5c6bc0;
            color: #fff;
            border: none;
            padding: 12px 25px;
            font-size: 16px;
            border-radius: 5px;
            cursor: pointer;
            transition: background-color 0.3s ease;
            text-decoration: none;
            width: 60%;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            text-align: center;
        }
        .btn:hover {
            background-color: #3f51b5;
        }
        .exit-btn {
            background-color: #e53935;
            padding: 8px 16px;
            font-size: 14px;
            width: auto;
            color: #fff;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            text-align: center;
        }
        .exit-btn:hover {
            background-color: #d32f2f;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Dashboard <?php echo ucfirst($ruolo); ?></h1>
        <div class="button-container">
            <!-- Pulsanti comuni a tutti -->
            <form action="indica_livello_competenza.php" method="get">
                <button type="submit" class="btn">Inserisci Competenze</button>
            </form>
            <form action="progetti.php" method="get">
                <button type="submit" class="btn">Visualizza Progetti</button>
            </form>
        </div>
        <div style="margin-top: 30px;">
            <a href="logout.php" class="exit-btn">Esci</a>
        </div>
    </div>
</body>
</html>
