<?PHP

header('content-Type: application/json');

define("CHAVE", "");

function validarRecibo($recibo, $diretorioDestino = "fasmapay/")
{
    $resultado = array(); // Inicializa o vetor de resultado

    if (isset($_FILES[$recibo]) && $_FILES[$recibo]['error'] === UPLOAD_ERR_OK) {
        $nomeArquivo = $_FILES[$recibo]['name'];
        $caminhoArquivo = $_FILES[$recibo]['tmp_name'];

        // Verifique se a pasta de destino existe e tem permissões adequadas
        if (!is_dir($diretorioDestino)) {
            mkdir($diretorioDestino, 0777, true); // Cria a pasta se não existir
        }

        $caminhoDestino = $diretorioDestino . $nomeArquivo;

        // Verifique se o upload do PDF foi bem-sucedido
        if (move_uploaded_file($caminhoArquivo, $caminhoDestino)) {
            // URL da API
            $urlApi = 'https://api.fasma.ao?sudopay_key=' . CHAVE;

            // Inicializa a sessão cURL
            $ch = curl_init();

            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
            // Configura a solicitação POST para a API
            curl_setopt($ch, CURLOPT_URL, $urlApi);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, array(
                'url' => $_SERVER['HTTP_ORIGIN'],
                'sudopay_file' => new CURLFile($caminhoDestino)
            ));

            // Realiza a solicitação
            $response = curl_exec($ch);

            // Verifica se houve algum erro
            if (curl_errno($ch)) {
                $resultado['status'] = 'erro';
                $resultado['mensagem'] = 'Erro ao enviar a solicitação: ' . curl_error($ch);
            } else {
                $vetorResposta = json_decode($response, true);
                if ($vetorResposta !== null) {
                    return $vetorResposta;
                } else {
                    $resultado['STATUS'] = 'erro';
                    $resultado['mensagem'] = 'Erro ao decodificar a resposta JSON da API';
                }
            }

            // Fecha a sessão cURL
            curl_close($ch);
        } else {
            $resultado['status'] = 'erro';
            $resultado['mensagem'] = 'Erro ao fazer o upload do arquivo PDF';
        }
    } else {
        $resultado['status'] = 'erro';
        $resultado['mensagem'] = 'Nenhum arquivo enviado ou erro no envio do PDF';
    }

    return $resultado; // Retorna o vetor de resultado
}

function validarTokens($where, $token)
{
    if (isset($_SESSION[$where]) && $_SESSION[$where] === $token) {
        // unset($_SESSION['token']);
        return true;
    }
    return false;
}

function generarTokens($where)
{
    // Salva o vetor na sessão
    return $_SESSION[$where] =  bin2hex(random_bytes(32));;
}
