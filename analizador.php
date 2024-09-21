<?php
// TIPOS DE TOKENS
define('OP_OU', 1);
define('OP_REL', 2);
define('NUMERO', 3);
define('ID_VARIAVEL', 4);
define('ATRIBUICAO', 5);
define('FIM_ARQUIVO', 0);

// LÊ A EXPRESSÃO DO ARQUIVO 'expressao.txt'
$nome_arquivo = 'expressao.txt';
if (!file_exists($nome_arquivo)) {
    die("Erro: O arquivo 'expressao.txt' não foi encontrado.\n");
}

// CARREGA O CONTEUDO DO ARQUIVO
$entrada = trim(file_get_contents($nome_arquivo));
if (empty($entrada)) {
    die("Erro: O arquivo está vazio.\n");
}

$lista_tokens = [];
$token_atual = 0;
$indice_caractere = 0;

// ANALIZADOR LEXICO PARA TOKENIZAR A STRING DE ENTRADA
function analisador_lexico($entrada) {
    global $lista_tokens, $indice_caractere;
    $comprimento = strlen($entrada);
    while ($indice_caractere < $comprimento) {
        $caractere = $entrada[$indice_caractere];
        
        if (ctype_space($caractere)) {
            $indice_caractere++;
            continue;
        }

        // VARIÁVEL (a-zA-Z_)
        if (ctype_alpha($caractere)) {
            $variavel = '';
            while (ctype_alnum($caractere) || $caractere == '_') {
                $variavel .= $caractere;
                $indice_caractere++;
                $caractere = ($indice_caractere < $comprimento) ? $entrada[$indice_caractere] : null;
            }
            $lista_tokens[] = ['tipo' => ID_VARIAVEL, 'valor' => $variavel];
        }
        // NÚMERO INTEIRO OU REAL
        elseif (ctype_digit($caractere) || ($caractere == '.' && $indice_caractere + 1 < $comprimento && ctype_digit($entrada[$indice_caractere + 1]))) {
            $numero = '';
            $tem_ponto = false;
            while (ctype_digit($caractere) || ($caractere == '.' && !$tem_ponto)) {
                if ($caractere == '.') {
                    $tem_ponto = true;
                }
                $numero .= $caractere;
                $indice_caractere++;
                $caractere = ($indice_caractere < $comprimento) ? $entrada[$indice_caractere] : null;
            }
            $lista_tokens[] = ['tipo' => NUMERO, 'valor' => $numero];
        }
        // OPERADOR DE ATRIBUIÇÃO =
        elseif ($caractere == '=') {
            $indice_caractere++;
            $lista_tokens[] = ['tipo' => ATRIBUICAO, 'valor' => '='];
        }
        // OPERADORES RELACIONAIS
        elseif ($caractere == '>') {
            $indice_caractere++;
            if ($indice_caractere < $comprimento && $entrada[$indice_caractere] == '=') {
                $lista_tokens[] = ['tipo' => OP_REL, 'valor' => '>='];
                $indice_caractere++;
            } else {
                $lista_tokens[] = ['tipo' => OP_REL, 'valor' => '>'];
            }
        } elseif ($caractere == '<') {
            $indice_caractere++;
            if ($indice_caractere < $comprimento && $entrada[$indice_caractere] == '=') {
                $lista_tokens[] = ['tipo' => OP_REL, 'valor' => '<='];
                $indice_caractere++;
            } else {
                $lista_tokens[] = ['tipo' => OP_REL, 'valor' => '<'];
            }
        } elseif ($caractere == '=') {
            $indice_caractere++;
            if ($indice_caractere < $comprimento && $entrada[$indice_caractere] == '=') {
                $lista_tokens[] = ['tipo' => OP_REL, 'valor' => '=='];
                $indice_caractere++;
            }
        } elseif ($caractere == '|') {
            $indice_caractere++;
            if ($indice_caractere < $comprimento && $entrada[$indice_caractere] == '|') {
                $lista_tokens[] = ['tipo' => OP_OU, 'valor' => '||'];
                $indice_caractere++;
            } else {
                erro("Operador inválido '|' detectado.");
            }
        } else {
            $indice_caractere++;
            erro("Caractere desconhecido: $caractere");
        }
    }
    $lista_tokens[] = ['tipo' => FIM_ARQUIVO, 'valor' => 'EOF'];
}

// FUNÇÃO PARA OBTER O PRÓXIMO TOKEN
function obter_proximo_token() {
    global $lista_tokens, $token_atual;
    return $lista_tokens[$token_atual++] ?? null;
}

// FUNÇÃO PARA TRATAMENTO DE ERROS
function erro($mensagem) {
    echo "Erro: $mensagem\n";
    exit(1);
}

// FUNÇÃO DE PARSING PARA ATRIBUIÇÕES
function atribuicao() {
    global $token_atual;
    echo "Entrando em <atribuicao>\n";
    $token = obter_proximo_token();
    if ($token['tipo'] == ID_VARIAVEL) {
        echo "Variável de atribuição: " . $token['valor'] . "\n";
    } else {
        erro("Esperado uma variável para atribuição, encontrado: " . $token['valor']);
    }
    
    $token = obter_proximo_token();
    if ($token['tipo'] == ATRIBUICAO) {
        echo "Operador de atribuição '=' encontrado\n";
    } else {
        erro("Esperado operador '=', encontrado: " . $token['valor']);
    }
    
    expressao();
    echo "Saindo de <atribuicao>\n";
}

// FUNÇÃO DE PARSING PARA EXPRESSÕES
function expressao() {
    global $token_atual;
    echo "Entrando em <expressao>\n";
    expressao_relacional();
    
    while (true) {
        $token = obter_proximo_token();
        if ($token && $token['tipo'] == OP_OU) {
            echo "Encontrado operador OU\n";
            expressao_relacional();
        } else {
            $token_atual--;  // Retrocede
            break;
        }
    }
    echo "Saindo de <expressao>\n";
}

// FUNÇÃO DE PARSING PARA EXPRESSÕES RELACIONAIS
function expressao_relacional() {
    global $token_atual;
    echo "Entrando em <expressao_relacional>\n";
    valor();
    
    $token = obter_proximo_token();
    if ($token && $token['tipo'] == OP_REL) {
        echo "Encontrado operador relacional: " . $token['valor'] . "\n";
        valor();
    } else {
        $token_atual--;  // Retrocede se não houver operador relacional
    }
    
    echo "Saindo de <expressao_relacional>\n";
}

// FUNÇÃO DE PARSING PARA VALORES (números ou variáveis)
function valor() {
    global $token_atual;
    echo "Entrando em <valor>\n";
    $token = obter_proximo_token();
    if ($token && $token['tipo'] == NUMERO) {
        echo "Número encontrado: " . $token['valor'] . "\n";
    } elseif ($token && $token['tipo'] == ID_VARIAVEL) {
        echo "Variável encontrada: " . $token['valor'] . "\n";
    } else {
        erro("Esperado número ou variável, encontrado: " . $token['valor']);
    }
    echo "Saindo de <valor>\n";
}

// EXECUTA O ANALIZADOR LEXICO
analisador_lexico($entrada);

// INICIA O PARSING DE ATRIBUIÇÃO
atribuicao();

?>
