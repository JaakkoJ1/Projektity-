<?php
session_start();
include 'db.php';

$available_rooms = [];
$message = "";

if (!isset($_SESSION['opettajaid'])) {
    header("Location: kirjautuminen.php");
    exit();
}

if (isset($_POST['etsi'])) {
    $tyyppi = $_POST['tyyppi'];
    $maara = trim($_POST['maara']);
    $pvm = trim($_POST['pvm']);
    $aikaalkaa = trim($_POST['aikaalkaa']);
    $aikaloppuu = trim($_POST['aikaloppuu']);
    $opettajaid = $_SESSION['opettajaid'];
    
    $varausalku = $pvm . " " . $aikaalkaa;
    $varausloppu = $pvm . " " . $aikaloppuu;

    $stmt = $conn->prepare("
        SELECT luokkanumero, tyyppi, luokkakoko
        FROM luokat
        WHERE tyyppi = ? AND luokkakoko >= ?
        AND kaytto = 'opettaja'
        AND luokkanumero NOT IN (
            SELECT luokkanumero FROM opettajavaraukset
            WHERE (varausalku < ? AND varausloppu > ?)
        )
    ");

    $stmt->bind_param("siss", $tyyppi, $maara, $varausloppu, $varausalku);
    $stmt->execute();
    $result = $stmt->get_result();

    while ($row = $result->fetch_assoc()) {
        $available_rooms[] = $row;
    }

    if (empty($available_rooms)) {
        $message = "❌ Ei vapaita luokkia annetuilla ehdoilla.";
    }
    
    $stmt->close();
}

if (isset($_POST['valitse_luokka'])) {
    $opettajaid = $_SESSION['opettajaid'];
    $luokkanumero = $_POST['luokkanumero'];
    $pvm = $_POST['pvm'];
    $varausalku = $pvm . " " . $_POST['aikaalkaa'];
    $varausloppu = $pvm . " " . $_POST['aikaloppuu'];
    $maara = $_POST['maara'];

    $stmt = $conn->prepare("
    INSERT INTO opettajavaraukset (opettajaid, luokkanumero, henkilomaara, varausalku, varausloppu)
    VALUES (?, ?, ?, ?, ?)
    ");
    $stmt->bind_param("isiss", $opettajaid, $luokkanumero, $maara, $varausalku, $varausloppu);

    if ($stmt->execute()) {
        echo "<div class='message success tasa-orbiter'>✅ Varaus onnistui luokkaan $luokkanumero!</div>";
    } else {
        echo "<div class='message error tasa-orbiter'>❌ Virhe varauksessa: " . htmlspecialchars($stmt->error) . "</div>";
    }

    $stmt->close();
}
?>
<!DOCTYPE html>
<html lang="fi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Luokkavarausjärjestelmä - Varaus (Opettajat)</title>

    <!-- CSS -->
    <link rel="stylesheet" href="css/styles.css">

    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Noto+Serif+Bengali:wght@100..900&family=TASA+Orbiter:wght@400..800&display=swap" rel="stylesheet">
</head>
    <body>
        <div class="center">
            <h1 class="noto-serif-bengali">VARAUS - Opettajat</h1>
            <a class="tasa-orbiter button" href="omat_varaukset_opettajat.php">OMAT VARAUKSET</a>
            <a class="tasa-orbiter button" href="luokkien_tilat.php">LUOKKIEN TILAT</a>
            <a class="tasa-orbiter button" href="index.html">KIRJAUDU ULOS</a>
        </div>
        <div class="container">
            <?php if (!empty($message)): ?>
                <div class="tasa-orbiter" style="margin: 10px 0;">
                    <?php echo $message; ?>
                </div>
            <?php endif; ?>
            
            <form method="POST" action="">
                <p class="tasa-orbiter">Valitse luokka tyyppi.</p>
                 <select name="tyyppi" id="tyyppi" class="tasa-orbiter">
                    <option value="normaali">Normaali</option>
                    <option value="kemia">Kemia</option>
                    <option value="tvt">TVT</option>
                    <option value="liikuntasali">Liikuntasali</option>
                    <option value="kuntosali">Kuntosali</option>
                    <option value="musiikki">Musiikki</option>
                    <option value="kuvis">Kuvis</option>
                    <option value="puukäsityöt">Puukäsityöt</option>
                    <option value="tekstiili">Tekstiili</option>
                    <option value="kotitalous">Kotitalous</option>
                </select>

                <p class="tasa-orbiter">Syötä oppilaiden määrä.</p>
                <input type="number" name="maara" required>

                <p class="tasa-orbiter">Syötä päivämäärä.</p>
                <input type="date" name="pvm" required>

                <p class="tasa-orbiter">Syötä aika (alkaa).</p>
                <input type="time" name="aikaalkaa" required>

                <p class="tasa-orbiter">Syötä aika (loppuu).</p>
                <input type="time" name="aikaloppuu" required>

                <br><br><br>
                <button type="submit" name="etsi" class="tasa-orbiter button">Etsi vapaat luokat</button>
            </form>
        </div>
        <?php if (!empty($available_rooms)): ?>
            <p class="tasa-orbiter">Vapaat luokat:</p>
            <table class="tasa-orbiter" border="1" cellpadding="8" cellspacing="0" style="margin-top: 10px; width: 100%; border-collapse: collapse; border: rgb(65, 65, 65) solid 2px;">
                <tr style="background-color: rgba(36, 36, 36, 1);">
                    <th>Luokkanumero</th>
                    <th>Tyyppi</th>
                    <th>Kapasiteetti</th>
                    <th>Toiminnot</th>
                </tr>
                <?php foreach ($available_rooms as $room): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($room['luokkanumero']); ?></td>
                        <td><?php echo htmlspecialchars($room['tyyppi']); ?></td>
                        <td><?php echo htmlspecialchars($room['luokkakoko']); ?></td>
                        <td style="text-align: center;">
                            <form method="POST" action="" onsubmit="return confirm('Haluatko varmasti varata tämän luokan?')">
                                <input type="hidden" name="luokkanumero" value="<?php echo htmlspecialchars($room['luokkanumero']); ?>">
                                <input type="hidden" name="pvm" value="<?php echo htmlspecialchars($pvm); ?>">
                                <input type="hidden" name="aikaalkaa" value="<?php echo htmlspecialchars($aikaalkaa); ?>">
                                <input type="hidden" name="aikaloppuu" value="<?php echo htmlspecialchars($aikaloppuu); ?>">
                                <input type="hidden" name="maara" value="<?php echo htmlspecialchars($maara); ?>">
                                <button type="submit" name="valitse_luokka" class="button tasa-orbiter" style="width: 225px;">Varaa</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </table>
        <?php endif; ?>
        <script>
            function confirmReservation(event) {
                if (!confirm("Haluatko varmasti varata tämän luokan?")) {
                    event.preventDefault();
                }
            }
        </script>
</body>
</html>