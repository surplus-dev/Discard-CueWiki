<?php
    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
    error_reporting(E_ALL & ~E_NOTICE);

    $setting = json_decode(file_get_contents('./setting.json'), TRUE);
    require_once('./skin/'.$setting['skin'].'/index.php');

    $conn = new PDO('sqlite:data.db');
    session_start();

    function xss_protect($data) {
        $data = preg_replace("/</", "&lt;", $data);
        $data = preg_replace("/>/", "&gt;", $data);

        return $data;
    }

    function load_id() {
        if($_SESSION["id"]) {
            $id = $_SESSION["id"];
        } else {
            $id = $_SERVER['REMOTE_ADDR'];
        }

        return $id;
    }

    function load_pass($data = "owner") {
        $pass = FALSE;

        $acl = load_acl();
        switch($data) {
            case "owner":
                if($acl === "owner") {
                    $pass = TRUE;
                }
                
                break;
            case "admin":
                $check = ["owner", "admin"];
                if(in_array($acl, $check)) {
                    $pass = TRUE;
                }

                break;
            case "registered":
                if($acl !== "ip") {
                    $pass = TRUE;
                }
                
                break;
        }
        
        return $pass;
    }
    
    function ip_or_id($data) {
        if(preg_match("/\.|:/", $data)) {
            return TRUE;
        } else {
            return FALSE;
        }
    }

    function load_acl() {
        $conn = new PDO('sqlite:data.db');

        if($_SESSION["id"]) {
            $sql = $conn -> prepare('select data from user_set where title = "acl" and id = ?');
            $sql -> execute([$_SESSION["id"]]);
            $data = $sql -> fetchAll();
            if($data) {
                $acl = $data[0]["data"];
            } else {
                $acl = "registered";
            }

            return $acl;
        } else {
            return "ip";
        }
    }

    function load_render($title, $data) {
        $conn = new PDO('sqlite:data.db');

        $data = preg_replace("/\r\n/", "\n", $data);
        $data = "\n".$data."\n";

        $data = xss_protect($data);

        if($setting['grammar'] === "namumark") {
            $data = preg_replace("/'''((?:(?!''').)+)'''/", "<b>$1</b>", $data);
            $data = preg_replace("/''((?:(?!'').)+)''/", "<i>$1</i>", $data);
        } else {
            $data = preg_replace("/\*\*((?:(?!\*\*).)+)\*\*/", "<b>$1</b>", $data);
            $data = preg_replace("/\*((?:(?!\*).)+)\*/", "<i>$1</i>", $data);

            $data = preg_replace("/__((?:(?!__).)+)__/", "<b>$1</b>", $data);
            $data = preg_replace("/_((?:(?!_).)+)_/", "<i>$1</i>", $data);

            $data = preg_replace("/~~((?:(?!~~).)+)~~/", "<s>$1</s>", $data);

            $data = preg_replace("/`((?:(?!`).)+)`/", "<pre>$1</pre>", $data);

            $data = preg_replace_callback("/\n(#{1,6}) ?([^\n]+)/",
                function($matches) {
                    return "<h".mb_strlen($matches[1], 'UTF-8').">".$matches[2]."</h".mb_strlen($matches[1], 'UTF-8').">";
                }
            , $data);

            $data = preg_replace_callback("/\[([^\]]*)\]\(([^)]*)\)/",
                function($matches) {
                    $conn = new PDO('sqlite:data.db');
                    
                    if($matches[1] === "" && $matches[2] === "") {
                        return "";
                    } else {
                        if($matches[2] === "") {
                            $sql = $conn -> prepare('select data from history where title = ? order by date desc limit 1');
                            $sql -> execute([$matches[2]]);
                            $data = $sql -> fetchAll();
                            if($data && $data[0]["data"] !== "") {
                                $href_class = "";
                            } else {
                                $href_class = "style=\"color: red;\"";
                            }

                            return "<a ".$href_class." href=\"?action=w&title=".urlencode($matches[1])."\">".$matches[1]."</a>";
                        } else {
                            if($matches[1] === "") {
                                $out_link = $matches[2];
                            } else {
                                $out_link = $matches[1];
                            }

                            if(preg_match("/^https?:\/\//i", $matches[2])) {
                                return "<a style=\"color: green;\" href=\"".$matches[2]."\">".$out_link."</a>";
                            } else {
                                $sql = $conn -> prepare('select data from history where title = ? order by date desc limit 1');
                                $sql -> execute([$matches[2]]);
                                $data = $sql -> fetchAll();
                                if($data && $data[0]["data"] !== "") {
                                    $href_class = "";
                                } else {
                                    $href_class = "style=\"color: red;\"";
                                }
                                
                                return "<a ".$href_class." href=\"?action=w&title=".urlencode($matches[2])."\">".$out_link."</a>";
                            }
                        }
                    }
                }
            , $data);

            $data = preg_replace_callback("/((?:&gt;([^\n]+)\n)+)/",
                function($matches) {
                    $return = preg_replace("/\n&gt;/", "\n", $matches[1]);
                    $return = preg_replace("/^&gt;/", "", $return);

                    return "<blockquote>".preg_replace("/\n/", "<br>", $return)."</blockquote>";
                }
            , $data);

            $data = preg_replace_callback("/(\n{2,})/",
                function($matches) {
                    return preg_replace("/\n/", "<br>", $matches[1]);
                }
            , $data);
        }

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
            return $data.' (M)';
        }
    }
    
    function load_skin($head = '', $body = '', $tool = [], $other = []) {
        $main_skin = skin_render($head, $body, $tool, $other);

        return $main_skin;
    }
    
    function load_error($data = "Missing error", $menu = []) {
        return load_skin("", $data, $menu, ["title" => load_lang("error")]);
    }

    function change_alphabet($data) {        
        return preg_replace_callback("/^([a-z]+)$/",
            function($matches) {
                if(in_array($matches[1], ["ip"])) {
                    return strtoupper($matches[1]);
                } else {
                    return ucfirst($matches[1]);
                }
            }
        , $data);
    }
    
    $lang_file = [];
    
    $lang_file["en-US"] = json_decode(file_get_contents('./language/en-US.json'), TRUE);

    $version = "v0.0.04";

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
        $sql -> execute([$version]);

        $data = '0001';
    } else {
        $data = $data[0]['data'];
    }

    if((int)$data < 2) {
        $sql = $conn -> prepare('alter table history add how text default ""');
        $sql -> execute();

        $sql = $conn -> prepare('update setting set data = "0002" Where title = "version"');
        $sql -> execute();
    }

    if((int)$data < 3) {
        $sql = $conn -> prepare('create table if not exists user(id text, pw text, encode text)');
        $sql -> execute();
        
        $sql = $conn -> prepare('create table if not exists user_set(title text, data text)');
        $sql -> execute();

        $sql = $conn -> prepare('alter table user_set add id text default ""');
        $sql -> execute();

        $sql = $conn -> prepare('update setting set data = "0003" Where title = "version"');
        $sql -> execute();
    }

    if((int)$data < 4) {
        $sql = $conn -> prepare('create table if not exists acl(title text, acl text, topic text)');
        $sql -> execute();

        $sql = $conn -> prepare('update setting set data = "0004" Where title = "version"');
        $sql -> execute();
    }

    switch($_GET['action']) {
        case "":
            echo redirect("?action=w&title=Wiki:Main");

            break;
        case "w":
        case "raw":
            if($_GET['title']) {
                if($_GET['num']) {
                    $sql = $conn -> prepare('select data from history where title = ? and num = ? order by date desc limit 1');
                    $sql -> execute([$_GET['title'], $_GET['num']]);
                    $title = ["title" => $_GET['title'], "sub" => $_GET['num']];
                    $menu = [
                        [load_lang("return"), '?action=history&title='.urlencode($_GET['title'])]
                    ];
                } else {
                    $sql = $conn -> prepare('select data from history where title = ? order by date desc limit 1');
                    $sql -> execute([$_GET['title']]);
                    $title = ["title" => $_GET['title']];
                    $menu = [
                        [load_lang('edit'), '?action=edit&title='.urlencode($_GET['title'])],
                        [load_lang('raw'), '?action=raw&title='.urlencode($_GET['title'])],
                        [load_lang("history"), '?action=history&title='.urlencode($_GET['title'])],
                        ["ACL", '?action=acl&title='.urlencode($_GET['title'])]
                    ];
                }
                $data = $sql -> fetchAll();
                if($data && $data[0]["data"] !== "") {
                    if($_GET['action'] === "w") {
                        $get_data = load_render($_GET['title'], $data[0]["data"]);
                    } else {
                        $get_data = "<pre>".$data[0]["data"]."</pre>";
                        if($title["sub"]) {
                            $title = ["title" => $_GET['title'], "sub" => $_GET['num']." | ".load_lang("raw")];
                        } else {
                            $title = ["title" => $_GET['title'], "sub" => load_lang("raw")];
                            $menu = [
                                [load_lang("return"), '?action=w&title='.urlencode($_GET['title'])]
                            ];
                        }
                    }
                } else {
                    $get_data = "404";
                }
                
                echo load_skin("", $get_data, $menu, $title);
            } else {
                echo redirect();
            }

            break;
        case "acl":
            if($_GET['title']) {
                $pass = load_pass("admin");

                if($pass) {
                    if($_SERVER['REQUEST_METHOD'] === 'POST') {
                        $sql = $conn -> prepare('select acl from acl where title = ?');
                        $sql -> execute([$_GET['title']]);
                        $data = $sql -> fetchAll();
                        if($data) {
                            if($_POST["acl"] !== "ip") {
                                $sql = $conn -> prepare('update acl set acl = ? where title = ?');
                                $sql -> execute([$_POST["acl"], $_GET['title']]);
                            } else {
                                $sql = $conn -> prepare('delete from acl where title = ?');
                                $sql -> execute([$_GET['title']]);
                            }       
                        } else {
                            if($_POST["acl"] !== "ip") {
                                $sql = $conn -> prepare('insert into acl (title, acl, topic) values (?, ?, "")');
                                $sql -> execute([$_GET['title'], $_POST["acl"]]);
                            }
                        }

                        echo redirect('?action=w&title='.$_GET['title']);
                    } else {
                        $sql = $conn -> prepare('select acl from acl where title = ?');
                        $sql -> execute([$_GET['title']]);
                        $data = $sql -> fetchAll();
                        if($data) {
                            switch($data[0]['acl']) {
                                case "register":
                                    $option = "
                                        <option value=\"register\">Registered</option>
                                        <option value=\"ip\">IP</option>    
                                        <option value=\"admin\">Admin</option>
                                        <option value=\"owner\">Owner</option>
                                    ";

                                    break;
                                case "admin":
                                    $option = "
                                        <option value=\"admin\">Admin</option>
                                        <option value=\"ip\">IP</option>    
                                        <option value=\"register\">Registered</option>
                                        <option value=\"owner\">Owner</option>
                                    ";

                                    break;
                                case "owner":
                                    $option = "
                                        <option value=\"owner\">Owner</option>
                                        <option value=\"ip\">IP</option>    
                                        <option value=\"register\">Registered</option>
                                        <option value=\"admin\">Admin</option>
                                    ";

                                    break;
                            }
                        } else {
                            $option = "
                                <option value=\"ip\">IP</option>    
                                <option value=\"register\">Registered</option>
                                <option value=\"admin\">Admin</option>
                                <option value=\"owner\">Owner</option>
                            ";
                        }
                        $html_data = "
                            <form method=\"post\">
                                <select name=\"acl\">
                                    ".$option."
                                </select>
                                <br>
                                <br>
                                <button type=\"submit\">".load_lang("save")."</button>
                            </form>
                        ";
                        echo load_skin("", $html_data, [[load_lang("return"), '?action=w&title='.urlencode($_GET['title'])]], ["title" => $_GET['title'], "sub" => "ACL"]);
                    }
                } else {
                    echo load_error(load_lang("acl_e"), [[load_lang("return"), '?action=w&title='.urlencode($_GET['title'])]]);
                }
            } else {
                echo redirect();
            }

            break;
        case "edit":
            if($_GET['title']) {
                $sql = $conn -> prepare('select acl from acl where title = ?');
                $sql -> execute([$_GET['title']]);
                $data = $sql -> fetchAll();
                $pass = load_pass($data[0]["acl"]);

                if($pass) {
                    if($_SERVER['REQUEST_METHOD'] === 'POST') {
                        $sql = $conn -> prepare('select num from history where title = ? order by date desc limit 1');
                        $sql -> execute([$_GET['title']]);
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

                        if($_POST["data"] === "") {
                            $type = "delete";
                        } else {
                            $type = "";
                        }

                        $sql = $conn -> prepare('insert into history (num, title, data, date, who, why, blind, how) values (?, ?, ?, ?, ?, ?, "", ?)');
                        $sql -> execute([$num, $_GET['title'], $_POST["data"], (string)date("Y-m-d H:i:s"), load_id(), $why, $type]);

                        echo redirect("?action=w&title=".$_GET['title']);
                    } else {
                        $sql = $conn -> prepare('select data from history where title = ? order by date desc limit 1');
                        $sql -> execute([$_GET['title']]);
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
                    $sql = $conn -> prepare('select acl from acl where title = ?');
                    $sql -> execute([$_GET['title']]);
                    $data = $sql -> fetchAll();
                    if($data) {
                        echo load_error(load_lang("acl_e")."<br><br>ACL | ".$data[0]["acl"], [[load_lang("return"), '?action=w&title='.urlencode($_GET['title'])]]);
                    } else {
                        echo load_error(load_lang("acl_e"), [[load_lang("return"), '?action=w&title='.urlencode($_GET['title'])]]);
                    }
                }
            } else {
                echo redirect();
            }

            break;
        case "history":
            if($_GET['title']) {
                $html_data = '';

                $sql = $conn -> prepare('select num, date, who, why, how from history where title = ? order by date desc');
                $sql -> execute([$_GET['title']]);
                $data = $sql -> fetchAll();
                foreach($data as &$in_data) {
                    if($in_data["how"] === "") {
                        $type = "edit";
                    } else {
                        $type = $in_data["how"];
                    }

                    $html_data = $html_data."
                        <a href=\"?action=w&num=".$in_data["num"]."&title=".$_GET['title']."\">".$in_data["num"]."</a> (<a href=\"?action=raw&num=".$in_data["num"]."&title=".$_GET['title']."\">".load_lang('raw')."</a>) | ".$in_data["date"]." | ".$in_data["who"]." | ".$type." | ".$in_data["why"]."
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

            $sql = $conn -> prepare('select num, title, date, who, why, how from history order by date desc limit 50');
            $sql -> execute();
            $data = $sql -> fetchAll();
            foreach($data as &$in_data) {
                if($in_data["how"] === "") {
                    $type = "edit";
                } else {
                    $type = $in_data["how"];
                }

                $html_data = $html_data."
                    <a href=\"?action=w&title=".urlencode($in_data["title"])."\">".$in_data["title"]."</a> | <a href=\"?action=history&title=".urlencode($in_data["title"])."\">".$in_data["num"]."</a> | ".$in_data["date"]." | ".$in_data["who"]." | ".$type." | ".$in_data["why"]."
                    <br>
                ";
            }

            echo load_skin("", $html_data, [], ["title" => load_lang("recent_changes")]);

            break;
        case "u_menu":
            $id = load_id();
            $acl = load_acl();

            $html_data = "
                ID | ".$id."
                <br>
                ACL | ".change_alphabet($acl)."
                <br>
                <br>
                <a href=\"?action=sign_up\">".load_lang("sign_up")."</a>
                <br>
                <a href=\"?action=sign_in\">".load_lang("sign_in")."</a>
                <br>
                <a href=\"?action=sign_out\">".load_lang("sign_out")."</a>
            ";

            echo load_skin("", $html_data, [], ["title" => load_lang("users_menu")]);

            break;
        case "sign_in":
            if($_SESSION["id"] === NULL) {
                if($_SERVER['REQUEST_METHOD'] === 'POST') {
                    $sql = $conn -> prepare('select pw from user where id = ?');
                    $sql -> execute([$_POST["id"]]);
                    $data = $sql -> fetchAll();
                    if($data) {
                        if($data[0]["pw"] === hash("sha256", $_POST["pw"])) {
                            $_SESSION["id"] = $_POST["id"];

                            echo redirect("?action=u_menu");
                        } else {
                            echo load_error(load_lang("pw_not_same_e"), [[load_lang("return"), '?action=sign_in']]);
                        }
                    } else {
                        echo load_error(load_lang("id_not_exist_e"), [[load_lang("return"), '?action=sign_in']]);
                    }
                } else {            
                    $html_data = "
                        <form method=\"post\">
                            ID
                            <br>
                            <input name=\"id\"></input>
                            <br>
                            <br>
                            Password
                            <br>
                            <input name=\"pw\" type=\"password\"></input>
                            <br>
                            <br>
                            <button type=\"submit\">".load_lang("sign_in")."</button>
                        </form>
                    ";
                    
                    echo load_skin("", $html_data, [[load_lang("return"), "?action=u_menu"]], ["title" => load_lang("sign_in")]);                    
                }
            } else {
                echo redirect("?action=u_menu");
            }
        
            break;
        case "sign_out":
            $_SESSION["id"] = NULL;
            
            echo redirect("?action=u_menu");
            
            break;
        case "sign_up":
            if($_SESSION["id"] === NULL) {
                if($_SERVER['REQUEST_METHOD'] === 'POST') {
                    if($_POST["pw"] !== $_POST["pw_2"]) {                    
                        echo load_error(load_lang("re_not_same_e"), [[load_lang("return"), '?action=sign_up']]);
                    } else {
                        if(!preg_match("/^[a-zA-Z0-9]+$/", $_POST["id"])) {
                            echo load_error(load_lang("id_match_e"), [[load_lang("return"), '?action=sign_up']]);
                        } else {
                            $sql = $conn -> prepare('select id from user where id = ?');
                            $sql -> execute([$_POST["id"]]);
                            $data = $sql -> fetchAll();
                            if(!$data) {
                                $sql = $conn -> prepare('select id from user limit 1');
                                $sql -> execute();
                                $data = $sql -> fetchAll();
                                if(!$data) {
                                    $sql = $conn -> prepare('insert into user_set (title, id, data) values ("acl", ?, "owner")');
                                    $sql -> execute([$_POST["id"]]);
                                }

                                $sql = $conn -> prepare('insert into user (id, pw, encode) values (?, ?, "sha256")');
                                $sql -> execute([$_POST["id"], hash("sha256", $_POST["pw"])]);

                                echo redirect("?action=u_menu");
                            } else {
                                echo load_error(load_lang("id_exist_e"), [[load_lang("return"), '?action=sign_up']]);
                            }
                        }
                    }
                } else {
                    $html_data = "
                        <form method=\"post\">
                            ID
                            <br>
                            <input name=\"id\"></input>
                            <br>
                            <br>
                            Password
                            <br>
                            <input name=\"pw\" type=\"password\"></input>
                            <br>
                            <br>
                            Repeat
                            <br>
                            <input name=\"pw_2\" type=\"password\"></input>
                            <br>
                            <br>
                            <button type=\"submit\">".load_lang("sign_up")."</button>
                        </form>
                    ";
                    
                    echo load_skin("", $html_data, [[load_lang("return"), "?action=u_menu"]], ["title" => load_lang("sign_up")]);                
                }
            } else {
                echo redirect("?action=u_menu");
            }
            
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
                Test
            ";

            echo load_skin("", $html_data, [], ["title" => load_lang("other_tool")]);

            break;
        default:
            echo redirect();

            break;
    }
?>