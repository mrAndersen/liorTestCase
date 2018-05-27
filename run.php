<?php
/**
 * Created by PhpStorm.
 * User: mrAndersen
 * Date: 27.05.2018
 * Time: 13:27
 */


use Parser\Parser;

include_once 'vendor/autoload.php';

$parser = new Parser();

//Will parse only first 50 images (for load reasons)
$parser->parse(50);

