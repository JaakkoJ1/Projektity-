<?php
session_start();
include 'db.php';

$error = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $rooli = $_POST['rooli'];
    $sahkoposti = trim($_POST['email']);
    $salasana = trim($_POST['salasana']);

    if ($rooli === 'opettaja') {
        $stmt = $conn->prepare("SELECT opettajaid, salasana FROM opettaja WHERE sahkoposti = ?");
    } else {
        $stmt = $conn->prepare("SELECT oppilasid, salasana FROM oppilas WHERE sahkoposti = ?");
    }

    $stmt->bind_param("s", $sahkoposti);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows === 1) {
        if ($rooli === 'opettaja') {
            $stmt->bind_result($opettajaid, $hash);
        } else {
            $stmt->bind_result($oppilasid, $hash);
        }

        $stmt->fetch();

        if (trim($salasana) === trim($hash)) {
            if ($rooli === 'opettaja') {
                $_SESSION['opettajaid'] = $opettajaid;
                header("Location: varaus_opettajat.php");
                exit();
            } else {
                $_SESSION['oppilasid'] = $oppilasid;
                header("Location: varaus_oppilaat.php");
                exit();
            }
        } else {
            $error = "❌ Väärä salasana.";
        }
    } else {
        $error = "❌ Sähköpostia ei löytynyt.";
    }

    $stmt->close();
}
?>
<!DOCTYPE html>
<html lang="fi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Luokkavarausjärjestelmä - Kirjautuminen</title>

    <!-- CSS -->
    <link rel="stylesheet" href="css/styles.css">

    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Noto+Serif+Bengali:wght@100..900&family=TASA+Orbiter:wght@400..800&display=swap" rel="stylesheet">
</head>
    <body>
        <div class="center">
            <h1 class="noto-serif-bengali">KIRJAUDU SISÄÄN</h1>
            <a class="tasa-orbiter button" href="index.html">TAKAISIN</a>
            <br>
            <br>
            <p class="tasa-orbiter">Jos ei ole käyttäjätunnusta,<br>rekisteröidy ensin, ja kirjaudu sen jälkeen sisään.</p>
            <a class="tasa-orbiter button" href="rekisteroityminen.php">REKISTERÖIDY</a>
        </div>
        <div class="container">
            <?php if ($error): ?>
                <div class="message error"><?php echo $error; ?></div>
            <?php endif; ?>
            <form method="POST" action="">
            <p class="tasa-orbiter">Valitse rooli.</p>
            <select name="rooli" id="rooli" class="tasa-orbiter">
                <option value="opettaja">Opettaja</option>
                <option value="oppilas">Oppilas</option>
            </select>
            <p class="tasa-orbiter">Syötä sähköposti.</p>
            <input type="email" name="email" required>
            <br>
            <p class="tasa-orbiter">Syötä salasana.</p>
            <input type="password" name="salasana" required>
            <br>
            <br>
            <br>
            <button type="submit" class="tasa-orbiter button">KIRJAUDU</button>
        </div>
    </body>
</html>