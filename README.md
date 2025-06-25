# reverse_shell_php

Este es un script básico en PHP que crea una reverse shell, una conexión remota que permite controlar una computadora desde otra máquina a través de la red.

¿Qué hace este script?
Abre una conexión desde la máquina víctima hacia una IP y puerto específicos (el atacante).

Ejecuta una shell (una consola de comandos) en la máquina víctima.

Envía y recibe comandos y sus resultados a través de esa conexión, permitiendo controlar el sistema de forma remota.

¿Cómo funciona?
El script se ejecuta en la máquina víctima y trata de conectar con el atacante (IP y puerto que tú configures).

Cuando la conexión se establece, ejecuta comandos en la víctima y envía los resultados de vuelta.

El atacante puede enviar comandos a través de esta conexión y obtener respuestas, como si estuviera usando una consola directamente en la máquina víctima.

Características técnicas
El script intenta correr en segundo plano para no interrumpir otros procesos.

Utiliza funciones para manejar la entrada y salida de datos sin bloquearse.

Usa un bucle infinito para mantener la conexión abierta y escuchar comandos continuamente.
