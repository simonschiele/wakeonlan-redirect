<?php

/// {{{ wakeup 
// source: http://www.administrator.de/content/print.php?id=164517
function wakeup5($mac,$ip='255.255.255.255') {
    $mac_exploded=explode(':',$mac);
    $hw_addr='';
    for($i=0;$i<6;$i++)$hw_addr.=chr(hexdec($mac_exploded[$i]));
        $msg=str_repeat(chr(255),6);
        $msg.=str_repeat($hw_addr,16);
        $socket=socket_create(AF_INET,SOCK_DGRAM,SOL_UDP);
        socket_set_option($socket,SOL_SOCKET,SO_BROADCAST,1);
        socket_sendto($socket,$msg,strlen($msg),0,$ip,7);
        //echo socket_strerror(socket_last_error($socket));
        socket_close($socket);
}

// source: http://www.vdr-wiki.de/wiki/index.php/WAKE_ON_LAN_-_PHP
function wakeup4($mac, $addr) {
    $addr_byte = explode(':', $mac);
    $hw_addr = '';

    for ($a=0; $a < 6; $a++) $hw_addr .= chr(hexdec($addr_byte[$a]));

    $msg = chr(255).chr(255).chr(255).chr(255).chr(255).chr(255);

    for ($a = 1; $a <= 16; $a++)    $msg .= $hw_addr;

    $s = socket_create(AF_INET, SOCK_DGRAM, SOL_UDP);
    if ($s == false) {
        return false;
    } else {
        $opt_ret =  socket_set_option($s, 1, 6, TRUE);
        if($opt_ret < 0) {
            return false;
        }
        $e = socket_sendto($s, $msg, strlen($msg), 0, $addr, 2050);
        socket_close($s);
        return true;
    }
}

// source: http://www.php.de/php-tipps-2005/20819-script-fuer-wake-lan.html
function wakeup3($mac_addr,$router_addr) {
    if ($fp = fsockopen($router_addr, 9, $errno, $errstr, 4)) {
        $hexchars = array("0","1","2","3","4","5","6","7","8","9","A","B","C","D","E","F","a","b","c","d","e","f");
        $data = "\xFF\xFF\xFF\xFF\xFF\xFF";
        $hexmac = "";
        for ($i = 0; $i < strlen($mac_addr); $i++) {
            if (!in_array(substr($mac_addr, $i, 1), $hexchars)) {
                $mac_addr = str_replace(substr($mac_addr, $i, 1), "", $mac_addr);
            }
        }

        for ($i = 0; $i < 12; $i += 2) {
            $hexmac .= chr(hexdec(substr($mac_addr, $i, 2)));
        }

        for ($i = 0; $i < 16; $i++) {
            $data .= $hexmac;
        }

        fputs($fp, $data);
        fclose($fp);
        return true;
    } else {
        return false;
    }
}

// source: ???
function wakeup2 ($mac_addr, $broadcast) {
    $mac_hex = preg_replace('=[^a-f0-9]=i', '', $mac_addr);
    $mac_bin = pack('H12', $mac_hex);

    if (!$fp = fsockopen('udp://' . $broadcast, 2304, $errno, $errstr, 2)) {
        return false;
    }

    $data = str_repeat("\xFF", 6) . str_repeat($mac_bin, 16);

    fputs($fp, $data);
    fclose($fp);

    return true;
}

// }}}

function service_status($url) {
    $url = parse_url($url);

    if (!array_key_exists('port',$url)) {
        if ($url['scheme'] == 'https') {
            $url['port'] = 443;
        } else {
            $url['port'] = 80;
        }
    }

    $fp = fsockopen($url['host'], $url['port'], $errno, $errstr, 4);

    if ($fp) {
        fclose($fp);
        return true;
    }

    return false;
}

$title = "Boot em' up!";
$default_mac = "00:01:2e:27:62:87";
$default_mac_list = array('mediacenter' => '00:01:2e:27:62:87','host2' => '00:01:2e:27:62:87');
$default_broadcast = "192.168.5.255";
$default_redirect = "http://enkheim.dyndns.org";
$default_redirect_list = array("fileserver" => "http://enkheim.dyndns.org");

$status = True;
$error = array();
$data = array();

if ($_POST || !empty($_GET)) {

    if (!array_key_exists('mac',$_REQUEST) || empty(trim($_REQUEST['mac']))) {
        $status = False;
        $error[] = "parameter 'mac' missing or empty";
    } elseif (preg_match('/([a-fA-F0-9]{2}[:|\-]?){6}/', $_REQUEST['mac']) != 1) {
        $status = False;
        $error[] = "invalid 'mac' address";
    }

    if (!array_key_exists('broadcast',$_REQUEST) || empty(trim($_REQUEST['broadcast']))) {
        $status = False;
        $error[] = "parameter 'broadcast' missing or empty";
    } elseif (long2ip(ip2long($_REQUEST['broadcast'])) != $_REQUEST['broadcast']) {
        $status = False;
        $error[] = "'broadcast' is an invalid ip address";
    }

}

$step1_display='';
$step2_display=' style="display:none;"';

if ($status && ($_POST || !empty($_GET))) {
    $boot = true;
    if (array_key_exists('action',$_REQUEST) && !empty(trim($_REQUEST['action']))) {
        switch ($_REQUEST['action']) {
            case "redirect":
                $boot = false;
                break;
            case "poll":
                $data['online'] = service_status($_REQUEST['redirect']);
                $boot = false;
                break;
        }
    }
    if ($boot) {
        $data['message'] = "booting";
        $step1_display=' style="display:none;"';
        $step2_display='';
        $data['ret2'] = wakeup2($_REQUEST['mac'],$_REQUEST['broadcast']);
        $data['ret3'] = wakeup3($_REQUEST['mac'],$_REQUEST['broadcast']);
        $data['ret4'] = wakeup4($_REQUEST['mac'],$_REQUEST['broadcast']);
        $data['ret5'] = wakeup5($_REQUEST['mac'],$_REQUEST['broadcast']);
    }
}

