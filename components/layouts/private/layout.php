<!DOCTYPE html>
<html lang="pt-BR">


<head>


    <meta charset="UTF-8">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.min.css">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $tituloPagina ?></title>
    <link rel="stylesheet" href="/SistemaMuseuArt.GuardaBem/assets/css/design/layout.css">
    <link rel="stylesheet" href="/SistemaMuseuArt.GuardaBem/assets/css/design/sidebar.css">
    <link rel="stylesheet" href="/SistemaMuseuArt.GuardaBem/assets/css/design/header.css">
    <link rel="stylesheet" href="/SistemaMuseuArt.GuardaBem/assets/css/design/card.css">
    <link rel="stylesheet" href="/SistemaMuseuArt.GuardaBem/assets/css/design/preview.css">
    <link rel="stylesheet" href="/SistemaMuseuArt.GuardaBem/assets/css/design/list.css">
    <link rel="stylesheet" href="/SistemaMuseuArt.GuardaBem/assets/css/design/toast.css">
     <link rel="shortcut icon" type="imagex/png" href="/SistemaMuseuArt.GuardaBem/assets/img/ico/icomenu.ico">

    <link rel="stylesheet" href="<?= $cssPagina ?>">
</head>


<body>
    <div class="layout">
        <?php require_once 'sidebar.php'; ?>
        <?php require_once 'header.php'; ?>


        <main class="pagina">
            <?php include $pagina; ?>
        </main>
    </div>


    <?php include __DIR__ . "/../../toast.php"; ?>


    <script>
             const itens = <?= json_encode($itens, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?>;
    </script>


    <script src="/SistemaMuseuArt.GuardaBem/assets/js/sidebar.js" defer></script>
    <script src="/SistemaMuseuArt.GuardaBem/assets/js/header.js" defer></script>
    <script src="/SistemaMuseuArt.GuardaBem/assets/js/toast.js" defer></script>
    <script src="../../../assets/js/desativar-autocomplete.js" defer></script>


    <?php if (!empty($scriptPagina)): ?>
        <script src="<?= $scriptPagina ?>"></script>
    <?php endif; ?>


    <?php if (!empty($toast)): ?>
        <script>
            document.addEventListener("DOMContentLoaded", function () {
                mostrarToast(
                    <?= json_encode($toast['tipo']) ?>,
                    <?= json_encode($toast['titulo']) ?>,
                    <?= json_encode($toast['mensagem']) ?>
                );
            });
        </script>
    <?php endif; ?>
</body>


</html>


        
        

