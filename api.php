<?php
header("Content-Type: application/json");
include 'db.php';

$method = $_SERVER['REQUEST_METHOD'];
$input = json_decode(file_get_contents('php://input'), true);

switch ($method) {
    case 'GET':
        handleGet($pdo, $input);
        break;
    case 'POST':
        handlePost($pdo, $input);
        break;
    case 'PUT':
        handlePut($pdo, $input);
        break;
    case 'DELETE':
        handleDelete($pdo, $input);
        break;
    default:
        echo json_encode(['message' => 'Invalid request method']);
        break;
}

function handleGet($pdo, $input) {
    $sql = "SELECT user FROM id_table";
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $tables = [];
    foreach ($result as $row) {
        $tables[] = $row["user"];
    }
    if (isset($input["table_id"])) {
        $table = $input["table_id"];
    } else {
        echo json_encode(["message"=> "No table_id given!"]);
        exit();
    }
    if ($table == "id_table") {
        echo json_encode($tables);
        exit();
    }
    if ($table == "adm") {
        echo json_encode(["message"=>"That is not a valid table_id to request!"]);
        exit();
    }
    if (!in_array($table, $tables)) {
        echo json_encode(["message"=> "That is not a valid table_id!"]);
        exit();
    }
    $sql = "SELECT * FROM $table";
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode($result);
}

function handlePost($pdo, $input) {
    $sql = "SELECT user FROM id_table";
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $tables = [];
    foreach ($result as $row) {
        $tables[] = $row["user"];
    }
    if (isset($input["table_id"])) {
        if (isset($input["pass"])) {
            if (strlen($input["pass"]) < 8) {
                echo json_encode(["message"=> "Given pass must be at least 8 characters"]);
                exit();
            }
        } else {
            echo json_encode(["message"=> "No pass provided!"]);
            exit();
        }
    } else {
        echo json_encode(["message"=> "No table_id provided!"]);
        exit();
    }
    if ($input["table_id"] == "id_table") {
        if (!isset($input["user"])) {
            echo json_encode(["message"=> "No user provided!"]);
            exit();
        }
        $isValid = true;
            $user = $input["user"];
            for ($i = 0; $i < strlen($user); $i++) {
                $char = $user[$i];
                if (!ctype_alnum($char) && $char != "_") {
                    $isValid = false;
                    break;
                }
            }
            if (!$isValid || !(strlen($input["user"]) <= 64)) {
                echo json_encode(["message"=> "Invalid user name"]);
                exit();
            }
        if (in_array($input["user"], $tables)) {
            echo json_encode(["message"=> "Given user already exists!"]);
        } else {
            $table = $input["table_id"];
            $hashedpassword = password_hash($input["pass"], PASSWORD_DEFAULT);
            $sql = "INSERT INTO $table (user, pass) VALUES (:user, :pass)";
            $stmt = $pdo->prepare($sql);
            $stmt->execute(['user' => $input['user'], 'pass' => $hashedpassword]);
            $new_table = $input['user'];
            $sql = "CREATE TABLE $new_table (
            time DATETIME DEFAULT CURRENT_TIMESTAMP,
            loc TEXT,
            sensor_1 TEXT,
            sensor_2 TEXT
            )";
            $pdo->exec($sql);
            echo json_encode(['message'=> 'User Created']);
            exit();
        }
    } elseif (in_array($input['table_id'], $tables)) {
        if ($input['table_id'] == "adm") {
            echo json_encode(["message"=> "You cannot send this kind of request to this table_id!"]);
            exit();
        }
        $table = $input['table_id'];
        $sql = "SELECT pass FROM id_table WHERE user = :user";
        $stmt = $pdo->prepare($sql);
        $stmt->execute(["user"=> $table]);
        $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $result = json_encode($result);
        $data = json_decode($result,true);
        if (isset($data[0]["pass"])) {
            $pass = strval($data[0]["pass"]);
        }
        if (password_verify($input["pass"], $pass)) {
            if (!isset($input["loc"])) {
                echo json_encode(['message'=>'No loc specified!']);
                exit();
            }
            if (!isset($input["sensor_1"])) {
                echo json_encode(['message'=>'No sensor_1 data!']);
                exit();
            }
            if (!isset($input["sensor_2"])) {echo json_encode(['message'=>'No sensor_2 data!']);
                exit();
            }
            $sql = "INSERT INTO $table (loc, sensor_1, sensor_2) VALUES (:loc, :sensor_1, :sensor_2)";
            $stmt = $pdo->prepare($sql);
            $stmt->execute(["loc"=> $input["loc"], "sensor_1"=> $input["sensor_1"], "sensor_2"=> $input["sensor_2"]]);
            echo json_encode(["message"=> "Data entered!"]);
        } else {
            echo json_encode(["message"=> "Incorrect pass!"]);
        }
    } else {
        echo json_encode(['message'=> 'That table_id does not exist!']);
    }    
}

