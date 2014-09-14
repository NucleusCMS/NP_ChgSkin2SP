<?php
/* NP_ChgSkin2SP v1.1
 * 
 * UserAgentによりスマートホンを判別し、適切なSkinへ振り分け
 * Nakazoe氏(nakazoe@comiu.com)作 NP_AdjustSkin2MobileLite 0.2を改造
 * 
 * v1.0 2013.09.21 初版 kyu
 * v1.1 2014.05.08 Feed(RSS/ATOM)パースエラー修正  kyu
 */

class NP_ChgSkin2SP extends NucleusPlugin{
    function getName()       {return 'ChgSkin2SP';}
    function getAuthor()     {return 'kyu';}
    function getVersion()    {return '1.1[2014.05.08]';}
    function getURL()        {return 'mailto:kyumfg@gmail.com';}
    function getDescription(){return 'UserAgentによりスマートホンを判別し、適切なSkinへ振り分けます。スキンに<%ChgSkin2SP%>と記述するとPC表示/スマートホン表示を切り替えるためのリンクを出力します。';}
    
    function supportsFeature($w) {return in_array($w, array('SqlTablePrefix'));}
    function getMinNucleusVersion(){return '341';}
    function getEventList()  {return array('InitSkinParse');}
    
    function install() {
        $this->createOption('spskinname','スマートホン表示で使用するスキン名','text','smartphone');
        $this->createOption('viewsp','スキン変数<%ChgSkin2SP%>: スマートホン表示するためのリンク名','text','スマートホン表示');
        $this->createOption('viewpc','スキン変数<%ChgSkin2SP%>: PC表示するためのリンク名','text','PC表示');
    }
    
    function uninstall() {
        $this->deleteOption('spskinname');
        $this->deleteOption('viewsp');
        $this->deleteOption('viewpc');
    }
    
    //スキンパース前処理
    function event_InitSkinParse(&$data){
        if (!$this->isSmartPhone()) return;
        $request_uri = $_SERVER['REQUEST_URI'];
        if (strpos($request_uri, '.php') !== false && strpos($request_uri, 'index.php') === false)
            return;

        $viewmode = getVar('viewmode');
        if (is_null($viewmode))
            $viewmode = cookieVar('viewmode');
        elseif (is_null($viewmode) == false)
            $viewmode = intval($viewmode);
        
        if ($viewmode == 0 || $viewmode == 1){
            setcookie('viewmode', $viewmode);
        }
        if ($viewmode == 1 || is_null($viewmode))
        {
                $optionSpskinname = htmlspecialchars($this->getOption('spskinname'), ENT_QUOTES, _CHARSET);
                
                if(!SKIN::exists($optionSpskinname))
                    $SkinName = $data['skin']->name;
                else
                    $SkinName = $optionSpskinname;
        }
        elseif ($viewmode == 0)
            $SkinName = $data['skin']->name;
        else
            return;
        
        $skin =& SKIN::createFromName($SkinName);
        $data['skin']->SKIN($skin->getID());
    
        return;
    }
    
    //スキン変数
    function doSkinVar(){
        if (!$this->isSmartPhone()) return;
        $viewmode = getVar('viewmode');
        if (is_null($viewmode)){
            $viewmode = cookieVar('viewmode');
        }
        if (is_null($viewmode)){
            if ($this->isSmartPhone()) $viewmode = 1;
            else                       $viewmode = 0;
        }
        else                           $viewmode = intval($viewmode);
    
        $Url = 'http://'. $_SERVER['HTTP_HOST']. $_SERVER['REQUEST_URI'];
        
        if (strpos($Url,'?') === false) $Url .= '?';
        else                            $Url .= '&';
        
        $_ = strstr($Url,'viewmode');
        if(strpos($Url,$_)!==false) $Url = str_replace($_, '', $Url);
        {
            $optionViewsp = htmlspecialchars($this->getOption('viewsp'), ENT_QUOTES, _CHARSET);
            $optionViewpc = htmlspecialchars($this->getOption('viewpc'), ENT_QUOTES, _CHARSET);
            $echo = '<div class="viewmode">';
            if ($viewmode == 0)
                $echo .= sprintf('<a href="%s">%s</a>', "{$Url}viewmode=1", $optionViewsp);
            elseif ($viewmode == 1)
                $echo .= sprintf('<a href="%s">%s</a>', "{$Url}viewmode=0", $optionViewpc);
            $echo .= '</div>';
            echo $echo;
        }
    }
    
    //UA判定
    function Platform(){
        $ua = strtolower($_SERVER['HTTP_USER_AGENT']);
        
        if(strpos($ua, 'iphone')!==false)                                // iPhone
            $platform = array('Platform' => 'iPhone', 'PlatformFlg' => 10);
        elseif(strpos($ua, 'ipod')!==false)                              // iPod Touch
            $platform = array('Platform' => 'iPhone','PlatformFlg' => 11);
        elseif(strpos($ua, 'android')!==false)                           // android
            $platform = array('Platform' => 'Android','PlatformFlg' => 12);
        else                                                             // others(PC)
            $platform = array('Platform' => 'pc', 'PlatformFlg' => 9);
        
        return $platform;
    }
    
    function isiPhone(){
        $PlatForm_ary = $this->Platform();
        if($PlatForm_ary['PlatformFlg'] == 10 || $PlatForm_ary['PlatformFlg'] == 11)
            return true;
        else
            return false;
    }
    
    function isAndroid(){
        $PlatForm_ary = $this->Platform();
        if($PlatForm_ary['PlatformFlg'] == 12)
            return true;
        else
            return false;
    }
    
    function isSmartPhone(){
        if($this->isiPhone() || $this->isAndroid())
            return true;
        else
            return false;
    }
}
