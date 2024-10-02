<?php
/**
 * Plugin Name: Modificador de Formulario de Compra para Excursiones
 * Description: Agrega campos adicionales al formulario de compra cuando hay productos de la categoría Excursiones en el carrito, incluyendo campos dinámicos para pasajeros.
 * Version: 1.4
 * Author: Esteban Selvggi
 */

// Evitar acceso directo al archivo
if (!defined('ABSPATH')) {
    exit;
}

class ModificadorFormularioCompraExcursiones {
    
    public function __construct() {
        // Agregar campos al formulario de checkout
        add_action('woocommerce_before_order_notes', array($this, 'agregar_campos_formulario'));
        
        // Validar campos
        add_action('woocommerce_checkout_process', array($this, 'validar_campos'));
        
        // Guardar datos adicionales
        add_action('woocommerce_checkout_update_order_meta', array($this, 'guardar_datos_adicionales'));
        
        // Mostrar datos en el panel de administración
        add_action('woocommerce_admin_order_data_after_billing_address', array($this, 'mostrar_datos_en_admin'), 10, 1);

        // Agregar scripts para campos dinámicos
        add_action('wp_enqueue_scripts', array($this, 'agregar_scripts'));
    }

    /**
     * Verifica si hay productos de la categoría 'excursiones' en el carrito.
     */
    private function carrito_tiene_excursion() {
        foreach (WC()->cart->get_cart() as $cart_item) {
            $product_id = $cart_item['product_id'];
            if (has_term('excursiones', 'product_cat', $product_id) || has_term(44, 'product_cat', $product_id)) {
                return true;
            }
        }
        return false;
    }

    /**
     * Agrega los campos adicionales al formulario de checkout.
     */
    public function agregar_campos_formulario($checkout) {
        if (!$this->carrito_tiene_excursion()) {
            return;
        }

        echo '<div id="campos_excursion"><h3>' . __('Información adicional para excursiones') . '</h3>';

        woocommerce_form_field('tel', array(
            'type' => 'tel',
            'class' => array('form-row-wide'),
            'label' => 'Número de teléfono',
            'placeholder' => 'Número de teléfono',
            'required' => true,
        ), $checkout->get_value('tel'));

        woocommerce_form_field('fecha_llegada', array(
            'type' => 'date',
            'class' => array('form-row-wide'),
            'label' => 'Fecha de llegada',
            'required' => true,
        ), $checkout->get_value('fecha_llegada'));

        woocommerce_form_field('flight', array(
            'type' => 'text',
            'class' => array('form-row-wide'),
            'label' => 'Número de vuelo',
            'placeholder' => 'Número de vuelo',
            'required' => true,
        ), $checkout->get_value('flight'));

        woocommerce_form_field('pasajeros', array(
            'type' => 'number',
            'class' => array('form-row-wide'),
            'label' => 'Cantidad de pasajeros',
            'required' => true,
            'min' => 1,
            'custom_attributes' => array(
                'data-pasajeros' => 'true'
            )
        ), $checkout->get_value('pasajeros'));

        woocommerce_form_field('hotel', array(
            'type' => 'text',
            'class' => array('form-row-wide'),
            'label' => 'Hotel o alojamiento',
            'placeholder' => 'Hotel o alojamiento',
            'required' => true,
        ), $checkout->get_value('hotel'));

        echo '<div id="campos_pasajeros"></div>';

        woocommerce_form_field('terminos', array(
            'type' => 'checkbox',
            'class' => array('form-row-wide'),
            'label' => 'Acepto los términos y condiciones',
            'required' => true,
        ));

        echo '</div>';
    }