function handlePut($pdo, $input) {
    $sql = "SELECT user FROM id_table";
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $tables = [];
    foreach ($result as $row) {
        $tables[] = $row["user"];
    }
    if (!in_array($input["user"], $tables)) {
        echo json_encode(["message"=> "No such user in id_table!"]);
        exit();
    }
    if ($input["user"] == "adm") {
        echo json_encode(["message"=> "Cannot modify admin user!"]);
        exit();
    }
    if (!isset($input["password"])) {
        echo json_encode(["message"=> "No password set!"]);
        exit();
    }
    if (!isset($input["user"])) {
        echo json_encode(["message"=> "No user set!"]);
        exit();
    }
    if (!isset($input["pass"])) {
        echo json_encode(["message"=> "No pass set!"]);
        exit();
    }
    if (!isset($input["new_user"])) {
        echo json_encode(["message"=> "No new_user set!"]);
        exit();
    }
    $sql = "SELECT pass FROM id_table WHERE user = 'adm'";
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $result = json_encode($result);
    $data = json_decode($result,true);
    if (isset($data[0]["pass"])) {
        $pass = strval($data[0]["pass"]);
    }
    if (password_verify($input["password"],$pass)) {
        $isValid = true;
        $user = $input["new_user"];
        for ($i = 0; $i < strlen($user); $i++) {
            $char = $user[$i];
            if (!ctype_alnum($char) && $char != "_") {
                $isValid = false;
                break;
            }
        }
        if (!$isValid || !(strlen($input["new_user"]) <= 64)) {
            echo json_encode(["message"=> "Invalid new_user name"]);
            exit();
        }
        $sql = 'UPDATE id_table SET pass = :pass, user = :new_user WHERE user = :user';
        $stmt = $pdo->prepare($sql);
        $stmt->execute(['user'=> $input["user"], 'pass'=>password_hash($input['pass'], PASSWORD_DEFAULT), 'new_user'=>$input["new_user"]]);
        echo json_encode(['message'=>'User modified successfully']);
    }
}

function handleDelete($pdo, $input) {
    $sql = "SELECT user FROM id_table";
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $tables = [];
    foreach ($result as $row) {
        $tables[] = $row["user"];
    }
    if (!in_array($input["user"], $tables)) {
        echo json_encode(["message"=> "No such user in id_table!"]);
        exit();
    }
    if ($input["user"] == "adm") {
        echo json_encode(["message"=> "Cannot delete admin user!"]);
        exit();
    }
    if (!isset($input["password"])) {
        echo json_encode(["message"=> "No password set!"]);
        exit();
    }
    if (!isset($input["user"])) {
        echo json_encode(["message"=> "No user set!"]);
        exit();
    }
    $sql = "SELECT pass FROM id_table WHERE user = 'adm'";
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $hashedpassword = hash('sha256',$input["password"]);
    $result = json_encode($result);
    $data = json_decode($result,true);
    if (isset($data[0]["pass"])) {
        $pass = strval($data[0]["pass"]);
    }
    if (hash_equals($pass, $hashedpassword)) {
        $user = $input["user"];
        $sql = 'DELETE FROM id_table WHERE user = :user';
        $stmt = $pdo->prepare($sql);
        $stmt->execute(['user'=>$user]);
        $sql = "DROP TABLE $user";
        $pdo->exec($sql);
        echo json_encode(['message'=>'User deleted successfully']);
    }
    
}

?>