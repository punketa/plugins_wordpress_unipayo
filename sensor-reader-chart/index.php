<?php
/**
 * Plugin Name: Sensor Reader Chart
 * Author: UniPayo
 * Description: Tenperatura eta hezetasunaren grafikoa, eguna eta orduaren filtroarekin.
 */

//Kodea azalduta por mi, (chatgpt me ayudo)

//QUE HACE ESTO?
//EN RESUMEN ESTE CODIGO HACE EL HTML, RECIBE LOS FILTROS QUE LE METEMOS, SE CONECTA CON LA BASE DE DATOS, HACE LA CONSULTA Y NOS TRAE EL RESULTADO DE LA CONSULTA
//BASICAMENTE ES EL QUE HABLA CON LA BASE DE DATOS

if (!defined('ABSPATH')) exit;

//Hay muchas cosas q son igual al anterior, ns si voy a comentar todo, a lo mejor lo distinto

//Lehenik, CARGAR APP.JS que esta en la otra carpetita
function src_cargar_scripts() {

    // cargar Chart.js desde la libreria
    wp_enqueue_script(
        'src-chartjs',
        'https://cdn.jsdelivr.net/npm/chart.js',
        array(),
        false,
        true
    );

    // kargamos el archivo js
    wp_enqueue_script(
        'src-app',
        plugin_dir_url(__FILE__) . 'js/app.js',
        array('jquery', 'src-chartjs'),
        false,
        true
    );

    //honek baimentzen du datuak PHP-tik JS-ra pasatzea
    wp_localize_script('src-app', 'src_ajax', array(
        'ajax_url' => admin_url('admin-ajax.php')
    ));
}
add_action('wp_enqueue_scripts', 'src_cargar_scripts');


// Shortcode [sensor_reader_chart]
function src_shortcode() {
    ob_start(); //aqui empezamos con el html pa ponerlo bonico
    ?>

    <h2>Sentsoreen Grafikoa</h2>

    <div style="margin-bottom:15px;">
        <label>Data:</label>
        <input type="date" id="src_date">

        <label style="margin-left:10px;">Ordua:</label>
        <select id="src_hour">
            <option value="">-- Denak --</option>
            <?php for ($h = 0; $h < 24; $h++): ?>
                <option value="<?php echo $h; ?>">
                    <?php echo sprintf("%02d", $h); ?>:00
                </option>
            <?php endfor; ?>
        </select>

        <button id="src_filtrar" style="margin-left:10px;">Filtratu</button>
        <button id="src_reset">Reset</button>
    </div>

    <canvas id="src_canvas" height="100"></canvas>

    <?php
    return ob_get_clean(); //devuelve todo lo q escribimos como html para mostrarlo con el shortcode
}
add_shortcode('sensor_reader_chart', 'src_shortcode');


//ola, vengo del app.js, ejecutame la funcion de sr_get_data pls
add_action('wp_ajax_sr_get_chart', 'sr_get_chart');
add_action('wp_ajax_nopriv_sr_get_chart', 'sr_get_chart');


function sr_get_chart() {

    //estos son los datos q mandamos desde el js, es decir el filtro
    $date = $_POST['date'] ?? '';
    $hour = $_POST['hour'] ?? '';

    //mariadb-rekin konexioa
    $db = new wpdb('adrian', 'Admin123', 'arduino_db', '192.168.71.214');

    if ($db->last_error) {
        echo json_encode(['error' => $db->last_error]);
        wp_die();
    }

    // construimos el filtro (where)
    $where = " WHERE 1=1 ";

    if ($date && $hour !== "") {
        // eguna + ordua
        $start = sprintf("%s %02d:00:00", $date, $hour);
        $end   = sprintf("%s %02d:59:59", $date, $hour);

        $where .= $db->prepare(" AND data >= %s AND data <= %s ", $start, $end);

    } else if ($date) {
        // bakarrik egun osoa
        $where .= $db->prepare("
            AND data >= %s 
            AND data <= %s
        ", $date . " 00:00:00", $date . " 23:59:59");
    }

    // pedir los datos al mariadb
    $rows = $db->get_results("
        SELECT data, temperatura, hezetasuna
        FROM Datuak
        $where
        ORDER BY data ASC
    ", ARRAY_A);

     //respuesta de la query hicimos en json
    echo json_encode(['rows' => $rows]);
    wp_die(); //y acabamos ajax
}
//(vale de aqui debemos ir d vuelta al app.js)