<?php

require_once("user.php");

function reCaptcha($recaptcha){
    $secret = "";
    $ip = $_SERVER['REMOTE_ADDR'];

    $postvars = array("secret"=>$secret, "response"=>$recaptcha, "remoteip"=>$ip);
    $url = "https://www.google.com/recaptcha/api/siteverify";
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $postvars);
    $data = curl_exec($ch);
    curl_close($ch);

    return json_decode($data, true);
}

function emptyInputSignup($name_first,$name_last,$email,$emailconfirm,$password,$cpassword){
    $result;
    if(empty($name_first) || empty($name_last) || empty($email) || empty($emailconfirm) || empty($password) || empty($cpassword)){
        $result = true;
    }
    else{
        $result = false;
    }
    return $result;
}

function emptyInputEMAILChange($email,$emailconfirm){
    $result;
    if(empty($email) || empty($emailconfirm)){
        $result = true;
    }
    else{
        $result = false;
    }
    return $result;
}

function invalidEmail($email){
    $result;
    if(!filter_var($email,FILTER_VALIDATE_EMAIL)){
        $result = true;
    }
    else{
        $result = false;
    }
    return $result;
}

function validateString($string){
    $string;
    $string = filter_var($string,FILTER_SANITIZE_STRING);
    return $string;
}

function validateURL($url){
    if(filter_var($url, FILTER_VALIDATE_URL) !== false){
        return true;
    }
    elseif(empty($url)){
        return true;
    }
}

function emailMatch($email,$emailconfirm){
    $result;
    if($email !== $emailconfirm){
        $result = true;
    }
    else{
        $result = false;
    }
    return $result;
}

function pwdMatch($password,$cpassword){
    $result;
    if($password !== $cpassword){
        $result = true;
    }
    else{
        $result = false;
    }
    return $result;
}

function unameDupp($conn,$email){
    $sql = "SELECT * FROM users WHERE email = ?;";
    $stmt = mysqli_stmt_init($conn);
    if (!mysqli_stmt_prepare($stmt,$sql)){
        header("location: ../../register?error=emailTaken");
        exit();
    }
    mysqli_stmt_bind_param($stmt,"s",$email);
    mysqli_stmt_execute($stmt);

    $resultData = mysqli_stmt_get_result($stmt);

    if($row = mysqli_fetch_assoc($resultData)){
        return $row;
    }else{
        $result = false;
        return $result;
    }
    mysqli_stmt_close($stmt);
}


function createUser($conn,$name_last,$name_first,$email,$IP,$password,$admin,$updates){
    $getDate = date('d-m-y');
    $name_first = validateString($name_first);
    $name_last = validateString($name_last);
    $sql = "INSERT INTO users (lastName,firstName,email,IP,password,admin,mailing) VALUES (?,?,?,?,?,?,?);";
    $stmt = mysqli_stmt_init($conn);
    if (!mysqli_stmt_prepare($stmt,$sql)){
        header("location: ../../index?error=stmtfailure");
        exit();
    }

    $hashedPwd = password_hash($password,PASSWORD_BCRYPT);

    mysqli_stmt_bind_param($stmt,"sssssss",$name_last,$name_first,$email,$IP,$hashedPwd,$admin,$updates);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
    $sql2 = "UPDATE users SET date='{$getDate}' WHERE email LIKE '{$email}';";
    mysqli_query($conn,$sql2);
    header("location: ../../login?status=registered");
    exit();
}

function updateEmail($conn,$email,$UID){
    $sql = "UPDATE users SET email = ? WHERE ID LIKE ?;";
    $stmt = mysqli_stmt_init($conn);
    if (!mysqli_stmt_prepare($stmt,$sql)){
        header("location: ../../profile?error=stmtfailure");
        exit();
    }
    mysqli_stmt_bind_param($stmt,"ss",$email,$UID);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
    header("location: ../../profile?sstatus=emailChanged");
    exit();
}
function updatePassword($conn,$password,$UID){
    $sql = "UPDATE users SET password = ? WHERE ID LIKE ?;";
    $stmt = mysqli_stmt_init($conn);
    if (!mysqli_stmt_prepare($stmt,$sql)){
        header("location: ../../profile?serror=stmtfailure");
        exit();
    }
    $hashedPwd = password_hash($password,PASSWORD_BCRYPT);
    mysqli_stmt_bind_param($stmt,"ss",$hashedPwd,$UID);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
    header("location: ../../profile?sstatus=passwordChanged");
    exit();
}

