
const canvas = document.getElementById('graficoCategorias');

if (canvas) {

    const labels = window.dashboardCategorias || [];
    const valores = window.dashboardValoresCategorias || [];

    const cores = [
        '#7e56da',  
        '#54b4da',  
        '#da5497',  
        '#1cc96a',  
        '#f59e0b',  
        '#833d0e',  
        '#d12e2e'   
    ];

    if (labels.length > 0 && valores.length > 0) {

        const coresTransparentes = cores.map(
            () => 'rgba(255, 255, 255, 0.02)'
        );


        const chart = new Chart(canvas, {

            type: 'doughnut',

            data: {

                labels: labels,

                datasets: [{

                    data: valores,

                    backgroundColor:
                        coresTransparentes.slice(
                            0,
                            valores.length
                        ),

                    borderColor: '#1d1d1d',

                    borderWidth: 3,

                    spacing: 2,

                    offset: 4,

                    hoverBorderColor: '#ffffff',

                    hoverBorderWidth: 2,

                    hoverOffset: 8
                }]
            },


            options: {

                responsive: true,

                maintainAspectRatio: true,

                cutout: '68%',

                animation: {

                    animateRotate: false,

                    animateScale: false,

                    duration: 100
                },

                plugins: {

                    legend: {
                        display: false
                    },

                    tooltip: {

                        backgroundColor:
                            'rgba(29, 29, 29, 0.95)',

                        titleColor: '#ffffff',

                        bodyColor: '#b7bdc9',

                        borderColor:
                            'rgba(255, 255, 255, 0.05)',

                        borderWidth: 1,

                        padding: 12,

                        cornerRadius: 10,


                        titleFont: {

                            size: 13,

                            weight: '600'
                        },


                        bodyFont: {

                            size: 12
                        },


                        callbacks: {

                            label: function(context) {

                                const total =
                                    context.dataset.data.reduce(
                                        (a, b) => a + b,
                                        0
                                    );


                                const percentage =
                                    (
                                        (context.parsed / total) *
                                        100
                                    ).toFixed(1);


                                return `${context.parsed} itens (${percentage}%)`;
                            }
                        }
                    }
                }
            },


            
            
            

            plugins: [{

                id: 'centerText',

                beforeDraw: function(chart) {

                    const {
                        width,
                        height,
                        ctx
                    } = chart;


                    ctx.save();


                    
                    
                    

                    const total =
                        chart.data.datasets[0].data.reduce(
                            (a, b) => a + b,
                            0
                        );


                    const centerX = width / 2;

                    const centerY = height / 2;


                    
                    
                    

                    ctx.shadowColor =
                        'rgba(139, 92, 246, 0.15)';

                    ctx.shadowBlur = 20;


                    
                    
                    

                    ctx.font =
                        'bold 28px Inter, sans-serif';

                    ctx.fillStyle = '#ffffff';

                    ctx.textAlign = 'center';

                    ctx.textBaseline = 'middle';


                    ctx.fillText(
                        total,
                        centerX,
                        centerY - 6
                    );


                    
                    
                    

                    ctx.shadowBlur = 0;

                    ctx.font =
                        '11px Inter, sans-serif';

                    ctx.fillStyle = '#9ca3af';


                    ctx.fillText(
                        'Itens',
                        centerX,
                        centerY + 22
                    );


                    ctx.restore();
                }
            }]
        });


        
        
        
        

        let indexAtual = 0;


        function aparecerProximaFatia() {

            if (indexAtual < valores.length) {

                const coresAtuais =
                    chart.data.datasets[0]
                        .backgroundColor;


                
                coresAtuais[indexAtual] =
                    cores[indexAtual];


                
                
                

                chart.update();


                indexAtual++;


                setTimeout(
                    aparecerProximaFatia,
                    300
                );
            }
        }


        

        setTimeout(
            aparecerProximaFatia,
            500
        );


        
        
        

        document.addEventListener(
            'DOMContentLoaded',
            function() {

                const legendItems =
                    document.querySelectorAll(
                        '.legenda-grafico div'
                    );


                if (!legendItems.length) {
                    return;
                }


                
                
                

                const fatiasVisiveis =
                    new Array(valores.length)
                        .fill(false);


                
                
                

                const observer = setInterval(
                    function() {

                        const coresAtuais =
                            chart.data.datasets[0]
                                .backgroundColor;


                        coresAtuais.forEach(
                            function(cor, i) {

                                if (
                                    cor !==
                                    'rgba(255, 255, 255, 0.02)'
                                ) {

                                    fatiasVisiveis[i] =
                                        true;
                                }
                            }
                        );

                    },
                    100
                );


                
                
                

                legendItems.forEach(
                    function(item, index) {


                        
                        
                        

                        item.addEventListener(
                            'mouseenter',
                            function() {


                                
                                if (!fatiasVisiveis[index]) {
                                    return;
                                }


                                const meta =
                                    chart.getDatasetMeta(0);


                                if (
                                    !meta.data ||
                                    !meta.data[index]
                                ) {
                                    return;
                                }


                                
                                
                                

                                meta.data.forEach(
                                    function(arc, i) {

                                        if (
                                            i === index &&
                                            fatiasVisiveis[i]
                                        ) {

                                            
                                            arc.options.borderColor =
                                                '#ffffff';

                                            arc.options.borderWidth =
                                                2;

                                            arc.options.offset =
                                                12;

                                        } else if (
                                            fatiasVisiveis[i]
                                        ) {

                                            
                                            arc.options.borderColor =
                                                '#1d1d1d';

                                            arc.options.borderWidth =
                                                3;

                                            arc.options.offset =
                                                4;
                                        }
                                    }
                                );


                                
                                
                                
                                

                                chart.draw();

                            }
                        );


                        
                        
                        

                        item.addEventListener(
                            'mouseleave',
                            function() {

                                const meta =
                                    chart.getDatasetMeta(0);


                                if (!meta.data) {
                                    return;
                                }


                                
                                
                                

                                meta.data.forEach(
                                    function(arc, i) {

                                        if (
                                            fatiasVisiveis[i]
                                        ) {

                                            arc.options.borderColor =
                                                '#1d1d1d';

                                            arc.options.borderWidth =
                                                3;

                                            arc.options.offset =
                                                4;
                                        }
                                    }
                                );


                                
                                
                                

                                chart.draw();

                            }
                        );

                    }
                );


                
                
                

                window.addEventListener(
                    'beforeunload',
                    function() {

                        clearInterval(observer);

                    }
                );

            }
        );

    }
}

function atualizarCabecalhoDashboard() {

    const agora = new Date();

    const hora = agora.getHours();

    let saudacao = '';

    if (hora >= 5 && hora < 12) {

        saudacao = 'Bom dia';

    } else if (hora >= 12 && hora < 18) {

        saudacao = 'Boa tarde';

    } else {

        saudacao = 'Boa noite';

    }


    const elementoSaudacao = document.getElementById('saudacao');

    const elementoData = document.getElementById('data-atual');

    const elementoHora = document.getElementById('hora-atual');

    const elementoAtualizacao =
        document.getElementById('ultima-atualizacao');


    if (elementoSaudacao) {

        elementoSaudacao.textContent = saudacao;

    }


    if (elementoData) {

        elementoData.textContent =
            agora.toLocaleDateString('pt-BR');

    }


    if (elementoHora) {

        elementoHora.textContent =
            agora.toLocaleTimeString('pt-BR');

    }


    if (elementoAtualizacao) {

        elementoAtualizacao.textContent =
            'Atualizado às ' +
            agora.toLocaleTimeString('pt-BR');

    }

}


atualizarCabecalhoDashboard();

setInterval(atualizarCabecalhoDashboard, 1000);