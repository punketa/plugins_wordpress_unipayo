jQuery(document).ready(function ($) {
    //Y QUE HACE ESTO?
    //ESTO MIRA SI EL Q ESTA VIENDO LA PAGINA QUIERE FILTRAR, SI QUIERE FILTRAR LLAMA AL PHP PARA Q LE DE LOS DATOS NUEVOS, ESPERA LA RESPUESTA DE DE LA CONSULTA, RELLENA EL HTML CON ESOS DATOS
    //BASICAMENTE ES EL QUE SE ENCARGA DE MOSTRAR LOS DATOS

    //variables inicialess
    let pagina = 1; //es la pagina actual
    let totalPaginas = 1; //es el total de paginas q devolveremos

    //okei ps vamos con el js, aqui cargamos lo q nos pide el usuario q esta viendo la pagina
    function cargar() {

        let limit = $("#sr_limit").val(); //las  filas q mostraremos (coge la opcion q escogemos entre 5, 10, 15, 20)
        let df = $("#sr_date_from").val();  //hasierako data (coge la id del input)
        let dt = $("#sr_date_to").val();    //amaierako data (coge la id del input)

        //aqui hacemos la peticion al wordpress: 
        $.ajax({
            url: sr_ajax.ajax_url, //
            method: "POST",
            data: {
                action: "sr_get_data", //aqui decimos ejecuta la funcion sr_get_data del php !!!
               //y le mandamos estos datos
                pagina: pagina,
                limit: limit,
                date_from: df,
                date_to: dt
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

                let tbody = $("#sr_tabla tbody");
                tbody.empty(); //esto vacia la tabla

                //y aqui inserta los datos recibos de la base de datos y los inserta en cada fila
                datos.rows.forEach(fila => {
                    tbody.append(`
                        <tr>
                            <td>${fila.id}</td>
                            <td>${fila.data}</td>
                            <td>${fila.temperatura}</td>
                            <td>${fila.hezetasuna}</td>
                            <td>${fila.soinua}</td>
                            <td>${fila.detektatuta}</td>
                        </tr>
                    `);
                });

                //actualiza las paginas
                totalPaginas = datos.total_paginas; 

                $("#sr_pagina_actual").text(pagina);
                $("#sr_anterior").prop("disabled", pagina <= 1);
                $("#sr_siguiente").prop("disabled", pagina >= totalPaginas);
            }
        });
    }


    // Botoiak (dependiendo de estos botones cambiara la informacion)

    //si estas en la pagina 2 y queieres volver atras ps resta 1
    $("#sr_anterior").click(function () {
        if (pagina > 1) {
            pagina--;
            cargar(); //hace la funcion q esta arriba
        }
    });

    //si estas en la pagina 1 y queieres ir al siguiente ps suma 1
    $("#sr_siguiente").click(function () {
        if (pagina < totalPaginas) {
            pagina++;
            cargar(); //hace la funcion q esta arriba
        }
    });

    //al filtrar t lleva a la pagina 1 (es logico q t lleve ahi)
    $("#sr_filtrar").click(function () {
        pagina = 1;
        cargar(); //hace la funcion q esta arriba
    });

    //reseteo, devuelve los filtros a como estaban y t lleva a la pagina 1
    $("#sr_reset").click(function () {
        $("#sr_date_from").val("");
        $("#sr_date_to").val("");
        pagina = 1;
        cargar(); //hace la funcion q esta arriba
    });

    //cuando cambias la cantidad de filas, ps te lleva de vuelta a la pagina 1
    $("#sr_limit").change(function () {
        pagina = 1;
        cargar(); //hace la funcion q esta arriba
    });

    //hace la funcion q esta arriba 
    cargar();
});
