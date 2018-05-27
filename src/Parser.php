<?php

namespace Parser;

use GuzzleHttp\Client;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;


/**
 * Created by PhpStorm.
 * User: mrAndersen
 * Date: 27.05.2018
 * Time: 13:28
 */
class Parser
{
    /**
     * @var Client
     */
    private $guzzle;

    /**
     * @var string
     */
    private $dataFolder;


    /**
     * Parser constructor.
     */
    public function __construct()
    {
        $this->guzzle = new Client(['verify' => false]);

        $dataFolder = str_replace('run.php', '', $_SERVER['PHP_SELF']) . 'data';

        if (file_exists($dataFolder)) {
            $this->removeDirRecursive($dataFolder);
        }

        mkdir($dataFolder);
        $this->dataFolder = $dataFolder;
    }

    private function removeDirRecursive($dir)
    {
        $files = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($dir, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::CHILD_FIRST
        );

        foreach ($files as $fileinfo) {
            $todo = ($fileinfo->isDir() ? 'rmdir' : 'unlink');
            $todo($fileinfo->getRealPath());
        }

        rmdir($dir);
    }

    /**
     * @param $nasaId
     */
    public function writeMediumImage($nasaId)
    {
        $assets = json_decode($this->guzzle->get("https://images-api.nasa.gov/asset/{$nasaId}")->getBody()->getContents(), true);

        foreach ($assets['collection']['items'] as $item) {
            $imageUrl = $item['href'];
            preg_match_all('/\~medium/u', $imageUrl, $matches);

            if (isset($matches[0][0])) {
                $explode = explode('.', $imageUrl);
                $ext = end($explode);

                file_put_contents($this->dataFolder . "\\{$nasaId}.{$ext}", file_get_contents($imageUrl));
                echo('.');
            }
        }
    }

    /**
     * @param int $limit
     * @throws \Exception
     */
    public function parse($limit = 100)
    {
        try {
            $response = $this->guzzle->get('https://images-assets.nasa.gov/recent.json');
            $json = json_decode($response->getBody()->getContents(), true);
        } catch (\Exception $exception) {
            throw new \Exception('Connection error occurred: ' . $exception->getMessage());
        }

        if (!isset($json['collection']) || !isset($json['collection']['items']) || count($json['collection']['items']) == 0) {
            throw new \Exception('No nasa images for you today :(');
        }

        $json['collection']['items'] = array_slice($json['collection']['items'], 0, $limit);


        foreach ($json['collection']['items'] as $item) {
            $nasaId = $item['data'][0]['nasa_id'];
            $metaFile = "NASA ID: %s\nKeywords: %s\nCenter: %s\nDate Created: %s\nDescription: %s";

            $metaFile = sprintf(
                $metaFile,
                $nasaId,
                ($item['data'][0]['keywords'] ? implode(', ', $item['data'][0]['keywords']) : ""),
                $item['data'][0]['center'],
                (new \DateTime($item['data'][0]['date_created']))->format('Y-m-d'),
                $item['data'][0]['description']
            );

            file_put_contents(sprintf($this->dataFolder . "\%s", "{$nasaId}.txt"), $metaFile);
            $this->writeMediumImage($nasaId);
        }
    }

}