<?php
    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
    error_reporting(E_ALL & ~E_NOTICE);

    function skin_render($head, $body, $tool, $other) {
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

        $title1 = "";
        $title2 = "";
        if($other["title"] !== "") {
            $title1 = $other["title"];
            $title2 = "<span id=\"main_title\">".$other["title"]."</span>";

            if($other["sub"] !== "") {
                $title1 = $title1." (".$other["sub"].")";
                $title2 = $title2." (".$other["sub"].")";
            }
        }
        
        $main_skin = "
            <html>
                <head>
                    <title>".$title1."</title>
                    <link rel=\"stylesheet\" href=\"".file_fix("/skin/yousoro/css/main.css")."\">
                    <meta name=\"viewport\" content=\"width=device-width, initial-scale=1\">
                    ".$head."
                </head>
                <body>
                    <div id=\"top\">
                        <a href=\"".url_fix()."\">".load_lang("main")."</a>
                        <a href=\"?action=r_change\">".load_lang("recent_changes")."</a>
                        <a href=\"?action=o_tool\">".load_lang("other_tool")."</a>
                        <a href=\"?action=u_menu\">".load_lang("users_menu")."</a>
                    </div>
                    <div id=\"middle\">
                        <div id=\"title\">
                            ".$title2."
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
                        <br>
                        <br>
                        <div id=\"bottom\">
                            <a href=\"https://github.com/Make-openNAMU/PHP-openNAMU_Lite\">openNAMU_Lite</a>
                        </div>
                    </div>
                </body>
            </html>
        ";

        return $main_skin;
    }
?>