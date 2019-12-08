<?php
    function ValidateCredentials($data) 
    {
        require 'users.php';

        if (array_key_exists("credentials", $data)==false)
        {   
            die('{"res":"need credentials"}');
            return;
        }
        
        $credentials = $data["credentials"];
        
        if (array_key_exists("username", $credentials)==true && array_key_exists("password", $credentials)==true)
        {
            $username = $credentials["username"];
            $password = $credentials["password"];
            
            if (array_key_exists($username, $users))
            {
                if ($users[$username]!=$password)
                {   
                    die('{"res":"bad login/passwd"}');
                }
                else
                {
                    return $username;
                }
            }
            else
            {
                die('{"res":"bad login/passwd"}');
            }
        }
        else
        {
            die('{"res":"login/pass not set"}');
        }
    }

    $ACCESS_PWD='';
    
    date_default_timezone_set("Europe/Brussels"); 
    
    header("Content-Type: application/javascript; charset=us-ascii");
    header_remove("Transfer-Encoding"); 
    header_remove("Connection");

    // Create connection
    $lang = substr($_SERVER['HTTP_ACCEPT_LANGUAGE'], 0, 2);
    if ($lang=="es")
        $conn = new SQLite3("SEMap_es.sqlite");
    else
        $conn = new SQLite3("SEMap_en.sqlite");

    if ($conn===false) 
    {
        echo "Failed to connect to MySQL:  " . $conn->lastErrorMsg();
    }

    if (!($conn->exec("CREATE TABLE IF NOT EXISTS NODES (id TEXT UNIQUE, label TEXT, x FLOAT, y FLOAT, username TEXT DEFAULT 'raul', timestamp TEXT DEFAULT CURRENT_TIMESTAMP)")))
    {
        die( "Error creating NODES table" . $conn->lastErrorMsg());
    }

    if (!($conn->exec("CREATE TABLE IF NOT EXISTS EDGES (id TEXT UNIQUE, source TEXT, target TEXT, answer TEXT, username TEXT DEFAULT 'raul', timestamp TEXT DEFAULT CURRENT_TIMESTAMP)")))
    {
        die( "Error creating EDGES table" . $conn->lastErrorMsg());
    }

    $method =  $_SERVER['REQUEST_METHOD'];

    if ($method=="POST") 
    {
        $data = json_decode(file_get_contents('php://input'), true);

        $username = validateCredentials($data);

        if (!($conn->exec("BEGIN TRANSACTION")))
        {
            die( '{"res":"' . $conn->lastErrorMsg() . '"}');
        } 

        if (array_key_exists("nodes", $data)==true)
        {
            $nodelist = $data["nodes"];
            foreach ($nodelist as $node) 
            {
                $id = SQLite3::escapeString($node["id"]);
                $l  = SQLite3::escapeString($node["l"]);
                $x  = SQLite3::escapeString($node["x"]);
                $y  = SQLite3::escapeString($node["y"]);
                $u  = SQLite3::escapeString($username);

                $str = "insert or replace INTO NODES values( '$id', '$l', '$x', '$y', '$u', datetime('now') )";
                if (!$conn->exec($str)) 
                {
                    die( '{"res":"Node Insert failed: ' . $conn->lastErrorMsg() . '\n\n'  . escapeJsonString($str) . '"}');
                }
            }
        }

        if (array_key_exists("edges", $data)==true)
        {
            $edgelist = $data["edges"];
            foreach ($edgelist as $edge)
            {
                $id = SQLite3::escapeString($edge["id"]);
                $s  = SQLite3::escapeString($edge["s"]);
                $t  = SQLite3::escapeString($edge["t"]);
                $l  = SQLite3::escapeString($edge["l"]);
                $u  = SQLite3::escapeString($username);

                $str = "insert or replace INTO EDGES values( '$id', '$s', '$t', '$l', '$u', datetime('now') )";
                if (!$conn->exec($str)) 
                {
                    die( '{"res":"Edge Insert failed: ' . escapeJsonString($str) . '  ' . $conn->lastErrorMsg() . '"}');
                }
            }
        }

        if (!($conn->exec("END TRANSACTION")))
        {
            die( '{"res":"' . $conn->lastErrorMsg() . '"}');
        } 

        header("HTTP/1.1 200 OK");
        echo '{"res":"OK"}';
    } 
    else if ($method=="GET") 
    {
        echo '{"nodes":[';

        $results = $conn->query('SELECT * FROM "NODES"');
        $counter = 0;        
        while ($row = $results->fetchArray(1)) 
        {
            if ($counter!=0)
                echo ',';
            $id = json_encode($row["id"]);
            $q =  json_encode($row["label"]);
            $x =  json_encode($row["x"]);
            $y =  json_encode($row["y"]);
            $u =  json_encode($row["username"]);
            echo '{"id":'.$id.', "q":'.$q.', "x":'.$x.', "y":'.$y.', "u":'.$u.'}';
            $counter++;
        }
        
        echo "],";

        echo '"edges":[';

        $results = $conn->query('SELECT * FROM "EDGES"');
        $counter = 0;        
        while ($row = $results->fetchArray(1)) 
        {
            if ($counter!=0)
                echo ',';

            $id = json_encode($row["id"]);
            $s = json_encode($row["source"]);
            $t = json_encode($row["target"]);
            $l = json_encode($row["answer"]);
            $u = json_encode($row["username"]);
            echo '{"id":'.$id.',"s":'.$s.',"t":'.$t.',"l":'.$l.',"u":'.$u.'}';

            $counter++;
        }
        
        header("HTTP/1.1 200 OK");
        echo '],"res":"OK" }';
        
    }
    else if ($method=="DELETE") 
    {
        $data = json_decode(file_get_contents('php://input'), true);
        
        validateCredentials($data);
        
        if (!($conn->exec("BEGIN TRANSACTION")))
        {
            die( '{"res":"' . $conn->lastErrorMsg() . '"}');
        } 

        if (array_key_exists("nodes", $data)==true)
        {
            $idlist = $data["nodes"];
            foreach ($idlist as $id) 
            {
                $str = "delete from NODES where id='".SQLite3::escapeString($id)."'";
                if (!$conn->exec($str)) 
                {
                    die( '{"res":"Node Insert failed: ' . $str . '  ' . $conn->lastErrorMsg() . '"}');
                }
            }
        }
    
        if (array_key_exists("edges", $data)==true)
        {
            $idlist = $data["edges"];
            foreach ($idlist as $id) 
            {
                $str = "delete from EDGES where id='".SQLite3::escapeString($id)."'";
                if (!$conn->exec($str)) 
                {
                    die( '{"res":"Edge Insert failed: ' . $str . '  ' . $conn->lastErrorMsg() . '"}');
                }
            }
        }
        
        if (!($conn->exec("END TRANSACTION")))
        {
            die( '{"res":"' . $conn->lastErrorMsg() . '"}');
        } 
        
        header("HTTP/1.1 200 OK");
        echo '{"res":"OK"}';
    }    
    
    $conn->close();
?>
