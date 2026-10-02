window.addEventListener("load", () => {

    const splash = document.getElementById("splash-screen");

    setTimeout(() => {

        splash.classList.add("sumir");

        
        setTimeout(() => {
            splash.remove();
        }, 1200);

    }, 2000);

});