function emptyInputLogin($email,$password){
    $result;
    if (empty($email) || empty($password)){
        $result = true;
    }
    else{
        $result = false;
    }
    return $result;
}

function loginUser($conn,$email,$password){
    // Set variables
    $IP = getClientIP();
    $failedattempts = 0;
    date_default_timezone_set("Europe/London");
    $getDate = date('d-m-y H:i:s');
    $date = explode(" ",$getDate);
    $onlydate = $date[0];
    // Check if banned
    $sql = "SELECT IP FROM bannedIPs WHERE IP LIKE '{$IP}';";
    $result = mysqli_query($conn,$sql);
    $resultCheck = mysqli_num_rows($result);
    if($resultCheck !== 0){
        header("location: ../../login?error=banned");
        exit();
    }
    // Check attempts
    $sql = "SELECT * FROM loginLog WHERE status LIKE '0' AND IP LIKE '{$IP}' AND date LIKE '%{$onlydate}%';";
    $result = mysqli_query($conn,$sql);
    $resultCheck = mysqli_num_rows($result);
    if($resultCheck >= 5){
        $sql = "INSERT INTO bannedIPs (IP) VALUE (?);";
        $stmt = mysqli_stmt_init($conn);
        if (!mysqli_stmt_prepare($stmt,$sql)){
            header("location: ../../index?error=stmtfailure");
            exit();
        }
        mysqli_stmt_bind_param($stmt,"s",$IP);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
        header("location: ../../login?error=bannadded");
        exit();
    }
    // Set login state
    $status = 0;
    // Login Check
    $unameDupp = unameDupp($conn,$email);
    if($unameDupp === false){
        header("location: ../../login?error=loginFailure");
        // Add to loginLog
        $sql = "INSERT INTO loginLog (date,IP,email,status) VALUES (?,?,?,?);";
        $stmt = mysqli_stmt_init($conn);
        if (!mysqli_stmt_prepare($stmt,$sql)){
            header("location: ../../index?error=stmtfailure");
            exit();
        }
        mysqli_stmt_bind_param($stmt,"ssss",$getDate,$IP,$email,$status);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
        exit();
    }
    $passwordhashed = $unameDupp["password"];
    $checkpwd = password_verify($password,$passwordhashed);

    if($checkpwd === false){
        header("location: ../../login?error=loginFailure");
        // Check ID
        $sql = "SELECT ID FROM users WHERE email LIKE '{$email}';";
        $result = mysqli_query($conn,$sql);
        $resultCheck = mysqli_num_rows($result);
        // SET ID
        if($resultCheck == 0){
            $userID = 0;
        }
        $userID = mysqli_fetch_object($result)->ID;
        // Add to loginLog
        $sql = "INSERT INTO loginLog (date,IP,userID,email,status) VALUES (?,?,?,?,?);";
        $stmt = mysqli_stmt_init($conn);
        if (!mysqli_stmt_prepare($stmt,$sql)){
            header("location: ../../index?error=stmtfailure");
            exit();
        }
        mysqli_stmt_bind_param($stmt,"sssss",$getDate,$IP,$userID,$email,$status);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
        exit();
    }
    else if($checkpwd === true){
        // Set login state
        $status = 1;
        // Start session
        session_start();
        $_SESSION["email"] = $unameDupp["email"];
        // Redirect
        header("location: ../../index");
        // SQL Queries
        $sql = "SELECT firstName FROM users WHERE email LIKE '{$email}';";
        $result = mysqli_query($conn,$sql);
        $resultCheck = mysqli_num_rows($result);
        $sql2 = "SELECT admin FROM users WHERE email LIKE '{$email}';";
        $result2 = mysqli_query($conn,$sql2);
        $sql3 = "SELECT ID FROM users WHERE email LIKE '{$email}';";
        $result3 = mysqli_query($conn,$sql3);
        // SET ID
        $userID = mysqli_fetch_object($result3)->ID;

        $sql4 = "INSERT INTO loginLog (date,IP,userID,email,status) VALUES (?,?,?,?,?);";
        $stmt = mysqli_stmt_init($conn);
        if (!mysqli_stmt_prepare($stmt,$sql4)){
            header("location: ../../index?error=stmtfailure");
            exit();
        }
        mysqli_stmt_bind_param($stmt,"sssss",$getDate,$IP,$userID,$email,$status);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);

        if ($resultCheck > 0){
            $_SESSION["name_first"] = mysqli_fetch_object($result)->firstName;
            $_SESSION["admin"] = mysqli_fetch_object($result2)->admin;
            $_SESSION["UID"] = $userID;
        }
        else{
            $_SESSION["error"] = "ERROR: " .mysqli_error($conn);
            header("location: ../../login?error=noSessionName");
        }
        exit();
    }
}
function getRegisteredUsers($conn){
    $sql = "SELECT * FROM users;";
    $qry = mysqli_query($conn,$sql);
    $result = mysqli_num_rows($qry);
    echo "{$result}";
}

