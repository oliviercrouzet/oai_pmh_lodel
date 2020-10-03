<?php

global $db;
global $database_prefix;
global $current_site;

# Connexion à lodel
function lodel_init () {
    global $database_prefix;
    define('backoffice-lodeladmin', true);
    require_once '../lodelconfig.php';

    define("SITEROOT","../");
    $cfg['home'] = "lodel/scripts/";
    $cfg['sharedir'] = SITEROOT . $cfg['sharedir'];
    ini_set('include_path', SITEROOT. $cfg['home'] . PATH_SEPARATOR . ini_get('include_path'));
    error_log(ini_get('include_path'));
    error_log($cfg['sharedir']);
    error_log(SITEROOT);
    require 'context.php';
    C::setCfg($cfg);

    require_once 'auth.php';
    $lodeluser = array('rights'=>LEVEL_ADMINLODEL, 'adminlodel'=>1, 'id'=>1, 'groups'=>'');
    C::set('lodeluser', $lodeluser);
    C::set('login', 'admin');
    C::setUser($lodeluser);
    unset($lodeluser);

    $GLOBALS['nodesk'] = true;
    C::set('nocache', true);
    require_once 'connect.php';

    $database_prefix = c::Get('database','cfg');
};

# Role:
#   Connexion à la base d'un site lodel
# Input:
#   $site: nom du site, ne rien mettre pour le site lodeladmin
# Output:
#   none
function connect_site($site='') {
    global $database_prefix;
    global $current_site;

    if ($site == $current_site) return true;

    $current_site = $site;
    $db_name = $database_prefix . ($site ? ("_" . $site) : '');
    $GLOBALS['currentdb'] = $db_name;
    _log("Connexion à $db_name", false);

    # Do it ourself, lodel usecurrentdb() always return true…
    # We want to return false if error
    return $GLOBALS['db']->SelectDB($db_name);
}

# Role:
#   Liste des sites lodel de cette instance qui ont OAI d'activé
function get_sites($status=0) {
    global $db;
    global $current_site;

    # Save current site name then connect to main
    $previous_site = $current_site;
    connect_site();

    $sites = array();
    $les_sites = $db->execute(lq("SELECT title, name, url FROM #_MTP_sites WHERE status>?"), [$status]);

    while ($site = $les_sites->FetchRow()) {
        connect_site($site['name']);
        $stmt = $db->execute(lq("SELECT value FROM #_TP_options WHERE `name`='oai_id'"));
        $oai_id = $stmt->GetAll();
        # Seulement les sites avec oai_id de renseigné
        if ($oai_id) {
            $oai_id = $oai_id[0]['value'];
            $sites[] = ['name' => $site['name'], 'title' => $site['title'], 'url' => $site['url'], 'oai_id' => $oai_id];
        }
    }

    # connect back
    connect_site($previous_site);

    return $sites;
}

# Role:
#   Recevoir la liste de entités d'une class
#   Donne les informations essentielles
function get_entity_info($class, $type='', $site='') {
    global $db;
    _log("tentative de connexion à $site");
    if ($site) {
        connect_site($site);
    }
    $query = lq("SELECT identity, titre, datemisenligne, langue, status FROM `#_TP_$class` c, `#_TP_entities` e WHERE c.identity = e.id AND status>0");
    _log_debug($query);
    $stmt = $db->execute($query);
    _log_debug($stmt);
    return $stmt->GetAll();
}

function _log_debug($var, $print=true) {
    $error = var_export($var, 1);
    _log($error, $print);
}

function _log ($var, $print=true) {
    if ($print) {
        print "<p>$var</p>";
    }
    error_log($var);
}