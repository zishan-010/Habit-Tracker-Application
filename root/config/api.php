<?php
header("Content-Type: application/json");
require_once "database.php";
$pdo = getDatabase();
$action = $_GET["action"] ?? "";

/*
|--------------------------------------------------------------------------
| GET DATA
|--------------------------------------------------------------------------
*/
if ($action === "getData") {
    $habits = $pdo
        ->query("
            SELECT *
            FROM habits
            ORDER BY id ASC
        ")
        ->fetchAll(PDO::FETCH_ASSOC);
    $completions = $pdo
        ->query("
            SELECT *
            FROM completions
            ORDER BY completed_date ASC
        ")
        ->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode([
        "habits" => $habits,
        "completions" => $completions
    ]);
    exit;
}

/*
|--------------------------------------------------------------------------
| ADD HABIT
|--------------------------------------------------------------------------
*/

if ($action === "addHabit") {
    $data = json_decode(
        file_get_contents("php://input"),
        true
    );
    $name = trim(
        $data["name"] ?? ""
    );
    if ($name === "") {
        http_response_code(400);
        echo json_encode([
            "error" =>
                "Habit name is required"
        ]);
        exit;
    }

    $statement =
        $pdo->prepare("
            INSERT INTO habits (name)
            VALUES (?)
        ");

    $statement->execute([
        $name
    ]);

    $habits = $pdo
        ->query("
            SELECT *
            FROM habits
            ORDER BY id ASC
        ")
        ->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        "success" => true,
        "habits" => $habits
    ]);
    exit;
}

/*
|--------------------------------------------------------------------------
| UPDATE HABIT
|--------------------------------------------------------------------------
*/
if ($action === "updateHabit") {

    $data = json_decode(
        file_get_contents("php://input"),
        true
    );

    $id = intval(
        $data["id"] ?? 0
    );

    $name = trim(
        $data["name"] ?? ""
    );

    if (!$id || $name === "") {
        http_response_code(400);
        echo json_encode([
            "error" =>
                "Invalid habit data"
        ]);
        exit;
    }

    $statement =
        $pdo->prepare("
            UPDATE habits
            SET name = ?
            WHERE id = ?
        ");

    $statement->execute([
        $name,
        $id
    ]);

    $habits = $pdo
        ->query("
            SELECT *
            FROM habits
            ORDER BY id ASC
        ")
        ->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        "success" => true,
        "habits" => $habits
    ]);
    exit;
}

/*
|--------------------------------------------------------------------------
| TOGGLE COMPLETION
|--------------------------------------------------------------------------
*/
if ($action === "toggle") {

    $data = json_decode(
        file_get_contents("php://input"),
        true
    );

    $habitId = intval(
        $data["habit_id"] ?? 0
    );

    $date =
        $data["date"] ?? "";


    if (!$habitId || !$date) {

        http_response_code(400);

        echo json_encode([
            "error" =>
                "Invalid request"
        ]);

        exit;
    }


    $check =
        $pdo->prepare("
            SELECT id
            FROM completions
            WHERE habit_id = ?
            AND completed_date = ?
        ");

    $check->execute([
        $habitId,
        $date
    ]);


    if ($check->fetch()) {

        $delete =
            $pdo->prepare("
                DELETE FROM completions
                WHERE habit_id = ?
                AND completed_date = ?
            ");

        $delete->execute([
            $habitId,
            $date
        ]);

    } else {

        $insert =
            $pdo->prepare("
                INSERT INTO completions
                (
                    habit_id,
                    completed_date
                )
                VALUES (?, ?)
            ");

        $insert->execute([
            $habitId,
            $date
        ]);

    }


    $completions = $pdo
        ->query("
            SELECT *
            FROM completions
            ORDER BY completed_date ASC
        ")
        ->fetchAll(PDO::FETCH_ASSOC);


    echo json_encode([
        "success" => true,
        "completions" => $completions
    ]);

    exit;
}


/*
|--------------------------------------------------------------------------
| DELETE HABIT
|--------------------------------------------------------------------------
*/

if ($action === "deleteHabit") {

    $id = intval(
        $_GET["id"] ?? 0
    );


    if (!$id) {

        http_response_code(400);

        echo json_encode([
            "error" =>
                "Invalid habit ID"
        ]);

        exit;
    }


    $pdo
        ->prepare("
            DELETE FROM completions
            WHERE habit_id = ?
        ")
        ->execute([
            $id
        ]);


    $pdo
        ->prepare("
            DELETE FROM habits
            WHERE id = ?
        ")
        ->execute([
            $id
        ]);


    $habits = $pdo
        ->query("
            SELECT *
            FROM habits
            ORDER BY id ASC
        ")
        ->fetchAll(PDO::FETCH_ASSOC);


    $completions = $pdo
        ->query("
            SELECT *
            FROM completions
            ORDER BY completed_date ASC
        ")
        ->fetchAll(PDO::FETCH_ASSOC);


    echo json_encode([
        "success" => true,
        "habits" => $habits,
        "completions" => $completions
    ]);
    exit;
}


/*
|--------------------------------------------------------------------------
| UNKNOWN REQUEST
|--------------------------------------------------------------------------
*/
http_response_code(404);
echo json_encode([
    "error" =>
        "Unknown action"
]);
?>