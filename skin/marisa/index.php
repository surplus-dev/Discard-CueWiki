<?php
    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
    error_reporting(E_ALL & ~E_NOTICE);

    function skin_render($head, $body, $tool, $other) {
        $tool_html = "";
        foreach($tool as &$tool_data) {
            $tool_html = $tool_html."<a class=\"menu-item\" href=\"".$tool_data[1]."\">".$tool_data[0]."</a> | ";
        }

        $tool_html = preg_replace("/\| $/", "", $tool_html);

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
            $title2 = "<span class=\"change_space\">".$other["title"]."</span>";

            if($other["sub"] !== "") {
                $title1 = $title1." (".$other["sub"].")";
                $title2 = $title2." (".$other["sub"].")";
            }
        }

        $main_skin = "
            <!DOCTYPE html>
            <html>
                <head>
                    <meta charset=\"utf-8\">
                    <title>".$title1."</title>
                    <link rel=\"stylesheet\" href=\"".file_fix("/skin/marisa/css/main.css?ver=1")."\">
                    <script src=\"".file_fix("/skin/marisa/js/skin_set.js?ver=1")."\"></script>
                    <script src=\"".file_fix("/skin/marisa/js/main.js?ver=1")."\"></script>
                    <script>window.onload = function () { skin_set(); }</script>
                    <script src=\"https://code.iconify.design/1/1.0.3/iconify.min.js\"></script>
                    ".$head."
                </head>
                <body>
                    <div id=\"background\">
                        <div id=\"top\">
                            <div id=\"top_main\">
                                <div id=\"logo\">
                                    <a href=\"".$_SERVER['PHP_SELF']."\">".load_lang("main")."</a>
                                </div>
                                <div id=\"top_tool\">
                                    <div id=\"top_menu_groups\">
                                        <div id=\"top_tool_cel\">
                                            <a href=\"?action=r_change\">
                                                <span class=\"iconify\" data-icon=\"ic:baseline-autorenew\" data-inline=\"true\"></span>
                                                ".load_lang("recent_change")."
                                            </a>
                                        </div>
                                         
                                        <div id=\"top_tool_cel\">
                                            <a href=\"?action=o_tool\">
                                                <span class=\"iconify\" data-icon=\"ic:baseline-build\" data-inline=\"true\"></span>
                                                ".load_lang("other_tool")."
                                            </a>
                                        </div>
                                         
                                        <div id=\"top_tool_cel\">
                                            <a href=\"?action=u_menu\">
                                                <span class=\"iconify\" data-icon=\"ic:baseline-person\" data-inline=\"true\"></span>
                                                <span class=\"not_mobile\">".load_lang("users_menu")."</span>
                                            </a>
                                        </div>
                                    </div>
                                     
                                    <form id=\"search\" role=\"search\">
                                        <input id=\"search_input\" name=\"search\" onclick=\"view_search();\" placeholder=\"Search\" autocomplete=\"off\" type=\"search\">
                                        |
                                        <button>
                                            <span class=\"iconify\" data-icon=\"ic:round-find-in-page\" data-inline=\"true\"></span>
                                        </button>
                                        |
                                        <button>
                                            <span class=\"iconify\" data-icon=\"ic:baseline-search\" data-inline=\"true\"></span>
                                        </button>
                                        <div id=\"pre_search\" style=\"display: none;\"></div>
                                    </form>
                                </div>
                            </div>
                        </div>
                        <div id=\"main\">
                            <div id=\"main_top\">
                                <div id=\"tool\" class=\"not_mobile\">
                                    <div id=\"tool_cel\">
                                        ".$tool_html."
                                    </div>
                                </div>
                                <h1 id=\"title\">
                                    ".$title2."
                                </h1>
                                <div id=\"tool\" class=\"is_mobile\">
                                    <div id=\"tool_cel\">
                                        ".$tool_html."
                                    </div>
                                </div>
                            </div>
                            <div id=\"main_data\">
                                ".$body."
                            </div>
                        </div>
                    </div>
                    <div id=\"bottom\">
                        <div id=\"bottom_main\">
                            <a href=\"https://github.com/Make-openNAMU/PHP-CueWiki\"><i class=\"fab fa-github\"></i> CueWiki</a>
                        </div>
                    </div>
                    <div id=\"nav_bar\">
                        <div id=\"go_top\">
                            <a href=\"#top\">
                                <span class=\"iconify\" data-icon=\"ic:baseline-arrow-upward\" data-inline=\"true\"></span>
                            </a>                  
                        </div>
                        <div id=\"go_bottom\">
                            <a href=\"#bottom\">
                                <span class=\"iconify\" data-icon=\"ic:baseline-arrow-downward\" data-inline=\"true\"></span>
                            </a>
                        </div>
                        <div id=\"go_toc\">
                            <a href=\"#toc\">
                                <span class=\"iconify\" data-icon=\"ic:baseline-list\" data-inline=\"true\"></span>
                            </a>
                        </div>                                    
                    </div>
                </body>
            </html>   
        ";

        return $main_skin;
    }
?>