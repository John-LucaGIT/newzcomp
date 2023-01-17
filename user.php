<?php

function getClientIP() {
    $ipaddress = '';
    if (isset($_SERVER['HTTP_CLIENT_IP'])){
        $ipaddress = $_SERVER['HTTP_CLIENT_IP'];
    }
    else if(isset($_SERVER['HTTP_X_FORWARDED_FOR'])){
        $ipaddress = $_SERVER['HTTP_X_FORWARDED_FOR'];
    }
    else if(isset($_SERVER['HTTP_X_FORWARDED'])){
        $ipaddress = $_SERVER['HTTP_X_FORWARDED'];
    }
    else if(isset($_SERVER['HTTP_FORWARDED_FOR'])){
        $ipaddress = $_SERVER['HTTP_FORWARDED_FOR'];
    }
    else if(isset($_SERVER['HTTP_FORWARDED'])){
        $ipaddress = $_SERVER['HTTP_FORWARDED'];
    }
    else if(isset($_SERVER['REMOTE_ADDR'])){
        $ipaddress = $_SERVER['REMOTE_ADDR'];
    }
    else{
        $ipaddress = 'UNKNOWN';
    }
    return $ipaddress;
}

require_once("connectdb.php");

if ($_GET["state"] == "bot"){
    IPAccess($conn,"cybersniffer");
}

function crawlerDetect($USER_AGENT)
{
    $crawlers = array(
        array('Google', 'Google'),
        array('msnbot', 'MSN'),
        array('MJ12bot','mj12bot'),
        array('DuckDuckBot','duckduckbot'),
        array('Zoominfobot','zoominfobot'),
        array('bot','bot'),
        array('python-requests','python-requests/2.26.0'),
        array('Rambler', 'Rambler'),
        array('Yahoo', 'Yahoo'),
        array('Twitter', 'Twitterbot'),
        array('twitter', 'twitter'),
        array('Facebook', 'externalhit'),
        array('facebook', 'facebook'),
        array('AbachoBOT', 'AbachoBOT'),
        array('accoona', 'Accoona'),
        array('AcoiRobot', 'AcoiRobot'),
        array('ASPSeek', 'ASPSeek'),
        array('CrocCrawler', 'CrocCrawler'),
        array('Dumbot', 'Dumbot'),
        array('FAST-WebCrawler', 'FAST-WebCrawler'),
        array('GeonaBot', 'GeonaBot'),
        array('Gigabot', 'Gigabot'),
        array('Lycos', 'Lycos spider'),
        array('MSRBOT', 'MSRBOT'),
        array('Scooter', 'Altavista robot'),
        array('AltaVista', 'Altavista robot'),
        array('IDBot', 'ID-Search Bot'),
        array('eStyle', 'eStyle Bot'),
        array('Scrubby', 'Scrubby robot')
    );

    foreach ($crawlers as $c)
    {
        if (stristr($USER_AGENT, $c[0]))
        {
            return(true);
        }
    }

    return false;
}



