<?php

namespace Extensions;

class Image {
    
    private static $whiteList;
    private static $avatar;
    private static $poster;
    
    public static function saveAvatar($file){
        self::$whiteList = \Extensions\Config::getInstance()->getOption('image', 'format');
        self::$avatar    = \Extensions\Config::getInstance()->getOption('image', 'avatar');
        $file['name'] = mb_strtolower($file['name'],'utf8');
        foreach(self::$whiteList as $item){
            if(preg_match("/$item\$/i", $file['name'])) {
                $format = end(explode(".", $file['name']));
                switch($format){
                    case 'png':{
                        $image = imagecreatefrompng($file['tmp_name']);
                        break;
                    }
                    case 'jpg':{
                        $image = imagecreatefromjpeg($file['tmp_name']);
                        break;
                    }
                    case 'jpeg':{
                        $image = imagecreatefromjpeg($file['tmp_name']);
                        break;
                    }
                    case 'gif':{
                        $image = imagecreatefromgif($file['tmp_name']);
                        break;
                    }
                }
                $avatar_name = md5(time().rand(1,999));
                $width = imagesx($image);
                $height = imagesy($image);
                $ratio = $height/$width;
                (self::$avatar['width']*$ratio) < self::$avatar['height'] ? $height = self::$avatar['width']*$ratio : $height = self::$avatar['height'];
                $new_image = imagecreatetruecolor(self::$avatar['width'], $height);
                imagecopyresampled($new_image, $image, 0, 0, 0, 0, self::$avatar['width'], $height, imagesx($image), imagesy($image));
                imagepng($new_image,ROOT.'/uploads/avatars/'.$avatar_name.'.png');
                unlink($file['tmp_name']);
                return '/uploads/avatars/'.$avatar_name.'.png';
            }
        }
        return false;
    }
    
    public static function savePoster($url)
    {
        self::$poster['min']    = \Extensions\Config::getInstance()->getOption('image', 'poster_min');
        self::$poster['max']    = \Extensions\Config::getInstance()->getOption('image', 'poster_max');
        $posterName = md5(time().rand(1,999));
        $posterHeaders = get_headers($url);
        
        $orgiginalExt = explode('.',$url);
        $orgiginalExt = end($orgiginalExt);
        
        if($posterHeaders[0] == 'HTTP/1.1 200 OK'){
            copy($url,ROOT.'/uploads/tmp/'.$posterName.'.'.$orgiginalExt);
        } else {
            return 'no_poster';
        }
        
        switch(mb_strtolower($orgiginalExt,'utf8')){
            case 'png':{
                $image = imagecreatefrompng(ROOT.'/uploads/tmp/'.$posterName.'.'.$orgiginalExt);
                break;
            }
            case 'jpg':{
                $image = imagecreatefromjpeg(ROOT.'/uploads/tmp/'.$posterName.'.'.$orgiginalExt);
                break;
            }
            case 'jpeg':{
                $image = imagecreatefromjpeg(ROOT.'/uploads/tmp/'.$posterName.'.'.$orgiginalExt);
                break;
            }
            case 'gif':{
                $image = imagecreatefromgif(ROOT.'/uploads/tmp/'.$posterName.'.'.$orgiginalExt);
                break;
            }
        }
        $newBigPoster = imagecreatetruecolor(self::$poster['max']['width'], self::$poster['max']['height']);
        imagecopyresampled($newBigPoster, $image, 0, 0, 0, 0, self::$poster['max']['width'], self::$poster['max']['height'], imagesx($image), imagesy($image));
        $newSmalPoster = imagecreatetruecolor(self::$poster['min']['width'], self::$poster['min']['height']);
        imagecopyresampled($newSmalPoster, $image, 0, 0, 0, 0, self::$poster['min']['width'], self::$poster['min']['height'], imagesx($image), imagesy($image));
        
        imagepng($newBigPoster,ROOT.'/uploads/posters/big/'.$posterName.'.png');
        imagepng($newSmalPoster,ROOT.'/uploads/posters/smal/'.$posterName.'.png');
        
        unlink(ROOT.'/uploads/tmp/'.$posterName.'.'.$orgiginalExt);
        return $posterName;
    }        
    
}

?>
