<?php
try {
    $imagick = new Imagick();
    echo 'Imagick is working!';
} catch (Exception $e) {
    echo 'Error: ' . $e->getMessage();
}
?>