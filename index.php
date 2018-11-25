<?php
    $lang_file = [];
    
    $lang_file["en-US"] = [];
    
    $lang_file["en-US"]["Edit"] = "Edit";
    $lang_file["en-US"]["version"] = "Version";

    function load_skin($head, $body) {
        $main_skin = "
            <html>
                <head>
                    ".$head."
                </head>
                <body>
                    <div id=\"top\">
                        <a href=\"/\">Main</a>
                    </div>
                    <br>
                    <br>
                    <div id=\"middle\">
                        ".$body."
                    </div>
                    <br>
                    <br>
                    <div id=\"bottom\">
                        openNAMU_Lite
                    </div>
                </body>
            </html>
        ";

        return $main_skin;
    }
    
    function load_render($title, $data) {
    
    }
    
    function redirect($url = '') {
        return '<meta http-equiv="refresh" content="0; url='.$_SERVER['PHP_SELF'].$url.'">';
    }

    function file_fix($url) {
        return preg_replace('/\/index.php$/' , '', $_SERVER['PHP_SELF']).$url;
    }

    function url_fix($url = '') {
        return $_SERVER['PHP_SELF'].$url;
    }
    
    function load_lang($data) {
        global $lang_file;

        if($lang_file["en-US"][$data]) {
            return $lang_file[$data];
        } else {
            return $data.' (Missing)';
        }
    }
    
    $conn = new PDO('sqlite:data.db');
    session_start();
    
    $create = $conn -> prepare('create table if not exists history(num text, title text, data text, date text, who text)');
    $create -> execute();
    
    switch($_GET['action']) {
        case "":
            echo redirect("/index.php?action=w&title=Wiki:Main");

            break;
        case "w":
            if($_GET['title']) {
                $select = $conn -> prepare('select data from history where title = ? order by date limit 1');
                $select -> execute($_GET['title']);
                $data = $select -> fetchAll();
                if($data) {
                    echo load_skin("", $data[0]["data"]);
                } else {
                    echo load_skin("", "404");
                }
            } else {
                echo redirect();
            }

            break;
        case "edit":
            echo redirect();

            break;
        case "history":
            echo redirect();

            break;
        default:
            echo redirect();

            break;
    }
?>