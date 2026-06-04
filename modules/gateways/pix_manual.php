<?php

if (!defined("WHMCS")) {
    die("This file cannot be accessed directly");
}

/**
 * Metadados do Módulo
 */
function pix_manual_MetaData() {
    return array(
        'DisplayName' => 'PIX Manual - QR Code',
        'APIVersion' => '1.1',
        'DisableLocalCreditCardInput' => true,
        'TokenisedStorage' => false,
    );
}

/**
 * Configurações do Módulo no painel Administrativo
 */
function pix_manual_config() {
    return array(
        'FriendlyName' => array(
            'Type' => 'System',
            'Value' => 'PIX Manual (QR Code e Comprovante)',
        ),
        'pixKeyType' => array(
            'FriendlyName' => 'Tipo de Chave',
            'Type' => 'dropdown',
            'Options' => 'CPF/CNPJ,Email,Telefone,Chave Aleatória',
            'Description' => 'Selecione o tipo da sua chave PIX.',
        ),
        'pixKey' => array(
            'FriendlyName' => 'Chave PIX',
            'Type' => 'text',
            'Size' => '50',
            'Description' => 'Sua chave (Ex: para telefone use +5511999999999).',
        ),
        'merchantName' => array(
            'FriendlyName' => 'Nome do Recebedor',
            'Type' => 'text',
            'Size' => '25',
            'Description' => 'Nome exato como aparece na conta bancária (Máx 25 char).',
        ),
        'merchantCity' => array(
            'FriendlyName' => 'Cidade',
            'Type' => 'text',
            'Size' => '15',
            'Description' => 'Cidade do recebedor (Máx 15 char).',
        ),
        'ticketDeptId' => array(
            'FriendlyName' => 'ID do Depto de Suporte',
            'Type' => 'text',
            'Size' => '5',
            'Default' => '1',
            'Description' => 'ID do departamento de faturamento para envio de comprovantes.',
        ),
    );
}

/**
 * Função Auxiliar: Formatação do Payload PIX
 */
function pix_manual_format_string($id, $value) {
    $size = str_pad(strlen($value), 2, '0', STR_PAD_LEFT);
    return $id . $size . $value;
}

/**
 * Função Auxiliar: Gerador do Código EMV (BR Code)
 */
function pix_manual_gerar_payload($pixKey, $merchantName, $merchantCity, $amount, $txid) {
    
    // Rotina nativa e segura para remover acentos e caracteres especiais
    $limpar_texto = function($str) {
        $map = [
            'á'=>'a','à'=>'a','ã'=>'a','â'=>'a','é'=>'e','ê'=>'e','í'=>'i',
            'ó'=>'o','ô'=>'o','õ'=>'o','ú'=>'u','ü'=>'u','ç'=>'c',
            'Á'=>'A','À'=>'A','Ã'=>'A','Â'=>'A','É'=>'E','Ê'=>'E','Í'=>'I',
            'Ó'=>'O','Ô'=>'O','Õ'=>'O','Ú'=>'U','Ü'=>'U','Ç'=>'C'
        ];
        $str = strtr($str, $map);
        return preg_replace('/[^a-zA-Z0-9 ]/', '', $str);
    };

    // Higienização de campos
    $pixKey = trim(str_replace(' ', '', $pixKey));
    $merchantName = substr($limpar_texto($merchantName), 0, 25);
    $merchantCity = substr($limpar_texto($merchantCity), 0, 15);
    
    // Fallback de segurança: Impede que o campo vá vazio e invalide o PIX
    if (empty(trim($merchantName))) $merchantName = 'Empresa';
    if (empty(trim($merchantCity))) $merchantCity = 'Cidade';

    $gui = pix_manual_format_string('00', 'BR.GOV.BCB.PIX');
    $key = pix_manual_format_string('01', $pixKey);
    $merchantAccount = pix_manual_format_string('26', $gui . $key);
    $merchantCategoryCode = pix_manual_format_string('52', '0000');
    $transactionCurrency = pix_manual_format_string('53', '986'); // 986 = BRL
    $transactionAmount = pix_manual_format_string('54', number_format((float)$amount, 2, '.', ''));
    $countryCode = pix_manual_format_string('58', 'BR');
    $merchantNameStr = pix_manual_format_string('59', $merchantName);
    $merchantCityStr = pix_manual_format_string('60', $merchantCity);
    
    $txidField = pix_manual_format_string('05', $txid);
    $additionalDataFieldTemplate = pix_manual_format_string('62', $txidField);

    $payloadStr = pix_manual_format_string('00', '01') .
                  $merchantAccount .
                  $merchantCategoryCode .
                  $transactionCurrency .
                  $transactionAmount .
                  $countryCode .
                  $merchantNameStr .
                  $merchantCityStr .
                  $additionalDataFieldTemplate .
                  "6304";

    // Calcula o CRC16 CCITT de forma segura para arquiteturas PHP mistas
    $polynomial = 0x1021;
    $res = 0xFFFF;
    for ($i = 0; $i < strlen($payloadStr); $i++) {
        $res ^= ord($payloadStr[$i]) << 8;
        for ($j = 0; $j < 8; $j++) {
            if (($res & 0x8000) > 0) {
                $res = (($res << 1) ^ $polynomial) & 0xFFFF;
            } else {
                $res = ($res << 1) & 0xFFFF;
            }
        }
    }
    $crc = strtoupper(str_pad(dechex($res), 4, '0', STR_PAD_LEFT));

    return $payloadStr . $crc;
}

