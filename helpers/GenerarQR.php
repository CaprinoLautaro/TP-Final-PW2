<?php
require dirname(__DIR__) . '/vendor/phpqrcode/phpqrcode/qrlib.php';
class GenerarQR
{
    public static function generador($id) {
        $dir = 'public/perfilQR/';

        if (!file_exists($dir))
            mkdir($dir, 0777, true);

        $rutaQR = $dir . uniqid() . '.png';
        $url = 'controller=perfil&method=verPerfil&id=' . $id;

        QRcode::png($url, $rutaQR, 'M', 8, 2);

        return $rutaQR;
    }
}
