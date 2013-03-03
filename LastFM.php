<?php

namespace Extensions;

class LastFM {
    
    private static $root = "http://ws.audioscrobbler.com/2.0/";
    
    public static function request($conf, $method, $params) {
        $apiKey = $conf->getOption('lastfm', 'apikey');
        
        $qparams = http_build_query($params);
        $q = self::$root . "?method={$method}&format=json&api_key={$apiKey}&{$qparams}";
        
        $cacheKey = "lastfm_".sha1($q).".json";

        $data = \Extensions\FileCache::get($cacheKey);

        if ( !$data ) {
            $data = file_get_contents($q);
            \Extensions\FileCache::set($cacheKey, $data);
        }

        $data = json_decode($data);
        return $data;
    }
    
}

?>