if ($_POST) {

    if (!$status) {
        $data['error'] = $error;
    }
    $data['success'] = $status;

    header('Content-Type: application/javascript');
    echo json_encode($data);
    exit();
}

if (array_key_exists('UI',$_REQUEST) && !$_REQUEST['UI']) {
    exit();
}

$default_mac_html = '';
if (!empty($default_mac)) {
    $default_mac_html = sprintf(' value="%s"',$default_mac);
}

$default_broadcast_html = '';
if (!empty($default_broadcast)) {
    $default_broadcast_html = sprintf(' value="%s"',$default_broadcast);
}

$default_redirect_html = '';
if (!empty($default_redirect)) {
    $default_redirect_html = sprintf(' value="%s"',$default_redirect);
}


?>
<!DOCTYPE html>
<html lang="en">
<head>
    <title><?= $title; ?></title>

    <script src="http://code.jquery.com/jquery-1.10.1.min.js"></script>
    <link href="//netdna.bootstrapcdn.com/twitter-bootstrap/2.3.2/css/bootstrap-combined.min.css" rel="stylesheet">
    <script src="//netdna.bootstrapcdn.com/twitter-bootstrap/2.3.2/js/bootstrap.min.js"></script>

    <script type="text/javascript" language="javascript">

        function boot() {
            console.log('booting');
            $('#step1').hide();
            $('#step2').show();
            $.ajax({
                type: 'POST',
                cache: false,
                url: location.href,
                dataType: 'json',
                data: $('form').serialize(),
                success: poll
            });
        }

        var poll = function poll() {
            console.log('poll');

            var data = {
                'action': 'poll',
                'mac': $('#mac').val(),
                'broadcast': $('#broadcast').val(),
                'redirect': $('#redirect').val()
            }

            $.ajax({
                type: 'POST',
                cache: false,
                url: location.href,
                dataType: 'json',
                data: data,
                success: function (data) {
                    if (data.success && data.online) {
                        console.log('redirecting'); // todo
                        window.document.location.href = $('#redirect').val();
                    } else {
                        setTimeout(poll, 5000); // todo
                    }
                },
                error: function () {
                    console.log('error while polling');
                }
            });
        }

        $(document).ready(function(e) {
            $('#boot').on('click', function () {
                boot();
            });

            $('#redirect').on('change', function () {
                if ($('#redirect').val().replace('\ ','') == '') {
                    console.log('empty');
                } else {
                    console.log('not empty');
                };
            });
        });
    </script>
</head>

<body>

<div class="navbar navbar-fixed-top">
    <div class="navbar-inner">
        <div class="container">
            <a class="brand" href=""><?= $title; ?></a>
        </div>
    </div>
</div>

<div class="container" id="step1"<?= $step1_display; ?>>
    <div class="row">
        <div class="span8 offset2">
            <div class="well">
                <form class="form-horizontal">
                    <fieldset>
                        <legend>Please select your host</legend>
                        <div class="control-group">
                            <label class="control-label" for="mac">MAC-Address</label>
                            <div class="controls">
                                <input id="mac" name="mac" type="text" placeholder="00:00:00:00:00:00" class="input-large" required=""<?= $default_mac_html; ?>>
                                <p class="help-block">MAC-Address of the host to wake up</p>
                            </div>
                        </div>

                        <div class="control-group">
                            <label class="control-label" for="broadcast">Broadcast</label>
                            <div class="controls">
                                <input id="broadcast" name="broadcast" type="text" placeholder="192.168.000.255" class="input-large" required=""<?= $default_broadcast_html; ?>>
                                <p class="help-block">Broadcast-Address of your net</p>
                            </div>
                        </div>

                        <div class="control-group">
                            <label class="control-label" for="redirect">Redirect</label>
                            <div class="controls">
                                <div class="input-prepend">
                                    <span class="add-on">
                                        <label class="checkbox">
                                            <input type="checkbox">
                                        </label>
                                    </span>
                                    <input id="redirect" name="redirect" type="text" placeholder="http://192.168.0.23" class="input-large" required=""<?= $default_redirect_html; ?>>
                                </div>
                                <p class="help-block">Address to wait for and to redirect afterwards</p>
                            </div>
                        </div>

                        <div class="control-group">
                            <label class="control-label" for="boot"></label>
                                <div class="controls">
                                <button type="button" class="btn btn-primary" id="boot" name="boot">Boot</button>
                            </div>
                        </div>
                    </fieldset>
                </form>
            </div>
        </div>
    </div>
</div>

<div class="container" id="step2"<?= $step2_display; ?>>
    <div class="row">
        <div class="span8 offset2">
            <div class="well">
                <br/><h3>Waiting for host...</h3>
            </div>
        </div>
    </div>
</div>

<?php
if (array_key_exists('debug',$_REQUEST) && $_REQUEST['debug']) {
    $debug_array = array('$_GET' => $_GET, '$_POST' => $_POST, '$_COOKIE' => $_COOKIE, '$_SERVER' => $_SERVER);
    foreach ($debug_array as $key => $val) {
        echo '<div class="span6">';
        echo '<pre>'.$key.":<br/>\n";
        print_r($val);
        echo "</pre></div>\n";
    }
}

?>
</body>
</html>
