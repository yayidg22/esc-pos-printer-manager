<?php
// Archivos y directorios que quieres incluir en el archivo Phar
/* $files = [
    'index.php',
    'print_example.php',
    'print.php',
    'printers.php',
    'vendor',
]; */

// Nombre del archivo Phar
$pharFile = 'miaplicacion.phar';

// Crear el archivo Phar
$phar = new Phar($pharFile);

$phar->buildFromDirectory(__DIR__ . '/');

/* 
// Agregar archivos al Phar
foreach ($files as $file) {
    if (is_dir($file)) {
        $phar->buildFromDirectory($file);
    } else {
        $phar->addFile($file);
    }
}
 */
// Punto de entrada de la aplicación en el Phar
$phar->setStub($phar->createDefaultStub('index.php'));

echo "Archivo Phar creado: $pharFile\n";