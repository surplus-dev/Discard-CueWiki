<?php
    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
    error_reporting(E_ALL & ~E_NOTICE);

    function load_render($title, $data) {
        $data = preg_replace("/'''((?:(?!''').)+)'''/", "<b>$1</b>", $data);
        $data = preg_replace("/''((?:(?!'').)+)''/", "<i>$1</i>", $data);

        return $data;
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
            return $lang_file["en-US"][$data];
        } else {
            return $data.' (Missing)';
        }
    }
    
    function load_skin($head = '', $body = '', $tool = [], $other = []) {
        $tool_html = "";
        foreach($tool as &$tool_data) {
            $tool_html = $tool_html."<a href=".$tool_data[1].">".$tool_data[0]."</a> ";
        }

        if(!$other["title"]) {
            $other["title"] = "";
        }

        if(!$other["sub"]) {
            $other["sub"] = "";
        }

        $title = "";
        if($other["title"] !== "") {
            $title = $other["title"];

            if($other["sub"] !== "") {
                $title = $title." (".$other["sub"].")";
            }
        }
        
        $main_skin = "
            <html>
                <head>
                    <title>".$title."</title>
                    ".$head."
                </head>
                <body>
                    <div id=\"top\">
                        <a href=\"".url_fix()."\">".load_lang("main")."</a>
                        <a href=\"?action=r_change\">".load_lang("recent_changes")."</a>
                        <a href=\"?action=o_tool\">".load_lang("other_tool")."</a>
                    </div>
                    <br>
                    <br>
                    <div id=\"middle\">
                        <div id=\"title\">
                            ".$title."
                        </div>
                        <br>
                        <br>
                        <div id=\"tool\">
                            ".$tool_html."
                        </div>
                        <br>
                        <br>
                        <div id=\"data\">
                            ".$body."
                        </div>
                    </div>
                    <br>
                    <br>
                    <div id=\"bottom\">
                        <a href=\"https://github.com/Make-openNAMU/PHP-openNAMU_Lite\">openNAMU_Lite</a>
                    </div>
                </body>
            </html>
        ";

        return $main_skin;
    }
    
    $lang_file = [];
    
    $lang_file["en-US"] = json_decode(file_get_contents('./language/en-US.json'), true);

    $inter_version = "v0.0.02";
    $version = "0002";

    $conn = new PDO('sqlite:data.db');
    session_start();

    $sql = $conn -> prepare('create table if not exists setting(title text, data text)');
    $sql -> execute();
    
    $sql = $conn -> prepare('select data from setting where title = "version"');
    $sql -> execute();
    $data = $sql -> fetchAll();
    if(!$data) {
        $sql = $conn -> prepare('create table if not exists history(num text, title text, data text, date text, who text)');
        $sql -> execute();

        $sql = $conn -> prepare('alter table history add why text default ""');
        $sql -> execute();

        $sql = $conn -> prepare('alter table history add blind text default ""');
        $sql -> execute();

        $sql = $conn -> prepare('insert into setting (title, data) values ("version", ?)');
        $sql -> execute(array($version));

        $data = '0001';
    } else {
        $data = $data[0]['data'];
    }

    if((int)$data < 0002) {
        $sql = $conn -> prepare('alter table history add how text default ""');
        $sql -> execute();

        $sql = $conn -> prepare('update setting set data = ? Where title = "version"');
        $sql -> execute(array($version));
    }

    switch($_GET['action']) {
        case "":
            echo redirect("?action=w&title=Wiki:Main");

            break;
        case "w":
            if($_GET['title']) {
                $sql = $conn -> prepare('select data from history where title = ? order by date desc limit 1');
                $sql -> execute(array($_GET['title']));
                $data = $sql -> fetchAll();
                if($data) {
                    $get_data = load_render($_GET['title'], $data[0]["data"]);
                } else {
                    $get_data = "404";
                }
                
                echo load_skin("", $get_data,
                    [
                        [load_lang('edit'), '?action=edit&title='.urlencode($_GET['title'])],
                        [load_lang("history"), '?action=history&title='.urlencode($_GET['title'])]
                    ], ["title" => $_GET['title']]);
            } else {
                echo redirect();
            }

            break;
        case "edit":
            if($_GET['title']) {
                if($_SERVER['REQUEST_METHOD'] === 'POST') {
                    $sql = $conn -> prepare('select num from history where title = ? order by date desc limit 1');
                    $sql -> execute(array($_GET['title']));
                    $data = $sql -> fetchAll();
                    if($data) {
                        $num = (string)((int)$data[0]["num"] + 1);
                    } else {
                        $num = "1";
                    }
                    
                    if(mb_strlen($_POST["why"], 'UTF-8') > 64) {
                        $why = "";
                    } else {
                        $why = $_POST["why"];
                    }

                    $sql = $conn -> prepare('insert into history (num, title, data, date, who, why, blind) values (?, ?, ?, ?, ?, ?, "")');
                    $sql -> execute(array($num, $_GET['title'], $_POST["data"], (string)date("Y-m-d H:i:s"), $_SERVER['REMOTE_ADDR'], $why));

                    echo redirect("?action=w&title=".$_GET['title']);
                } else {
                    $sql = $conn -> prepare('select data from history where title = ? order by date desc limit 1');
                    $sql -> execute(array($_GET['title']));
                    $data = $sql -> fetchAll();
                    if($data) {
                        $get_data = $data[0]["data"];
                    } else {
                        $get_data = "";
                    }

                    echo load_skin("", "
                        <form method=\"post\">
                            <textarea style=\"width: 500px; height: 300px;\" name=\"data\">".$get_data."</textarea>
                            <br>
                            <br>
                            <input name=\"why\"></input>
                            <br>
                            <br>
                            <button type=\"submit\">".load_lang("save")."</button>
                        </form>
                    ", [[load_lang("return"), "?action=w&title=".$_GET['title']]], ["title" => $_GET['title'], "sub" => load_lang("edit")]);
                }
            } else {
                echo redirect();
            }

            break;
        case "history":
            if($_GET['title']) {
                $html_data = '';

                $sql = $conn -> prepare('select num, date, who, why from history where title = ? order by date desc');
                $sql -> execute(array($_GET['title']));
                $data = $sql -> fetchAll();
                foreach($data as &$in_data) {
                    $html_data = $html_data."
                        ".$in_data["num"]." | ".$in_data["date"]." | ".$in_data["who"]." | ".$in_data["why"]."
                        <br>
                    ";
                }

                echo load_skin("", $html_data, [[load_lang("return"), "?action=w&title=".$_GET['title']]], ["title" => $_GET['title'], "sub" => load_lang("history")]);
            } else {
                echo redirect();
            }

            break;
        case "r_change":
            $html_data = '';

            $sql = $conn -> prepare('select num, title, date, who, why from history order by date desc limit 50');
            $sql -> execute();
            $data = $sql -> fetchAll();
            foreach($data as &$in_data) {
                $html_data = $html_data."
                    <a href=\"?action=w&title=".urlencode($in_data["title"])."\">".$in_data["title"]."</a> | ".$in_data["num"]." | ".$in_data["date"]." | ".$in_data["who"]." | ".$in_data["why"]."
                    <br>
                ";
            }

            echo load_skin("", $html_data, [], ["title" => load_lang("recent_changes")]);

            break;
        case "o_tool":
            $html_data = "
                ".load_lang("users_tool")."
                <br>
                Test
                <br>
                <br>
                ".load_lang("admins_tool")."
                <br>
                <a href=\"?action=setting\">".load_lang("setting")."</a>
            ";

            echo load_skin("", $html_data, [], ["title" => load_lang("other_tool")]);

            break;
        default:
            echo redirect();

            break;
    }
?>