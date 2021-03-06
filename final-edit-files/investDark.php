<?php
    session_start();
    ini_set("allow_url_fopen", 1);

    if (array_key_exists("signedIn", $_SESSION) && $_SESSION["signedIn"] === TRUE) {
        $username = $_SESSION["username"];
        $symbol = $_REQUEST["symbol"];

        include 'databaseConnection.php';

        $submitType = $_POST["submit"];
        $userInfoSql = "SELECT * FROM Users WHERE u_username=\"$username\";";
        $userInfoResults = $mysqli->query($userInfoSql);
        if (!$userInfoResults) {
            echo "Error while retrieving user info: " . $mysqli->connect_errno;
            exit();
        }
        $userInfoRow = $userInfoResults->fetch_assoc();
        $balance = $userInfoRow["u_balance"];

        // For retrieving price data from IEX API
        $arrContextOptions=array(
            "ssl"=>array(
                "verify_peer"=>false,
                "verify_peer_name"=>false,
            ),
        );

        $price = file_get_contents("https://api.iextrading.com/1.0/stock/".urlencode($symbol)."/price", false, stream_context_create($arrContextOptions));
        $price = (int) (((float) $price) * 100.0);

        // error msg if retrieved price is -1
        if ($price == -1) {
            echo "<strong>Unable to call stock data. Please try again</strong>";
            exit();
        }
        if ($submitType == "Sell") {
            $sellAmount = $_REQUEST["sellAmount"];
            $newBalance = $balance + $price*$sellAmount;
            $sellSql = "UPDATE Investments SET i_amount=i_amount-$sellAmount WHERE i_username=\"$username\" AND i_symbol=\"" .
                            mysqli_real_escape_string($mysqli, $symbol) . "\";";
            if ($mysqli->query($sellSql) === FALSE) {
                echo "Error while updating investment data";
                exit();
            }
            $balanceSql = "UPDATE Users SET u_balance=$newBalance WHERE u_username=\"$username\";";
            if ($mysqli->query($balanceSql) === FALSE) {
                echo "Error while updating user balance data";
                exit();
            }
            header("Location: mymoneyDark.php?status=success");
        }
        else if ($submitType == "Buy") {
            $buyAmount = $_REQUEST["buyAmount"];
            $newBalance = $balance - $price*$buyAmount;
            if ($newBalance < 0) {
                header("Location: mymoneyDark.php?status=lowBalance&symbol=".urlencode($symbol));
            }
            else {
                $buySql = "UPDATE Investments SET i_amount=i_amount+$buyAmount WHERE i_username=\"$username\" AND i_symbol=\"" .
                            $symbol . "\";";
                if ($mysqli->query($buySql) === FALSE) {
                    echo "Error while updating investment data";
                    exit();
                }
                $balanceSql = "UPDATE Users SET u_balance=$newBalance WHERE u_username=\"$username\";";
                if ($mysqli->query($balanceSql) === FALSE) {
                    echo "Error while updating user balance data";
                    exit();
                }
                header("Location: mymoneyDark.php?status=success");
            }
        }
    }
    else {
        header("Location: index.php");
    }
?>
