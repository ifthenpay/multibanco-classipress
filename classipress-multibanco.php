<?php

    /*
        Plugin Name: Multibanco (IfthenPay gateway) for ClassiPress
        Description: Este plugin permite que clientes Portugueses paguem encomendas do ClassiPress através de Pagamento de Serviços no Multibanco, utilizando a gateway da IfthenPay.
        Version: 1.0
        Author: Rafael Almeida
        Author URI: http://www.ifthenpay.com
        License: MIT
    */

    add_action( 'init', 'mb_gateway_setup' );

    function mb_gateway_setup(){
        include 'classipress-multibanco-gateway.php';
    }

?>