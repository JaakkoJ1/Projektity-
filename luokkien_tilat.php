<?php
session_start();
include 'db.php';

$status_table = [];
$message = "";

if (!isset($_SESSION['opettajaid']) &&  !isset($_SESSION['oppilasid'])) {
    header("Location: kirjautuminen.php");
    exit();
}

if (isset($_SESSION['opettajaid'])) {
    $takaisin_link = "varaus_opettajat.php";
} elseif (isset($_SESSION['oppilasid'])) {
    $takaisin_link = "varaus_oppilaat.php";
}

if (isset($_POST['hae_tila'])) {
    $pvm = trim($_POST['pvm']);
    $aikaalkaa = trim($_POST['aikaalkaa']);
    $aikaloppuu = trim($_POST['aikaloppuu']);

    $varausalku = $pvm . " " . $aikaalkaa;
    $varausloppu = $pvm . " " . $aikaloppuu;

    $query = "SELECT luokkanumero, tyyppi, luokkakoko, kaytto FROM luokat";
    $result = $conn->query($query);

    while ($room = $result->fetch_assoc()) {
        $luokkanumero = $room['luokkanumero'];

        $stmt1 = $conn->prepare("
            SELECT o.etunimi, v.varausalku, v.varausloppu
            FROM oppilasvaraukset v
            JOIN oppilas o ON v.oppilasid = o.oppilasid
            WHERE v.luokkanumero = ? 
            AND v.varausalku < ? 
            AND v.varausloppu > ?
            LIMIT 1
        ");
        $stmt1->bind_param("sss", $luokkanumero, $varausloppu, $varausalku);
        $stmt1->execute();
        $result1 = $stmt1->get_result();
        $row1 = $result1->fetch_assoc();
        $oppilas_nimi = $row1['etunimi'] ?? "";
        $oppilas_alku = $row1['varausalku'] ?? "";
        $oppilas_loppu = $row1['varausloppu'] ?? "";
        $stmt1->close();

        $stmt2 = $conn->prepare("
            SELECT o.etunimi, v.varausalku, v.varausloppu
            FROM opettajavaraukset v
            JOIN opettaja o ON v.opettajaid = o.opettajaid
            WHERE v.luokkanumero = ? 
            AND v.varausalku < ? 
            AND v.varausloppu > ?
            LIMIT 1
        ");
        $stmt2->bind_param("sss", $luokkanumero, $varausloppu, $varausalku);
        $stmt2->execute();
        $result2 = $stmt2->get_result();
        $row2 = $result2->fetch_assoc();
        $opettaja_nimi = $row2['etunimi'] ?? "";
        $opettaja_alku = $row2['varausalku'] ?? "";
        $opettaja_loppu = $row2['varausloppu'] ?? "";
        $stmt2->close();

        if (!empty($oppilas_nimi)) {
            $status = "🔴 Varattu (Oppilas)";
            $varaaja = $oppilas_nimi . " (" . date('H:i', strtotime($oppilas_alku)) . " - " . date('H:i', strtotime($oppilas_loppu)) . ")";
        } elseif (!empty($opettaja_nimi)) {
            $status = "🔴 Varattu (Opettaja)";
            $varaaja = $opettaja_nimi . " (" . date('H:i', strtotime($opettaja_alku)) . " - " . date('H:i', strtotime($opettaja_loppu)) . ")";
        } else {
            $status = "🟢 Vapaa";
            $varaaja = "-";
        }
        
        $status_table[] = [
            "luokkanumero" => $luokkanumero,
            "tyyppi" => $room['tyyppi'],
            "luokkakoko" => $room['luokkakoko'],
            "kaytto" => $room['kaytto'],
            "status" => $status,
            "varaaja" => $varaaja
        ];
    }

    if (empty($status_table)) {
        $message ="❌ Ei löytynyt luokkia.";
    }
}
?>
<!DOCTYPE html>
<html lang="fi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Luokkavarausjärjestelmä - Luokkien tilat</title>

    <!-- CSS -->
    <link rel="stylesheet" href="css/styles.css">

    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Noto+Serif+Bengali:wght@100..900&family=TASA+Orbiter:wght@400..800&display=swap" rel="stylesheet">
</head>
    <body>
        <div class="center">
            <h1 class="noto-serif-bengali">LUOKKIEN TILAT</h1>

            <form method="POST">
                <label class="tasa-orbiter" for="pvm">Päivämäärä:</label><br>
                <input class="luokkien-tilat" type="date" name="pvm" required><br><br>

                <label class="tasa-orbiter" for="aikaalkaa">Aika alku:</label><br>
                <input class="luokkien-tilat" type="time" name="aikaalkaa" required><br><br>

                <label for="aikaloppuu">Aika loppu:</label><br>
                <input class="luokkien-tilat" type="time" name="aikaloppuu" required><br><br>

                <button type="submit" name="hae_tila">Hae tila</button>
            </form>

            <br>
            <a href="<?php echo $takaisin_link; ?>" class="tasa-orbiter button">TAKAISIN</a>
            <br><br>

            <?php if (!empty($message)): ?>
                <p><?php echo $message; ?></p>
            <?php endif; ?>

            <?php if (!empty($status_table)): ?>
            <table border="1" cellpadding="8" cellspacing="0" style="width:100%; border-collapse: collapse; margin-top: 10px; width: 100%; border-collapse: collapse; border: rgb(65, 65, 65) solid 2px;">
                    <tr class="tasa-orbiter" style="background-color: rgba(36, 36, 36, 1);">
                        <th>Luokkanumero</th>
                        <th>Tyyppi</th>
                        <th>Kapasiteetti</th>
                        <th>Käyttö</th>
                        <th>Tila</th>
                        <th>Varaaja</th>
                    </tr>
                    <?php foreach ($status_table as $luokka): ?>
                        <tr class="tasa-orbiter">
                            <td><?php echo htmlspecialchars($luokka['luokkanumero']); ?></td>
                            <td><?php echo htmlspecialchars($luokka['tyyppi']); ?></td>
                            <td><?php echo htmlspecialchars($luokka['luokkakoko']); ?></td>
                            <td><?php echo htmlspecialchars($luokka['kaytto']); ?></td>
                            <td><?php echo $luokka['status']; ?></td>
                            <td><?php echo htmlspecialchars($luokka['varaaja']); ?></td>
                        </tr>
                    <?php endforeach; ?>
            </table>
    <?php endif; ?>
</div>
</body>
</html>