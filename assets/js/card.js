document.addEventListener("DOMContentLoaded", () => {

    const contadores = document.querySelectorAll(".contador");

    contadores.forEach(contador => {

        const alvo = parseInt(contador.dataset.target);
        const duracao = 2500; 
        let inicio = null;

        function animar(timestamp) {

            if (!inicio) inicio = timestamp;

            const progresso = Math.min((timestamp - inicio) / duracao, 1);

            contador.textContent = Math.floor(progresso * alvo);

            if (progresso < 1) {
                requestAnimationFrame(animar);
            } else {
                contador.textContent = alvo;
            }
        }

        requestAnimationFrame(animar);

    });

});