    /**
     * Valida los campos adicionales en el checkout.
     */
    public function validar_campos() {
        if (!$this->carrito_tiene_excursion()) {
            return;
        }

        if (empty($_POST['tel'])) {
            wc_add_notice('Por favor, ingrese un número de teléfono.', 'error');
        }
        if (empty($_POST['fecha_llegada'])) {
            wc_add_notice('Por favor, seleccione una fecha de llegada.', 'error');
        }
        if (empty($_POST['flight'])) {
            wc_add_notice('Por favor, ingrese un número de vuelo.', 'error');
        }
        if (empty($_POST['pasajeros']) || $_POST['pasajeros'] < 1) {
            wc_add_notice('Por favor, ingrese una cantidad válida de pasajeros.', 'error');
        }
        if (empty($_POST['hotel'])) {
            wc_add_notice('Por favor, ingrese un hotel o alojamiento.', 'error');
        }
        if (empty($_POST['terminos'])) {
            wc_add_notice('Debe aceptar los términos y condiciones para continuar.', 'error');
        }

        $num_pasajeros = intval($_POST['pasajeros']);
        for ($i = 1; $i <= $num_pasajeros; $i++) {
            if (empty($_POST["nombre_pasajero_$i"])) {
                wc_add_notice("Por favor, ingrese el nombre del pasajero $i.", 'error');
            }
            if (empty($_POST["apellidos_pasajero_$i"])) {
                wc_add_notice("Por favor, ingrese los apellidos del pasajero $i.", 'error');
            }
            if (empty($_POST["nacionalidad_pasajero_$i"])) {
                wc_add_notice("Por favor, ingrese la nacionalidad del pasajero $i.", 'error');
            }
            if (empty($_POST["fecha_nacimiento_pasajero_$i"])) {
                wc_add_notice("Por favor, ingrese la fecha de nacimiento del pasajero $i.", 'error');
            }
            if (empty($_POST["documento_pasajero_$i"])) {
                wc_add_notice("Por favor, ingrese el documento/pasaporte del pasajero $i.", 'error');
            }
            if (empty($_POST["email_pasajero_$i"])) {
                wc_add_notice("Por favor, ingrese el email del pasajero $i.", 'error');
            }
        }
    }

    /**
     * Guarda los datos adicionales en el pedido.
     */
    public function guardar_datos_adicionales($order_id) {
        if (!$this->carrito_tiene_excursion()) {
            return;
        }

        if (!empty($_POST['tel'])) {
            update_post_meta($order_id, 'tel', sanitize_text_field($_POST['tel']));
        }
        if (!empty($_POST['fecha_llegada'])) {
            update_post_meta($order_id, 'fecha_llegada', sanitize_text_field($_POST['fecha_llegada']));
        }
        if (!empty($_POST['flight'])) {
            update_post_meta($order_id, 'flight', sanitize_text_field($_POST['flight']));
        }
        if (!empty($_POST['pasajeros'])) {
            update_post_meta($order_id, 'pasajeros', absint($_POST['pasajeros']));
        }
        if (!empty($_POST['hotel'])) {
            update_post_meta($order_id, 'hotel', sanitize_text_field($_POST['hotel']));
        }

        $num_pasajeros = intval($_POST['pasajeros']);
        for ($i = 1; $i <= $num_pasajeros; $i++) {
            $pasajero_data = array(
                'nombre' => sanitize_text_field($_POST["nombre_pasajero_$i"]),
                'apellidos' => sanitize_text_field($_POST["apellidos_pasajero_$i"]),
                'nacionalidad' => sanitize_text_field($_POST["nacionalidad_pasajero_$i"]),
                'fecha_nacimiento' => sanitize_text_field($_POST["fecha_nacimiento_pasajero_$i"]),
                'documento' => sanitize_text_field($_POST["documento_pasajero_$i"]),
                'email' => sanitize_email($_POST["email_pasajero_$i"])
            );
            update_post_meta($order_id, "pasajero_$i", $pasajero_data);
        }
    }

