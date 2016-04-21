<?php

    class MB_Gateway extends APP_Gateway {
        
		protected $options;
        
        public function __construct()
		{
			parent::__construct( 'multibanco', array(
					'admin' 	=> 'Multibanco',
					'dropdown' 	=> 'Multibanco'
			) );
            
            add_action('parse_request', array($this,'multibanco_callback'));
            //add_action('wp_ajax_validate_payment', array($this,'multibanco_callback'));
		}
        
        

function my_action_callback() {
	global $wpdb; // this is how you get access to the database

	$whatever = intval( $_POST['whatever'] );

	$whatever += 10;

        echo $whatever;

	wp_die(); // this is required to terminate immediately and return a proper response
}
        
		public function create_form( $order, $options ){ }

        public function form() {
			$fields = array(
                array(
                    'name' => 'mbentidade',
                    'title' => 'Entidade',
                    'type' => 'text',
                    'desc' => 'Entidade fornecida pela IfthenPay aquando da assinatura do contrato. (Ex.: 10559, 11202, 11473, 11604)'
                ),
                array(
                    'name' => 'mbsubentidade',
                    'title' => 'Sub-entidade',
                    'type' => 'text',
                    'desc' => 'Subentidade fornecida pela IfthenPay aquando da assinatura do contrato. (Ex.: 999)'
                ),
                array(
                    'name' => 'mbchave',
                    'title' => 'Chave Antiphishing',
                    'type' => 'text',
                    'default' => sha1(date('m/d/Y h:i:s a')),
                    'desc' => 'Para garantir a segurança do callback, gerada pelo sistema e que tem de ser fornecida à IfthenPay no pedido de activação do callback.'
                ),
                array(
                    'name' => 'urlCallback',
                    'title' => 'URL de Callback',
                    'type' => 'hidden',
                    'desc' => get_site_url()."/MultibancoPaymentValidation?chave=[CHAVE_ANTI_PHISHING]&entidade=[ENTIDADE]&referencia=[REFERENCIA]&val=[VALOR]<br/><br/>Deverá comunicar este endereço à IfthenPay juntamente com a Chave Anti-phishing que foi gerada."
                )
			);
            //http://vagrant.dev/classipress
			$arr = array(
                array(
                        'title' => "Configurações Multibanco (Gateway IfthenPay)",
                        'fields' => $fields
                )
			);
	
			return $arr;
        }
    
        public function process( $order, $options ){
            
            //var_dump($order);
            //var_dump($order->get_id());
            
            //var_dump(GenerateMbRef($options["mbentidade"],$options["mbsubentidade"],,));
            
            $ent_id = $options["mbentidade"];
            $subent_id = $options["mbsubentidade"];
            
            $chk_val = 0;

            $order_id = "0000".$order->get_id();

            if (strlen($ent_id) < 5) {
                echo "Lamentamos mas tem de indicar uma entidade válida";
                return;
            } else if (strlen($ent_id) > 5) {
                echo "Lamentamos mas tem de indicar uma entidade válida";
                return;
            }
            if (strlen($subent_id) == 0) {
                echo "Lamentamos mas tem de indicar uma subentidade válida";
                return;
            }

            $order_value = sprintf("%01.2f", $order->get_total());

            $order_value = format_number($order_value);

            if ($order_value < 1) {
                echo "Lamentamos mas é impossível gerar uma referência MB para valores inferiores a 1 Euro";
                return;
            }
            if ($order_value >= 1000000) {
                echo "<b>AVISO:</b> Pagamento fraccionado por exceder o valor limite para pagamentos no sistema Multibanco<br>";
            }

            if (strlen($subent_id) == 1) {
                //Apenas sao considerados os 6 caracteres mais a direita do order_id
                $order_id = substr($order_id, (strlen($order_id) - 6), strlen($order_id));
                $chk_str = sprintf('%05u%01u%06u%08u', $ent_id, $subent_id, $order_id, round($order_value * 100));
            } else if (strlen($subent_id) == 2) {
                //Apenas sao considerados os 5 caracteres mais a direita do order_id
                $order_id = substr($order_id, (strlen($order_id) - 5), strlen($order_id));
                $chk_str = sprintf('%05u%02u%05u%08u', $ent_id, $subent_id, $order_id, round($order_value * 100));
            } else {
                //Apenas sao considerados os 4 caracteres mais a direita do order_id
                $order_id = substr($order_id, (strlen($order_id) - 4), strlen($order_id));
                $chk_str = sprintf('%05u%03u%04u%08u', $ent_id, $subent_id, $order_id, round($order_value * 100));
            }

            //cálculo dos check digits

            $chk_array = array(3, 30, 9, 90, 27, 76, 81, 34, 49, 5, 50, 15, 53, 45, 62, 38, 89, 17, 73, 51);

            for ($i = 0; $i < 20; $i++) {
                $chk_int = substr($chk_str, 19 - $i, 1);
                $chk_val += ($chk_int % 10) * $chk_array[$i];
            }

            $chk_val %= 97;

            $chk_digits = sprintf('%02u', 98 - $chk_val);
            
            echo "<table cellpadding=\"0\" cellspacing=\"0\">
                <tbody>
                    <tr>
                        <th colspan=\"2\" style=\"border-bottom:1px solid #000; text-align: center; font-weight: bold;\">Instruções de pagamento
                            <br/><img src=\"https://raw.githubusercontent.com/ifthenpay/omnipay-ifthenpay/master/mb.png\" alt=\"Multibanco\">
                        </th>
                    </tr>
                    <tr>
                        <td>Entidade:</td>
                        <td style=\"font-weight: bold;\">".$ent_id."</td>
                    </tr>
                    <tr>
                        <td>Referência:</td>
                        <td style=\"font-weight: bold;\">".substr($chk_str, 5, 3)." ".substr($chk_str, 8, 3)." ".substr($chk_str, 11, 1).$chk_digits."</td>
                    </tr>
                    <tr>
                        <td>Valor:</td>
                        <td style=\"font-weight: bold;\">".number_format($order_value, 2, ',', ' ')."€</td>
                    </tr>
                    <tr>
                        <td style=\"border-top:1px solid #000;\" colspan=\"2\" style=\"font-size: small;\">O recibo emitido pelo terminal Multibanco é prova de pagamento. Guarde-o.</td>
                    </tr>
                </tbody>
            </table>";
            
            $order->pending();
            
            return true;
        }
        
        function multibanco_callback ()
        {
            
            if(strpos($_SERVER["REQUEST_URI"], 'MultibancoPaymentValidation') !== false) {
                $opts = get_option("cp_options");
                if($_GET["chave"] == $opts["gateways"]["multibanco"]["mbchave"])
                {
                    $orderID = substr($_GET["referencia"],3,4);
                    $order = appthemes_get_order($orderID);
                    $order->complete();
                }
                //return true;
                exit();
            }
        }
        
        //INICIO TRATAMENTO DEFINIÇÕES REGIONAIS
        function format_number($number) {
            $verifySepDecimal = number_format(99, 2);

            $valorTmp = $number;

            $sepDecimal = substr($verifySepDecimal, 2, 1);

            $hasSepDecimal = True;

            $i = (strlen($valorTmp) - 1);

            for ($i; $i != 0; $i -= 1) {
                if (substr($valorTmp, $i, 1) == "." || substr($valorTmp, $i, 1) == ",") {
                    $hasSepDecimal = True;
                    $valorTmp = trim(substr($valorTmp, 0, $i)).
                    "@".trim(substr($valorTmp, 1 + $i));
                    break;
                }
            }

            if ($hasSepDecimal != True) {
                $valorTmp = number_format($valorTmp, 2);

                $i = (strlen($valorTmp) - 1);

                for ($i; $i != 1; $i--) {
                    if (substr($valorTmp, $i, 1) == "." || substr($valorTmp, $i, 1) == ",") {
                        $hasSepDecimal = True;
                        $valorTmp = trim(substr($valorTmp, 0, $i)).
                        "@".trim(substr($valorTmp, 1 + $i));
                        break;
                    }
                }
            }

            for ($i = 1; $i != (strlen($valorTmp) - 1); $i++) {
                if (substr($valorTmp, $i, 1) == "." || substr($valorTmp, $i, 1) == "," || substr($valorTmp, $i, 1) == " ") {
                    $valorTmp = trim(substr($valorTmp, 0, $i)).trim(substr($valorTmp, 1 + $i));
                    break;
                }
            }

            if (strlen(strstr($valorTmp, '@')) > 0) {
                $valorTmp = trim(substr($valorTmp, 0, strpos($valorTmp, '@'))).trim($sepDecimal).trim(substr($valorTmp, strpos($valorTmp, '@') + 1));
            }

            return $valorTmp;
        }
        //FIM TRATAMENTO DEFINIÇÕES REGIONAIS
        
    }
    
    appthemes_register_gateway( 'MB_Gateway' );

?>