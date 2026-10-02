<?php
$strcon = mysqli_connect('localhost:3306', 'root', '', 'museu_art');

if (!$strcon) { 
        die('Erro na conexão: ' . mysqli_connect_error());
}

mysqli_set_charset($strcon, "utf8");

//** computador gio:localhost:3306  */
//** computador escola:localhost:3307 */
