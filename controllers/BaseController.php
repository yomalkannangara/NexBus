<?php
abstract class BaseController {
    protected function view(string $module, string $name, array $data = []) {
        extract($data);
        $page = $name;
        include __DIR__ . '/../views/shared/header.php';
        include __DIR__ . '/../views/'.$module.'/'.$name.'.php';
        include __DIR__ . '/../views/shared/footer.php';
    }
    protected function redirect(string $url) { header('Location: '.$url); exit; }
}
?>