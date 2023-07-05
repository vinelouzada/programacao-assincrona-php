<?php

$listaDeStreams = [
    stream_socket_client('tcp://localhost:8000'),
    stream_socket_client('tcp://localhost:8001')
];

fwrite($listaDeStreams[0], 'GET /requisicoes/http-server.php HTTP/1.1' . PHP_EOL . PHP_EOL);
fwrite($listaDeStreams[1], 'GET /requisicoes/http-server.php HTTP/1.1' . PHP_EOL . PHP_EOL);

foreach ($listaDeStreams as $stream){
    stream_set_blocking($stream, false);
}

do {
    $streamsParaLer = $listaDeStreams;

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