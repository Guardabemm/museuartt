
const dropdowns = document.querySelectorAll(".dropdown-crud");

dropdowns.forEach(dropdown => {

    const botao = dropdown.querySelector(".btn-crud");

    botao.addEventListener("click", function (e) {

        e.preventDefault();
        e.stopPropagation();

        dropdowns.forEach(item => {

            if(item !== dropdown){
                item.classList.remove("ativo");
            }

        });

        dropdown.classList.toggle("ativo");

    });

});

document.addEventListener("click", function(){

    dropdowns.forEach(dropdown => {

        dropdown.classList.remove("ativo");

    });

});

document.querySelectorAll(".crud-menu").forEach(menu => {

    menu.addEventListener("click", function(e){

        e.stopPropagation();

    });

});

