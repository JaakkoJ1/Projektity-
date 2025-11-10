<!DOCTYPE html>
<html lang="fi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Luokkavarausjärjestelmä - Rekisteröityminen</title>

    <!-- CSS -->
    <link rel="stylesheet" href="css/styles.css">

    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Noto+Serif+Bengali:wght@100..900&family=TASA+Orbiter:wght@400..800&display=swap" rel="stylesheet">
</head>
    <body>
        <div class="center">
            <h1 class="noto-serif-bengali">REKISTERÖIDY</h1>
            <a class="tasa-orbiter button" href="index.html">TAKAISIN</a>
            <a class='tasa-orbiter button' href='kirjautuminen.php'>KIRJAUDU SISÄÄN</a>
            <br>
            <br>
            <p class="tasa-orbiter">Admin tarkistaa ja hyväksyy uuden käyttäjän<br>sekä asettaa salasanan tietokantaan
            ennen kuin käyttäjä voi kirjautua sisään.</p>
        </div>
        <div class="container">
            <form method="POST" action="">
                <p class="tasa-orbiter">Valitse rooli.</p>
                <select name="rooli" id="rooli" class="tasa-orbiter">
                    <option value="opettaja">Opettaja</option>
                    <option value="oppilas">Oppilas</option>
                </select>
                <p class="tasa-orbiter">Syötä etunimi.</p>
                <input type="text" name="etunimi" required>
                <p class="tasa-orbiter">Syötä sukunimi.</p>
                <input type="text" name="sukunimi" required>
                <p class="tasa-orbiter">Syötä puhelinnumero.</p>
                <input type="text" name="puhelin" required>
                <p class="tasa-orbiter">Syötä sähköposti.</p>
                <input type="email" name="email" required>
                <div id="luokkaDiv">
                    <p class="tasa-orbiter">Syötä luokka.</p>
                    <input type="text" name="luokka" id="luokka">
                </div>
                <br>
                <br>
                <br>
                <button type="submit" name="rekisteroi" class="tasa-orbiter button">REKISTERÖIDY</button>
            </form>
        </div>
        <script>
            const rooliSelect = document.getElementById("rooli");
            const luokkaDiv = document.getElementById("luokkaDiv");

            luokkaDiv.style.display = "none";

            rooliSelect.addEventListener("change", function() {
                if (this.value === "oppilas") {
                    luokkaDiv.style.display = "block";
                } else {
                    luokkaDiv.style.display = "none";
                }
            });
        </script>
        <?php
        include 'db.php';

        if (isset($_POST['rekisteroi'])) {
            $rooli = $_POST['rooli'];
            $etunimi = trim($_POST['etunimi']);
            $sukunimi = trim($_POST['sukunimi']);
            $puhelinnumero = trim($_POST['puhelin']);
            $sahkoposti = trim($_POST['email']);
            $luokka = isset($_POST['luokka']) ? trim($_POST['luokka']) : null;

            $virheet = [];

            if ($rooli === 'opettaja') {
            $stmt_check = $conn->prepare("SELECT sahkoposti FROM opettaja WHERE sahkoposti = ?");
            } else {
            $stmt_check = $conn->prepare("SELECT sahkoposti FROM oppilas WHERE sahkoposti = ?");
            }
            $stmt_check->bind_param("s", $sahkoposti);
            $stmt_check->execute();
            $stmt_check->store_result();

            if ($stmt_check->num_rows > 0) {
                $virheet[] = "Sähköposti on jo käytössä.";
            }

            if (empty($virheet)) {
                if ($rooli === 'opettaja') {
                    $stmt = $conn->prepare("INSERT INTO opettaja (etunimi, sukunimi, puhelinnumero, sahkoposti) VALUES (?, ?, ?, ?)");
                    $stmt->bind_param("ssss", $etunimi, $sukunimi, $puhelinnumero, $sahkoposti);
                } else {
                    $stmt = $conn->prepare("INSERT INTO oppilas (etunimi, sukunimi, luokka, puhelinnumero, sahkoposti) VALUES (?, ?, ?, ?, ?)");
                    $stmt->bind_param("sssss", $etunimi, $sukunimi, $luokka, $puhelinnumero, $sahkoposti);
                }

                $stmt->execute();

                echo "<br><br><div class='message success tasa-orbiter center'>✅ Rekisteröinti onnistui!</div>";
            } else {
                foreach ($virheet as $virhe) {
                    echo "<div class='message error'>❌ $virhe</div>";
                }
            }
        }
        ?>
    </body>
</html>