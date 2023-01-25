<?php
namespace App\Tools;

class Debug
{
    public static function prettyPrint($data)
    {

        ?>
        <pre
            style="
            max-height: 500px;
            overflow-y: auto;
            font-size: 14px;
            max-width: 700px;
            padding: 10px;
            overflow-x: auto;
            font-family: Consolas, monospace;
            background: lightgoldenrodyellow;
            "
        ><?=htmlspecialchars(print_r($data, true))?></pre>
        <?php
    }
}