function getPageVisits($conn){
    $sql = "SELECT * FROM unique_ip;";
    $qry = mysqli_query($conn,$sql);
    $result = mysqli_num_rows($qry);
    echo "{$result}";
}
function getBotCount($conn){
    $sql = "SELECT bot FROM unique_ip WHERE bot > 0;";
    $qry = mysqli_query($conn,$sql);
    $result = mysqli_num_rows($qry);
    echo "{$result}";
}

function getContinent($conn,$cont){
      if($cont == "EU"){
        $sql = "SELECT * FROM unique_ip WHERE country RLIKE 'AT|BE|BG|HR|CY|CZ|DK|EE|FI|FR|DE|GR|HU|IE|IT|LV|LT|LU|MT|NL|PL|PT|RO|SK|SI|ES|SE|AL|AD|AM|BY|BA|FO|GE|GI|IS|IM|XK|LI|MK|MD|MC|ME|NO|RU|SM|RS|CH|TR|UA|GB|VA';";
        $qry = mysqli_query($conn,$sql);
            if(mysqli_num_rows($qry) > 0){
                $EU = mysqli_num_rows($qry);
                echo "{$EU}";
            }
    }
    if($cont == "ASIA"){
        $sql = 'SELECT * FROM unique_ip WHERE country RLIKE "AM|AZ|BH|BD|BT|BN|KH|CN|CX|CC|IO|GE|HK|IN|ID|IR|IQ|IL|JP|JO|KZ|KW|KG|LA|LB|MO|MY|MV|MN|MM|NP|KP|OM|PK|PS|PH|QA|SA|SG|KR|LK|SY|TW|TJ|TH|TR|TM|AE|UZ|VN|YE";';
        $qry = mysqli_query($conn,$sql);
            if(mysqli_num_rows($qry) > 0){
                $ASIA = mysqli_num_rows($qry);
                echo "{$ASIA}";
            }
    }
    elseif($cont == "AUSTRALIA"){
        $sql = 'SELECT * FROM unique_ip WHERE country RLIKE "AU|NZ|CK|TL|FM|FJ|PF|GU|KI|MP|MH|UM|NR|NC|NZ|NU|NF|PW|PG|MP|WS|SB|TK|TO|TV|VU|UM|WF";';
        $qry = mysqli_query($conn,$sql);
            if(mysqli_num_rows($qry) > 0){
                $AUS = mysqli_num_rows($qry);
                echo "{$AUS}";
            }
    }
    elseif($cont == "AFRICA"){
        $sql = 'SELECT * FROM unique_ip WHERE country RLIKE "AO|SH|BJ|BW|BF|BI|CM|CV|CF|TD|KM|CG|CD|DJ|EG|GQ|ER|SZ|ET|GA|GM|GH|GN|GW|CI|KE|LS|LR|LY|MG|MW|ML|MR|MU|YT|MA|MZ|NA|NE|NG|ST|RE|RW|SN|SC|SL|SO|ZA|SS|SD|TZ|TG|TN|UG|ZM|ZW;"';
        $qry = mysqli_query($conn,$sql);
            if(mysqli_num_rows($qry) > 0){
                $AFR = mysqli_num_rows($qry);
                echo "{$AFR}";
            }
    }
    elseif($cont == "SOUTH AMERICA"){
        $sql = 'SELECT * FROM unique_ip WHERE country RLIKE "BO|BR|CL|CO|EC|FK|GF|GY|PY|PE|SR|UY|VE";';
        $qry = mysqli_query($conn,$sql);
            if(mysqli_num_rows($qry) > 0){
                $SA = mysqli_num_rows($qry);
                echo "{$SA}";
            }
    }
    elseif($cont == "NORTH AMERICA"){
        $sql = 'SELECT * FROM unique_ip WHERE country RLIKE "AG|AW|BS|BB|BZ|BM|BQ|VG|CA|KY|CR|CU|CW|DM|DO|SV|GL|GD|GP|GT|HT|HN|JM|MQ|MX|PM|MS|KN|NI|PA|PR|SX|LC|VC|TT|TC|US|VI;"';
        $qry = mysqli_query($conn,$sql);
            if(mysqli_num_rows($qry) > 0){
                $NA = mysqli_num_rows($qry);
                echo "{$NA}";
            }
    }
}
function getClicks($conn){
    $sql = "SELECT SUM(visits) FROM unique_ip;";
    $qry = mysqli_query($conn,$sql);
    $result = mysqli_fetch_assoc($qry);
    $result = $result['SUM(visits)'];
    echo "{$result}";
}
function getUserInfo($conn,$info,$UID){
    if($info == "lastname"){
        $sql = "SELECT lastName FROM users WHERE ID LIKE '{$UID}' LIMIT 1;";
        $qry = mysqli_query($conn,$sql);
        $result = mysqli_fetch_assoc($qry);
        $result = $result['lastName'];
        echo "{$result}";
    }
    elseif($info == "email"){
        $sql = "SELECT email FROM users WHERE ID LIKE '{$UID}' LIMIT 1;";
        $qry = mysqli_query($conn,$sql);
        $result = mysqli_fetch_assoc($qry);
        $result = $result['email'];
        echo "{$result}";
    }
    elseif($info == "member"){
        $sql = "SELECT date FROM users WHERE ID LIKE '{$UID}' LIMIT 1;";
        $qry = mysqli_query($conn,$sql);
        $result = mysqli_fetch_assoc($qry);
        $result = $result['date'];
        echo "{$result}";
    }
    elseif($info == "mailing"){
        $sql = "SELECT mailing FROM users WHERE ID LIKE '{$UID}' LIMIT 1;";
        $qry = mysqli_query($conn,$sql);
        $result = mysqli_fetch_assoc($qry);
        $result = $result['mailing'];
        if($result != 0 || $result != "0"){
            return $state = true;
        }
        else{
            return $state = false;
        }
    }
    elseif($info == "admin"){
        if(isUserAdmin($conn,$UID) == true){
            echo "Administrator";
        }
        else{
            echo "Member";
        }
    }
}
function changeNotificationStatus($conn,$UID){
    if(getUserInfo($conn,"mailing",$UID) == true){
        $sql = "UPDATE users set mailing='0' WHERE ID LIKE '{$UID}';";
        $qry = mysqli_query($conn,$sql);
    }else{
        $sql = "UPDATE users set mailing='1' WHERE ID LIKE '{$UID}';";
        $qry = mysqli_query($conn,$sql);
    }
    header("location: ../../profile?sstatus=passwordChanged");
}
function emptyInputArticle($title,$slink,$source,$stitle,$stext){
    if(empty($title) || empty($slink[0]) || empty($source[0]) || empty($stitle[0]) || empty($stext[0])){
        return true;
    }

    return false;
}

