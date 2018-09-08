<?php
  $login = $_POST['login'];
  $pass = $_POST['pass'];
  if (($login == "Admin") && ($pass == "AdminPass"))
    echo "Привет, Admin!";
  else echo "Доступ закрыт";
?>