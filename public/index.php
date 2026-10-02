<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.min.css">
             <link rel="stylesheet" href="../assets/css/design/pop-up.css">   
            <link rel="stylesheet" href="../assets/css/pages/login.css">
                <link rel="stylesheet" href="../assets/css/design/splash.css">
                  <link rel="stylesheet" href="../assets/css/design/toast.css">   
              
                <title>MuseuArt</title>

                     <link rel="shortcut icon" type="imagex/png" href="../assets/img/ico/icomenu.ico">

</head>
<body class = pagina-index>
   <div id="splash-screen">
        <img src="../assets/img/SplashMuseuArt.svg" alt="Museu Art Logo" class="splash-logo">
    </div>
 <div id="toast" class="toast">

    <i id="toastIcon" class="bi"></i>

    <div class="toast-texto">

        <h4 id="toastTitulo"></h4>

        <p id="toastMensagem"></p>

    </div>

    <div class="toast-barra"></div>

</div>


   <main class="login">

        <section class="esq">
        <?php $imagens = glob("../assets/img/obras/*.{jpg,jpeg,png,webp}", GLOB_BRACE);?>
         
        <div class="carousel">
        
        <?php foreach($imagens as $i => $img): ?>

            <div class="slide <?= $i == 0 ? 'active' : '' ?>">
                <img src="<?= $img ?>" alt="Obra de Arte">
            </div>

        <?php endforeach; ?>

        <div class="overlay">

            <div class="texto">

                <h2>
                    A arte existe para que a realidade
                    não nos destrua.
                </h2>

                <p>— Friedrich Nietzsche</p>

            </div>

            <div class="indicadores">
                <?php foreach($imagens as $i => $img): ?>
                    <span class="<?= $i == 0 ? 'ativo' : '' ?>"></span>
                <?php endforeach; ?>
            </div>

        </div>

    </div>
        </section>
        

         <section class="dir">
            <div id="imglogo">
                 <img src="../assets/img/logomenuart.svg" alt="Museu Art">
         </div>

                 <form id = formLogin  action="../backend/API/Login.php" method="POST" autocomplete="off">
                    <div class= titulo> 
                     <h1> Seja 
                        <span> bem-vindo! </span>
                    </h1>
                <h2>Acesse o sistema administrativo do Museu </h2>
</div> 

        <div class="campo">
                <label for="ID-con">Código de Identificação</label>
                 <div class="input-people">
                    <input type="text" id="ID" name="ID" placeholder="Digite seu ID">
                    <i class="bi bi-person" id="iconpeople"></i>
</div>
        </div>


        <div class="campo">
                <label for ="senha">Senha</label>
                    <div class="input-senha">
                     <i class="bi bi-lock" id="iconsenha"></i>

                        <input type="password" id="senha" name="senha" placeholder="Digite sua senha">
                            <i class="bi bi-eye" id="btn-senha" onclick="mostrarSenha()"></i>  
        </div>
</div>

        <div class="opcoes">
                <p class="esqueceusenha">
                     <a href="javascript:void(0);" id="esqueciSenha">
                        Esqueceu sua senha?</a>
                </p>
            </div>
        <button type="submit" class="botao-entrar"> Entrar
</button>
            
</form>
                  



</section>

<?php if(isset($_GET["erro"])): ?>

<script>

document.addEventListener("DOMContentLoaded", function() {

    <?php switch($_GET["erro"]):

        case "campos":
    ?>

        mostrarToast(
            "aviso",
            "Campos obrigatórios",
            "Preencha todos os campos."
        );

    <?php break; ?>


        <?php case "login": ?>

        mostrarToast(
            "erro",
            "Login não encontrado",
            "Este login não está cadastrado."
        );

    <?php break; ?>


        <?php case "senha": ?>

        mostrarToast(
            "erro",
            "Senha incorreta",
            "A senha informada está incorreta."
        );

    <?php break; ?>


        <?php case "tipo": ?>

        mostrarToast(
            "erro",
            "Tipo inválido",
            "Tipo de usuário inválido."
        );

    <?php break; ?>

    <?php endswitch; ?>

});

</script>

<?php endif; ?>

</main>
    <?php include "../components/popup-recuperar.php"; ?>
    <script src="../assets/js/popup.js" ></script>
     <script src="../assets/js/splash.js" ></script>
      <script src="../assets/js/login.js"></script>
      <script src="../assets/js/toast.js"></script>
</body>
</html>