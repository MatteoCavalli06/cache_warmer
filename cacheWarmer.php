<?php

class cacheWarmerClass
{
     
    //Proprietà della classe.
    public $level = 0;
    public $allLinks=[];
    public $visitCount=0;

    //Costruttore.
    public function __construct(public string $baseUrl, public int $maxLevel, public array $excludes, public int $sleep )
    {
        date_default_timezone_set('Europe/Rome');
        $this->baseUrl=rtrim(trim($baseUrl),"/");
    }

    public function excludeLinks($links)
    {
        $filteredLinks = [];

        foreach($links as $link){
            $excludeLink = false;

            foreach($this->excludes as $toExclude){
                if(strpos($link, $toExclude) !== false){
                    $excludeLink = true;
                    break;
                }
            }

            if(!$excludeLink){
                $filteredLinks[]= $link;
            }
        }

        return $filteredLinks; 
    }

    public function excludeHash($links)
    {
        $filteredLinks = [];
        foreach($links as $link){
            if(strpos($link, '/#') === false){
                $filteredLinks[]=$link;
            }
        }
        return  $filteredLinks;
    }

    public function relativeToAbsolute($current,$url)
    {
        $parseCurrentUrl=parse_url($current);
        $parseUrl = parse_url($url);
    
        if(isset($parseUrl['scheme'])){ //Verifico che l'url inserito è assoluto o no.
                return $url; //se è assoluto ritorno l url senza modifiche.
        }
            
        $rootUrl=$parseCurrentUrl['scheme'].'://'.$parseCurrentUrl['host'];   

        //Trasformo il link relativi in assoluti in base alle varie casistiche.
        if(str_starts_with($url, "//")){
            $absoluteUrl=$rootUrl.substr($url, 1);  
        }elseif(str_starts_with($url,"/")){ 
            $absoluteUrl=$rootUrl.$url; 
        }else{ 
            $absoluteUrl=$current."/".$url;
        }
        
        return $absoluteUrl;
    }

    public function linksExtractor($html) //Metodo che estrae i link tramite un'espressione regolare.
    {
        $links=[];
        if (preg_match_all('/<a\s+.*?href=[\"\']?([^\"\' >]*)[\"\']?[^>]*>(.*?)<\/a>/i',$html,$m))
        {
            $links=$m[1];
        }
        return $links;
    }

    public function linksCleaner($links,$currentUrl) //Metodo che pulisce i link rimuovendo eventuali '/' finali e richiamando la funzione relativeToAbsolute.
    {
        foreach ($links as $k=>&$link)
        {
            $link=rtrim(trim($link),"/");
            $link=$this->relativeToAbsolute($currentUrl,$link);
            if(str_starts_with($link,"/")){
                $link=$this->baseUrl.$link;
            }
            if($link == $this->baseUrl){
                unset($links[$k]);
            }
            elseif(str_starts_with($link,$this->baseUrl)){
                continue;   
            }
            else{
                unset($links[$k]);
            }
        }
        $links=$this->excludeLinks($links);
        $links=$this->excludeHash($links);
        $links = array_unique($links); //evito che un link venga visitato 2 volte
        return $links;
    }

    public function visitUrl($url,$level) //Metodo che "visita" il contenuto di un url.
    {
        if($level > $this->maxLevel)
        {
            return;
        } 
            
        $this->log("Visito url: $url ..."); // Inserisce gli URL visitati nel .log

        $html=@file_get_contents($url);

        $this->visitCount++;
        
        //Richiamo dei metodi.
        $links=$this->linksExtractor($html);
    
        $links=$this->linksCleaner($links,$url);
        
        $links=array_diff($links,$this->allLinks);
    
        $this->allLinks=array_merge($links,$this->allLinks);

        sleep($this->sleep);
        
        foreach ($links as $link)
        {
            $this->visitUrl($link,$level+1);
        }
    }

    public function execute() //Metodo che avvia il metodo visitUrl
    {
        $this->visitUrl($this->baseUrl,1);
        $this->log( "Ho visitato ".$this->visitCount." links");
    }

    private function log($msg)
    { 
        file_put_contents("chachwarmer.log",date('Y-m-d\TH:i:sP')." ".$msg."\n",FILE_APPEND);
        echo "$msg\n";
    }

}

//$url = $argv[1]; //Inserimento dalla riga di comando
$opts=getopt('',['url:','level:','exclude:','help','sleep:']);

$url=$opts['url'] ?? null;
$maxLevel=$opts['level'] ?? 2;
$help=isset($opts['help']);
$sleep=$opts['sleep'] ?? 0;
$exclude = $opts['exclude'] ?? null;
$excludes=[];
if (is_array($exclude)) {
    $excludes = $exclude;
} elseif(is_string($exclude)) {
    $excludes = [$exclude];
}
if ($help || !$url) {
    echo "Sintassi Errata \n";
    exit; 
}

echo "Visiting: $url \nExcludes: ".implode(', ',$excludes)." \nLevel: $maxLevel \nSleep: $sleep\n";
$visitor = new cacheWarmerClass($url,$maxLevel,$excludes,$sleep);
$visitor->execute();


?>