/**
 * Geração da visualização na Fatura
 */
function pix_manual_link($params) {
    // Parâmetros da fatura
    $invoiceId = $params['invoiceid'];
    $amount = $params['amount'];
    
    // Parâmetros do módulo
    $pixKey = $params['pixKey'];
    $merchantName = $params['merchantName'];
    $merchantCity = $params['merchantCity'];
    $ticketDeptId = $params['ticketDeptId'];
    
    // ATENÇÃO: Para PIX manual (estático), o txid DEVE ser '***' para máxima compatibilidade.
    // Muitos aplicativos bancários falham se houver um ID customizado em um PIX não registrado via API.
    $txid = "***"; 

    // Gera a String do PIX Copia e Cola
    $payload = pix_manual_gerar_payload($pixKey, $merchantName, $merchantCity, $amount, $txid);

    // Usa a API gratuita do qrserver para gerar a imagem.
    $qrCodeUrl = "https://api.qrserver.com/v1/create-qr-code/?size=250x250&data=" . urlencode($payload);

    // Monta o layout HTML
    $html = '
    <div style="max-width: 400px; margin: 0 auto; padding: 20px; border: 1px solid #ddd; border-radius: 8px; background-color: #fcfcfc; text-align: center; font-family: sans-serif;">
        <h3 style="color: #32bcad; margin-top: 0;">Pagamento via PIX</h3>
        <p style="font-size: 14px; color: #555;">Escaneie o QR Code abaixo com o app do seu banco para pagar o valor exato da fatura.</p>
        
        <img src="' . $qrCodeUrl . '" alt="QR Code PIX" style="border: 5px solid #fff; box-shadow: 0 0 10px rgba(0,0,0,0.1); border-radius: 8px; margin-bottom: 20px;">
        
        <p style="font-size: 13px; margin-bottom: 5px;"><strong>Ou utilize a opção PIX Copia e Cola:</strong></p>
        <input type="text" id="pixCopiaCola" value="' . $payload . '" readonly style="width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 4px; margin-bottom: 10px; text-align: center; font-size: 12px; color: #333;">
        <button type="button" onclick="copiarPix()" style="background-color: #32bcad; color: white; border: none; padding: 10px 15px; border-radius: 4px; cursor: pointer; font-weight: bold; width: 100%;">Copiar Código PIX</button>
        
        <hr style="border: 0; border-top: 1px solid #eee; margin: 25px 0;">
        
        <h4 style="margin-bottom: 10px; color: #333;">Já realizou o pagamento?</h4>
        <p style="font-size: 12px; color: #666; margin-bottom: 15px;">A confirmação não é automática. Por favor, envie o comprovante para liberação do serviço.</p>
        <a href="submitticket.php?step=2&deptid=' . $ticketDeptId . '&subject=Comprovante+Fatura+' . $invoiceId . '" target="_blank" style="display: inline-block; background-color: #4CAF50; color: white; padding: 12px 20px; text-decoration: none; border-radius: 4px; font-weight: bold;"><i class="fas fa-paperclip"></i> Enviar Comprovante</a>
        
        <script>
            function copiarPix() {
                var copyText = document.getElementById("pixCopiaCola");
                copyText.select();
                copyText.setSelectionRange(0, 99999); // Para mobile
                navigator.clipboard.writeText(copyText.value).then(function() {
                    alert("Código PIX copiado com sucesso!");
                }, function(err) {
                    alert("Erro ao copiar o código.");
                });
            }
        </script>
    </div>';

    return $html;
}
?>
