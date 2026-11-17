<?php
declare(strict_types=1);

// Carrega automaticamente qualquer classe cujo arquivo tenha o mesmo nome dela.
spl_autoload_register(function (string $classe): void {
    foreach (['core', 'models', 'dao', 'services'] as $pasta) {
        $arquivo = __DIR__ . "/src/$pasta/$classe.php";
        if (is_file($arquivo)) {
            require_once $arquivo;
            return;
        }
    }
});
