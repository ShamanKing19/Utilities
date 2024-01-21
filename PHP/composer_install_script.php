<?php
$script = "export COMPOSER_HOME=\"$_SERVER[DOCUMENT_ROOT]/local\"; curl -sS https://getcomposer.org/installer | php";
$res = shell_exec($script);