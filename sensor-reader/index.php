<?php
/**
 * Plugin Name: Sensor Reader
 * Author: UniPayo
 * Description: Taula orriekin sailkatuta, data filtroa eta errenkada kantitatea aukeratzeko.
 * Version: 1.2
 */

//Kodea azalduta por mi, (chatgpt me ayudo)

//QUE HACE ESTO?
//EN RESUMEN ESTE CODIGO HACE EL HTML, RECIBE LOS FILTROS QUE LE METEMOS, SE CONECTA CON LA BASE DE DATOS, HACE LA CONSULTA Y NOS TRAE EL RESULTADO DE LA CONSULTA
//BASICAMENTE ES EL QUE HABLA CON LA BASE DE DATOS

//hauxe dago por seguridad, nonor ahalegintzen bada artxibo hau wordpressetik zuzenean ejekutatzen bokleatu egingo du.
if (!defined('ABSPATH')) exit;


//Lehenik, CARGAR APP.JS que esta en la otra carpetita
function sr_cargar_scripts() {

    //kargamos el archivo js
    wp_enqueue_script(
        'sr-app',
        plugin_dir_url(__FILE__) . 'js/app.js', //js helbidea
        array('jquery'),    //script honek jquery beharko du
        false,              //ez du zehazten zein bertsio
        true                //orriaren amaieran kargatzen du </body> aurretik (mitik de poner el js al final)
    );

    //honek baimentzen du datuak PHP-tik JS-ra pasatzea 
    wp_localize_script('sr-app', 'sr_ajax', array(
        'ajax_url' => admin_url('admin-ajax.php')   //URL-a donde wordpress acepta las peticiones del ayax (sinmas es un archivo php q viene x defecto q esta en la carpeta wp-admin)
    ));
}
add_action('wp_enqueue_scripts', 'sr_cargar_scripts');


// Hemen , el shortcode [sensor_reader]
function sr_shortcode() {
    ob_start(); //aqui empezamos con el html pa ponerlo bonico
?>
    <!--nunca habia puesto comentarios asi en html lol -->
    <h2>Sentsoreen datuak</h2> 

    <!--Data filtroa egiteko -->
    <div style="margin-bottom:15px;">
        <label>Data hasiera:</label>
        <input type="date" id="sr_date_from">

        <label style="margin-left:10px;">Data amaiera:</label>
        <input type="date" id="sr_date_to">

    <!--Hauxe errankada kopurua ezartzeko, q si no ocupa toda la pantalla y me rayo ademas d q tarda un pokillo -->
        <label style="margin-left:10px;">Errenkadak:</label>
        <select id="sr_limit">
            <option value="5">5</option>
            <option value="10" selected>10</option>
            <option value="15">15</option>
            <option value="20">20</option>
        </select>

    <!--Botoiak -->    
        <button id="sr_filtrar" style="margin-left:10px;">Filtratu</button>
        <button id="sr_reset">Reset</button>
    </div>

    <!--Taula -->
    <table id="sr_tabla" border="1" style="margin-top:10px; border-collapse:collapse;">
        <thead>
            <tr>
                <th>ID</th>
                <th>Data</th>
                <th>Tenperatura</th>
                <th>Hezetasuna</th>
                <th>Soinua</th>
                <th>Detektatuta</th>
            </tr>
        </thead>
        <tbody></tbody>
    </table>

    <!--Orrien bitartez sailkatzeko -->
    <div id="sr_paginacion" style="margin-top:10px;">
        <button id="sr_anterior" disabled>← Aurrekoa</button>
        <span id="sr_pagina_actual">1</span>
        <button id="sr_siguiente">Hurrengoa →</button>
    </div>

    <?php
    return ob_get_clean(); //devuelve todo lo q escribimos como html para mostrarlo con el shortcode
}
add_shortcode('sensor_reader', 'sr_shortcode');


//ola, vengo del app.js, ejecutame la funcion de sr_get_data pls

add_action('wp_ajax_sr_get_data', 'sr_get_data'); //este pa users logeados en wp
add_action('wp_ajax_nopriv_sr_get_data', 'sr_get_data'); //y este pa no logeados en wp


function sr_get_data() {

    //estos son los datos q mandamos desde el js, es decir el filtro
    $pagina = isset($_POST['pagina']) ? intval($_POST['pagina']) : 1;   //dice la pagina actual de la taula
    $limit = isset($_POST['limit']) ? intval($_POST['limit']) : 10; //la cantidad de filas q pedimos
    $offset = ($pagina - 1) * $limit; //la decimos q empieze por la fila 1
    $df = isset($_POST['date_from']) ? $_POST['date_from'] : ''; //dataren filtroak     
    $dt = isset($_POST['date_to'])   ? $_POST['date_to']   : ''; //dataren filtroak

    //mariadb-rekin konexioa (de normal estaria en la personal-php, pero nerea me dijo q asi tambien esta bien)
    $db = new wpdb('adrian', 'Admin123', 'arduino_db', '192.168.71.202');
    //como estamos pasando los datos desde la compu d igor a la base de datos de adrian y de su db a wordpress estamos usando el remoto de mariadb

    if ($db->last_error) {
        echo json_encode(['error' => $db->last_error]); //por si falla la konexion
        wp_die();
    }

    // construimos el filtro (where)
    $where = " WHERE 1=1 "; //digamos q vamos a añadir el filtro con un AND sin romper la query (tipo de normal un AND lleva algo antes y ese algo es la condicion inicial q le ponemos al where, q en este caso sera q las horas q conforman 1 dia, de 00:00 a 23:59, sin mas q es fundamental para q funcione la query )
    if ($df) $where .= $db->prepare(" AND data >= %s", $df . " 00:00:00");
    if ($dt) $where .= $db->prepare(" AND data <= %s", $dt . " 23:59:59");

    // esto cuenta cuantos datos si cumplen la condicion de antes del where (para q? ps para calcular el total de paginas q necesitara las taula en donde estaran los datos, estaba pensando usar eso para tambien poner como un contador d cuantos registros cumplen el where, capaz lo hago si no ni caso a esto xd)
    $total = $db->get_var("SELECT COUNT(*) FROM Datuak $where");

    // pedir los datos al mariadb
    $rows = $db->get_results("
        SELECT id, data, temperatura, hezetasuna, soinua, detektatuta
        FROM Datuak
        $where
        ORDER BY data DESC
        LIMIT $limit OFFSET $offset
    ", ARRAY_A);

    //respuesta de la query hicimos en json
    echo json_encode([
        'rows' => $rows,
        'pagina' => $pagina,
        'total_paginas' => ceil($total / $limit)
    ]);

    wp_die(); //y acabamos ajax
}
//(vale de aqui debemos ir d vuelta al app.js)