    /**
     * Muestra los datos adicionales en el panel de administración de pedidos.
     */
    public function mostrar_datos_en_admin($order) {
        echo '<h3>Información adicional para excursiones</h3>';
        echo '<p><strong>Teléfono:</strong> ' . get_post_meta($order->get_id(), 'tel', true) . '</p>';
        echo '<p><strong>Fecha de llegada:</strong> ' . get_post_meta($order->get_id(), 'fecha_llegada', true) . '</p>';
        echo '<p><strong>Número de vuelo:</strong> ' . get_post_meta($order->get_id(), 'flight', true) . '</p>';
        echo '<p><strong>Cantidad de pasajeros:</strong> ' . get_post_meta($order->get_id(), 'pasajeros', true) . '</p>';
        echo '<p><strong>Hotel o alojamiento:</strong> ' . get_post_meta($order->get_id(), 'hotel', true) . '</p>';

        $num_pasajeros = intval(get_post_meta($order->get_id(), 'pasajeros', true));
        for ($i = 1; $i <= $num_pasajeros; $i++) {
            $pasajero_data = get_post_meta($order->get_id(), "pasajero_$i", true);
            echo "<h4>Pasajero $i</h4>";
            echo '<p><strong>Nombre:</strong> ' . $pasajero_data['nombre'] . ' ' . $pasajero_data['apellidos'] . '</p>';
            echo '<p><strong>Nacionalidad:</strong> ' . $pasajero_data['nacionalidad'] . '</p>';
            echo '<p><strong>Fecha de nacimiento:</strong> ' . $pasajero_data['fecha_nacimiento'] . '</p>';
            echo '<p><strong>Documento/Pasaporte:</strong> ' . $pasajero_data['documento'] . '</p>';
            echo '<p><strong>Email:</strong> ' . $pasajero_data['email'] . '</p>';
        }
    }

    /**
     * Agrega scripts para manejar campos dinámicos de pasajeros.
     */
    public function agregar_scripts() {
        if (is_checkout() && $this->carrito_tiene_excursion()) {
            add_action('wp_footer', array($this, 'imprimir_script_campos_dinamicos'));
        }
    }

    /**
     * Imprime el script JavaScript para los campos dinámicos de pasajeros.
     */
    public function imprimir_script_campos_dinamicos() {
        ?>
        <script type="text/javascript">
        jQuery(function($) {
            var camposPasajeros = $('#campos_pasajeros');
            var inputPasajeros = $('input[name="pasajeros"]');

            function generarCamposPasajeros() {
                var numPasajeros = parseInt(inputPasajeros.val());
                camposPasajeros.empty();

                for (var i = 1; i <= numPasajeros; i++) {
                    camposPasajeros.append(`
                        <h4>Pasajero ${i}</h4>
                        <p class="form-row form-row-wide">
                            <label for="nombre_pasajero_${i}">Nombre</label>
                            <input type="text" class="input-text" name="nombre_pasajero_${i}" id="nombre_pasajero_${i}" required>
                        </p>
                        <p class="form-row form-row-wide">
                            <label for="apellidos_pasajero_${i}">Apellidos</label>
                            <input type="text" class="input-text" name="apellidos_pasajero_${i}" id="apellidos_pasajero_${i}" required>
                        </p>
                        <p class="form-row form-row-wide">
                            <label for="nacionalidad_pasajero_${i}">Nacionalidad</label>
                            <input type="text" class="input-text" name="nacionalidad_pasajero_${i}" id="nacionalidad_pasajero_${i}" required>
                        </p>
                        <p class="form-row form-row-wide">
                            <label for="fecha_nacimiento_pasajero_${i}">Fecha de nacimiento</label>
                            <input type="date" class="input-text" name="fecha_nacimiento_pasajero_${i}" id="fecha_nacimiento_pasajero_${i}" required>
                        </p>
                        <p class="form-row form-row-wide">
                            <label for="documento_pasajero_${i}">Documento/Pasaporte</label>
                            <input type="text" class="input-text" name="documento_pasajero_${i}" id="documento_pasajero_${i}" required>
                        </p>
                        <p class="form-row form-row-wide">
                            <label for="email_pasajero_${i}">Email</label>
                            <input type="email" class="input-text" name="email_pasajero_${i}" id="email_pasajero_${i}" required>
                        </p>
                    `);
                }
            }

            inputPasajeros.on('change', generarCamposPasajeros);
            generarCamposPasajeros(); // Generar campos iniciales
        });
        </script>
        <?php
    }
}

// Inicializar el plugin
new ModificadorFormularioCompraExcursiones();