function IPAccess($conn,$referer){
    $ip = getClientIP();
    $ip = mysqli_real_escape_string($conn, $ip);
    $result = mysqli_query($conn, "SELECT * FROM unique_ip WHERE IP LIKE '{$ip}';");
    $visits = 1;
    $crawler = crawlerDetect($_SERVER['HTTP_USER_AGENT']);
    if(mysqli_num_rows($result) > 0){
        $row = mysqli_fetch_assoc($result);
        $visits = $row["visits"];
        $visits = $visits + 1;
        $visits = mysqli_real_escape_string($conn, $visits);
        $sql = "UPDATE unique_ip SET visits={$visits} WHERE IP LIKE '{$ip}';";
        mysqli_query($conn, $sql);
        if($crawler == true){
            $bot = $bot["bot"];
            $bot = 1;
            $bot = mysqli_real_escape_string($conn,$bot);
            $botupdate = "UPDATE unique_ip SET bot={$bot} WHERE IP LIKE '{$ip}';";
            $csniffer = "UPDATE unique_ip SET securityrisk='identified by crawlerdetect' WHERE IP LIKE '{$ip}';";
            mysqli_query($conn,$botupdate);
            mysqli_query($conn,$csniffer);
        }
        if($referer == "cybersniffer"){
            $bot = $bot["bot"];
            $bot = 1;
            $bot = mysqli_real_escape_string($conn,$bot);
            $botupdate = "UPDATE unique_ip SET bot={$bot} WHERE IP LIKE '{$ip}';";
            $csniffer = "UPDATE unique_ip SET securityrisk='identified by cybersniffer' WHERE IP LIKE '{$ip}';";
            mysqli_query($conn,$botupdate);
            mysqli_query($conn,$csniffer);
            header('Location: ../../index');
        }
    }else{
        $ipd = json_decode(file_get_contents("https://ipinfo.io/{$ip}/json"));
        $getDate = date('d-m-y');
        $postcode = $ipd->postal;
        $city = $ipd->city;
        $region = $ipd->region;
        $country = $ipd->country;
        $hostname = $ipd->hostname;
        $company = $ipd->org;
        $botcheck = mysqli_query($conn,"SELECT * FROM unique_ip WHERE hostname LIKE '%bot%' OR hostname LIKE '%crawl%' OR hostname LIKE '%aws%' OR hostname LIKE '%ahrefs%' OR company LIKE '%OVH%' OR company LIKE '%Rechenzentrum%' OR company LIKE '%DigitalOcean%' OR company LIKE '%WholeSale%' OR company LIKE '%Cloudflare%' OR company LIKE '%Google%' OR company LIKE '%Datacamp%' OR company LIKE '%Microsoft%' OR company LIKE 'AS61317' OR company LIKE 'Hosting' OR company LIKE 'AS62874' OR company LIKE 'AS6939' OR company LIKE 'AS42926' OR hostname LIKE '%research%' OR hostname LIKE '%google%' OR hostname LIKE '%spider%' OR hostname LIKE '%vultr%' or hostname LIKE '%hosted%';");
        $page = "http://".$_SERVER['HTTP_HOST']."".$_SERVER['PHP_SELF'];
        $referrer = $_SERVER['HTTP_REFERER'];
        $useragent = $_SERVER['HTTP_USER_AGENT'];

        if(mysqli_num_rows($botcheck) > 0){
            $row2 = mysqli_fetch_assoc($botcheck);
            $bot = $bot["bot"];
            $bot = 1;
            $bot = mysqli_real_escape_string($conn,$bot);
            $botupdate = "UPDATE unique_ip SET bot={$bot} WHERE hostname LIKE '%bot%' OR hostname LIKE '%crawl%' OR hostname LIKE '%aws%' or hostname LIKE '%ahrefs%' OR company LIKE '%OVH%' OR company LIKE '%Rechenzentrum%' OR company LIKE '%DigitalOcean%' OR company LIKE '%WholeSale%' OR company LIKE '%Cloudflare%' OR company LIKE '%Google%' OR company LIKE '%Datacamp%' OR company LIKE '%Microsoft%' OR company LIKE 'AS61317' OR company LIKE 'Hosting' OR company LIKE 'AS62874' OR company LIKE 'AS6939' OR company LIKE 'AS42926' OR hostname LIKE '%research%' OR hostname LIKE '%google%' OR hostname LIKE '%spider%' OR hostname LIKE '%vultr%' or hostname LIKE '%hosted%';";
            mysqli_query($conn,$botupdate);
        }
        $sql = "INSERT INTO unique_ip (IP,visits,postcode,city,region,country,page,referrer,company,hostname,date,useragent) VALUES ('{$ip}','{$visits}','{$postcode}','{$city}','{$region}','{$country}','{$page}','{$referrer}','{$company}','{$hostname}','{$getDate}','{$useragent}');";
        mysqli_query($conn, $sql);
    }
}