function createArticle($author,$title,$slink,$source,$stitle,$stext,$smedia,$date){
    $getDate = date('d-m-y');
    $title = validateString($title);
    for($i=0;$i<6;$i++){
        if(!empty($slink[$i]) || empty($source[$i]) || empty($stitle[$i]) || empty($stext[$i]) || empty($smedia[$i])){
            $slink[$i] = validateString($slink[$i]);
            $source[$i] = validateString($source[$i]);
            $stitle[$i] = validateString($stitle[$i]);
            $stext[$i] = validateString($stext[$i]);
        }
    }
    if(validateURL($smedia[0]) == true){
        $sql = "INSERT INTO news (author,title,slink1,stitle1,source1,stext1,smedia1,date) VALUES (?,?,?,?,?,?,?,?);";
        $stmt = mysqli_stmt_init($conn);
        header("location: ../../assets/php/functions.php");

        echo '<pre>',print_r($source[0]),'</pre>';
        // if (!mysqli_stmt_prepare($stmt,$sql)){
        //     header("location: ../../index?error=stmtfailure");
        //     exit();
        // }
        mysqli_stmt_bind_param($stmt,"ssssssss",$author,$title,$slink[0],$stitle[0],$source[0],$stext[0],$smedia[0],$getDate);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
    }
    else{
        header("location: ../../administration?error=invalidURL");
        exit();
    }

}
