<?php
// 
// function getDatabase()
// {

//     $databaseFile = __DIR__ . "/habits.db";

//     $pdo = new PDO("sqlite:" . $databaseFile);

//     $pdo->setAttribute(
//         PDO::ATTR_ERRMODE,
//         PDO::ERRMODE_EXCEPTION
//     );

//     $pdo->exec("
//         CREATE TABLE IF NOT EXISTS habits (
//             id INTEGER PRIMARY KEY AUTOINCREMENT,
//             name TEXT NOT NULL,
//             created_at TEXT DEFAULT CURRENT_TIMESTAMP
//         )
//     ");

//     $pdo->exec("
//         CREATE TABLE IF NOT EXISTS completions (
//             id INTEGER PRIMARY KEY AUTOINCREMENT,
//             habit_id INTEGER NOT NULL,
//             completed_date TEXT NOT NULL,
//             UNIQUE(habit_id, completed_date),

//             FOREIGN KEY(habit_id)
//                 REFERENCES habits(id)
//                 ON DELETE CASCADE
//         )
//     ");

//     return $pdo;
// }
// ?>