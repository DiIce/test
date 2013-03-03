<?php

namespace Extensions;

class VK
{
    
    private $accounts;
    
    public function __construct() 
    {
        $this->accounts = \Extensions\Config::getInstance()->getOptions('vk');
    }
    
    public function audioSearch($query, $count = 10, $page = 0, $cacheTime = 86400)
    {
        if ( !$page || $page <= 0 ) $page = 0;
        else $page *= $count;
    
        $cacheKey = 'vk_' . sha1($query . '_' . $count . '_' . $page);
        
        $data = \Model\Cache::find('one', array('conditions' => array(
            '`expiredAt` > ? AND `key` = ?', time(), $cacheKey
        )));
        
        if( !$data )
        {
            $data = $this->api($query,$count,$page);
            $result = array();
            $data = simplexml_load_string($data);
            foreach($data->audio as $audio){
                
                $artist = (string)str_replace(array(',','\'','\"','amp;'),"",preg_replace("/club[0-9]{2,15}/i","",$audio->artist));
                $a_result = implode(array_slice(explode('<br>',wordwrap($artist,70,'<br>',false)),0,1));
                if($a_result!=$artist)$a_result = $a_result.'...';

                $title = (string)str_replace(array(',','\'','\"','amp;'),"",preg_replace("/club[0-9]{2,15}/i","",$audio->title));
                $t_result = implode(array_slice(explode('<br>',wordwrap($title,70,'<br>',false)),0,1));
                if($t_result!=$title)$t_result = $t_result.'...';

                $tmp = array(
                    'artist'    =>$a_result,
                    'title'     =>$t_result,
                    'duration'  =>date('i:s', (int)$audio->duration),
                    'owner_id'  =>(int)$audio->owner_id,
                    'aid'       =>(int)$audio->aid,
                    'url'       =>'/music/mp3/' . (int)$audio->owner_id . '_' . (int)$audio->aid . '/',
                    'lyrics_id' =>(int)$audio->lyrics_id
                );
                $result[] = $tmp;
            }
            $data = \Model\Cache::create(array(
                'key'       => $cacheKey,
                'data'      => serialize($result),
                'expiredAt' => date( "Y-m-d H:i:s", time() + $cacheTime)
            ));
        }
        
        return unserialize($data->data);
    }
    
