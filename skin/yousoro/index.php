<?php
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
?>