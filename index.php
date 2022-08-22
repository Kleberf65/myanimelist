<?php

require("models/Anime.php");

use models\Anime;

header('Content-Type: application/json; charset=utf-8');
setlocale(LC_ALL, 'pt_BR.UTF8');
mb_internal_encoding('UTF8');
mb_regex_encoding('UTF8');

libxml_use_internal_errors(true);

$arrContextOptions = array(
    "ssl" => array(
        "verify_peer" => false,
        "verify_peer_name" => false,
    ),
);

observerRequests();

function observerRequests()
{
    if (isset($_GET['q'])) {
        $translate = $_GET['t'] ?? false;
        $limit = $_GET['l'] ?? 10;
        searchAnimes($_GET['q'], $limit, $translate);
    } else echo
    json_encode(array(
        'success' => 'false',
        'data' => array('error' => "Params 'q' not found.")
    ), JSON_UNESCAPED_UNICODE);
}

function searchAnimes($query, $limit, $translate)
{
    global $arrContextOptions;

    try {
        $base_url = 'https://api.jikan.moe/v3/search/anime?q=' . urlencode($query) . '&type=anime&limit=' . $limit;
        $response = file_get_contents($base_url, false, stream_context_create($arrContextOptions));
        $data = json_decode($response, true);

        $list = array();

        foreach ($data['results'] as $obj) {
            $anime = new Anime();
            $anime->anime_title = $obj['title'];
            $anime->anime_poster = $obj['image_url'];
            $anime->anime_raring = $obj['score'];
            $anime_sinopse = $obj['synopsis'];
            $anime->anime_sinopse = $translate
                ? utf8_decode(googleTranslate($anime_sinopse)) : $anime_sinopse;
            $list[] = $anime;
        }
        echo json_encode(array('success' => 'true', 'data' => $list), JSON_UNESCAPED_UNICODE);

    } catch (Exception $e) {
        echo json_encode(array('success' => 'false', 'data'
        => array('error' => $e->getMessage())), JSON_UNESCAPED_UNICODE);
    }
}

function googleTranslate($text)
{
    global $arrContextOptions;

    $response = file_get_contents('https://translate.google.com/m?sl=en&tl=pt-BR&hl=pt-BR&q='
        . urlencode($text), false, stream_context_create($arrContextOptions));
    $document = new DOMDocument();
    $document->loadHTML($response);


    $xPath = new DOMXPath($document);
    $elementList = $xPath->query('.//div[@class="result-container"]');

    $sei_la = array('movie');
    echo $sei_la['type'] == 'movie' ? "__(Filme)"
        : $sei_la['type'] == 'serie' ? "__(Série)" : "__(Animes)";

    return $elementList->item(0)->textContent;

}

