jQuery(document).ready(function ($) {
    //Y QUE HACE ESTO?
    //ESTO MIRA SI EL Q ESTA VIENDO LA PAGINA QUIERE FILTRAR, SI QUIERE FILTRAR LLAMA AL PHP PARA Q LE DE LOS DATOS NUEVOS, ESPERA LA RESPUESTA DE DE LA CONSULTA, RELLENA EL GRAFIKO CON ESOS DATOS
    //BASICAMENTE ES EL QUE SE ENCARGA DE MOSTRAR LOS DATOS

    //variables inicialess
    let chart = null;

    //okei ps vamos con el js, aqui cargamos lo q nos pide el usuario q esta viendo la pagina
    function cargarGrafico() {

        
        let fecha = $("#src_date").val();
        let hora  = $("#src_hour").val();

        //aqui hacemos la peticion al wordpress: 
        $.ajax({
            url: src_ajax.ajax_url,
            method: "POST",
            data: {
                action: "sr_get_chart", //aqui decimos ejecuta la funcion sr_get_data del php !!!
                //y le mandamos estos datos
                date: fecha,
                hour: hora
            },
            //(ahora vamos al index.php)
            //ola, volvi de index.php
            //y si lee bien el json q enviamos en el index.php lo convierte en un objeto usable para js
            success: function (resp) {

                let datos = JSON.parse(resp);
                //y si no ps muestra el error
                if (datos.error) {
                    alert("ERROR: " + datos.error);
                    return;
                }

                let rows = datos.rows;

                let labels = rows.map(r => r.data);
                let temps  = rows.map(r => Number(r.temperatura));
                let hums   = rows.map(r => Number(r.hezetasuna));

                let ctx = document.getElementById("src_canvas");

                // Si ya existe un gráfico lo borra
                if (chart !== null) chart.destroy();

                // grafikoa sortzen du
                chart = new Chart(ctx, {
                    type: "line",
                    data: {
                        labels: labels,
                        datasets: [
                            {
                                label: "Tenperatura (°C)",
                                data: temps,
                                borderWidth: 2,
                                borderColor: "red",
                                backgroundColor: "rgba(255,0,0,0.2)",
                                pointRadius: 3,
                                tension: 0.3
                            },
                            {
                                label: "Hezetasuna (%)",
                                data: hums,
                                borderWidth: 2,
                                borderColor: "blue",
                                backgroundColor: "rgba(0,0,255,0.2)",
                                pointRadius: 3,
                                tension: 0.3
                            }
                        ]
                    },
                    options: {
                        responsive: true,
                        interaction: {
                            intersect: false,
                            mode: "index"
                        },
                        scales: {
                            x: {
                                ticks: { maxRotation: 45, minRotation: 45 }
                            }
                        }
                    }
                });

            }
        });
    }

    // Botoiak (dependiendo de estos botones cambiara la informacion)
    $("#src_filtrar").click(cargarGrafico);
    //reseteo, devuelve los filtros a como estaban
    $("#src_reset").click(() => {
        $("#src_date").val("");
        $("#src_hour").val("");
        cargarGrafico();
    });

    //hace la funcion de cargar grafiko 
    cargarGrafico();
});
