# Programação Assíncrona - PHP

## O que é programação assíncrona?

A programação assíncrona é um estilo de programação que permite que várias tarefas sejam executadas simultaneamente de forma eficiente. Em vez de esperar que uma tarefa seja concluída antes de iniciar a próxima, as tarefas são programadas para serem executadas em segundo plano enquanto o programa continua a executar outras operações.

O cenário abaixo descreve o **fluxo síncrono**, onde cada requisição é **independente**. Cada requisição precisa aguardar a anterior finalizar o processamento para que seja possível iniciar o seu processamento.

<img alt="Fluxo Síncrono" src="https://github.com/vinelouzada/programacao-assincrona-php/assets/56182156/38bf0184-6dc3-4edd-84b0-a684764ee41f" style="height: 450px">

O código abaixo faz uma breve implementação do funcionamento mostrado na imagem acima:

```
<?php
use GuzzleHttp\Client;
use GuzzleHttp\Promise\Utils;

require_once "vendor/autoload.php";

$client = new Client();

$resposta1 = $client->get("http://localhost:8080/http-server.php");
$resposta2 = $client->get("http://localhost:8000/http-server.php");

echo "Resposta 1 " . $respostas1->getBody()->getContents() . PHP_EOL;
echo "Resposta 2 " . $respostas2->getBody()->getContents() . PHP_EOL;
```

### Para executar

1. Entre na pasta `requisicoes` e suba dois servidores: 1 na porta `8080` e outro na `8000`, conforme o comando abaixo:

```
php -S localhost:8080
```
```
php -S localhost:8000
```
2. Abra o terminal use o comando `time` e execute o arquivo `requisicoes-sincronas`, conforme abaixo:

```
time php requisicoes-sincronas.php
```

3. O resultado semelhante abaixo será exibido:

```
Resposta 1 Resposta do servidor que levou 5 segundos

Resposta 2 Resposta do servidor que levou 3 segundos


real    0m8,579s
user    0m0,000s
sys     0m0,030s
```

Logo, podemos concluir que o tempo de resposta total dessas requisições será o **tempo total das duas requisições somadas.** 

## Solução

Para resolver a problemática acima, uma das soluções é utilizar a programação assíncrona que em vez de esperar que uma tarefa seja concluída antes de iniciar a próxima, as tarefas são programadas para serem executadas em segundo plano enquanto o programa continua a executar outras operações. Conforme a imagem abaixo

<img alt="Fluxo Síncrono" src="https://github.com/vinelouzada/programacao-assincrona-php/assets/56182156/e1aaea12-ae12-4d0f-8abe-060c8348b057" style="height: 450px">

O código abaixo faz uma breve implementação do funcionamento mostrado na imagem acima:

```
<?php
use GuzzleHttp\Client;
use GuzzleHttp\Promise\Utils;

require_once "vendor/autoload.php";

$client = new Client();

$promessa1 = $client->getAsync("http://localhost:8080/http-server.php");
$promessa2 = $client->getAsync("http://localhost:8000/http-server.php");

$respostas = Utils::unwrap([
    $promessa1, $promessa2
]);

echo "Resposta 1 " . $respostas[0]->getBody()->getContents() . PHP_EOL;
echo "Resposta 2 " . $respostas[1]->getBody()->getContents() . PHP_EOL;
```

### Para executar

1. Entre na pasta `requisicoes` e suba dois servidores: 1 na porta `8080` e outro na `8000`, conforme o comando abaixo:

```
php -S localhost:8080
```
```
php -S localhost:8000
```
2. Abra o terminal use o comando `time` e execute o arquivo `requisicoes-assincronas`, conforme abaixo:

```
time php requisicoes-assincronas.php
```

3. O resultado semelhante abaixo será exibido:

```
Resposta 1 Resposta do servidor que levou 2 segundos

Resposta 2 Resposta do servidor que levou 3 segundos


real    0m3,348s
user    0m0,000s
sys     0m0,015s
```

Logo, podemos concluir que o tempo de resposta total dessas requisições será o **tempo da requisição maior**.

## Programação Assíncrona por baixo dos panos

O cenário acima foi desenvolvido utilizando bibliotecas para facilitar o uso e ocultar toda a complexidade. Dessa forma, abaixo descreve essa implementação de forma nativa com PHP

```
<?php

//Abrindo os streams
$listaDeStreams = [
    stream_socket_client('tcp://localhost:8000'),
    stream_socket_client('tcp://localhost:8001')
];

//Enviando uma requisicao http para cada um dos streams
fwrite($listaDeStreams[0], 'GET /requisicoes/http-server.php HTTP/1.1' . PHP_EOL . PHP_EOL);
fwrite($listaDeStreams[1], 'GET /requisicoes/http-server.php HTTP/1.1' . PHP_EOL . PHP_EOL);

//Informamos que os recursos devem ser abertos em modo não-bloqueante - assim, o processo que acessa o arquivo não bloqueará a CPU por causar um estado de espera ao tentar acessá-los.
foreach ($listaDeStreams as $stream){
    stream_set_blocking($stream, false);
}

do {
    $streamsParaLer = $listaDeStreams;

// Observamos modicacoes nestes streams, ou seja, quando este recurso estiver pronto para a leitura
    $streamsProntos = stream_select($streamsParaLer, $write, $except, 1, 0);

    if ($streamsProntos  === 0) {
        continue;
    }

    foreach ($streamsParaLer as $indice => $stream) {
        $conteudo = stream_get_contents($stream);

        echo $conteudo;
        if (feof($stream)) {
            fclose($stream);
            unset($listaDeStreams[$indice]);
        }
    }
} while (!empty($listaDeStreams));

echo 'ok';

```

### Para executar

1. Entre na pasta `requisicoes` e suba dois servidores: 1 na porta `8000` e outro na `8001`, conforme o comando abaixo:

```
php -S localhost:8000
```
```
php -S localhost:8001
```
2. Abra o terminal use o comando `time` e execute o arquivo `requisicoes-assincronas`, conforme abaixo:

```
time php implementacao/requisicoes-assincronas.php
```

3. O resultado semelhante abaixo será exibido:

```
time php implementacao/requisicoes-assincronas.php 
HTTP/1.1 200 OK
Date: Wed, 05 Jul 2023 13:12:06 GMT
Connection: close
X-Powered-By: PHP/8.2.3
Content-type: text/html; charset=UTF-8

Resposta do servidor que levou 1 segundos
HTTP/1.1 200 OK
Date: Wed, 05 Jul 2023 13:12:10 GMT
Connection: close
X-Powered-By: PHP/8.2.3
Content-type: text/html; charset=UTF-8

Resposta do servidor que levou 5 segundos
ok
real    0m5.080s
user    0m0.000s
sys     0m0.015s

```

## Vantagem de usar programação assíncrona

Olhando esses dois cenários podemos concluir que conseguimos atingir uma melhor performance em cenários de I/O (entrada e/ou saída de dados), pois não bloqueamos o processador enquanto espera a resposta das requisições.


