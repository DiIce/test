<?php

namespace Extensions;

class FileCache {
    
  public static $cacheRoot = "/app/cache";

  public static function set( $key, $data, $time = 43200 ) {
    $cache = new \Model\Cache;
    $filename = ROOT . self::$cacheRoot . '/' . $key;  
    file_put_contents($filename, $data);
    $cache->key = $key;
    $cache->data = 'filecache';
    $cache->expiredat = date( "Y-m-d H:i:s", time() + $time );
    $cache->save();
  }
    
  public static function get( $key ) {
    $data = \Model\Cache::find('one', array('conditions' => array(
      '`expiredAt` > ? AND `key` = ?', time(), $key
    )));
    
    return $data ? file_get_contents(ROOT . self::$cacheRoot . '/' . $key) : null;
  }
    
  public static function clear( $key ) {
    $cache = \Model\Cache::find('one', array('conditions' => array(
      '`expiredAt` > ? AND `key` = ?', time(), $key
    )));
    
    return $cache ? $cache->delete() : false;
  }       
}