    private function api($query,$count,$page)
    {
        $rand = rand(0,count($this->accounts['id'])-1);
        $result = array();
        $ch = curl_init('http://api.vkontakte.ru/api.php');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_REFERER, 'http://vk.com/app'.$this->accounts['api_id'][$rand].'_'.$this->accounts['api_id'][$rand]);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, 'api_id='.$this->accounts['api_id'][$rand].'&count='.$count.'&lyrics=0&method=audio.search&offset='.$page.'&q='.urlencode($query).'&sort=0&test_mode=1&v=3.0&sig='.$this->getSignature($this->accounts['id'][$rand].'api_id='.$this->accounts['api_id'][$rand].'count='.$count.'lyrics=0method=audio.searchoffset='.$page.'q='.$query.'sort=0test_mode=1v=3.0'));
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 15);
        curl_setopt($ch, CURLOPT_USERAGENT, 'Opera/9.80 (Windows NT 5.1; U; ru) Presto/2.2.15 Version/10.00');
        $data = curl_exec($ch);
        curl_close($ch);
        return $data;
    } 
    
    private function getSignature($string){
        return md5($string);
    }
    
    public function audioGetById($owner_id,$aid) {
        $rand = rand(0,count($this->accounts['id'])-1);
        $ch = curl_init('http://api.vkontakte.ru/api.php');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_REFERER, 'http://vk.com/app'.$this->accounts['api_id'][$rand].'_'.$this->accounts['api_id'][$rand]);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, 'api_id='.$this->accounts['api_id'][$rand].'&audios='.$owner_id.'_'.$aid.'&method=audio.getById&test_mode=1&v=3.0&sig='.$this->getSignature($this->accounts['id'][$rand].'api_id='.$this->accounts['api_id'][$rand].'audios='.$owner_id.'_'.$aid.'method=audio.getByIdtest_mode=1v=3.0'));
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 15);
        curl_setopt($ch, CURLOPT_USERAGENT, 'Opera/9.80 (Windows NT 5.1; U; ru) Presto/2.2.15 Version/10.00');
        $data = curl_exec($ch);
        curl_close($ch);

        $song = new \SimpleXMLElement($data);
        
        return array(
            'url'   => (string)$song->audio->url,
            'artist'=> (string)$song->audio->artist,
            'title' => (string)$song->audio->title
        );
    }
    
    public function audioGetByName($query) {
        $rand = rand(0,count($this->accounts['id'])-1);
        $ch = curl_init('http://api.vkontakte.ru/api.php');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_REFERER, 'http://vk.com/app'.$this->accounts['api_id'][$rand].'_'.$this->accounts['id'][$rand]);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, 'api_id='.$this->accounts['api_id'][$rand].'&count=1&lyrics=0&method=audio.search&offset=0&q='.urlencode($query).'&sort=0&test_mode=1&v=3.0&sig='.$this->getSignature($this->accounts['id'][$rand].'api_id='.$this->accounts['api_id'][$rand].'count=1lyrics=0method=audio.searchoffset=0q='.$query.'sort=0test_mode=1v=3.0'));
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 15);
        curl_setopt($ch, CURLOPT_USERAGENT, 'Opera/9.80 (Windows NT 5.1; U; ru) Presto/2.2.15 Version/10.00');
        $data = curl_exec($ch);
        curl_close($ch);
        
        //echo "<pre>";print_r($data);die;

        $song = new \SimpleXMLElement($data);
        
        return array(
            'url'   => (string)$song->audio->url,
            'artist'=> (string)$song->audio->artist,
            'title' => (string)$song->audio->title
        );
    }
  
    public function getTrackById($owner_id,$aid,$cacheTime = 86400) 
    {
        $cacheKey = 'vk_' . sha1($owner_id . '_' . $aid);
        
        $data = \Model\Cache::find('one', array('conditions' => array(
            '`expiredAt` > ? AND `key` = ?', time(), $cacheKey
        )));
        
        if( !$data )
        {
            $data = \Model\Cache::create(array(
                'key'       => $cacheKey,
                'data'      => serialize($this->audioGetById($owner_id,$aid)),
                'expiredAt' => date( "Y-m-d H:i:s", time() + $cacheTime)
            ));
        }
        
        $track = unserialize($data->data);
        

        return array(
            'url'       => $track['url'],
            'fname'     => str_replace(" ", "_", "{$track['artist']} - {$track['title']}.mp3"),
            'size'      => $this->remoteFilesize($track['url']),
            'artist'    => $track['artist'],
            'track'     => $track['title']       
        );
    }
    
    public function getTrackByName($query,$cacheTime = 86400) 
    {
        $cacheKey = 'vk_' . sha1($query);
        
        $data = \Model\Cache::find('one', array('conditions' => array(
            '`expiredAt` > ? AND `key` = ?', time(), $cacheKey
        )));
        
        if( !$data )
        {
            $data = \Model\Cache::create(array(
                'key'       => $cacheKey,
                'data'      => serialize($this->audioGetByName($query)),
                'expiredAt' => date( "Y-m-d H:i:s", time() + $cacheTime)
            ));
        }
        
        $track = unserialize($data->data);
        

        return array(
            'url'       => $track['url'],
            'fname'     => str_replace(" ", "_", "{$track['artist']} - {$track['title']}.mp3"),
            'size'      => $this->remoteFilesize($track['url']),
            'artist'    => $track['artist'],
            'track'     => $track['title']          
        );
    }
    
    function remoteFilesize($url) {
        ob_start();
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_HEADER, 1);
        curl_setopt($ch, CURLOPT_NOBODY, 1);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 2);
        curl_setopt($ch, CURLOPT_TIMEOUT, 2);
        $ok = curl_exec($ch);
        curl_close($ch);

        $head = ob_get_contents();
        ob_end_clean();

        $regex = '/Content-Length:\s([0-9].+?)\s/';
        preg_match($regex, $head, $matches);

        return isset($matches[1]) ? $matches[1] : "unknown";
    }
}

?>
