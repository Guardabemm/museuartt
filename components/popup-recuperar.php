<div class="overlay-popup" id="overlay">


    <div class="tela-popup" id="popupRecuperar">

        <div class="mng">

            <button 
                class="fechar" 
                type="button"
                onclick="fecharPopup()">

                <i class="bi bi-x-lg"></i>

            </button>


            <div class="iconeconf">

                <i class="bi bi-envelope"></i>

            </div>


            <h2>Recuperar senha</h2>


            <p class="info">
                Informe seu e-mail para receber o link de recuperação.
            </p>


            <form id="formRecuperacao">


                <div class="campo-email">

                    <input
                        type="email"
                        id="emailRecuperacao"
                        name="email"
                        placeholder="Digite seu e-mail"
                        required>

                </div>


                <button
                    type="submit"
                    class="btn-fechar-recup"
                    id="btnEnviarRecuperacao">

                    Enviar e-mail

                </button>


            </form>


        </div>

    </div>




    <div class="tela-popup" id="loader">

        <div class="mng">


            <div class="loader"></div>


            <h3>Enviando e-mail...</h3>


            <p class="info">
                Aguarde alguns instantes.
            </p>


        </div>


    </div>



    <div class="tela-popup" id="popupSucesso">

        <div class="mng">


            <button 
                class="fechar"
                type="button"
                onclick="fecharPopup()">

                <i class="bi bi-x-lg"></i>

            </button>



            <div class="iconeconf">

                <i class="bi bi-check-circle"></i>

            </div>



            <h2>E-mail enviado!</h2>



            <p class="info">
                Enviamos um link de recuperação para o seu e-mail cadastrado.
            </p>




            <div class="aviso">


                <i class="bi bi-envelope"></i>


                <div class="texto-aviso">


                    <strong>
                        Não encontrou o e-mail?
                    </strong>


                    <p>
                        Verifique sua caixa de spam.
                    </p>


                </div>


            </div>




            <button
                type="button"
                class="btn-fechar"
                onclick="fecharPopup()">

                Voltar ao login

            </button>




            <div class="divisor">

                <span>ou</span>

            </div>




            <button
                type="button"
                class="reenviar"
                id="btnReenviar">

                Reenviar e-mail

            </button>



        </div>


    </div>



    <div class="tela-popup" id="popupErro">


        <div class="mng">


            <button
                type="button"
                class="fechar"
                onclick="fecharPopup()">

                <i class="bi bi-x-lg"></i>

            </button>




            <div class="iconeconf">

                <i class="bi bi-x-circle"></i>

            </div>




            <h2>Falha ao enviar!</h2>




            <p class="info">

                Não foi possível enviar o e-mail de recuperação.

            </p>





            <div class="aviso">


                <i class="bi bi-exclamation-triangle"></i>


                <div class="texto-aviso">


                    <strong>
                        O que fazer?
                    </strong>


                    <p>
                        Confira o e-mail informado ou tente novamente em alguns instantes.
                    </p>


                </div>


            </div>





            <button
                type="button"
                class="btn-fechar"
                id="btnTentarNovamente">


                Tentar novamente


            </button>





            <div class="divisor">

                <span>ou</span>

            </div>





            <button
                type="button"
                class="reenviar"
                onclick="fecharPopup()">

                Voltar ao login

            </button>




        </div>


    </div>



</div>